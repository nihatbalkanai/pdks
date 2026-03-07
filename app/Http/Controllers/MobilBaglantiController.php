<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\Firma;

class MobilBaglantiController extends Controller
{
    public function index()
    {
        $firma = Firma::find(Auth::user()->firma_id);

        // Firma kodu yoksa otomatik oluştur
        if (!$firma->firma_kodu) {
            $kod = strtoupper(Str::slug(substr($firma->firma_adi, 0, 5), '') . rand(100, 999));
            $firma->update(['firma_kodu' => $kod]);
        }

        $cihazlar = DB::table('mobil_cihazlar')
            ->leftJoin('personeller', 'mobil_cihazlar.personel_id', '=', 'personeller.id')
            ->where('mobil_cihazlar.firma_id', $firma->id)
            ->select('mobil_cihazlar.*', DB::raw("CONCAT(personeller.ad, ' ', personeller.soyad) as personel_adi"))
            ->orderBy('mobil_cihazlar.son_giris', 'desc')
            ->get();

        $sonHareketler = DB::table('mobil_hareketler')
            ->leftJoin('personeller', 'mobil_hareketler.personel_id', '=', 'personeller.id')
            ->where('mobil_hareketler.firma_id', $firma->id)
            ->select('mobil_hareketler.*', DB::raw("CONCAT(personeller.ad, ' ', personeller.soyad) as personel_adi"))
            ->orderBy('mobil_hareketler.created_at', 'desc')
            ->limit(50)
            ->get();

        $qrKodlar = DB::table('qr_kod_oturumlari')
            ->where('firma_id', $firma->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Baglantilar/MobilBaglanti', [
            'firma' => $firma,
            'cihazlar' => $cihazlar,
            'sonHareketler' => $sonHareketler,
            'qrKodlar' => $qrKodlar,
        ]);
    }

    public function ayarlarKaydet(Request $request)
    {
        $v = $request->validate([
            'firma_kodu' => 'required|string|max:20',
            'lokasyon_enlem' => 'nullable|numeric',
            'lokasyon_boylam' => 'nullable|numeric',
            'geofence_yaricap' => 'required|integer|min:10|max:5000',
            'wifi_ssid' => 'nullable|string|max:255',
            'mobil_giris_aktif' => 'boolean',
            'qr_kod_aktif' => 'boolean',
            'gps_zorunlu' => 'boolean',
            'selfie_zorunlu' => 'boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $firma = Firma::find(Auth::user()->firma_id);

        if ($request->hasFile('logo')) {
            if ($firma->logo_yolu && Storage::disk('public')->exists($firma->logo_yolu)) {
                Storage::disk('public')->delete($firma->logo_yolu);
            }
            $v['logo_yolu'] = $request->file('logo')->store('firmalar/logolar', 'public');
        }

        unset($v['logo']); // We only store logo_yolu in DB, remove the file obj from array
        $firma->update($v);

        return back()->with('success', 'Mobil bağlantı ayarları güncellendi.');
    }

    public function cihazDurumDegistir(Request $request, $id)
    {
        $firma = Firma::find(Auth::user()->firma_id);
        DB::table('mobil_cihazlar')
            ->where('id', $id)
            ->where('firma_id', $firma->id)
            ->update(['aktif' => $request->boolean('aktif'), 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function cihazSil($id)
    {
        $firma = Firma::find(Auth::user()->firma_id);
        DB::table('mobil_cihazlar')
            ->where('id', $id)
            ->where('firma_id', $firma->id)
            ->delete();

        return response()->json(['success' => true]);
    }

    public function qrKodOlustur(Request $request)
    {
        $v = $request->validate([
            'konum_adi' => 'nullable|string|max:255',
            'gecerlilik_dakika' => 'required|integer|min:5|max:1440',
        ]);

        $firma = Firma::find(Auth::user()->firma_id);
        $kod = Str::random(32);

        DB::table('qr_kod_oturumlari')->insert([
            'firma_id' => $firma->id,
            'kod' => $kod,
            'konum_adi' => $v['konum_adi'] ?? 'Ana Giriş',
            'gecerlilik_bitis' => now()->addMinutes($v['gecerlilik_dakika']),
            'aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'kod' => $kod]);
    }
}
