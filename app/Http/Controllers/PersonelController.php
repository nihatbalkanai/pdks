<?php

namespace App\Http\Controllers;

use App\Models\Personel;
use App\Models\TanimKodu;
use App\Models\GunlukPuantajParametresi;
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

        $gunlukPuantajParametreleri = GunlukPuantajParametresi::where('firma_id', $firma_id)
            ->where('durum', true)
            ->orderBy('ad')
            ->get();

        return Inertia::render('Personel/Index', [
            'personeller' => $personeller,
            'filtreler' => $request->only(['arama']),
            'tanimKodlari' => $tanimKodlari,
            'aylikPuantajParametreleri' => $aylikPuantajParametreleri,
            'gunlukPuantajParametreleri' => $gunlukPuantajParametreleri,
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
            'puantaj_parametre_id' => 'nullable|exists:gunluk_puantaj_parametreleri,id',
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
        $personel->load(['izinler.izinTuru', 'avansKesintiler', 'primKazanclar', 'zimmetler', 'dosyalar', 'pdksKayitlari' => function($q) {
            $q->orderBy('created_at', 'desc')->limit(50);
        }]);

        // Onaylanmış izinleri al
        $onayliIzinler = $personel->izinler->where('durum', 'onaylandi');

        // İlişki adlarını frontend ile uyumlu snake_case olarak döndür
        $data = $personel->toArray();

        // PDKS kayıtlarını işle: izinli günleri tek satıra indir, saatleri boşalt
        $izinliTarihler = []; // Hangi tarihleri zaten "İZİNLİ" olarak ekledik
        $sonuc = collect();

        foreach ($personel->pdksKayitlari as $kayit) {
            $kayitTarihi = \Carbon\Carbon::parse($kayit->kayit_tarihi)->format('Y-m-d');

            // Bu tarih izinli mi kontrol et
            $izin = $onayliIzinler->first(function ($iz) use ($kayitTarihi) {
                $baslangic = $iz->tarih;
                $bitis = $iz->bitis_tarihi ?? $iz->tarih;
                return $kayitTarihi >= $baslangic && $kayitTarihi <= $bitis;
            });

            if ($izin) {
                // İzinli gün — bu tarihi daha önce ekledik mi?
                if (!in_array($kayitTarihi, $izinliTarihler)) {
                    $izinliTarihler[] = $kayitTarihi;
                    $sonuc->push([
                        'id'            => 'izin_' . $kayitTarihi,
                        'kayit_tarihi'  => $kayitTarihi . ' 00:00:00',
                        'islem_tipi'    => null,
                        'izinli_mi'     => true,
                        'izin_aciklama' => $izin->izinTuru?->ad ?? 'İzinli',
                    ]);
                }
                // İzinli gündeki diğer giriş/çıkış kayıtlarını atla
                continue;
            }

            // Normal gün
            $k = $kayit->toArray();
            $k['izinli_mi'] = false;
            $k['izin_aciklama'] = null;
            $sonuc->push($k);
        }

        $data['pdks_kayitlari'] = $sonuc->values();

        $data['avans_kesintiler'] = $personel->avansKesintiler;
        $data['prim_kazanclar'] = $personel->primKazanclar;

        // Dosyalar
        $data['dosyalar'] = $personel->dosyalar->map(function ($d) {
            return [
                'id' => $d->id,
                'dosya_adi' => $d->dosya_adi,
                'dosya_tipi' => $d->dosya_tipi,
                'boyut' => $d->boyut,
                'created_at' => $d->created_at,
                'url' => asset('storage/' . $d->dosya_yolu),
            ];
        });

        // İzin Hakediş Hesaplama (İş Kanunu m.53)
        $izinHakedis = null;
        if ($personel->giris_tarihi) {
            $giris = \Carbon\Carbon::parse($personel->giris_tarihi);
            $kidem = $giris->diffInYears(now());
            $yillikHak = 14; // varsayılan
            if ($kidem >= 15) $yillikHak = 26;
            elseif ($kidem >= 5) $yillikHak = 20;

            $kullanilanIzin = $personel->izinler
                ->where('durum', 'onaylandi')
                ->where('izin_tipi', 'gunluk')
                ->whereNotNull('gun_sayisi')
                ->sum('gun_sayisi');

            // Bu yıl kullanılan
            $buYilKullanilan = $personel->izinler
                ->where('durum', 'onaylandi')
                ->where('izin_tipi', 'gunluk')
                ->filter(function ($iz) { return \Carbon\Carbon::parse($iz->tarih)->year === now()->year; })
                ->sum('gun_sayisi');

            $izinHakedis = [
                'kidem_yil' => $kidem,
                'yillik_hak' => $yillikHak,
                'toplam_kullanilan' => (int)$kullanilanIzin,
                'bu_yil_kullanilan' => (int)$buYilKullanilan,
                'kalan' => max(0, $yillikHak - (int)$buYilKullanilan),
            ];
        }
        $data['izin_hakedis'] = $izinHakedis;

        // Mesai verileri (günlük özetlerden fazla mesai dakikası > 0 olanlar)
        $firma_id = \Illuminate\Support\Facades\Auth::user()->firma_id ?? 1;
        $data['mesailer'] = \App\Models\PdksGunlukOzet::withoutGlobalScopes()
            ->where('personel_id', $personel->id)
            ->where('firma_id', $firma_id)
            ->where('fazla_mesai_dakika', '>', 0)
            ->orderByDesc('tarih')
            ->limit(60)
            ->get(['id', 'tarih', 'ilk_giris', 'son_cikis', 'toplam_calisma_suresi', 'fazla_mesai_dakika', 'durum']);

        // Aylık puantaj parametresinden çarpanları al
        $aylikParam = \App\Models\AylikPuantajParametresi::withoutGlobalScopes()
            ->find($personel->puantaj_parametre_id);
        $data['mesai_carpanlari'] = $aylikParam ? [
            'fazla_mesai'       => (float)$aylikParam->fazla_mesai_carpani,
            'tatil_mesai'       => (float)$aylikParam->tatil_mesai_carpani,
            'resmi_tatil_mesai' => (float)$aylikParam->resmi_tatil_mesai_carpani,
            'gunluk_saat'       => (float)$aylikParam->gunluk_calisma_saati,
            'parametre_adi'     => $aylikParam->hesap_parametresi_adi,
        ] : null;

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
            'puantaj_parametre_id' => 'nullable|exists:gunluk_puantaj_parametreleri,id',
            'tc_no' => 'nullable|string|size:11',
            'iban_no' => 'nullable|string|max:34',
            'adres' => 'nullable|string',
            'acil_kisi_adi' => 'nullable|string|max:255',
            'acil_kisi_telefonu' => 'nullable|string|max:20',
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

        return response()->json(['success' => true, 'message' => 'Personel başarıyla silindi.']);
    }
}
