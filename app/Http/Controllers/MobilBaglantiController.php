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
            // Eski logoyu sil
            if ($firma->logo_yolu && Storage::disk('public')->exists($firma->logo_yolu)) {
                Storage::disk('public')->delete($firma->logo_yolu);
            }

            // Resize & compress (max 300x300, JPEG kalite 80)
            $file = $request->file('logo');
            $img = null;
            $ext = strtolower($file->getClientOriginalExtension());

            if (in_array($ext, ['jpg', 'jpeg'])) $img = @imagecreatefromjpeg($file->getPathname());
            elseif ($ext === 'png') $img = @imagecreatefrompng($file->getPathname());
            elseif ($ext === 'gif') $img = @imagecreatefromgif($file->getPathname());
            elseif ($ext === 'webp') $img = @imagecreatefromwebp($file->getPathname());

            if ($img) {
                $w = imagesx($img);
                $h = imagesy($img);
                $maxDim = 300;

                if ($w > $maxDim || $h > $maxDim) {
                    $ratio = min($maxDim / $w, $maxDim / $h);
                    $newW = (int)($w * $ratio);
                    $newH = (int)($h * $ratio);
                    $resized = imagecreatetruecolor($newW, $newH);

                    // PNG/GIF şeffaflık desteği
                    if (in_array($ext, ['png', 'gif'])) {
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                        imagefilledrectangle($resized, 0, 0, $newW, $newH, $transparent);
                    }

                    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
                    imagedestroy($img);
                    $img = $resized;
                }

                // Dosya adı
                $filename = 'firmalar/logolar/' . Str::random(20);

                // PNG ise PNG olarak kaydet, diğerleri JPEG
                if (in_array($ext, ['png'])) {
                    $filename .= '.png';
                    $tmpPath = sys_get_temp_dir() . '/' . Str::random(10) . '.png';
                    imagepng($img, $tmpPath, 8); // compression level 8
                } else {
                    $filename .= '.jpg';
                    $tmpPath = sys_get_temp_dir() . '/' . Str::random(10) . '.jpg';
                    imagejpeg($img, $tmpPath, 80); // quality 80
                }
                imagedestroy($img);

                Storage::disk('public')->put($filename, file_get_contents($tmpPath));
                unlink($tmpPath);
                $v['logo_yolu'] = $filename;
            } else {
                // GD başarısızsa orijinal dosyayı kaydet
                $v['logo_yolu'] = $file->store('firmalar/logolar', 'public');
            }
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
