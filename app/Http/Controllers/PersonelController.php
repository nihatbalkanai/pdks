<?php

namespace App\Http\Controllers;

use App\Models\Personel;
use App\Models\TanimKodu;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class PersonelController extends Controller
{
    /**
     * Personel listesini sayfalama ve arama filtreleri ile getir
     */
    public function index(Request $request): Response
    {
        $arama = $request->input('arama');

        // Eloquent sorgusu
        $personeller = Personel::query()
            ->when($arama, function ($query, $arama) {
                // Arama filtresi: ad_soyad veya sicil_no'ya göre
                $query->where(function ($q) use ($arama) {
                    $q->where('ad_soyad', 'like', "%{$arama}%")
                      ->orWhere('sicil_no', 'like', "%{$arama}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString(); // Filtrelerin sayfalama sırasında kaybolmaması için

        $firma_id = Auth::user()->firma_id ?? 1;

        // Tanım kodlarını getir
        $tanimKodlari = TanimKodu::where('firma_id', $firma_id)
            ->where('durum', true)
            ->orderBy('kod')
            ->get()
            ->groupBy('tip')
            ->map(fn($items) => $items->map(fn($i) => ['kod' => $i->kod, 'aciklama' => $i->aciklama])->values());

        $aylikPuantajParametreleri = \App\Models\AylikPuantajParametresi::where('firma_id', $firma_id)
            ->where('durum', true)
            ->get();

        return Inertia::render('Personel/Index', [
            'personeller' => $personeller,
            'filtreler' => $request->only(['arama']),
            'tanimKodlari' => $tanimKodlari,
            'aylikPuantajParametreleri' => $aylikPuantajParametreleri,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kart_no' => 'nullable|string|max:100',
            'ad' => 'required|string|max:100',
            'soyad' => 'required|string|max:100',
            'ad_soyad' => 'nullable|string|max:255', // Optional alias
            'sicil_no' => 'required|string|max:100',
            'ssk_no' => 'nullable|string|max:100',
            'unvan' => 'nullable|string|max:100',
            'sirket' => 'nullable|string|max:255',
            'bolum' => 'nullable|string|max:255',
            'ozel_kod' => 'nullable|string|max:100',
            'departman' => 'nullable|string|max:255',
            'servis_kodu' => 'nullable|string|max:255',
            'hesap_gurubu' => 'nullable|string|max:255',
            'agi' => 'nullable|string|max:100',
            'aylik_ucret' => 'nullable|numeric',
            'gunluk_ucret' => 'nullable|numeric',
            'saat_1' => 'nullable|numeric',
            'saat_2' => 'nullable|numeric',
            'saat_3' => 'nullable|numeric',
            'giris_tarihi' => 'nullable|date',
            'cikis_tarihi' => 'nullable|date',
            'durum' => 'boolean',
            'notlar' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'telefon' => 'nullable|string|max:50',
            'gec_kalma_bildirimi' => 'boolean',
            'dogum_tarihi' => 'nullable|date',
        ]);

        if (empty($validated['ad_soyad'])) {
            $validated['ad_soyad'] = $validated['ad'] . ' ' . $validated['soyad'];
        }

        $firma_id = Auth::user()->firma_id ?? 1;

        $personel = Personel::create(array_merge($validated, [
            'firma_id' => $firma_id
        ]));

        return response()->json(['success' => true, 'personel' => $personel]);
    }

    public function show($id)
    {
        $personel = Personel::withoutGlobalScopes()->findOrFail($id);
        $personel->load(['izinler', 'avansKesintiler', 'primKazanclar', 'zimmetler', 'pdksKayitlari' => function($q) {
            $q->orderBy('created_at', 'desc')->limit(50);
        }]);

        // İlişki adlarını frontend ile uyumlu snake_case olarak döndür
        $data = $personel->toArray();
        $data['pdks_kayitlari'] = $personel->pdksKayitlari;
        $data['avans_kesintiler'] = $personel->avansKesintiler;
        $data['prim_kazanclar'] = $personel->primKazanclar;

        return response()->json(['personel' => $data]);
    }

    public function update(Request $request, Personel $personel)
    {
        $validated = $request->validate([
            'kart_no' => 'nullable|string|max:100',
            'ad' => 'required|string|max:100',
            'soyad' => 'required|string|max:100',
            'sicil_no' => 'required|string|max:100',
            'ssk_no' => 'nullable|string|max:100',
            'unvan' => 'nullable|string|max:100',
            'sirket' => 'nullable|string|max:255',
            'bolum' => 'nullable|string|max:255',
            'ozel_kod' => 'nullable|string|max:100',
            'departman' => 'nullable|string|max:255',
            'servis_kodu' => 'nullable|string|max:255',
            'hesap_gurubu' => 'nullable|string|max:255',
            'agi' => 'nullable|string|max:100',
            'aylik_ucret' => 'nullable|numeric',
            'gunluk_ucret' => 'nullable|numeric',
            'saat_1' => 'nullable|numeric',
            'saat_2' => 'nullable|numeric',
            'saat_3' => 'nullable|numeric',
            'giris_tarihi' => 'nullable|date',
            'cikis_tarihi' => 'nullable|date',
            'durum' => 'boolean',
            'notlar' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'telefon' => 'nullable|string|max:50',
            'gec_kalma_bildirimi' => 'boolean',
            'dogum_tarihi' => 'nullable|date',
        ]);

        $validated['ad_soyad'] = $validated['ad'] . ' ' . $validated['soyad'];

        $personel->update($validated);
        $personel->refresh();

        return response()->json(['success' => true, 'personel' => $personel]);
    }

    /**
     * Personel resim yükleme
     */
    public function resimYukle(Request $request, $id)
    {
        $request->validate([
            'resim' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $personel = Personel::withoutGlobalScopes()->findOrFail($id);

        // Eski resmi sil
        if ($personel->resim_yolu && file_exists(public_path($personel->resim_yolu))) {
            unlink(public_path($personel->resim_yolu));
        }

        $dosyaAdi = 'personel_' . $id . '_' . time() . '.' . $request->resim->extension();
        $request->resim->move(public_path('uploads/personel'), $dosyaAdi);

        $personel->resim_yolu = 'uploads/personel/' . $dosyaAdi;
        $personel->save();

        return response()->json(['success' => true, 'resim_yolu' => $personel->resim_yolu]);
    }

    /**
     * Personel silme işlemi
     */
    public function destroy(Personel $personel)
    {
        $personel->delete();

        return redirect()->route('personeller.index')->with('success', 'Personel başarıyla silindi.');
    }
}
