<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personel;
use App\Models\Firma;
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
        if ($personel->durum !== 'aktif') {
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
                'ad' => $personel->ad,
                'soyad' => $personel->soyad,
                'kart_no' => $personel->kart_no,
                'departman' => $personel->departman,
                'pozisyon' => $personel->gorev,
            ],
            'firma' => [
                'id' => $firma->id,
                'firma_adi' => $firma->firma_adi,
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

        // GPS doğrulama
        if ($firma->gps_zorunlu && $yontem === 'gps') {
            if (!$request->enlem || !$request->boylam) {
                return response()->json(['hata' => true, 'mesaj' => 'Konum bilgisi zorunludur.'], 422);
            }

            // Mesafe hesapla (Haversine formülü)
            if ($firma->lokasyon_enlem && $firma->lokasyon_boylam) {
                $mesafe = $this->mesafeHesapla(
                    $request->enlem, $request->boylam,
                    $firma->lokasyon_enlem, $firma->lokasyon_boylam
                );

                if ($mesafe > $firma->geofence_yaricap) {
                    return response()->json([
                        'hata' => true,
                        'mesaj' => "Çalışma alanı dışındasınız. Mesafe: {$mesafe}m (İzin verilen: {$firma->geofence_yaricap}m)",
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

        $tipLabel = $request->tip === 'giris' ? 'Giriş' : 'Çıkış';
        return response()->json([
            'hata' => false,
            'mesaj' => "{$tipLabel} başarıyla kaydedildi.",
            'zaman' => now()->format('H:i:s'),
            'mesafe' => $mesafe,
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
        $R = 6371000; // Dünya yarıçapı (metre)
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return (int) round($R * $c);
    }
}
