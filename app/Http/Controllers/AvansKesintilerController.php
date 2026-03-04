<?php

namespace App\Http\Controllers;

use App\Models\PersonelAvansKesinti;
use App\Models\Personel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AvansKesintilerController extends Controller
{
    public function index(Request $request)
    {
        $query = PersonelAvansKesinti::with('personel')
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

        $kesintiler = $query->get()->map(function ($k) {
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

        $bordroAlanlari = PersonelAvansKesinti::distinct()->pluck('bordro_alani')->filter();
        $personeller = Personel::withoutGlobalScopes()->select('id', 'kart_no', 'ad', 'soyad')->get();

        return Inertia::render('AvansKesintiler/Index', [
            'kesintiler' => $kesintiler,
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

        PersonelAvansKesinti::create($validated);

        return back()->with('success', 'Avans/kesinti eklendi.');
    }

    public function destroy($id)
    {
        PersonelAvansKesinti::findOrFail($id)->delete();
        return back()->with('success', 'Kayıt silindi.');
    }
}
