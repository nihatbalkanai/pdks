<?php

namespace App\Http\Controllers;

use App\Models\PersonelAvansKesinti;
use App\Models\BordroAlani;
use App\Models\Personel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
                'taksit_grup_id' => $k->taksit_grup_id,
                'taksit_no' => $k->taksit_no,
                'toplam_taksit' => $k->toplam_taksit,
                'toplam_tutar' => $k->toplam_tutar,
            ];
        });

        // Bordro alanlarını DB'den çek
        $bordroAlanlari = BordroAlani::orderBy('kod')->get(['id', 'kod', 'aciklama', 'bordro_tipi']);
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
            'taksitli' => 'nullable|boolean',
            'taksit_sayisi' => 'nullable|integer|min:2|max:60',
        ]);

        $personel = Personel::withoutGlobalScopes()->findOrFail($validated['personel_id']);
        $firmaId = $personel->firma_id;

        // Taksitli kayıt
        if ($request->boolean('taksitli') && !empty($validated['taksit_sayisi']) && $validated['taksit_sayisi'] > 1) {
            $taksitSayisi = (int) $validated['taksit_sayisi'];
            $toplamTutar = (float) $validated['tutar'];
            $taksitTutar = round($toplamTutar / $taksitSayisi, 2);
            $grupId = (string) Str::uuid();
            $baslangicTarihi = \Carbon\Carbon::parse($validated['tarih']);
            $olusturulanlar = [];

            for ($i = 1; $i <= $taksitSayisi; $i++) {
                $tutar = ($i === $taksitSayisi) ? round($toplamTutar - ($taksitTutar * ($taksitSayisi - 1)), 2) : $taksitTutar;

                $kayit = PersonelAvansKesinti::create([
                    'firma_id' => $firmaId,
                    'personel_id' => $validated['personel_id'],
                    'tarih' => $baslangicTarihi->copy()->addMonths($i - 1)->format('Y-m-d'),
                    'tutar' => $tutar,
                    'aciklama' => ($validated['aciklama'] ?? '') . " (Taksit {$i}/{$taksitSayisi})",
                    'bordro_alani' => $validated['bordro_alani'],
                    'taksit_grup_id' => $grupId,
                    'taksit_no' => $i,
                    'toplam_taksit' => $taksitSayisi,
                    'toplam_tutar' => $toplamTutar,
                ]);

                $olusturulanlar[] = [
                    'id' => $kayit->id,
                    'kart_no' => $personel->kart_no ?? '-',
                    'isim' => $personel->ad ?? '',
                    'soyad' => $personel->soyad ?? '',
                    'maas' => $personel->aylik_ucret ?? 0,
                    'tarih' => $kayit->tarih,
                    'tutar' => $kayit->tutar,
                    'aciklama' => $kayit->aciklama,
                    'bordro_alani' => $kayit->bordro_alani,
                    'personel_id' => $kayit->personel_id,
                    'taksit_grup_id' => $kayit->taksit_grup_id,
                    'taksit_no' => $kayit->taksit_no,
                    'toplam_taksit' => $kayit->toplam_taksit,
                    'toplam_tutar' => $kayit->toplam_tutar,
                ];
            }

            return response()->json(['success' => true, 'items' => $olusturulanlar, 'taksitli' => true]);
        }

        // Tek seferlik kayıt
        $kayit = PersonelAvansKesinti::create([
            'firma_id' => $firmaId,
            'personel_id' => $validated['personel_id'],
            'tarih' => $validated['tarih'],
            'tutar' => $validated['tutar'],
            'aciklama' => $validated['aciklama'],
            'bordro_alani' => $validated['bordro_alani'],
        ]);
        $personel = $kayit->personel;

        return response()->json(['success' => true, 'item' => [
            'id' => $kayit->id, 'kart_no' => $personel?->kart_no ?? '-',
            'isim' => $personel?->ad ?? '', 'soyad' => $personel?->soyad ?? '',
            'maas' => $personel?->aylik_ucret ?? 0, 'tarih' => $kayit->tarih,
            'tutar' => $kayit->tutar, 'aciklama' => $kayit->aciklama,
            'bordro_alani' => $kayit->bordro_alani, 'personel_id' => $kayit->personel_id,
            'taksit_grup_id' => null, 'taksit_no' => null,
            'toplam_taksit' => null, 'toplam_tutar' => null,
        ]]);
    }

    public function destroy($id)
    {
        PersonelAvansKesinti::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }


    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'personel_id' => 'required|exists:personeller,id',
            'tarih' => 'required|date',
            'tutar' => 'required|numeric',
            'aciklama' => 'nullable|string',
            'bordro_alani' => 'required|string',
        ]);

        $firmaId = Auth::user()->firma_id ?? 1;
        $kayit = PersonelAvansKesinti::where('firma_id', $firmaId)->findOrFail($id);
        
        // Sadece temel bilgileri güncellemeye izin ver (taksit yapısını bozmamak için)
        // Eğer taksitli bir işlem güncelleniyorsa tüm grubun tutarını vb güncelletmek daha karmaşık. 
        // Şimdilik sadece seçili satırın bilgilerini güncelliyoruz.
        $kayit->update($validated);
        
        $personel = Personel::withoutGlobalScopes()->find($kayit->personel_id);

        $item = [
            'id' => $kayit->id,
            'kart_no' => $personel->kart_no ?? '-',
            'isim' => $personel->ad ?? '',
            'soyad' => $personel->soyad ?? '',
            'maas' => $personel->aylik_ucret ?? 0,
            'tarih' => $kayit->tarih,
            'tutar' => $kayit->tutar,
            'aciklama' => $kayit->aciklama,
            'bordro_alani' => $kayit->bordro_alani,
            'personel_id' => $kayit->personel_id,
            'taksit_grup_id' => $kayit->taksit_grup_id,
            'taksit_no' => $kayit->taksit_no,
            'toplam_taksit' => $kayit->toplam_taksit,
            'toplam_tutar' => $kayit->toplam_tutar,
        ];

        return response()->json(['item' => $item]);
    }

    public function destroyGrup($grupId)
    {
        $silinen = PersonelAvansKesinti::where('taksit_grup_id', $grupId)->delete();
        return response()->json(['success' => true, 'silinen_adet' => $silinen]);
    }
}
