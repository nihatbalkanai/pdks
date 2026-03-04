<?php

namespace App\Http\Controllers;

use App\Models\BordroAlani;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BordroAlaniController extends Controller
{
    public function index()
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $alanlar = BordroAlani::where('firma_id', $firma_id)->orderBy('kod')->get();

        return Inertia::render('HesapParam/BordroAlanlari', [
            'alanlar' => $alanlar,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kod' => 'required|integer',
            'aciklama' => 'required|string|max:255',
            'gun' => 'boolean',
            'saat' => 'boolean',
            'ucret' => 'boolean',
            'bordro_tipi' => 'required|string',
        ]);

        $validated['firma_id'] = Auth::user()->firma_id ?? 1;
        BordroAlani::create($validated);

        return redirect()->back()->with('success', 'Bordro alanı eklendi.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'kod' => 'required|integer',
            'aciklama' => 'required|string|max:255',
            'gun' => 'boolean',
            'saat' => 'boolean',
            'ucret' => 'boolean',
            'bordro_tipi' => 'required|string',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $alan = BordroAlani::where('firma_id', $firma_id)->findOrFail($id);
        $alan->update($validated);

        return redirect()->back()->with('success', 'Bordro alanı güncellendi.');
    }

    public function destroy($id)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $alan = BordroAlani::where('firma_id', $firma_id)->findOrFail($id);
        $alan->delete();

        return redirect()->back()->with('success', 'Bordro alanı silindi.');
    }
}
