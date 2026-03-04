<?php

namespace App\Http\Controllers;

use App\Models\GunlukPuantajParametresi;
use App\Models\GunlukPuantajBordroAlani;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GunlukPuantajController extends Controller
{
    public function index()
    {
        $firma_id = Auth::user()->firma_id ?? 1;

        $parametreler = GunlukPuantajParametresi::where('firma_id', $firma_id)
            ->with('bordroAlanlari')
            ->orderBy('ad')
            ->get();

        return Inertia::render('HesapParam/GunlukPuantaj', [
            'parametreler' => $parametreler,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ad' => 'required|string|max:255',
            'gun_donum_saati' => 'nullable|string',
            'iceri_giris_saati' => 'nullable|string',
            'disari_cikis_saati' => 'nullable|string',
            'erken_gelme_toleransi' => 'nullable|string',
            'gec_gelme_toleransi' => 'nullable|string',
            'erken_cikma_toleransi' => 'nullable|string',
            'hesaplama_tipi' => 'nullable|string',
            'mola_suresi' => 'nullable|integer',
            'gec_gelme_cezasi' => 'nullable|integer',
            'erken_cikma_cezasi' => 'nullable|integer',
            'durum' => 'boolean',
        ]);

        $validated['firma_id'] = Auth::user()->firma_id ?? 1;

        $parametre = GunlukPuantajParametresi::create($validated);

        return redirect()->back()->with('success', 'Günlük puantaj parametresi eklendi.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'ad' => 'required|string|max:255',
            'gun_donum_saati' => 'nullable|string',
            'iceri_giris_saati' => 'nullable|string',
            'disari_cikis_saati' => 'nullable|string',
            'erken_gelme_toleransi' => 'nullable|string',
            'gec_gelme_toleransi' => 'nullable|string',
            'erken_cikma_toleransi' => 'nullable|string',
            'hesaplama_tipi' => 'nullable|string',
            'mola_suresi' => 'nullable|integer',
            'gec_gelme_cezasi' => 'nullable|integer',
            'erken_cikma_cezasi' => 'nullable|integer',
            'durum' => 'boolean',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $parametre = GunlukPuantajParametresi::where('firma_id', $firma_id)->findOrFail($id);
        $parametre->update($validated);

        return redirect()->back()->with('success', 'Parametre güncellendi.');
    }

    public function destroy($id)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $parametre = GunlukPuantajParametresi::where('firma_id', $firma_id)->findOrFail($id);
        $parametre->delete();

        return redirect()->back()->with('success', 'Parametre silindi.');
    }

    // --- BORDRO ALANLARI ---

    public function bordroStore(Request $request, $parametreId)
    {
        $validated = $request->validate([
            'bordro_alani' => 'required|string|max:255',
            'basla' => 'nullable|string',
            'bitis' => 'nullable|string',
            'min_sure' => 'nullable|string',
            'max_sure' => 'nullable|string',
            'ekle' => 'nullable|string',
            'carpan' => 'nullable|integer',
            'ucret' => 'nullable|string',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        // Parametrenin firmaya ait olduğunu doğrula
        GunlukPuantajParametresi::where('firma_id', $firma_id)->findOrFail($parametreId);

        $validated['gunluk_puantaj_id'] = $parametreId;
        GunlukPuantajBordroAlani::create($validated);

        return redirect()->back()->with('success', 'Bordro alanı eklendi.');
    }

    public function bordroUpdate(Request $request, $bordroId)
    {
        $validated = $request->validate([
            'bordro_alani' => 'required|string|max:255',
            'basla' => 'nullable|string',
            'bitis' => 'nullable|string',
            'min_sure' => 'nullable|string',
            'max_sure' => 'nullable|string',
            'ekle' => 'nullable|string',
            'carpan' => 'nullable|integer',
            'ucret' => 'nullable|string',
        ]);

        $bordro = GunlukPuantajBordroAlani::findOrFail($bordroId);
        $bordro->update($validated);

        return redirect()->back()->with('success', 'Bordro alanı güncellendi.');
    }

    public function bordroDestroy($bordroId)
    {
        $bordro = GunlukPuantajBordroAlani::findOrFail($bordroId);
        $bordro->delete();

        return redirect()->back()->with('success', 'Bordro alanı silindi.');
    }
}
