<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ServisController extends Controller
{
    public function index()
    {
        \Illuminate\Support\Facades\Gate::authorize('firma_tam_yetki');
        
        $servisler = \App\Models\Servis::withCount('personeller')->paginate(20);
        
        // Doluluk ve Son Hareketler Raporu için
        // Personel binişleri (Örn: Bugün)
        $bugunBinisler = \App\Models\ServisHareket::whereDate('binis_zamani', today())->count();
        
        return \Inertia\Inertia::render('Servisler/Index', [
            'servisler' => $servisler,
            'bugunBinisler' => $bugunBinisler
        ]);
    }

    public function store(\Illuminate\Http\Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('firma_tam_yetki');

        $validated = $request->validate([
            'plaka' => 'required|string|max:50',
            'sofor' => 'nullable|string|max:255',
            'guzergah' => 'nullable|string|max:255',
        ]);

        $validated['firma_id'] = \Illuminate\Support\Facades\Auth::user()->firma_id;

        \App\Models\Servis::create($validated);

        return redirect()->back()->with('success', 'Servis başarıyla eklendi.');
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Servis $servi)
    {
        // Route param is generally the model name lowercased, let's use $servis instead of $servi later, but Laravel might infer "servis". 
        // We'll use id directly to be safe.
        \Illuminate\Support\Facades\Gate::authorize('firma_tam_yetki');

        $validated = $request->validate([
            'plaka' => 'required|string|max:50',
            'sofor' => 'nullable|string|max:255',
            'guzergah' => 'nullable|string|max:255',
            'durum' => 'required|boolean'
        ]);

        $servi->update($validated);

        return redirect()->back()->with('success', 'Servis güncellendi.');
    }

    public function destroy(\App\Models\Servis $servi)
    {
        \Illuminate\Support\Facades\Gate::authorize('firma_tam_yetki');
        $servi->delete();
        return redirect()->back()->with('success', 'Servis silindi.');
    }
}
