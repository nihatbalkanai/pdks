<?php

namespace App\Http\Controllers;

use App\Models\CalismaGrubu;
use App\Models\CalismaPlan;
use App\Models\Vardiya;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class CalismaPlaniController extends Controller
{
    public function index()
    {
        $firma_id = Auth::user()->firma_id ?? 1;

        $gruplar = CalismaGrubu::where('firma_id', $firma_id)
            ->orderBy('aciklama')
            ->get();

        $vardiyalar = Vardiya::where('firma_id', $firma_id)
            ->where('durum', true)
            ->orderBy('ad')
            ->get();

        return Inertia::render('HesapParam/CalismaPlani', [
            'gruplar'    => $gruplar,
            'vardiyalar' => $vardiyalar,
        ]);
    }

    /** Grubun seçili yıla ait planını döndür */
    public function planGetir(Request $request, $grupId)
    {
        $yil      = $request->input('yil', now()->year);
        $firma_id = Auth::user()->firma_id ?? 1;

        $grup = CalismaGrubu::where('firma_id', $firma_id)->findOrFail($grupId);

        $planlar = CalismaPlan::where('calisma_grubu_id', $grupId)
            ->whereYear('tarih', $yil)
            ->with('vardiya')
            ->orderBy('tarih')
            ->get()
            ->map(fn ($p) => [
                'id'         => $p->id,
                'tarih'      => $p->tarih->format('d.m.Y'),
                'tarih_raw'  => $p->tarih->format('Y-m-d'),
                'gun'        => $p->tarih->locale('tr')->dayName,
                'tur'        => $p->tur,
                'vardiya_id' => $p->vardiya_id,
                'vardiya_ad' => $p->vardiya?->ad,
            ]);

        return response()->json(['planlar' => $planlar, 'grup' => $grup]);
    }

    /** Tekli satır güncelle */
    public function satirGuncelle(Request $request, $grupId)
    {
        $validated = $request->validate([
            'tarih'      => 'required|date',
            'vardiya_id' => 'nullable|integer',
            'tur'        => 'nullable|in:is_gunu,tatil,resmi_tatil',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;

        CalismaPlan::updateOrCreate(
            ['calisma_grubu_id' => $grupId, 'tarih' => $validated['tarih']],
            [
                'firma_id'   => $firma_id,
                'vardiya_id' => $validated['vardiya_id'] ?? null,
                'tur'        => $validated['tur'] ?? 'is_gunu',
            ]
        );

        return response()->json(['success' => true]);
    }

    /** Toplu atama: tarih aralığı + gün filtresi + vardiya */
    public function topluAta(Request $request, $grupId)
    {
        $validated = $request->validate([
            'baslangic'  => 'required|date',
            'bitis'      => 'required|date|after_or_equal:baslangic',
            'vardiya_id' => 'nullable|integer',
            'tur'        => 'nullable|in:is_gunu,tatil,resmi_tatil',
            'gun_filtre' => 'nullable|in:hepsi,hafta_ici,hafta_sonu',
        ]);

        $firma_id  = Auth::user()->firma_id ?? 1;
        $baslangic = Carbon::parse($validated['baslangic']);
        $bitis     = Carbon::parse($validated['bitis']);
        $filtre    = $validated['gun_filtre'] ?? 'hepsi';

        $current = $baslangic->copy();
        $count   = 0;

        while ($current->lte($bitis)) {
            $guncelle = match ($filtre) {
                'hafta_ici'  => $current->isWeekday(),
                'hafta_sonu' => $current->isWeekend(),
                default      => true,
            };

            if ($guncelle) {
                CalismaPlan::updateOrCreate(
                    ['calisma_grubu_id' => $grupId, 'tarih' => $current->format('Y-m-d')],
                    [
                        'firma_id'   => $firma_id,
                        'vardiya_id' => $validated['vardiya_id'] ?? null,
                        'tur'        => $validated['tur'] ?? 'is_gunu',
                    ]
                );
                $count++;
            }

            $current->addDay();
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    /** Grubun tüm planını sil */
    public function planiTemizle($grupId)
    {
        CalismaPlan::where('calisma_grubu_id', $grupId)->delete();
        return response()->json(['success' => true]);
    }

    // ============================
    // ÇALIŞMA GRUBU CRUD
    // ============================

    public function grupStore(Request $request)
    {
        $validated = $request->validate([
            'aciklama'          => 'required|string|max:100',
            'hesap_parametresi' => 'nullable|string|max:100',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;

        // GlobalScope'u bypass ederek kaydet
        $grup = CalismaGrubu::withoutGlobalScopes()->create(array_merge($validated, [
            'firma_id' => $firma_id,
            'durum'    => true,
        ]));

        return response()->json(['success' => true, 'grup' => $grup]);
    }

    public function grupUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'aciklama'          => 'required|string|max:100',
            'hesap_parametresi' => 'nullable|string|max:100',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $grup     = CalismaGrubu::where('firma_id', $firma_id)->findOrFail($id);
        $grup->update($validated);
        $grup->refresh();

        return response()->json(['success' => true, 'grup' => $grup]);
    }

    public function grupDestroy($id)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $grup     = CalismaGrubu::where('firma_id', $firma_id)->findOrFail($id);
        $grup->planlar()->delete();
        $grup->delete();

        return response()->json(['success' => true]);
    }

    // ============================
    // ÇALIŞMA PLANI OLUŞTURMA (RESMİ TATİLLER İLE)
    // ============================

    /**
     * Verilen yıl için çalışma planını resmi tatiller tablosunu baz alarak oluşturur.
     */
    public function aiPlanOlustur(Request $request, $grupId)
    {
        $validated = $request->validate([
            'yil'        => 'required|integer|min:2020|max:2035',
            'vardiya_id' => 'nullable|integer', // Hafta içi varsayılan vardiya
        ]);

        $yil      = $validated['yil'];
        $firma_id = Auth::user()->firma_id ?? 1;
        $grup     = CalismaGrubu::where('firma_id', $firma_id)->findOrFail($grupId);

        // O yıla ait resmi tatiller tablosunu çek
        $resmiTatiller = ResmiTatil::where('firma_id', $firma_id)
            ->where('yil', $yil)
            ->get()
            ->keyBy(function($item) {
                return $item->tarih->format('Y-m-d');
            });

        if ($resmiTatiller->isEmpty()) {
            return response()->json([
                'error' => "Sistemde {$yil} yılına ait resmi tatil tanımı bulunmamaktadır. Lütfen önce 'Tatil ve İzin Tanımlamaları' menüsünden {$yil} yılı tatillerini oluşturun."
            ], 422);
        }

        // Önce o yılın tüm günlerini yükle/oluştur (hafta içi → is_gunu, hafta sonu → tatil)
        $baslangic    = Carbon::create($yil, 1, 1);
        $bitis        = Carbon::create($yil, 12, 31);
        $current      = $baslangic->copy();
        $olusturulan  = 0;

        while ($current->lte($bitis)) {
            $tarihStr = $current->format('Y-m-d');
            
            // Eğer bu tarih resmi_tatiller tablosunda varsa
            if ($resmiTatiller->has($tarihStr)) {
                $tatilModel = $resmiTatiller->get($tarihStr);
                $tur = $tatilModel->tur ?? 'resmi_tatil'; // Default to 'resmi_tatil' if 'tur' is null
                
                CalismaPlan::updateOrCreate(
                    ['calisma_grubu_id' => $grup->id, 'tarih' => $tarihStr],
                    [
                        'firma_id'   => $firma_id,
                        'vardiya_id' => null,
                        'tur'        => $tur,
                    ]
                );
            } else {
                // Yoksa normal hafta içi / hafta sonu mantığı
                CalismaPlan::updateOrCreate(
                    ['calisma_grubu_id' => $grup->id, 'tarih' => $tarihStr],
                    [
                        'firma_id'   => $firma_id,
                        'vardiya_id' => $current->isWeekday() ? ($validated['vardiya_id'] ?? null) : null,
                        'tur'        => $current->isWeekend() ? 'tatil' : 'is_gunu',
                    ]
                );
            }

            $current->addDay();
            $olusturulan++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$yil} yılı planı başarıyla oluşturuldu.",
            'count'   => $olusturulan
        ]);
    }
}
