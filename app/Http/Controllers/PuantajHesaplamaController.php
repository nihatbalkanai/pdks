<?php

namespace App\Http\Controllers;

use App\Models\Personel;
use App\Models\PdksKaydi;
use App\Models\PdksGunlukOzet;
use App\Models\GunlukPuantajParametresi;
use App\Models\AylikPuantajParametresi;
use App\Models\ResmiTatil;
use App\Models\PersonelIzin;
use App\Models\PersonelAvansKesinti;
use App\Models\PersonelPrimKazanc;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PuantajHesaplamaController extends Controller
{
    // =============================================
    // MUHASEBE STANDARTLARI (Kullanıcı Ayarlarından Dinamik Çekilir):
    // Aylık Saat = 225 (aylik_puantaj_parametre'den)
    // Günlük Saat = 7.5 (aylik_puantaj_parametre'den)
    // Ay Standartı (Gün) = 225 / 7.5 = 30 gün
    // Günlük Ücret = Aylık Ücret / Standart Ay Günü
    // =============================================

    private const FM_TOLERANS_DAKIKA_DEFAULT = 5; // FM tolerans varsayılan (DB'den okunur)

    public function index()
    {
        $firma_id = Auth::user()->firma_id;
        $personeller = Personel::withoutGlobalScopes()
            ->where('firma_id', $firma_id)
            ->where('durum', true)
            ->select('id', 'ad', 'soyad', 'sicil_no', 'kart_no', 'puantaj_parametre_id')
            ->orderBy('ad')
            ->get();

        return Inertia::render('HesapRaporlari/PuantajHesaplama', [
            'personeller' => $personeller,
        ]);
    }

    private function getPersonelList()
    {
        $firma_id = Auth::user()->firma_id;
        return Personel::withoutGlobalScopes()
            ->where('firma_id', $firma_id)
            ->where('durum', true)
            ->select('id', 'ad', 'soyad', 'sicil_no', 'kart_no', 'bolum', 'puantaj_parametre_id')
            ->orderBy('ad')
            ->get();
    }

    public function genelMaasEkstresi()
    {
        return Inertia::render('HesapRaporlari/GenelMaasEkstresi', [
            'personeller' => $this->getPersonelList(),
        ]);
    }

    public function kisiBazindaMaasEkstresi()
    {
        return Inertia::render('HesapRaporlari/KisiBazindaMaasEkstresi', [
            'personeller' => $this->getPersonelList(),
        ]);
    }

    public function maasPusulasi()
    {
        return Inertia::render('HesapRaporlari/MaasPusulasi', [
            'personeller' => $this->getPersonelList(),
        ]);
    }

    public function grupBazliMaasEkstresi()
    {
        return Inertia::render('HesapRaporlari/GrupBazliMaasEkstresi', [
            'personeller' => $this->getPersonelList(),
        ]);
    }

    public function hesapla(Request $request)
    {
        $request->validate([
            'personel_ids' => 'required|array|min:1',
            'tarih_baslangic' => 'required|date',
            'tarih_bitis' => 'required|date|after_or_equal:tarih_baslangic',
            'ilk_giris_son_cikis' => 'nullable|boolean',
        ]);

        $firma_id = Auth::user()->firma_id;
        $baslangic = Carbon::parse($request->tarih_baslangic)->startOfDay();
        $bitis = Carbon::parse($request->tarih_bitis)->endOfDay();

        // Dönem bilgisi
        $donemBaslangic = $baslangic->copy()->startOfMonth();
        $donemBitis = $bitis->copy()->endOfMonth();
        $ayTakvimGunu = $donemBaslangic->daysInMonth; // Şubat=28, Mart=31 vs.

        // Resmi tatilleri al
        $resmiTatiller = ResmiTatil::withoutGlobalScopes()
            ->where('firma_id', $firma_id)
            ->whereBetween('tarih', [$baslangic->format('Y-m-d'), $bitis->format('Y-m-d')])
            ->pluck('tarih')
            ->map(fn($t) => Carbon::parse($t)->format('Y-m-d'))
            ->toArray();

        // === BORDRO ALANLARINI YÜKLE (Dinamik) ===
        $bordroAlanlari = \Illuminate\Support\Facades\DB::table('bordro_alanlari')
            ->where('firma_id', $firma_id)
            ->whereNull('deleted_at')
            ->orderBy('kod')
            ->get();

        $sonuclar = [];
        $personeller = Personel::withoutGlobalScopes()
            ->where('firma_id', $firma_id)
            ->whereIn('id', $request->personel_ids)
            ->get();

        foreach ($personeller as $personel) {
            // === PARAMETRELERİ AL ===
            $puantajParam = null;
            if ($personel->puantaj_parametre_id) {
                $puantajParam = GunlukPuantajParametresi::withoutGlobalScopes()
                    ->find($personel->puantaj_parametre_id);
            }

            // Aylık puantaj parametresini al (personel bazlı > firma bazlı)
            $aylikParam = null;
            if ($personel->aylik_puantaj_parametre_id) {
                $aylikParam = AylikPuantajParametresi::withoutGlobalScopes()
                    ->find($personel->aylik_puantaj_parametre_id);
            }
            if (!$aylikParam) {
                $aylikParam = AylikPuantajParametresi::withoutGlobalScopes()
                    ->where('firma_id', $firma_id)
                    ->where('durum', true)
                    ->first();
            }

            // === STANDART DEĞERLER (VERİTABANINDAN) ===
            $aylikStandartSaat = $aylikParam && $aylikParam->aylik_calisma_saati > 0
                ? (float)$aylikParam->aylik_calisma_saati : 225;
            
            // Standart Ay Günü (Yurtiçi SGK için 30'dur. Ancak yurtdışı firmalar için dinamik okunur)
            $standartAyGun = $aylikParam && $aylikParam->standart_ay_gunu > 0 
                ? (int)$aylikParam->standart_ay_gunu : 30;

            // Günlük çalışma saati — Öncelik: Vardiya > Günlük Param > Aylık Param > Varsayılan
            $gunlukCalismaSaat = 9.0; // varsayılan
            $molaSuresi = 0;

            // 1. Vardiya tanımından al (en öncelikli)
            $vardiya = null;
            if ($personel->vardiya_id) {
                $vardiya = \App\Models\Vardiya::withoutGlobalScopes()->find($personel->vardiya_id);
            }

            if ($vardiya && $vardiya->baslangic_saati && $vardiya->bitis_saati) {
                // Vardiya BRÜT süresini hesapla
                $brutDakika = abs($vardiya->toplam_sure) > 0
                    ? abs($vardiya->toplam_sure)
                    : abs(Carbon::parse($vardiya->bitis_saati)->diffInMinutes(Carbon::parse($vardiya->baslangic_saati)));
                // Mola süresi puantaj parametresinden alınır
                $molaSuresi = $puantajParam ? (int)($puantajParam->mola_suresi ?? 0) : 0;
                // NET çalışma = brüt - mola (Örn: 600 - 60 = 540dk = 9 saat)
                $gunlukCalismaSaat = max(0, $brutDakika - $molaSuresi) / 60;
            } elseif ($puantajParam && $puantajParam->iceri_giris_saati && $puantajParam->disari_cikis_saati) {
                // 2. Günlük puantaj parametresinden al
                $g = Carbon::parse($puantajParam->iceri_giris_saati);
                $c = Carbon::parse($puantajParam->disari_cikis_saati);
                $brutDakika = abs($c->diffInMinutes($g));
                $molaSuresi = (int)($puantajParam->mola_suresi ?? 0);
                // NET çalışma = brüt - mola
                $gunlukCalismaSaat = max(0, $brutDakika - $molaSuresi) / 60;
            } elseif ($aylikParam && $aylikParam->gunluk_calisma_saati > 0) {
                // 3. Aylık parametreden al (zaten NET değer)
                $gunlukCalismaSaat = (float)$aylikParam->gunluk_calisma_saati;
            }
            $gunlukCalismaDakika = (int)round($gunlukCalismaSaat * 60);

            // Çarpanlar ve parametreler (veritabanından)
            $fm50Carpan = $aylikParam ? (float)$aylikParam->fazla_mesai_carpani : 1.5;
            $fm100Carpan = $aylikParam ? (float)$aylikParam->tatil_mesai_carpani : 2.0;
            $fmToleransDakika = $aylikParam ? (int)($aylikParam->fazla_mesai_tolerans_dakika ?? self::FM_TOLERANS_DAKIKA_DEFAULT) : self::FM_TOLERANS_DAKIKA_DEFAULT;
            $gunFarkHesapla = $aylikParam ? (bool)($aylikParam->gun_fark_hesapla ?? true) : true;
            $sskToplamaDahil = $aylikParam ? (bool)($aylikParam->ssk_rapor_toplama_dahil ?? false) : false;

            // === GÜNLÜK PUANTAJ BORDRO ALANLARI (Mesai Kuralları) ===
            // Her günlük puantaj parametresine bağlı mesai kuralları:
            // Başla/Bitiş: Mesainin geçerli olduğu saat aralığı
            // Çarpan: 150 = x1.5 (%50 FM), 200 = x2.0 (%100 FM)
            // Min/Max: Minimum ve maksimum mesai süresi
            $puantajBordroKurallari = [];
            if ($puantajParam) {
                $puantajBordroKurallari = GunlukPuantajBordroAlani::where('gunluk_puantaj_id', $puantajParam->id)
                    ->orderBy('basla')
                    ->get()
                    ->toArray();
            }

            // === ÜCRET HESABI (ÖRNEK 30 GÜN / 225 SAAT STANDARDI ÜZERİNDEN DİNAMİK) ===
            $aylikUcret = (float)($personel->aylik_ucret ?? 0);
            $gunlukUcret = $aylikUcret / $standartAyGun;         // Örn: Maaş / 30
            $saatlikUcret = $aylikUcret / $aylikStandartSaat;    // Örn: Maaş / 225

            // === İZİNLERİ AL ===
            $izinler = PersonelIzin::withoutGlobalScopes()
                ->with('izinTuru')
                ->where('personel_id', $personel->id)
                ->where('durum', 'onaylandi')
                ->where(function ($q) use ($baslangic, $bitis) {
                    $q->whereBetween('tarih', [$baslangic, $bitis])
                      ->orWhereBetween('bitis_tarihi', [$baslangic, $bitis]);
                })->get();

            $izinliGunler = [];
            $toplamSskOdemesi = 0;
            foreach ($izinler as $izin) {
                $izinBaslangic = Carbon::parse($izin->tarih);
                $izinBitis = Carbon::parse($izin->bitis_tarihi ?? $izin->tarih);
                $izinTuruAd = $izin->izinTuru ? mb_strtolower($izin->izinTuru->ad) : '';
                $izinTuruAdOriginal = $izin->izinTuru ? $izin->izinTuru->ad : 'Bilinmeyen';
                
                if (str_contains($izinTuruAd, 'rapor')) {
                    $izinTipiStr = 'rapor';
                } else {
                    $izinTipiStr = ($izin->izinTuru && $izin->izinTuru->ucret_kesintisi_yapilacak_mi) ? 'ucretsiz' : 'ucretli';
                }

                // İzin türü adını bordro koduna eşle
                $bordroKod = $this->izinTuruBordroKodu($izinTuruAd, $izinTipiStr);

                for ($d = $izinBaslangic->copy(); $d->lte($izinBitis); $d->addDay()) {
                    $izinliGunler[$d->format('Y-m-d')] = [
                        'tip' => $izinTipiStr,
                        'izin_turu_adi' => $izinTuruAdOriginal,
                        'bordro_kod' => $bordroKod,
                    ];
                }
                if ($izin->ssk_odeme_tutari != null && $izin->ssk_odeme_tutari > 0) {
                    $toplamSskOdemesi += (float) $izin->ssk_odeme_tutari;
                }
            }

            // === PDKS KAYITLARI ===
            $kayitlar = PdksKaydi::withoutGlobalScopes()
                ->where('firma_id', $firma_id)
                ->where('personel_id', $personel->id)
                ->whereBetween('kayit_tarihi', [$baslangic, $bitis])
                ->orderBy('kayit_tarihi')
                ->get();

            // === AVANS / KESİNTİ / EK ÖDEME ===
            $toplamAvans = 0;
            $toplamKesinti = 0;
            $toplamEkOdeme = 0;

            try {
                $avanslar = PersonelAvansKesinti::withoutGlobalScopes()
                    ->where('personel_id', $personel->id)
                    ->whereBetween('tarih', [$baslangic->format('Y-m-d'), $bitis->format('Y-m-d')])
                    ->get();
                $toplamAvans = $avanslar->where('tur', 'avans')->sum('tutar');
                $toplamKesinti = $avanslar->where('tur', 'kesinti')->sum('tutar');
            } catch (\Exception $e) {}

            try {
                $ekKazanclar = PersonelPrimKazanc::withoutGlobalScopes()
                    ->where('personel_id', $personel->id)
                    ->whereBetween('tarih', [$baslangic->format('Y-m-d'), $bitis->format('Y-m-d')])
                    ->get();
                $toplamEkOdeme = $ekKazanclar->sum('tutar');
            } catch (\Exception $e) {}

            // ===== AYLIK RAPOR GÜN SAYISI KONTROLÜ =====
            $aydakiToplamRaporGunu = 0;
            foreach ($izinliGunler as $izinTarih => $izinBilgi) {
                if ($izinBilgi['tip'] === 'rapor') {
                    $d = Carbon::parse($izinTarih);
                    if ($d->between($baslangic, $bitis)) {
                        $aydakiToplamRaporGunu++;
                    }
                }
            }

            // Bordro alanları bazında izin sayaçları
            $bordroIzinSayac = [];

            // ===== GÜN GÜN HESAPLAMA =====
            $gunler = [];
            $toplamTakvimGunu = 0;       // Kişinin aktif olduğu toplam takvim günü
            $normalCalismaGun = 0;       // Çalışılan gün (iş günü + hafta sonu + tatil)
            $fm50Dakika = 0;             // Fazla mesai %50
            $fm100Dakika = 0;            // Fazla mesai %100 (tatil çalışma)
            $fm100Gun = 0;
            $devamsizlikGun = 0;         // İş günü ama PDKS kaydı yok
            $haftaTatiliGun = 0;         // Hafta sonu sayısı (bilgilendirme)
            $resmiTatilGun = 0;
            $ucretsizIzinGun = 0;
            $ucretliIzinGun = 0;
            
            $gelinenGunSayisi = 0; // Yol parası vs. için işe gelinen gün sayısı

            for ($gun = $baslangic->copy(); $gun->lte($bitis); $gun->addDay()) {
                $tarih = $gun->format('Y-m-d');
                $haftaGunu = $gun->dayOfWeekIso; // 1=Pzt, 7=Paz
                $haftaSonu = $haftaGunu >= 6;
                $resmiTatilMi = in_array($tarih, $resmiTatiller);
                $izinBilgi = $izinliGunler[$tarih] ?? null;
                $izinTipi = $izinBilgi ? $izinBilgi['tip'] : null;

                // Personel bu tarihte aktif mi?
                $girisDate = $personel->giris_tarihi ? Carbon::parse($personel->giris_tarihi) : null;
                $cikisDate = $personel->cikis_tarihi ? Carbon::parse($personel->cikis_tarihi) : null;
                if ($girisDate && $gun->lt($girisDate->startOfDay())) continue;
                if ($cikisDate && $gun->gt($cikisDate->endOfDay())) continue;

                $toplamTakvimGunu++;

                // PDKS kayıtları
                $gunKayitlari = $kayitlar->filter(fn($k) => Carbon::parse($k->kayit_tarihi)->format('Y-m-d') === $tarih)
                    ->sortBy('kayit_tarihi')->values();

                $giris = null;
                $cikis = null;
                $calismaDakika = 0;
                $durum = 'normal';

                if ($gunKayitlari->count() > 0) {
                    $giris = Carbon::parse($gunKayitlari->first()->kayit_tarihi);
                    $cikis = $gunKayitlari->count() > 1 ? Carbon::parse($gunKayitlari->last()->kayit_tarihi) : null;
                    if ($giris && $cikis) {
                        $calismaDakika = max(0, abs($cikis->diffInMinutes($giris)) - $molaSuresi);
                    }
                }

                if ($izinTipi) {
                    // İzinli gün — çalışma kayıtları yok sayılır, fazla mesai hesaplanmaz
                    $calismaDakika = 0;
                    $giris = null;
                    $cikis = null;
                    $izinBordroKod = $izinBilgi['bordro_kod'] ?? null;
                    
                    if ($izinTipi === 'ucretsiz') {
                        $durum = 'ucretsiz_izin';
                        $ucretsizIzinGun++;
                        $izinBordroKod = $izinBordroKod ?: 6;
                    } elseif ($izinTipi === 'rapor') {
                        if ($aydakiToplamRaporGunu <= 2) {
                            $durum = 'rapor_ucretli_firma';
                            $normalCalismaGun++;
                            $izinBordroKod = 21;
                        } else {
                            $durum = 'rapor_sgk';
                            $ucretsizIzinGun++;
                            $izinBordroKod = 22;
                        }
                    } else {
                        $durum = 'ucretli_izin';
                        $ucretliIzinGun++;
                        $izinBordroKod = $izinBordroKod ?: 5;
                    }
                    // Bordro izin sayacını artır
                    if ($izinBordroKod) {
                        $bordroIzinSayac[$izinBordroKod] = ($bordroIzinSayac[$izinBordroKod] ?? 0) + 1;
                    }
                } elseif ($resmiTatilMi) {
                    if ($calismaDakika > 0) {
                        $durum = 'tatil_calisma';
                        $fm100Dakika += $calismaDakika;
                        $fm100Gun++;
                    } else {
                        $durum = 'resmi_tatil';
                        $resmiTatilGun++;
                    }
                    // Resmi tatil normal güne dahil (30 gün standardı)
                    $normalCalismaGun++;
                } elseif ($haftaSonu) {
                    if ($calismaDakika > 0) {
                        $durum = 'hafta_sonu_calisma';
                        $fm100Dakika += $calismaDakika;
                        $fm100Gun++;
                    } else {
                        $durum = 'hafta_sonu';
                        $haftaTatiliGun++;
                    }
                    // Hafta sonu normal güne dahil (30 gün standardı)
                    $normalCalismaGun++;
                } elseif ($gunKayitlari->count() === 0) {
                    // İş günü ama PDKS kaydı yok = devamsız
                    $durum = 'devamsiz';
                    $devamsizlikGun++;
                } else {
                    // Normal iş günü — çalışmış
                    $normalCalismaGun++;
                    $gelinenGunSayisi++; // İzinsiz, iş günü, gelmiş = işe gelinen gün

                    // Fazla mesai kontrolü
                    // Bordro kuralları varsa onlara göre, yoksa basit hesaplama
                    $fmFark = $calismaDakika - $gunlukCalismaDakika;
                    if ($fmFark > $fmToleransDakika) {
                        if (!empty($puantajBordroKurallari) && $cikis) {
                            // Kural bazlı mesai hesaplama
                            $cikisSaat = Carbon::parse($tarih . ' ' . $cikis);
                            foreach ($puantajBordroKurallari as $kural) {
                                $kuralBasla = Carbon::parse($tarih . ' ' . $kural['basla']);
                                $kuralBitis = Carbon::parse($tarih . ' ' . $kural['bitis']);
                                $minDk = $this->saatToDakika($kural['min_sure'] ?? '00:00:00');
                                $maxDk = $this->saatToDakika($kural['max_sure'] ?? '24:00:00');
                                $carpan = ($kural['carpan'] ?? 150);

                                // Çıkış saati bu kuralın aralığında mı?
                                if ($cikisSaat->gte($kuralBasla)) {
                                    // Kural aralığında çalışılan dakika
                                    $kuralSonSaat = $cikisSaat->gt($kuralBitis) ? $kuralBitis : $cikisSaat;
                                    $kuralDakika = max(0, $kuralSonSaat->diffInMinutes($kuralBasla));
                                    // Min/Max sınırları uygula
                                    if ($kuralDakika >= $minDk) {
                                        $kuralDakika = min($kuralDakika, $maxDk);
                                        if ($carpan >= 200) {
                                            $fm100Dakika += $kuralDakika;
                                        } else {
                                            $fm50Dakika += $kuralDakika;
                                        }
                                    }
                                }
                            }
                        } else {
                            // Kural yoksa: tüm fark %50 mesai
                            $fm50Dakika += $fmFark;
                        }
                    }

                    // Geç gelme kontrolü
                    if ($giris && $puantajParam && $puantajParam->iceri_giris_saati) {
                        $planliGiris = Carbon::parse($tarih . ' ' . $puantajParam->iceri_giris_saati);
                        $tolerans = (int)($puantajParam->gec_gelme_toleransi ?? 0);
                        if ($giris->gt($planliGiris->copy()->addMinutes($tolerans))) {
                            $durum = 'gec_gelme';
                        }
                    }
                }

                $gunler[] = [
                    'tarih' => $tarih,
                    'gun_adi' => $gun->locale('tr')->dayName,
                    'giris' => $giris ? $giris->format('H:i') : null,
                    'cikis' => $cikis ? $cikis->format('H:i') : null,
                    'calisma_dakika' => $calismaDakika,
                    'calisma_saat' => round($calismaDakika / 60, 2),
                    'durum' => $durum,
                    'kayit_sayisi' => $gunKayitlari->count(),
                ];
            }

            // ===== STANDART AY (ÖRNEĞİN 30 GÜN) UYGULAMASI =====
            // Gösterim günü = Gerçek takvim günü (Şubat=28, Mart=31 vb.)
            // Hesaplama günü = Muhasebe standardı (Örn: 30 gün)
            $tamAyMi = ($toplamTakvimGunu >= $ayTakvimGunu);
            $normalCalismaGunGosterim = $normalCalismaGun; // Gerçek gün (gösterim)

            if ($tamAyMi && $devamsizlikGun == 0 && $ucretsizIzinGun == 0 && $ucretliIzinGun == 0) {
                // Tam ay, eksiksiz → Gösterim: takvim günü, Hesaplama: Standart Ay Günü
                $normalCalismaGunGosterim = $toplamTakvimGunu;
                $normalCalismaGun = $standartAyGun;
            } elseif ($tamAyMi) {
                // Tam ay ama devamsızlık/ücretsiz izin/ücretli izin var
                // Gösterim: takvim günü - tüm izin/devamsızlık (Excel formatı)
                $normalCalismaGunGosterim = $toplamTakvimGunu - $devamsizlikGun - $ucretsizIzinGun - $ucretliIzinGun;
                // Hesaplama: Standart gün standardı - kesintiler
                $normalCalismaGun = $standartAyGun - $devamsizlikGun - $ucretsizIzinGun;
            } else {
                // Kısmi ay: her iki değer de gerçek gün
                $normalCalismaGunGosterim = $normalCalismaGun;
            }

            // ===== ÜCRET HESAPLAMALARI =====
            // Normal Çalışma: Ücret = (Maaş/30) × min(gösterimGün, standartGün)
            // - Şubat (28 gün): min(28, 30) = 28 gün → normalUcret=(maaş/30)*28, gunFark=2 → toplam=30 gün ✓
            // - Mart  (31 gün): min(31, 30) = 30 gün → normalUcret=(maaş/30)*30, gunFark=0 → toplam=30 gün ✓
            $normalSaat = $normalCalismaGun * $gunlukCalismaSaat;           // Dahili hesaplama (30 gün bazlı)
            $normalSaatGosterim = $normalCalismaGunGosterim * $gunlukCalismaSaat; // Gösterim saati (takvim günü bazlı)
            $normalUcretGun = min($normalCalismaGunGosterim, $standartAyGun); // 31-günlük aylarda 30'a indir
            $normalUcret = $gunlukUcret * $normalUcretGun;                  // Ücret: standart bazlı (31-gün aşımı yok)

            // FM %50: saatlik × çarpan × saat
            $fm50SaatDecimal = round($fm50Dakika / 60, 2);
            $fm50Ucret = $saatlikUcret * $fm50Carpan * $fm50SaatDecimal;

            // FM %100: saatlik × çarpan × saat
            $fm100SaatDecimal = round($fm100Dakika / 60, 2);
            $fm100Ucret = $saatlikUcret * $fm100Carpan * $fm100SaatDecimal;

            // Devamsızlık: Ücret kesintisi = (Maaş/StandartGün) × devamsız gün
            $devSaat = $devamsizlikGun * $gunlukCalismaSaat;
            $devUcret = $gunlukUcret * $devamsizlikGun;

            // Ücretsiz İzin: Gün ve saat gösterilir, ücret kesintisi = (Maaş/StandartGün) × gün
            $uiSaat = $ucretsizIzinGun * $gunlukCalismaSaat;
            $uiUcret = $gunlukUcret * $ucretsizIzinGun;

            // Ücretli İzin: Ücret = (Maaş/StandartGün) × gün
            $uciSaat = $ucretliIzinGun * $gunlukCalismaSaat;
            $uciUcret = $gunlukUcret * $ucretliIzinGun;

            // Hafta Tatili: Bilgi amaçlı (Excel'de gösterilmez, normal güne dahil)
            $htSaat = 0;
            $htUcret = 0;

            // Gün Fark = Muhasebe standardı (Örn: 30) ile ayın takvim günü arasındaki fark
            // Ayar: gun_fark_hesapla (DB parametresi)
            // Örn: Şubat: 30-28=2, Mart: 30-31=0(max), Nisan: 30-30=0
            $gunFarkGun = 0;
            if ($gunFarkHesapla && $tamAyMi) {
                $gunFarkGun = max(0, $standartAyGun - $ayTakvimGunu);
            }
            $gunFarkUcret = $gunlukUcret * $gunFarkGun;

            // ===== YOL PARASI HESAPLAMA =====
            $yolParasiTutar = 0;
            if ($personel->ulasim_tipi === 'yol_parasi_gunluk') {
                $yolParasiTutar = (float)($personel->yol_parasi ?? 0) * $gelinenGunSayisi;
            } elseif ($personel->ulasim_tipi === 'yol_parasi_aylik') {
                $yolParasiTutar = (float)($personel->yol_parasi ?? 0);
            }

            // ===== YEMEK ÜCRETİ HESAPLAMA =====
            $yemekTutar = 0;
            if ($personel->yemek_tipi === 'ucret') {
                $yemekTutar = (float)($personel->yemek_ucreti ?? 0) * $gelinenGunSayisi;
            }

            // ===== ELDEN ÖDEME =====
            $eldenOdeme = (float)($personel->elden_odeme ?? 0);

            // TOPLAM (Bankaya Yatan)
            // NOT: $uiUcret (ücretsiz izin) normalCalismaGun içinde zaten düşüldüğünden burada tekrar çıkarılmaz.
            // NOT: $uciUcret (ücretli izin) bilgi amaçlı gösterilir; Excel uyumluluğu için bankaya yatan toplama dahil değil.
            // SSK dahil/hariç ayarı: ssk_rapor_toplama_dahil (DB parametresi)
            $toplam = $normalUcret + $fm50Ucret + $fm100Ucret
                    + $toplamEkOdeme + $gunFarkUcret + $yolParasiTutar + $yemekTutar
                    + ($sskToplamaDahil ? $toplamSskOdemesi : 0)
                    - $devUcret - $toplamAvans - $toplamKesinti;

            // Format fonksiyonları
            $fmt = fn($dakika) => sprintf('%02d:%02d', intdiv(abs((int)round($dakika)), 60), abs((int)round($dakika)) % 60);
            $fmtSaat = fn($saat) => sprintf('%02d:%02d', intdiv(abs((int)round($saat * 60)), 60), abs((int)round($saat * 60)) % 60);
            $fmtPara = fn($v) => number_format($v, 2, ',', '.');

            // Günlük özet kaydet
            foreach ($gunler as $g) {
                if ($g['giris'] || $g['cikis']) {
                    PdksGunlukOzet::withoutGlobalScopes()->updateOrCreate(
                        ['firma_id' => $firma_id, 'personel_id' => $personel->id, 'tarih' => $g['tarih']],
                        [
                            'ilk_giris' => $g['giris'] ? $g['tarih'] . ' ' . $g['giris'] : null,
                            'son_cikis' => $g['cikis'] ? $g['tarih'] . ' ' . $g['cikis'] : null,
                            'toplam_calisma_suresi' => $g['calisma_dakika'],
                            'fazla_mesai_dakika' => max(0, $g['calisma_dakika'] - $gunlukCalismaDakika),
                            'durum' => $g['durum'],
                        ]
                    );
                }
            }

            $sonuclar[] = [
                'personel_id' => $personel->id,
                'kart_no' => $personel->kart_no,
                'sicil_no' => $personel->sicil_no,
                'ad' => $personel->ad,
                'soyad' => $personel->soyad,
                'ad_soyad' => $personel->ad . ' ' . $personel->soyad,
                'bolum' => $personel->bolum ?? '-',
                'net_maas' => $fmtPara($aylikUcret),
                'elden_odeme' => $fmtPara($personel->elden_odeme ?? 0),
                'giris_tarihi' => $personel->giris_tarihi ? Carbon::parse($personel->giris_tarihi)->format('d.m.Y') : '',
                'cikis_tarihi' => $personel->cikis_tarihi ? Carbon::parse($personel->cikis_tarihi)->format('d.m.Y') : '',
                'gunler' => $gunler,
                'bordro' => [
                    // === ESKİ FORMAT (Mevcut frontend uyumluluğu) ===
                    'normal_calisma' => ['gun' => number_format($normalCalismaGunGosterim, 2, ',', '.'), 'saat' => $fmtSaat($normalSaatGosterim), 'ucret' => $fmtPara($normalUcret)],
                    'fazla_mesai_50' => ['saat' => $fmt($fm50Dakika), 'ucret' => $fmtPara($fm50Ucret)],
                    'fazla_mesai_100' => ['gun' => number_format($fm100Gun, 2, ',', '.'), 'saat' => $fmt($fm100Dakika), 'ucret' => $fmtPara($fm100Ucret)],
                    'devamsizlik' => ['gun' => number_format($devamsizlikGun, 2, ',', '.'), 'saat' => $fmtSaat($devSaat), 'ucret' => $fmtPara($devUcret)],
                    'hafta_tatili' => ['gun' => number_format($haftaTatiliGun, 2, ',', '.'), 'saat' => $fmtSaat($htSaat), 'ucret' => $fmtPara($htUcret)],
                    'ucretsiz_izin' => ['gun' => number_format($ucretsizIzinGun, 2, ',', '.'), 'saat' => $fmtSaat($uiSaat), 'ucret' => $fmtPara($uiUcret)],
                    'ucretli_izin' => ['gun' => number_format($ucretliIzinGun, 2, ',', '.'), 'saat' => $fmtSaat($uciSaat), 'ucret' => $fmtPara($uciUcret)],
                    'avans' => $fmtPara($toplamAvans),
                    'ek_odeme' => $fmtPara($toplamEkOdeme),
                    'yol_parasi' => $fmtPara($yolParasiTutar),
                    'yemek' => $fmtPara($yemekTutar),
                    'ssk_rapor_odemesi' => $fmtPara($toplamSskOdemesi),
                    'kesinti' => $fmtPara($toplamKesinti),
                    'gun_fark' => $fmtPara($gunFarkUcret),
                    'elden_odeme' => $fmtPara($eldenOdeme),
                    'toplam' => $fmtPara($toplam),
                    // === YENİ DİNAMİK FORMAT (Bordro Alanlarından) ===
                    'satirlar' => $this->bordroOlustur(
                        $bordroAlanlari, $fmtPara, $fmtSaat, $fmt,
                        $normalCalismaGunGosterim, $normalSaatGosterim, $normalUcret,
                        $fm50Dakika, $fm50Ucret, $fm100Gun, $fm100Dakika, $fm100Ucret,
                        $devamsizlikGun, $devSaat, $devUcret,
                        $haftaTatiliGun, $htSaat, $htUcret,
                        $ucretsizIzinGun, $uiSaat, $uiUcret,
                        $ucretliIzinGun, $uciSaat, $uciUcret,
                        $toplamAvans, $toplamEkOdeme, $yolParasiTutar, $yemekTutar,
                        $toplamSskOdemesi, $toplamKesinti, $gunFarkUcret, $eldenOdeme, $toplam,
                        $resmiTatilGun, $gunlukCalismaSaat, $gunlukUcret,
                        $bordroIzinSayac
                    ),
                ],
                'ozet' => [
                    'toplam_gun' => $toplamTakvimGunu,
                    'calisma_gun' => $normalCalismaGun,
                    'devamsizlik_gun' => $devamsizlikGun,
                    'izin_gun' => $ucretliIzinGun + $ucretsizIzinGun,
                    'tatil_gun' => $haftaTatiliGun + $resmiTatilGun,
                    'toplam_calisma_saat' => round($normalSaat, 2),
                    'toplam_fazla_mesai_saat' => round(($fm50Dakika + $fm100Dakika) / 60, 2),
                    'toplam_eksik_saat' => 0,
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'sonuclar' => $sonuclar,
            'tarih_araligi' => [
                'baslangic' => $baslangic->format('d.m.Y'),
                'bitis' => $bitis->format('d.m.Y'),
            ],
        ]);
    }

    /**
     * HH:MM:SS formatındaki süreyi dakikaya çevirir
     */
    private function saatToDakika(?string $saat): int
    {
        if (!$saat) return 0;
        $parts = explode(':', $saat);
        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);
        return ($h * 60) + $m;
    }

    /**
     * İzin türü adını bordro koduna eşler
     */
    private function izinTuruBordroKodu(string $izinTuruAd, string $izinTipiStr): ?int
    {
        $ad = mb_strtolower($izinTuruAd);
        
        if (str_contains($ad, 'babalık') || str_contains($ad, 'babalik')) return 15;
        if (str_contains($ad, 'evlenme') || str_contains($ad, 'evlilik')) return 16;
        if (str_contains($ad, 'ölüm') || str_contains($ad, 'olum') || str_contains($ad, 'vefat')) return 17;
        if (str_contains($ad, 'doğum') || str_contains($ad, 'dogum')) return 18;
        if (str_contains($ad, 'süt') || str_contains($ad, 'sut')) return 19;
        if (str_contains($ad, 'mazeret')) return 20;
        if (str_contains($ad, 'rapor')) return null; // Rapor günü, hesapla() içinde ilk 2 gün / 3+ gün ayrımı yapılır
        
        // Genel izin türleri
        if ($izinTipiStr === 'ucretsiz') return 6;
        if ($izinTipiStr === 'ucretli') return 5; // Yıllık İzin (varsayılan ücretli)
        
        return null;
    }

    /**
     * Bordro çıktısını bordro_alanlari tablosundan dinamik oluşturur
     */
    private function bordroOlustur(
        $bordroAlanlari, $fmtPara, $fmtSaat, $fmt,
        $normalCalismaGunGosterim, $normalSaatGosterim, $normalUcret,
        $fm50Dakika, $fm50Ucret, $fm100Gun, $fm100Dakika, $fm100Ucret,
        $devamsizlikGun, $devSaat, $devUcret,
        $haftaTatiliGun, $htSaat, $htUcret,
        $ucretsizIzinGun, $uiSaat, $uiUcret,
        $ucretliIzinGun, $uciSaat, $uciUcret,
        $toplamAvans, $toplamEkOdeme, $yolParasiTutar, $yemekTutar,
        $toplamSskOdemesi, $toplamKesinti, $gunFarkUcret, $eldenOdeme, $toplam,
        $resmiTatilGun, $gunlukCalismaSaat, $gunlukUcret,
        $bordroIzinSayac
    ): array {
        $bordro = [];

        // Kod bazlı değer haritası
        $kodDeger = [
            1  => ['gun' => $normalCalismaGunGosterim, 'saat_raw' => $normalSaatGosterim, 'ucret' => $normalUcret, 'fmt' => 'saat'],
            2  => ['saat_raw_dk' => $fm50Dakika, 'ucret' => $fm50Ucret, 'fmt' => 'dakika'],
            3  => ['gun' => $fm100Gun, 'saat_raw_dk' => $fm100Dakika, 'ucret' => $fm100Ucret, 'fmt' => 'dakika'],
            4  => ['gun' => $devamsizlikGun, 'saat_raw' => $devSaat, 'ucret' => $devUcret, 'fmt' => 'saat'],
            5  => ['gun' => $ucretliIzinGun, 'saat_raw' => $uciSaat, 'ucret' => $uciUcret, 'fmt' => 'saat'],
            6  => ['gun' => $ucretsizIzinGun, 'saat_raw' => $uiSaat, 'ucret' => $uiUcret, 'fmt' => 'saat'],
            7  => ['ucret' => $toplamAvans, 'fmt' => 'para'],
            8  => ['gun' => $haftaTatiliGun, 'saat_raw' => $htSaat, 'ucret' => $htUcret, 'fmt' => 'saat'],
            9  => ['ucret' => $toplamEkOdeme, 'fmt' => 'para'],
            10 => ['ucret' => $yolParasiTutar, 'fmt' => 'para'],
            11 => ['ucret' => $toplamKesinti, 'fmt' => 'para'],
            12 => ['ucret' => $gunFarkUcret, 'fmt' => 'para'],
            13 => ['ucret' => $yemekTutar, 'fmt' => 'para'],
            14 => ['ucret' => 0, 'fmt' => 'para'], // Servis ücreti (henüz hesaplanmıyor)
            23 => ['gun' => $resmiTatilGun, 'fmt' => 'saat'],
        ];

        // İzin bazlı kodlar (15-22): bordroIzinSayac'tan gelen gün sayıları
        foreach ($bordroIzinSayac as $kod => $gunSayisi) {
            $izinSaat = $gunSayisi * $gunlukCalismaSaat;
            $izinUcret = $gunlukUcret * $gunSayisi;
            $kodDeger[$kod] = ['gun' => $gunSayisi, 'saat_raw' => $izinSaat, 'ucret' => $izinUcret, 'fmt' => 'saat'];
        }

        foreach ($bordroAlanlari as $alan) {
            $kod = $alan->kod;
            $deger = $kodDeger[$kod] ?? null;

            $item = [
                'kod' => $kod,
                'aciklama' => $alan->aciklama,
                'bordro_tipi' => $alan->bordro_tipi,
            ];

            if ($deger) {
                if ($alan->gun) {
                    $item['gun'] = number_format($deger['gun'] ?? 0, 2, ',', '.');
                }
                if ($alan->saat) {
                    if (($deger['fmt'] ?? '') === 'dakika') {
                        $item['saat'] = $fmt($deger['saat_raw_dk'] ?? 0);
                    } else {
                        $item['saat'] = $fmtSaat($deger['saat_raw'] ?? 0);
                    }
                }
                if ($alan->ucret) {
                    $item['ucret'] = $fmtPara($deger['ucret'] ?? 0);
                }
            } else {
                // Bordro alanında veri yoksa boş göster
                if ($alan->gun) $item['gun'] = '0,00';
                if ($alan->saat) $item['saat'] = '00:00';
                if ($alan->ucret) $item['ucret'] = '0,00';
            }

            $bordro[] = $item;
        }

        // Ek hesaplar (toplam, ssk, elden)
        $bordro[] = ['kod' => 'ssk', 'aciklama' => 'SGK RAPOR ÖDEMESİ', 'ucret' => $fmtPara($toplamSskOdemesi), 'bordro_tipi' => 'diger_hesaplar_arti'];
        $bordro[] = ['kod' => 'elden', 'aciklama' => 'ELDEN ÖDEME', 'ucret' => $fmtPara($eldenOdeme), 'bordro_tipi' => 'diger_hesaplar_eksi'];
        $bordro[] = ['kod' => 'toplam', 'aciklama' => 'TOPLAM (BANKAYA YATAN)', 'ucret' => $fmtPara($toplam), 'bordro_tipi' => 'toplam'];

        return $bordro;
    }
}
