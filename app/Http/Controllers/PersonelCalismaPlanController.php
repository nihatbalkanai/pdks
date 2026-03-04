<?php

namespace App\Http\Controllers;

use App\Models\PersonelCalismaPlan;
use App\Models\Personel;
use App\Models\Vardiya;
use App\Models\ResmiTatil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonelCalismaPlanController extends Controller
{
    public function index()
    {
        $firma_id = Auth::user()->firma_id ?? 1;

        $personeller = Personel::where('firma_id', $firma_id)
            ->where('durum', true)
            ->orderBy('ad')
            ->select('id', 'ad', 'soyad', 'sicil_no', 'departman')
            ->get();

        $vardiyalar = Vardiya::where('firma_id', $firma_id)->orderBy('ad')->get();

        return Inertia::render('HesapParam/PersonelCalismaPlan', [
            'personeller' => $personeller,
            'vardiyalar' => $vardiyalar,
        ]);
    }

    /**
     * Seçili personel + ay için planı getir
     */
    public function planGetir(Request $request, $personelId)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $yil = $request->get('yil', now()->year);
        $ay = $request->get('ay', now()->month);

        $baslangic = Carbon::create($yil, $ay, 1)->startOfMonth();
        $bitis = $baslangic->copy()->endOfMonth();

        $planlar = PersonelCalismaPlan::where('firma_id', $firma_id)
            ->where('personel_id', $personelId)
            ->whereBetween('tarih', [$baslangic, $bitis])
            ->with('vardiya')
            ->orderBy('tarih')
            ->get();

        return response()->json($planlar);
    }

    /**
     * Tekli gün güncelleme
     */
    public function gunGuncelle(Request $request, $personelId)
    {
        $validated = $request->validate([
            'tarih' => 'required|date',
            'vardiya_id' => 'nullable|integer',
            'tur' => 'required|string',
            'aciklama' => 'nullable|string|max:255',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;

        PersonelCalismaPlan::updateOrCreate(
            ['personel_id' => $personelId, 'tarih' => $validated['tarih']],
            [
                'firma_id' => $firma_id,
                'vardiya_id' => $validated['vardiya_id'],
                'tur' => $validated['tur'],
                'aciklama' => $validated['aciklama'] ?? null,
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Grup planını personele kopyala
     */
    public function grupPlanKopyala(Request $request, $personelId)
    {
        $validated = $request->validate([
            'yil' => 'required|integer',
            'ay' => 'required|integer|min:1|max:12',
            'calisma_grubu_id' => 'required|integer',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $baslangic = Carbon::create($validated['yil'], $validated['ay'], 1)->startOfMonth();
        $bitis = $baslangic->copy()->endOfMonth();

        $grupPlanlari = \App\Models\CalismaPlan::where('firma_id', $firma_id)
            ->where('calisma_grubu_id', $validated['calisma_grubu_id'])
            ->whereBetween('tarih', [$baslangic, $bitis])
            ->get();

        $count = 0;
        foreach ($grupPlanlari as $gp) {
            PersonelCalismaPlan::updateOrCreate(
                ['personel_id' => $personelId, 'tarih' => $gp->tarih->format('Y-m-d')],
                [
                    'firma_id' => $firma_id,
                    'vardiya_id' => $gp->vardiya_id,
                    'tur' => $gp->tur,
                ]
            );
            $count++;
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * Toplu atama (tarih aralığı + gün filtresi)
     */
    public function topluAta(Request $request, $personelId)
    {
        $validated = $request->validate([
            'baslangic' => 'required|date',
            'bitis' => 'required|date|after_or_equal:baslangic',
            'gunler' => 'required|array', // [1,2,3,4,5] pazartesi-cuma
            'vardiya_id' => 'nullable|integer',
            'tur' => 'required|string',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $current = Carbon::parse($validated['baslangic']);
        $bitis = Carbon::parse($validated['bitis']);
        $count = 0;

        while ($current->lte($bitis)) {
            if (in_array($current->dayOfWeekIso, $validated['gunler'])) {
                PersonelCalismaPlan::updateOrCreate(
                    ['personel_id' => $personelId, 'tarih' => $current->format('Y-m-d')],
                    [
                        'firma_id' => $firma_id,
                        'vardiya_id' => $validated['vardiya_id'],
                        'tur' => $validated['tur'],
                    ]
                );
                $count++;
            }
            $current->addDay();
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * Planı temizle (ay bazlı)
     */
    public function temizle(Request $request, $personelId)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $yil = $request->get('yil', now()->year);
        $ay = $request->get('ay', now()->month);

        $baslangic = Carbon::create($yil, $ay, 1)->startOfMonth();
        $bitis = $baslangic->copy()->endOfMonth();

        PersonelCalismaPlan::where('firma_id', $firma_id)
            ->where('personel_id', $personelId)
            ->whereBetween('tarih', [$baslangic, $bitis])
            ->delete();

        return response()->json(['success' => true]);
    }
}
