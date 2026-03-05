<?php

namespace App\Http\Controllers;

use App\Models\AylikPuantajParametresi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AylikPuantajParametresiController extends Controller
{
    public function index()
    {
        $firma_id = Auth::user()->firma_id ?? 1;

        $parametreler = AylikPuantajParametresi::where('firma_id', $firma_id)
            ->orderBy('hesap_parametresi_adi')
            ->get();

        return Inertia::render('HesapParam/AylikPuantaj', [
            'parametreler' => $parametreler,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hesap_parametresi_adi'            => 'required|string|max:100',
            'aylik_calisma_saati'              => 'required|numeric',
            'haftalik_calisma_saati'           => 'required|numeric',
            'gunluk_calisma_saati'             => 'required|numeric',
            'eksik_gun_kesintisi_yapilacak_mi' => 'required|boolean',
            'fazla_mesai_carpani'              => 'required|numeric',
            'tatil_mesai_carpani'              => 'required|numeric',
            'resmi_tatil_mesai_carpani'        => 'required|numeric',
            'durum'                            => 'boolean',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $validated['firma_id'] = $firma_id;

        $parametre = AylikPuantajParametresi::create($validated);

        return response()->json(['success' => true, 'message' => 'Parametre eklendi.']);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'hesap_parametresi_adi'            => 'required|string|max:100',
            'aylik_calisma_saati'              => 'required|numeric',
            'haftalik_calisma_saati'           => 'required|numeric',
            'gunluk_calisma_saati'             => 'required|numeric',
            'eksik_gun_kesintisi_yapilacak_mi' => 'required|boolean',
            'fazla_mesai_carpani'              => 'required|numeric',
            'tatil_mesai_carpani'              => 'required|numeric',
            'resmi_tatil_mesai_carpani'        => 'required|numeric',
            'durum'                            => 'boolean',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        
        $parametre = AylikPuantajParametresi::where('firma_id', $firma_id)->findOrFail($id);
        $parametre->update($validated);

        return response()->json(['success' => true, 'message' => 'Parametre güncellendi.']);
    }

    public function destroy($id)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        
        $parametre = AylikPuantajParametresi::where('firma_id', $firma_id)->findOrFail($id);
        $parametre->delete();

        return response()->json(['success' => true, 'message' => 'Parametre silindi.']);
    }
}
