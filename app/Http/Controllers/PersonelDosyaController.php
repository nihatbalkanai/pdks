<?php

namespace App\Http\Controllers;

use App\Models\PersonelDosya;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PersonelDosyaController extends Controller
{
    /**
     * Dosya yükle
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'personel_id' => 'required|integer',
                'dosya'       => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            ]);

            $firma_id = Auth::user()->firma_id ?? 1;
            $file = $request->file('dosya');

            $path = $file->store("personel-dosyalar/{$request->personel_id}", 'public');

            $dosya = PersonelDosya::create([
                'uuid'        => (string) \Illuminate\Support\Str::uuid(),
                'firma_id'    => $firma_id,
                'personel_id' => $request->personel_id,
                'dosya_adi'   => $file->getClientOriginalName(),
                'dosya_yolu'  => $path,
                'dosya_tipi'  => $file->getClientOriginalExtension(),
                'boyut'       => $file->getSize(),
            ]);

            return response()->json(['success' => true, 'dosya' => $dosya, 'message' => 'Dosya yüklendi.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz dosya formatı veya boyutu.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            if (isset($path)) {
                Storage::disk('public')->delete($path); // Temizle
            }
            return response()->json([
                'success' => false,
                'message' => 'Sunucu hatası: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dosya sil
     */
    public function destroy($id)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $dosya = PersonelDosya::where('firma_id', $firma_id)->findOrFail($id);

        // Dosyayı diskten sil
        Storage::disk('public')->delete($dosya->dosya_yolu);
        $dosya->delete();

        return response()->json(['success' => true, 'message' => 'Dosya silindi.']);
    }

    /**
     * Dosya indir
     */
    public function download($id)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $dosya = PersonelDosya::where('firma_id', $firma_id)->findOrFail($id);

        return Storage::disk('public')->download($dosya->dosya_yolu, $dosya->dosya_adi);
    }
}
