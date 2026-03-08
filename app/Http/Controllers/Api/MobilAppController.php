<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personel;
use App\Models\Firma;
use App\Models\PdksKaydi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobilAppController extends Controller
{
    /**
     * Mobil Giriş — Firma Kodu + TC No + Şifre ile
     */
    public function login(Request $request)
    {
        $request->validate([
            'firma_kodu' => 'required|string',
            'tc_no' => 'required|string',
            'sifre' => 'required|string',
            'cihaz_id' => 'nullable|string',
            'cihaz_adi' => 'nullable|string',
            'platform' => 'nullable|in:ios,android,web',
            'push_token' => 'nullable|string',
        ]);

        // 1. Firmayı bul
        $firma = Firma::where('firma_kodu', strtoupper($request->firma_kodu))->first();
        if (!$firma) {
            return response()->json(['hata' => true, 'mesaj' => 'Firma kodu geçersiz.'], 404);
        }
        if (!$firma->mobil_giris_aktif) {
            return response()->json(['hata' => true, 'mesaj' => 'Bu firma için mobil giriş aktif değil.'], 403);
        }

        // 2. Personeli bul
        $personel = Personel::withoutGlobalScopes()
            ->where('firma_id', $firma->id)
            ->where('tc_no', $request->tc_no)
            ->first();

        if (!$personel) {
            return response()->json(['hata' => true, 'mesaj' => 'Bu TC numarası ile kayıtlı personel bulunamadı.'], 404);
        }
        if ($personel->durum !== 'aktif' && $personel->durum != 1) {
            return response()->json(['hata' => true, 'mesaj' => 'Hesabınız aktif değil.'], 403);
        }

        // 3. Şifre kontrolü (İlk giriş: TC'nin son 6 hanesi)
        $varsayilanSifre = substr($request->tc_no, -6);
        $sifreGecerli = ($request->sifre === $varsayilanSifre);

        // Eğer personelde hash'li şifre varsa onu kontrol et
        if ($personel->mobil_sifre) {
            $sifreGecerli = Hash::check($request->sifre, $personel->mobil_sifre);
        }

        if (!$sifreGecerli) {
            return response()->json(['hata' => true, 'mesaj' => 'Şifre hatalı.'], 401);
        }

        // 4. Cihaz kaydı
        if ($request->cihaz_id) {
            $cihaz = DB::table('mobil_cihazlar')
                ->where('firma_id', $firma->id)
                ->where('cihaz_id', $request->cihaz_id)
                ->first();

            if (!$cihaz) {
                // Yeni cihaz kaydet
                DB::table('mobil_cihazlar')->insert([
                    'firma_id' => $firma->id,
                    'personel_id' => $personel->id,
                    'cihaz_id' => $request->cihaz_id,
                    'cihaz_adi' => $request->cihaz_adi,
                    'platform' => $request->platform ?? 'android',
                    'son_giris' => now(),
                    'son_ip' => $request->ip(),
                    'aktif' => true,
                    'admin_onayli' => false,
                    'push_token' => $request->push_token,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Mevcut cihaz güncelle
                if (!$cihaz->aktif) {
                    return response()->json(['hata' => true, 'mesaj' => 'Bu cihaz devre dışı bırakılmış.'], 403);
                }
                DB::table('mobil_cihazlar')->where('id', $cihaz->id)->update([
                    'son_giris' => now(),
                    'son_ip' => $request->ip(),
                    'push_token' => $request->push_token ?? $cihaz->push_token,
                    'updated_at' => now(),
                ]);
            }
        }

        // 5. Token üret
        $token = $personel->createToken('mobil-app', ['firma:' . $firma->id])->plainTextToken;

        return response()->json([
            'hata' => false,
            'mesaj' => 'Giriş başarılı',
            'token' => $token,
            'personel' => [
                'id' => $personel->id,
                'ad' => $personel->ad ?: (explode(' ', $personel->ad_soyad ?? '')[0] ?? ''),
                'soyad' => $personel->soyad ?: (explode(' ', $personel->ad_soyad ?? '')[1] ?? ''),
                'kart_no' => $personel->kart_no,
                'departman' => $personel->departman,
                'pozisyon' => $personel->unvan,
            ],
            'firma' => [
                'id' => $firma->id,
                'firma_adi' => $firma->firma_adi,
                'logo' => $firma->logo_yolu ? request()->getSchemeAndHttpHost() . '/storage/' . $firma->logo_yolu : null,
                'gps_zorunlu' => $firma->gps_zorunlu,
                'qr_kod_aktif' => $firma->qr_kod_aktif,
                'geofence_yaricap' => $firma->geofence_yaricap,
            ],
        ]);
    }

    /**
     * Mobil Giriş/Çıkış Kaydet
     */
    public function girisYap(Request $request)
    {
        $request->validate([
            'tip' => 'required|in:giris,cikis',
            'enlem' => 'nullable|numeric',
            'boylam' => 'nullable|numeric',
            'dogrulama_yontemi' => 'nullable|in:gps,qr,wifi',
            'qr_kod' => 'nullable|string',
            'wifi_ssid' => 'nullable|string',
            'sahte_konum' => 'nullable|boolean',
        ]);

        $personel = $request->user();
        $firma = Firma::find($personel->firma_id);

        if (!$firma || !$firma->mobil_giris_aktif) {
            return response()->json(['hata' => true, 'mesaj' => 'Mobil giriş bu firma için aktif değil.'], 403);
        }

        // ═══ YARIM SAAT KURALI ═══
        // Son işlemden en az 30 dakika geçmeli
        $sonKayit = PdksKaydi::withoutGlobalScopes()
            ->where('personel_id', $personel->id)
            ->where('firma_id', $firma->id)
            ->orderByDesc('kayit_tarihi')
            ->first();

        if ($sonKayit) {
            $sonZaman = Carbon::parse($sonKayit->kayit_tarihi);
            $farkDakika = now()->diffInMinutes($sonZaman);
            if ($farkDakika < 30) {
                $kalanDk = 30 - $farkDakika;
                return response()->json([
                    'hata' => true,
                    'mesaj' => "Son işleminizin üzerinden henüz 30 dakika geçmedi. {$kalanDk} dakika sonra tekrar deneyiniz.",
                ], 429);
            }
        }

        $mesafe = null;
        $yontem = $request->dogrulama_yontemi ?? 'gps';

        // Sahte konum algılandıysa kaydet ama engelle
        if ($request->sahte_konum) {
            DB::table('mobil_hareketler')->insert([
                'firma_id' => $firma->id,
                'personel_id' => $personel->id,
                'tip' => $request->tip,
                'enlem' => $request->enlem,
                'boylam' => $request->boylam,
                'dogrulama_yontemi' => $yontem,
                'ip_adresi' => $request->ip(),
                'sahte_konum_algilandi' => true,
                'notlar' => 'Sahte konum algılandı, işlem reddedildi',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['hata' => true, 'mesaj' => 'Sahte konum algılandı! Bu işlem raporlanacaktır.'], 403);
        }

        // GPS doğrulama (Şube bazlı çoklu konum desteği)
        if ($firma->gps_zorunlu && $yontem === 'gps') {
            if (!$request->enlem || !$request->boylam) {
                return response()->json(['hata' => true, 'mesaj' => 'Konum bilgisi zorunludur.'], 422);
            }

            // Öncelik sırası: 1) Personelin şubesi, 2) Firma merkez konumu
            $hedefEnlem = null;
            $hedefBoylam = null;
            $hedefYaricap = $firma->geofence_yaricap ?? 200;
            $lokasyonAdi = 'Firma Merkezi';

            // Personelin şubesi varsa ve şubenin koordinatları tanımlıysa
            if ($personel->sube_id) {
                $sube = \App\Models\Sube::find($personel->sube_id);
                if ($sube && $sube->lokasyon_enlem && $sube->lokasyon_boylam) {
                    $hedefEnlem = $sube->lokasyon_enlem;
                    $hedefBoylam = $sube->lokasyon_boylam;
                    $hedefYaricap = $sube->geofence_yaricap ?? $hedefYaricap;
                    $lokasyonAdi = $sube->sube_adi;
                }
            }

            // Şube yoksa veya şubenin koordinatları tanımlı değilse firma merkezini kullan
            if (!$hedefEnlem && $firma->lokasyon_enlem && $firma->lokasyon_boylam) {
                $hedefEnlem = $firma->lokasyon_enlem;
                $hedefBoylam = $firma->lokasyon_boylam;
            }

            // Mesafe hesapla (Haversine formülü)
            if ($hedefEnlem && $hedefBoylam) {
                $mesafe = $this->mesafeHesapla(
                    $request->enlem, $request->boylam,
                    $hedefEnlem, $hedefBoylam
                );

                if ($mesafe > $hedefYaricap) {
                    return response()->json([
                        'hata' => true,
                        'mesaj' => "{$lokasyonAdi} çalışma alanı dışındasınız. Mesafe: {$mesafe}m (İzin verilen: {$hedefYaricap}m)",
                        'mesafe' => $mesafe,
                    ], 403);
                }
            }
        }

        // QR Kod doğrulama
        if ($yontem === 'qr' && $request->qr_kod) {
            $qr = DB::table('qr_kod_oturumlari')
                ->where('firma_id', $firma->id)
                ->where('kod', $request->qr_kod)
                ->where('aktif', true)
                ->where('gecerlilik_bitis', '>', now())
                ->first();

            if (!$qr) {
                return response()->json(['hata' => true, 'mesaj' => 'QR kod geçersiz veya süresi dolmuş.'], 403);
            }
        }

        // WiFi doğrulama
        if ($yontem === 'wifi' && $request->wifi_ssid) {
            if ($firma->wifi_ssid && $firma->wifi_ssid !== $request->wifi_ssid) {
                return response()->json(['hata' => true, 'mesaj' => 'Firma WiFi ağında değilsiniz.'], 403);
            }
        }

        // Giriş/çıkış kaydet
        DB::table('mobil_hareketler')->insert([
            'firma_id' => $firma->id,
            'personel_id' => $personel->id,
            'tip' => $request->tip,
            'enlem' => $request->enlem,
            'boylam' => $request->boylam,
            'dogrulama_yontemi' => $yontem,
            'mesafe_metre' => $mesafe,
            'wifi_ssid' => $request->wifi_ssid,
            'ip_adresi' => $request->ip(),
            'sahte_konum_algilandi' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Ana sisteme (Puantaj Hesaplamalarına / Personel Kartlarına) eş zamanlı olarak PDKS kaydı düşürüyoruz
        PdksKaydi::create([
            'firma_id' => $firma->id,
            'personel_id' => $personel->id,
            'kayit_tarihi' => now(),
            'islem_tipi' => $request->tip === 'giris' ? 'Giriş' : 'Çıkış',
            'ham_veri' => [
                'kaynak' => 'Mobil Uygulama',
                'yontem' => $yontem,
                'cihaz' => 'Mobil'
            ]
        ]);

        // Günün saatine göre ve doğum gününe göre mesaj oluşturma
        $saat = (int) now()->format('H');
        $mesajPrefix = '';
        
        $bugunDogumGunu = false;
        if ($personel->dogum_tarihi) {
            $bugunDogumGunu = now()->format('m-d') === \Carbon\Carbon::parse($personel->dogum_tarihi)->format('m-d');
        }

        if ($request->tip === 'giris') {
            if ($saat >= 5 && $saat < 12) {
                $mesajPrefix = 'Günaydın, hoş geldiniz! ';
            } elseif ($saat >= 12 && $saat < 18) {
                $mesajPrefix = 'İyi günler, iyi çalışmalar! ';
            } else {
                $mesajPrefix = 'İyi akşamlar, iyi çalışmalar! ';
            }
        } else {
            if ($saat >= 16) {
                $mesajPrefix = 'Mesainiz bitti, iyi akşamlar! Emeğinize sağlık. ';
            } else {
                $mesajPrefix = 'İyi günler dileriz! ';
            }
        }

        $tamMesaj = $mesajPrefix . " İşlem başarıyla kaydedildi.";
        if ($bugunDogumGunu && $request->tip === 'giris') {
            $tamMesaj = "🎉 Doğum gününüz kutlu olsun! " . $tamMesaj;
        }

        return response()->json([
            'hata' => false,
            'mesaj' => $tamMesaj,
            'zaman' => now()->format('H:i:s'),
            'mesafe' => $mesafe,
            'dogum_gunu_mu' => $bugunDogumGunu,
        ]);
    }

    /**
     * Bugünkü Durum
     */
    public function bugunDurum(Request $request)
    {
        $personel = $request->user();
        $bugun = now()->toDateString();

        $hareketler = DB::table('mobil_hareketler')
            ->where('personel_id', $personel->id)
            ->whereDate('created_at', $bugun)
            ->orderBy('created_at')
            ->get();

        $sonGiris = $hareketler->where('tip', 'giris')->last();
        $sonCikis = $hareketler->where('tip', 'cikis')->last();

        return response()->json([
            'hata' => false,
            'bugun' => $bugun,
            'hareketler' => $hareketler,
            'son_giris' => $sonGiris?->created_at,
            'son_cikis' => $sonCikis?->created_at,
            'toplam_hareket' => $hareketler->count(),
        ]);
    }

    /**
     * Son 30 Gün Geçmişi
     */
    public function gecmis(Request $request)
    {
        $personel = $request->user();

        $hareketler = DB::table('mobil_hareketler')
            ->where('personel_id', $personel->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'hata' => false,
            'hareketler' => $hareketler,
        ]);
    }

    /**
     * Profil ve Bildirimler
     */
    public function profil(Request $request)
    {
        $personel = $request->user();
        $firma = Firma::find($personel->firma_id);

        return response()->json([
            'hata' => false,
            'personel' => [
                'id' => $personel->id,
                'ad' => $personel->ad,
                'soyad' => $personel->soyad,
                'kart_no' => $personel->kart_no,
                'departman' => $personel->departman,
                'gorev' => $personel->gorev,
                'sicil_no' => $personel->sicil_no,
            ],
            'firma' => [
                'firma_adi' => $firma?->firma_adi,
                'gps_zorunlu' => $firma?->gps_zorunlu,
                'qr_kod_aktif' => $firma?->qr_kod_aktif,
                'geofence_yaricap' => $firma?->geofence_yaricap,
            ],
        ]);
    }

    /**
     * Şifre Değiştir
     */
    public function sifreDegistir(Request $request)
    {
        $request->validate([
            'mevcut_sifre' => 'required|string',
            'yeni_sifre' => 'required|string|min:6|confirmed',
        ]);

        $personel = $request->user();

        // Mevcut şifre kontrolü
        if ($personel->mobil_sifre && !Hash::check($request->mevcut_sifre, $personel->mobil_sifre)) {
            return response()->json(['hata' => true, 'mesaj' => 'Mevcut şifre hatalı.'], 401);
        }

        $personel->update(['mobil_sifre' => Hash::make($request->yeni_sifre)]);

        return response()->json(['hata' => false, 'mesaj' => 'Şifre başarıyla değiştirildi.']);
    }

    /**
     * Haversine formülü ile mesafe hesaplama (metre)
     */
    private function mesafeHesapla($lat1, $lon1, $lat2, $lon2): int
    {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return (int) round($R * $c);
    }

    // ═══════════════════ YENİ MODÜLLER ═══════════════════

    /**
     * İzinlerim — Personelin izin listesi
     */
    public function izinlerim(Request $request)
    {
        $personel = $request->user();
        $izinler = \App\Models\PersonelIzin::withoutGlobalScopes()
            ->with('izinTuru')
            ->where('personel_id', $personel->id)
            ->orderByDesc('tarih')
            ->limit(50)
            ->get()
            ->map(fn($iz) => [
                'id' => $iz->id,
                'tarih' => Carbon::parse($iz->tarih)->format('d.m.Y'),
                'bitis' => $iz->bitis_tarihi ? Carbon::parse($iz->bitis_tarihi)->format('d.m.Y') : null,
                'gun' => $iz->bitis_tarihi
                    ? Carbon::parse($iz->tarih)->diffInDays(Carbon::parse($iz->bitis_tarihi)) + 1
                    : 1,
                'tur' => $iz->izinTuru?->ad ?? 'Bilinmiyor',
                'durum' => $iz->durum, // beklemede, onaylandi, reddedildi
                'aciklama' => $iz->aciklama,
            ]);

        return response()->json(['hata' => false, 'izinler' => $izinler]);
    }

    /**
     * İzin Talebi Oluştur
     */
    public function izinTalebi(Request $request)
    {
        $request->validate([
            'izin_turu_id' => 'required|exists:izin_turleri,id',
            'tarih' => 'required|date|after:yesterday',
            'bitis_tarihi' => 'nullable|date|after_or_equal:tarih',
            'baslangic_saati' => 'nullable|string|max:5',
            'bitis_saati' => 'nullable|string|max:5',
            'aciklama' => 'nullable|string|max:500',
        ]);

        $personel = $request->user();

        // Saatlik izin bilgisini açıklamaya ekle
        $aciklama = $request->aciklama ?? '';
        if ($request->baslangic_saati && $request->bitis_saati) {
            $saatBilgisi = "⏰ Saatlik İzin: {$request->baslangic_saati} - {$request->bitis_saati}";
            $aciklama = $saatBilgisi . ($aciklama ? " | {$aciklama}" : '');
        }

        $izin = \App\Models\PersonelIzin::create([
            'firma_id' => $personel->firma_id,
            'personel_id' => $personel->id,
            'izin_turu_id' => $request->izin_turu_id,
            'tarih' => $request->tarih,
            'bitis_tarihi' => $request->bitis_tarihi ?? $request->tarih,
            'aciklama' => $aciklama,
            'durum' => 'beklemede',
        ]);

        $mesajTip = ($request->baslangic_saati && $request->bitis_saati)
            ? "Saatlik izin talebiniz ({$request->baslangic_saati}-{$request->bitis_saati}) oluşturuldu."
            : 'İzin talebiniz oluşturuldu. Yönetici onayı bekleniyor.';

        return response()->json([
            'hata' => false,
            'mesaj' => $mesajTip,
        ]);
    }

    /**
     * İzin Türleri Listesi
     */
    public function izinTurleri(Request $request)
    {
        $personel = $request->user();
        $turler = \App\Models\IzinTuru::withoutGlobalScopes()
            ->where('firma_id', $personel->firma_id)
            ->select('id', 'ad')
            ->orderBy('ad')
            ->get();

        return response()->json(['hata' => false, 'turler' => $turler]);
    }

    /**
     * Puantaj Özeti — Bu ay çalışma istatistikleri
     */
    public function puantajOzeti(Request $request)
    {
        $personel = $request->user();
        $ay = $request->ay ?? now()->month;
        $yil = $request->yil ?? now()->year;

        $baslangic = Carbon::create($yil, $ay, 1)->startOfMonth();
        $bitis = $baslangic->copy()->endOfMonth();
        $bugun = now();

        // PDKS kayıtları
        $kayitlar = PdksKaydi::withoutGlobalScopes()
            ->where('personel_id', $personel->id)
            ->whereBetween('kayit_tarihi', [$baslangic, $bitis])
            ->orderBy('kayit_tarihi')
            ->get();

        // Günlere göre grupla
        $gunler = $kayitlar->groupBy(fn($k) => Carbon::parse($k->kayit_tarihi)->format('Y-m-d'));

        $gelinenGun = 0;
        $toplamDakika = 0;

        foreach ($gunler as $tarih => $gunKayitlari) {
            $ilk = $gunKayitlari->first();
            $son = $gunKayitlari->last();
            if ($ilk && $son && $ilk->id !== $son->id) {
                $dakika = Carbon::parse($ilk->kayit_tarihi)->diffInMinutes(Carbon::parse($son->kayit_tarihi));
                $toplamDakika += $dakika;
            }
            $gelinenGun++;
        }

        // İzin sayıları
        $izinler = \App\Models\PersonelIzin::withoutGlobalScopes()
            ->with('izinTuru')
            ->where('personel_id', $personel->id)
            ->where('durum', 'onaylandi')
            ->where(function ($q) use ($baslangic, $bitis) {
                $q->whereBetween('tarih', [$baslangic, $bitis]);
            })->get();

        $ucretliIzin = 0;
        $ucretsizIzin = 0;
        $raporGun = 0;
        foreach ($izinler as $iz) {
            $gun = $iz->bitis_tarihi
                ? Carbon::parse($iz->tarih)->diffInDays(Carbon::parse($iz->bitis_tarihi)) + 1
                : 1;
            $turAd = mb_strtolower($iz->izinTuru?->ad ?? '');
            if (str_contains($turAd, 'rapor')) {
                $raporGun += $gun;
            } elseif ($iz->izinTuru?->ucret_kesintisi_yapilacak_mi) {
                $ucretsizIzin += $gun;
            } else {
                $ucretliIzin += $gun;
            }
        }

        // Resmi tatiller
        $resmiTatilSayisi = \App\Models\ResmiTatil::withoutGlobalScopes()
            ->where('firma_id', $personel->firma_id)
            ->whereBetween('tarih', [$baslangic->format('Y-m-d'), $bitis->format('Y-m-d')])
            ->count();

        return response()->json([
            'hata' => false,
            'ozet' => [
                'ay' => $baslangic->translatedFormat('F Y'),
                'gelinen_gun' => $gelinenGun,
                'toplam_saat' => round($toplamDakika / 60, 1),
                'toplam_dakika' => $toplamDakika,
                'ucretli_izin' => $ucretliIzin,
                'ucretsiz_izin' => $ucretsizIzin,
                'rapor_gun' => $raporGun,
                'resmi_tatil' => $resmiTatilSayisi,
                'takvim_gunu' => $bitis->day,
            ],
        ]);
    }

    /**
     * Vardiya Takvimi — Bu ay vardiya planı
     */
    public function vardiyaTakvimi(Request $request)
    {
        $personel = $request->user();
        $ay = $request->ay ?? now()->month;
        $yil = $request->yil ?? now()->year;

        $baslangic = Carbon::create($yil, $ay, 1)->startOfMonth();
        $bitis = $baslangic->copy()->endOfMonth();

        // Personele özel plan
        $planlar = \App\Models\PersonelCalismaPlan::withoutGlobalScopes()
            ->with('vardiya')
            ->where('personel_id', $personel->id)
            ->whereBetween('tarih', [$baslangic->format('Y-m-d'), $bitis->format('Y-m-d')])
            ->get()
            ->keyBy(fn($p) => Carbon::parse($p->tarih)->format('Y-m-d'));

        // Grup planı (fallback)
        $grupPlan = collect();
        if ($personel->calisma_grubu_id) {
            $grupPlan = \App\Models\CalismaPlan::withoutGlobalScopes()
                ->with('vardiya')
                ->where('calisma_grubu_id', $personel->calisma_grubu_id)
                ->whereBetween('tarih', [$baslangic->format('Y-m-d'), $bitis->format('Y-m-d')])
                ->get()
                ->keyBy(fn($p) => Carbon::parse($p->tarih)->format('Y-m-d'));
        }

        // Resmi tatiller
        $resmiTatiller = \App\Models\ResmiTatil::withoutGlobalScopes()
            ->where('firma_id', $personel->firma_id)
            ->whereBetween('tarih', [$baslangic->format('Y-m-d'), $bitis->format('Y-m-d')])
            ->pluck('ad', 'tarih')
            ->mapWithKeys(fn($ad, $t) => [Carbon::parse($t)->format('Y-m-d') => $ad]);

        $takvim = [];
        for ($d = $baslangic->copy(); $d->lte($bitis); $d->addDay()) {
            $t = $d->format('Y-m-d');
            $plan = $planlar[$t] ?? $grupPlan[$t] ?? null;
            $tatilAdi = $resmiTatiller[$t] ?? null;

            $takvim[] = [
                'tarih' => $d->format('d'),
                'gun' => $d->translatedFormat('D'),
                'tur' => $tatilAdi ? 'resmi_tatil' : ($plan?->tur ?? ($d->isWeekend() ? 'tatil' : 'is_gunu')),
                'vardiya' => $plan?->vardiya?->ad ?? null,
                'tatil_adi' => $tatilAdi,
            ];
        }

        return response()->json([
            'hata' => false,
            'ay' => $baslangic->translatedFormat('F Y'),
            'takvim' => $takvim,
        ]);
    }

    /**
     * Belgelerim — Personelin dosyaları
     */
    public function belgelerim(Request $request)
    {
        $personel = $request->user();

        try {
            $dosyalar = \App\Models\PersonelDosya::withoutGlobalScopes()
                ->where('personel_id', $personel->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn($d) => [
                    'id' => $d->id,
                    'ad' => $d->dosya_adi ?? $d->ad ?? 'Belge',
                    'tur' => $d->dosya_turu ?? $d->tur ?? 'Diğer',
                    'tarih' => Carbon::parse($d->created_at)->format('d.m.Y'),
                    'url' => $d->dosya_yolu ? asset('storage/' . $d->dosya_yolu) : null,
                ]);
        } catch (\Exception $e) {
            $dosyalar = [];
        }

        return response()->json(['hata' => false, 'belgeler' => $dosyalar]);
    }

    /**
     * Bordro Özeti — Son ay bordro bilgisi
     */
    public function bordroOzeti(Request $request)
    {
        $personel = $request->user();
        $ay = $request->ay ?? now()->month;
        $yil = $request->yil ?? now()->year;

        try {
            $puantaj = DB::table('puantaj_sonuclari')
                ->where('personel_id', $personel->id)
                ->where('ay', $ay)
                ->where('yil', $yil)
                ->first();
        } catch (\Exception $e) {
            $puantaj = null;
        }

        if (!$puantaj) {
            return response()->json([
                'hata' => false,
                'bordro' => null,
                'mesaj' => 'Bu dönem için bordro bulunamadı.',
            ]);
        }

        return response()->json([
            'hata' => false,
            'bordro' => [
                'ay' => Carbon::create($yil, $ay, 1)->translatedFormat('F Y'),
                'brut_maas' => (float)($puantaj->brut_maas ?? 0),
                'net_maas' => (float)($puantaj->net_maas ?? 0),
                'fazla_mesai_50' => (float)($puantaj->fazla_mesai_50_ucret ?? 0),
                'fazla_mesai_100' => (float)($puantaj->fazla_mesai_100_ucret ?? 0),
                'sgk_kesintisi' => (float)($puantaj->sgk_iscipay ?? 0),
                'gelir_vergisi' => (float)($puantaj->gelir_vergisi ?? 0),
                'avans' => (float)($puantaj->avans ?? 0),
                'kesinti' => (float)($puantaj->kesinti ?? 0),
                'calisma_gun' => (int)($puantaj->calisma_gunu ?? 0),
                'devamsizlik_gun' => (int)($puantaj->devamsizlik_gunu ?? 0),
            ],
        ]);
    }
}
