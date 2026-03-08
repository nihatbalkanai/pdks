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
            'tarih'      => 'required|date|after:2000-01-01|before:2100-01-01',
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
            'baslangic'  => 'required|date|after:2000-01-01|before:2100-01-01',
            'bitis'      => 'required|date|before:2100-01-01|after_or_equal:baslangic',
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
            'vardiya_id' => 'nullable|integer',
            'sablon'     => 'nullable|string|in:standart,cumartesi_calisma,market,restoran,surekli',
            'tatil_gunleri' => 'nullable|array', // Özel: [1,2,3,4,5,6,7] iso day numbers
        ]);

        $yil      = $validated['yil'];
        $firma_id = Auth::user()->firma_id ?? 1;
        $grup     = CalismaGrubu::where('firma_id', $firma_id)->findOrFail($grupId);
        $sablon   = $validated['sablon'] ?? 'standart';

        // Şablon bazında tatil günlerini belirle (ISO: 1=Pzt, 7=Paz)
        $tatilGunleri = match ($sablon) {
            'standart'          => [6, 7],       // Cmt+Paz tatil (İş Kanunu)
            'cumartesi_calisma' => [7],           // Sadece Pazar tatil
            'market'            => [1],           // Sadece Pazartesi tatil
            'restoran'          => [2],           // Sadece Salı tatil
            'surekli'           => [],            // Hiç tatil yok (7/7)
            default             => [6, 7],
        };

        // Özel tatil günleri gönderildiyse onları kullan
        if (!empty($validated['tatil_gunleri'])) {
            $tatilGunleri = $validated['tatil_gunleri'];
        }

        // O yıla ait resmi tatiller
        $resmiTatiller = \App\Models\ResmiTatil::where('firma_id', $firma_id)
            ->where('yil', $yil)
            ->get()
            ->keyBy(function($item) {
                return Carbon::parse($item->tarih)->format('Y-m-d');
            });

        if ($resmiTatiller->isEmpty()) {
            return response()->json([
                'error' => "Sistemde {$yil} yılına ait resmi tatil tanımı bulunmamaktadır. Lütfen önce 'Tatil ve İzin Tanımlamaları' menüsünden {$yil} yılı tatillerini oluşturun."
            ], 422);
        }

        $baslangic   = Carbon::create($yil, 1, 1);
        $bitis       = Carbon::create($yil, 12, 31);
        $current     = $baslangic->copy();
        $olusturulan = 0;

        while ($current->lte($bitis)) {
            $tarihStr = $current->format('Y-m-d');
            $isoDay   = $current->dayOfWeekIso; // 1=Pzt, 7=Paz

            if ($resmiTatiller->has($tarihStr)) {
                // Resmi tatil
                CalismaPlan::updateOrCreate(
                    ['calisma_grubu_id' => $grup->id, 'tarih' => $tarihStr],
                    [
                        'firma_id'   => $firma_id,
                        'vardiya_id' => null,
                        'tur'        => 'resmi_tatil',
                    ]
                );
            } elseif (in_array($isoDay, $tatilGunleri)) {
                // Şablona göre tatil günü
                CalismaPlan::updateOrCreate(
                    ['calisma_grubu_id' => $grup->id, 'tarih' => $tarihStr],
                    [
                        'firma_id'   => $firma_id,
                        'vardiya_id' => null,
                        'tur'        => 'tatil',
                    ]
                );
            } else {
                // İş günü
                CalismaPlan::updateOrCreate(
                    ['calisma_grubu_id' => $grup->id, 'tarih' => $tarihStr],
                    [
                        'firma_id'   => $firma_id,
                        'vardiya_id' => $validated['vardiya_id'] ?? null,
                        'tur'        => 'is_gunu',
                    ]
                );
            }

            $current->addDay();
            $olusturulan++;
        }

        $sablonAdi = match ($sablon) {
            'standart'          => 'Standart (İş Kanunu: Pzt-Cuma)',
            'cumartesi_calisma' => 'Cumartesi Çalışma (Pzt-Cmt)',
            'market'            => 'Market/Perakende (Sal-Paz, Pzt tatil)',
            'restoran'          => 'Restoran (Çar-Pzt, Salı tatil)',
            'surekli'           => '7/7 Sürekli Çalışma',
            default             => 'Özel',
        };

        return response()->json([
            'success' => true,
            'message' => "{$yil} yılı planı '{$sablonAdi}' şablonuyla oluşturuldu.",
            'count'   => $olusturulan
        ]);
    }
}
