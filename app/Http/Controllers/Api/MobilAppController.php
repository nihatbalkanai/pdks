<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Personel;
use App\Models\PdksGunlukOzet;

class MobilAppController extends Controller
{
    /**
     * Personel Mobil Giriş (Simülasyon/Test Amaçlı Sadece Sicil No yeterlidir)
     */
    public function login(Request $request)
    {
        $request->validate([
            'sicil_no' => 'required',
        ]);

        // Gerçek API'de şifre de aranır, burada personelin firmadan bağımsız public sicil no sorgusu ile token alması simüle edildi
        $personel = Personel::withoutGlobalScopes()->where('sicil_no', $request->sicil_no)->first();

        if (!$personel) {
            return response()->json(['hata' => true, 'mesaj' => 'Böyle bir sicil numarasına sahip personel bulunamadı.'], 404);
        }

        if ($personel->durum !== 'aktif') {
            return response()->json(['hata' => true, 'mesaj' => 'Hesabınız aktif değil.'], 403);
        }

        $token = $personel->createToken('mobil-app')->plainTextToken;

        return response()->json([
            'hata' => false,
            'mesaj' => 'Giriş Başarılı',
            'token' => $token,
            'personel' => $personel
        ]);
    }

    /**
     * Personel Profili
     */
    public function profil(Request $request)
    {
        return response()->json([
            'hata' => false,
            'personel' => $request->user()
        ]);
    }

    /**
     * Personel Hareketlerim (Log ve Özetler)
     */
    public function hareketlerim(Request $request)
    {
        $personel = $request->user();

        $hareketler = $personel ? PdksGunlukOzet::withoutGlobalScopes()
            ->where('personel_id', $personel->id)
            ->orderBy('tarih', 'desc')
            ->take(30)
            ->get() : []; // Son 30 günlük özet

        return response()->json([
            'hata' => false,
            'hareketler' => $hareketler
        ]);
    }
}
