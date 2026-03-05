<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubeController extends Controller
{
    public function index()
    {
        \Illuminate\Support\Facades\Gate::authorize('firma_tam_yetki');
        
        $subeler = \App\Models\Sube::withCount('personeller', 'cihazlar')->paginate(20);
        
        return \Inertia\Inertia::render('Subeler/Index', [
            'subeler' => $subeler
        ]);
    }

    public function store(\Illuminate\Http\Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('firma_tam_yetki');

        $validated = $request->validate([
            'sube_adi' => 'required|string|max:255',
            'lokasyon' => 'nullable|string|max:255',
        ]);

        $validated['firma_id'] = \Illuminate\Support\Facades\Auth::user()->firma_id;

        \App\Models\Sube::create($validated);

        return response()->json(['success' => true, 'message' => 'Şube başarıyla eklendi.']);
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Sube $sube)
    {
        \Illuminate\Support\Facades\Gate::authorize('firma_tam_yetki');

        $validated = $request->validate([
            'sube_adi' => 'required|string|max:255',
            'lokasyon' => 'nullable|string|max:255',
            'durum' => 'required|boolean'
        ]);

        $sube->update($validated);

        return response()->json(['success' => true, 'message' => 'Şube güncellendi.']);
    }

    public function destroy(\App\Models\Sube $sube)
    {
        \Illuminate\Support\Facades\Gate::authorize('firma_tam_yetki');
        $sube->delete();
        return response()->json(['success' => true, 'message' => 'Şube silindi.']);
    }
}
