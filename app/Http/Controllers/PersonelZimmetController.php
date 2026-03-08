<?php

namespace App\Http\Controllers;

use App\Models\PersonelZimmet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonelZimmetController extends Controller
{
    /**
     * Yeni zimmet ekle
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'personel_id'     => 'required|integer',
            'kategori'        => 'required|string|max:255',
            'bolum_adi'       => 'nullable|string|max:255',
            'aciklama'        => 'required|string|max:255',
            'verilis_tarihi'  => 'required|date|after:2000-01-01|before:2100-01-01',
            'iade_tarihi'     => 'nullable|date|after_or_equal:verilis_tarihi',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;

        $zimmet = PersonelZimmet::create([
            'firma_id'        => $firma_id,
            'personel_id'     => $validated['personel_id'],
            'kategori'        => $validated['kategori'],
            'bolum_adi'       => $validated['bolum_adi'] ?? null,
            'aciklama'        => $validated['aciklama'],
            'verilis_tarihi'  => $validated['verilis_tarihi'],
            'iade_tarihi'     => $validated['iade_tarihi'] ?? null,
        ]);

        return response()->json(['success' => true, 'zimmet' => $zimmet, 'message' => 'Zimmet kaydedildi.']);
    }

    /**
     * Zimmet güncelle
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'kategori'        => 'required|string|max:255',
            'bolum_adi'       => 'nullable|string|max:255',
            'aciklama'        => 'required|string|max:255',
            'verilis_tarihi'  => 'required|date|after:2000-01-01|before:2100-01-01',
            'iade_tarihi'     => 'nullable|date',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $zimmet = PersonelZimmet::where('firma_id', $firma_id)->findOrFail($id);
        $zimmet->update($validated);

        return response()->json(['success' => true, 'zimmet' => $zimmet, 'message' => 'Zimmet güncellendi.']);
    }

    /**
     * Zimmet sil
     */
    public function destroy($id)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $zimmet = PersonelZimmet::where('firma_id', $firma_id)->findOrFail($id);
        $zimmet->delete();

        return response()->json(['success' => true, 'message' => 'Zimmet silindi.']);
    }

    /**
     * Zimmet iade et (iade tarihini bugün olarak ata)
     */
    public function iade($id)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $zimmet = PersonelZimmet::where('firma_id', $firma_id)->findOrFail($id);
        $zimmet->update(['iade_tarihi' => now()->format('Y-m-d')]);

        return response()->json(['success' => true, 'zimmet' => $zimmet, 'message' => 'Zimmet iade edildi.']);
    }
}
