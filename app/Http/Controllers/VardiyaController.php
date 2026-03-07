<?php

namespace App\Http\Controllers;

use App\Models\Vardiya;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VardiyaController extends Controller
{
    public function index()
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $vardiyalar = Vardiya::where('firma_id', $firma_id)->orderBy('ad')->get();

        $gunlukParams = \App\Models\GunlukPuantajParametresi::withoutGlobalScopes()
            ->where('firma_id', $firma_id)
            ->where('durum', true)
            ->get(['id', 'ad', 'mola_suresi']);

        return Inertia::render('HesapParam/Vardiyalar', [
            'vardiyalar' => $vardiyalar,
            'gunlukPuantajParametreleri' => $gunlukParams,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ad'              => 'required|string|max:100',
            'baslangic_saati' => 'nullable|date_format:H:i',
            'bitis_saati'     => 'nullable|date_format:H:i',
            'renk'            => 'nullable|string|max:20',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;

        // Toplam süreyi hesapla (dakika)
        if (!empty($validated['baslangic_saati']) && !empty($validated['bitis_saati'])) {
            $bas = \Carbon\Carbon::createFromFormat('H:i', $validated['baslangic_saati']);
            $bit = \Carbon\Carbon::createFromFormat('H:i', $validated['bitis_saati']);
            $validated['toplam_sure'] = abs($bit->diffInMinutes($bas));
        }

        $vardiya = Vardiya::create(array_merge($validated, ['firma_id' => $firma_id]));

        return response()->json(['success' => true, 'vardiya' => $vardiya]);
    }

    public function update(Request $request, Vardiya $vardiya)
    {
        $validated = $request->validate([
            'ad'              => 'required|string|max:100',
            'baslangic_saati' => 'nullable|date_format:H:i',
            'bitis_saati'     => 'nullable|date_format:H:i',
            'renk'            => 'nullable|string|max:20',
        ]);

        if (!empty($validated['baslangic_saati']) && !empty($validated['bitis_saati'])) {
            $bas = \Carbon\Carbon::createFromFormat('H:i', $validated['baslangic_saati']);
            $bit = \Carbon\Carbon::createFromFormat('H:i', $validated['bitis_saati']);
            $validated['toplam_sure'] = abs($bit->diffInMinutes($bas));
        }

        $vardiya->update($validated);
        $vardiya->refresh();

        return response()->json(['success' => true, 'vardiya' => $vardiya]);
    }

    public function destroy(Vardiya $vardiya)
    {
        $vardiya->delete();
        return response()->json(['success' => true]);
    }
}
