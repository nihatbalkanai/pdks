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

            // Günlük çalışma saati (vardiya parametresinden)
            $gunlukCalismaSaat = 9.0; // varsayılan
            if ($puantajParam && $puantajParam->iceri_giris_saati && $puantajParam->disari_cikis_saati) {
                $g = Carbon::parse($puantajParam->iceri_giris_saati);
                $c = Carbon::parse($puantajParam->disari_cikis_saati);
                $gunlukCalismaSaat = abs($c->diffInMinutes($g)) / 60;
            } elseif ($aylikParam && $aylikParam->gunluk_calisma_saati > 0) {
                $gunlukCalismaSaat = (float)$aylikParam->gunluk_calisma_saati;
            }
            $gunlukCalismaDakika = (int)round($gunlukCalismaSaat * 60);

            $molaSuresi = $puantajParam ? (int)($puantajParam->mola_suresi ?? 0) : 0;

            // Çarpanlar ve parametreler (veritabanından)
            $fm50Carpan = $aylikParam ? (float)$aylikParam->fazla_mesai_carpani : 1.5;
            $fm100Carpan = $aylikParam ? (float)$aylikParam->tatil_mesai_carpani : 2.0;
            $fmToleransDakika = $aylikParam ? (int)($aylikParam->fazla_mesai_tolerans_dakika ?? self::FM_TOLERANS_DAKIKA_DEFAULT) : self::FM_TOLERANS_DAKIKA_DEFAULT;
            $gunFarkHesapla = $aylikParam ? (bool)($aylikParam->gun_fark_hesapla ?? true) : true;
            $sskToplamaDahil = $aylikParam ? (bool)($aylikParam->ssk_rapor_toplama_dahil ?? false) : false;

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
                $izinTipiStr = ($izin->izinTuru && $izin->izinTuru->ucret_kesintisi_yapilacak_mi) ? 'ucretsiz' : 'ucretli';
                for ($d = $izinBaslangic->copy(); $d->lte($izinBitis); $d->addDay()) {
                    $izinliGunler[$d->format('Y-m-d')] = $izinTipiStr;
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

            for ($gun = $baslangic->copy(); $gun->lte($bitis); $gun->addDay()) {
                $tarih = $gun->format('Y-m-d');
                $haftaGunu = $gun->dayOfWeekIso; // 1=Pzt, 7=Paz
                $haftaSonu = $haftaGunu >= 6;
                $resmiTatilMi = in_array($tarih, $resmiTatiller);
                $izinTipi = $izinliGunler[$tarih] ?? null;

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
                    // İzinli gün
                    if ($izinTipi === 'ucretsiz') {
                        $durum = 'ucretsiz_izin';
                        $ucretsizIzinGun++;
                    } else {
                        $durum = 'ucretli_izin';
                        $ucretliIzinGun++;
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

                    // Fazla mesai kontrolü (günlük standart saatten fazla çalışma)
                    // Tolerans: DB parametresinden (varsayılan: 5 dk)
                    $fmFark = $calismaDakika - $gunlukCalismaDakika;
                    if ($fmFark > $fmToleransDakika) {
                        $fm50Dakika += $fmFark;
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
            // Normal Çalışma: Ücret = (Maaş/30) × Gösterim Gün (takvim günü bazlı, Excel uyumlu)
            $normalSaat = $normalCalismaGun * $gunlukCalismaSaat;           // Dahili hesaplama (30 gün bazlı)
            $normalSaatGosterim = $normalCalismaGunGosterim * $gunlukCalismaSaat; // Gösterim saati (takvim günü bazlı)
            $normalUcret = $gunlukUcret * $normalCalismaGunGosterim;        // Ücret de takvim günü bazlı

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

            // TOPLAM (Bankaya Yatan)
            // SSK dahil/hariç ayarı: ssk_rapor_toplama_dahil (DB parametresi)
            $toplam = $normalUcret + $fm50Ucret + $fm100Ucret + $uciUcret
                    + $toplamEkOdeme + $gunFarkUcret
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
                    'normal_calisma' => [
                        'gun' => number_format($normalCalismaGunGosterim, 2, ',', '.'),
                        'saat' => $fmtSaat($normalSaatGosterim),
                        'ucret' => $fmtPara($normalUcret),
                    ],
                    'fazla_mesai_50' => [
                        'saat' => $fmt($fm50Dakika),
                        'ucret' => $fmtPara($fm50Ucret),
                    ],
                    'fazla_mesai_100' => [
                        'gun' => number_format($fm100Gun, 2, ',', '.'),
                        'saat' => $fmt($fm100Dakika),
                        'ucret' => $fmtPara($fm100Ucret),
                    ],
                    'devamsizlik' => [
                        'gun' => number_format($devamsizlikGun, 2, ',', '.'),
                        'saat' => $fmtSaat($devSaat),
                        'ucret' => $fmtPara($devUcret),
                    ],
                    'hafta_tatili' => [
                        'gun' => number_format($haftaTatiliGun, 2, ',', '.'),
                        'saat' => $fmtSaat($htSaat),
                        'ucret' => $fmtPara($htUcret),
                    ],
                    'ucretsiz_izin' => [
                        'gun' => number_format($ucretsizIzinGun, 2, ',', '.'),
                        'saat' => $fmtSaat($uiSaat),
                        'ucret' => $fmtPara($uiUcret),
                    ],
                    'ucretli_izin' => [
                        'gun' => number_format($ucretliIzinGun, 2, ',', '.'),
                        'saat' => $fmtSaat($uciSaat),
                        'ucret' => $fmtPara($uciUcret),
                    ],
                    'avans' => $fmtPara($toplamAvans),
                    'ek_odeme' => $fmtPara($toplamEkOdeme),
                    'ssk_rapor_odemesi' => $fmtPara($toplamSskOdemesi),
                    'kesinti' => $fmtPara($toplamKesinti),
                    'gun_fark' => $fmtPara($gunFarkUcret),
                    'elden_odeme' => $fmtPara($personel->elden_odeme ?? 0),
                    'toplam' => $fmtPara($toplam),
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
}
