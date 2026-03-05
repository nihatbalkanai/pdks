<?php

namespace App\Http\Controllers;

use App\Models\PdksGunlukOzet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MesaiController extends Controller
{
    /**
     * Manuel mesai ekle
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'personel_id'           => 'required|integer',
            'tarih'                 => 'required|date',
            'ilk_giris'             => 'nullable|string',
            'son_cikis'             => 'nullable|string',
            'toplam_calisma_suresi' => 'required|integer|min:0',
            'fazla_mesai_dakika'    => 'required|integer|min:0',
            'durum'                 => 'nullable|string',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;

        $mesai = PdksGunlukOzet::updateOrCreate(
            [
                'personel_id' => $validated['personel_id'],
                'tarih'       => $validated['tarih'],
            ],
            [
                'firma_id'              => $firma_id,
                'ilk_giris'             => $validated['ilk_giris'] ? $validated['tarih'] . ' ' . $validated['ilk_giris'] . ':00' : null,
                'son_cikis'             => $validated['son_cikis'] ? $validated['tarih'] . ' ' . $validated['son_cikis'] . ':00' : null,
                'toplam_calisma_suresi' => $validated['toplam_calisma_suresi'],
                'fazla_mesai_dakika'    => $validated['fazla_mesai_dakika'],
                'durum'                 => $validated['durum'] ?? 'geldi',
            ]
        );

        return response()->json(['success' => true, 'mesai' => $mesai, 'message' => 'Mesai kaydedildi.']);
    }

    /**
     * Mesai güncelle
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'fazla_mesai_dakika'    => 'required|integer|min:0',
            'toplam_calisma_suresi' => 'nullable|integer|min:0',
            'durum'                 => 'nullable|string',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $mesai = PdksGunlukOzet::withoutGlobalScopes()->where('firma_id', $firma_id)->findOrFail($id);
        $mesai->update($validated);

        return response()->json(['success' => true, 'mesai' => $mesai, 'message' => 'Mesai güncellendi.']);
    }

    /**
     * Mesai sil (fazla mesaiyi sıfırla)
     */
    public function destroy($id)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $mesai = PdksGunlukOzet::withoutGlobalScopes()->where('firma_id', $firma_id)->findOrFail($id);
        $mesai->update(['fazla_mesai_dakika' => 0]);

        return response()->json(['success' => true, 'message' => 'Mesai silindi.']);
    }
}
