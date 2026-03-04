<?php

namespace App\Http\Controllers;

use App\Models\PersonelPrimKazanc;
use App\Models\Personel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EkKazancController extends Controller
{
    public function index(Request $request)
    {
        $query = PersonelPrimKazanc::with('personel')
            ->orderBy('tarih', 'desc');

        if ($request->bordro_alani) {
            $query->where('bordro_alani', $request->bordro_alani);
        }
        if ($request->tarih) {
            $query->where('tarih', $request->tarih);
        }
        if ($request->personel_id) {
            $query->where('personel_id', $request->personel_id);
        }

        $kazanclar = $query->get()->map(function ($k) {
            return [
                'id' => $k->id,
                'kart_no' => $k->personel?->kart_no ?? '-',
                'isim' => $k->personel?->ad ?? '',
                'soyad' => $k->personel?->soyad ?? '',
                'maas' => $k->personel?->aylik_ucret ?? 0,
                'tarih' => $k->tarih,
                'tutar' => $k->tutar,
                'aciklama' => $k->aciklama,
                'bordro_alani' => $k->bordro_alani,
                'personel_id' => $k->personel_id,
            ];
        });

        $bordroAlanlari = PersonelPrimKazanc::distinct()->pluck('bordro_alani')->filter();
        $personeller = Personel::withoutGlobalScopes()->select('id', 'kart_no', 'ad', 'soyad')->get();

        return Inertia::render('EkKazanclar/Index', [
            'kazanclar' => $kazanclar,
            'bordroAlanlari' => $bordroAlanlari,
            'personeller' => $personeller,
            'filtreler' => $request->only(['bordro_alani', 'tarih', 'personel_id']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'personel_id' => 'required|exists:personeller,id',
            'tarih' => 'required|date',
            'tutar' => 'required|numeric',
            'aciklama' => 'nullable|string',
            'bordro_alani' => 'required|string',
        ]);

        $personel = Personel::withoutGlobalScopes()->findOrFail($validated['personel_id']);
        $validated['firma_id'] = $personel->firma_id;

        PersonelPrimKazanc::create($validated);

        return back()->with('success', 'Ek kazanç eklendi.');
    }

    public function destroy($id)
    {
        PersonelPrimKazanc::findOrFail($id)->delete();
        return back()->with('success', 'Kayıt silindi.');
    }
}
