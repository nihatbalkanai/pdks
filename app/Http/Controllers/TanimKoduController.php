<?php

namespace App\Http\Controllers;

use App\Models\TanimKodu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TanimKoduController extends Controller
{
    // Tip bilgileri
    private function tipBilgileri()
    {
        return [
            'sirket' => ['baslik' => 'Şirket Tanımlama', 'ikon' => 'building'],
            'departman' => ['baslik' => 'Departman Tanımlama', 'ikon' => 'department'],
            'bolum' => ['baslik' => 'Bölüm Tanımlama', 'ikon' => 'folder'],
            'odeme' => ['baslik' => 'Ödeme Tanımlama', 'ikon' => 'cash'],
            'servis' => ['baslik' => 'Servis Tanımlama', 'ikon' => 'truck'],
            'hesap_gurubu' => ['baslik' => 'Hesap Grubu Tanımlama', 'ikon' => 'tag'],
        ];
    }

    public function index($tip)
    {
        $tipBilgi = $this->tipBilgileri();
        if (!isset($tipBilgi[$tip])) abort(404);

        $firma_id = Auth::user()->firma_id ?? 1;

        $kodlar = TanimKodu::where('firma_id', $firma_id)
            ->where('tip', $tip)
            ->orderBy('kod')
            ->get();

        return Inertia::render('Tanim/TanimKodlari', [
            'tip' => $tip,
            'baslik' => $tipBilgi[$tip]['baslik'],
            'kodlar' => $kodlar,
        ]);
    }

    public function store(Request $request, $tip)
    {
        $validated = $request->validate([
            'kod' => 'required|string|max:50',
            'aciklama' => 'required|string|max:255',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;

        // Aynı tip+kod kontrolü
        $mevcut = TanimKodu::where('firma_id', $firma_id)
            ->where('tip', $tip)
            ->where('kod', $validated['kod'])
            ->first();

        if ($mevcut) {
            return response()->json(['success' => false, 'message' => 'Bu kod zaten tanımlı.'], 422);
        }

        $kayit = TanimKodu::create([
            'firma_id' => $firma_id,
            'tip' => $tip,
            'kod' => $validated['kod'],
            'aciklama' => $validated['aciklama'],
        ]);

        return response()->json(['success' => true, 'item' => $kayit]);
    }

    public function update(Request $request, $tip, $id)
    {
        $validated = $request->validate([
            'kod' => 'required|string|max:50',
            'aciklama' => 'required|string|max:255',
        ]);

        $kayit = TanimKodu::findOrFail($id);
        $kayit->update($validated);

        return response()->json(['success' => true, 'item' => $kayit->fresh()]);
    }

    public function destroy($tip, $id)
    {
        $kayit = TanimKodu::findOrFail($id);
        $kayit->delete();

        return response()->json(['success' => true]);
    }
}
