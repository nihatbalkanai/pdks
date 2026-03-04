<?php

namespace App\Http\Controllers;

use App\Models\PdksCihazi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PdksCihaziController extends Controller
{
    /**
     * Cihazlar listesini görüntüle
     */
    public function index(): Response
    {
        $cihazlar = PdksCihazi::orderBy('created_at', 'desc')->get()->map(function ($cihaz) {
            // Aktif/Pasif Durum Kontrolü (Örn: Son aktivite 5 dakikadan yeniyse Aktif)
            $isAktif = false;
            if ($cihaz->son_aktivite_tarihi) {
                // Carbon ile fark kontrolü
                $sonAktivite = Carbon::parse($cihaz->son_aktivite_tarihi);
                $isAktif = $sonAktivite->diffInMinutes(now()) <= 5;
            }

            return [
                'id' => $cihaz->id,
                'uuid' => $cihaz->uuid,
                'seri_no' => $cihaz->seri_no,
                'cihaz_modeli' => $cihaz->cihaz_modeli,
                'son_aktivite_tarihi' => $cihaz->son_aktivite_tarihi,
                'is_aktif' => $isAktif,
            ];
        });

        return Inertia::render('Cihaz/Index', [
            'cihazlar' => $cihazlar
        ]);
    }

    /**
     * Yeni Cihaz ekle
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'seri_no' => 'required|string|max:255',
            'cihaz_modeli' => 'nullable|string|max:255',
        ]);

        PdksCihazi::create(array_merge($validated, [
            'firma_id' => Auth::user()->firma_id ?? 1
        ]));

        return redirect()->route('cihazlar.index')->with('success', 'Cihaz başarıyla eklendi.');
    }

    /**
     * Cihazı güncelle
     */
    public function update(Request $request, PdksCihazi $cihaz)
    {
        $validated = $request->validate([
            'seri_no' => 'required|string|max:255',
            'cihaz_modeli' => 'nullable|string|max:255',
        ]);

        $cihaz->update($validated);

        return redirect()->route('cihazlar.index')->with('success', 'Cihaz başarıyla güncellendi.');
    }

    /**
     * Cihazı sil
     */
    public function destroy(PdksCihazi $cihaz)
    {
        $cihaz->delete();

        return redirect()->route('cihazlar.index')->with('success', 'Cihaz başarıyla kaldırıldı.');
    }
}
