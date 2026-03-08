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
            ->paginate(50)
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

        // Şube listesi (aktif şubeler)
        $subeler = \App\Models\Sube::withoutGlobalScopes()
            ->where('firma_id', $firma_id)
            ->where('durum', true)
            ->orderBy('sube_adi')
            ->get(['id', 'sube_adi', 'lokasyon_enlem', 'lokasyon_boylam']);

        // Vardiya listesi
        $vardiyalar = \App\Models\Vardiya::withoutGlobalScopes()
            ->where('firma_id', $firma_id)
            ->where('durum', true)
            ->orderBy('ad')
            ->get(['id', 'ad', 'baslangic_saati', 'bitis_saati', 'toplam_sure']);

        // Çalışma grupları
        $calismaGruplari = \App\Models\CalismaGrubu::withoutGlobalScopes()
            ->where('firma_id', $firma_id)
            ->where('durum', true)
            ->orderBy('aciklama')
            ->get(['id', 'aciklama']);

        // Firma kodu (mobil giriş için)
        $firma = \App\Models\Firma::find($firma_id);

        return Inertia::render('Personel/Index', [
            'personeller' => $personeller,
            'filtreler' => $request->only(['arama']),
            'tanimKodlari' => $tanimKodlari,
            'aylikPuantajParametreleri' => $aylikPuantajParametreleri,
            'gunlukPuantajParametreleri' => $gunlukPuantajParametreleri,
            'subeler' => $subeler,
            'vardiyalar' => $vardiyalar,
            'calismaGruplari' => $calismaGruplari,
            'firmaKodu' => $firma->firma_kodu ?? '',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kart_no' => 'nullable|string|max:100',
            'ad' => 'required|string|max:100',
            'soyad' => 'required|string|max:100',
            'ad_soyad' => 'nullable|string|max:255',
            'sicil_no' => 'nullable|string|max:100',
            'ssk_no' => 'nullable|string|max:100',
            'unvan' => 'nullable|string|max:100',
            'sirket' => 'nullable|string|max:255',
            'bolum' => 'nullable|string|max:255',
            'sube_id' => 'nullable|integer|exists:subeler,id',
            'ozel_kod' => 'nullable|string|max:100',
            'departman' => 'nullable|string|max:255',
            'servis_kodu' => 'nullable|string|max:255',
            'hesap_gurubu' => 'nullable|string|max:255',
            'agi' => 'nullable|string|max:100',
            'aylik_ucret' => 'nullable|numeric',
            'elden_odeme' => 'nullable|numeric',
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
            'aylik_puantaj_parametre_id' => 'nullable|exists:aylik_puantaj_parametreleri,id',
            'vardiya_id' => 'nullable|exists:vardiyalar,id',
            'tc_no' => 'nullable|digits:11',
            'iban_no' => 'nullable|string|max:34',
            'adres' => 'nullable|string',
            'acil_kisi_adi' => 'nullable|string|max:255',
            'acil_kisi_telefonu' => 'nullable|string|max:20',
            'yemek_tipi' => 'nullable|in:kart,ucret',
            'yemek_kart_no' => 'nullable|string|max:50',
            'yemek_ucreti' => 'nullable|numeric|min:0',
            'ulasim_tipi' => 'nullable|in:servis,yol_parasi_gunluk,yol_parasi_aylik',
            'servis_plaka' => 'nullable|string|max:20',
            'yol_parasi' => 'nullable|numeric|min:0',
        ]);

        \Log::info('PersonelController@store ISTEK', ['data' => $validated]);

        try {
            if (empty($validated['ad_soyad'])) {
                $validated['ad_soyad'] = $validated['ad'] . ' ' . $validated['soyad'];
            }

            // Sicil no boşsa otomatik ata
            if (empty($validated['sicil_no'])) {
                $sonSicil = Personel::withoutGlobalScopes()->max('id') ?? 0;
                $validated['sicil_no'] = str_pad($sonSicil + 1, 5, '0', STR_PAD_LEFT);
            }

            $firma_id = Auth::user()->firma_id ?? 1;

            $personel = Personel::create(array_merge($validated, [
                'firma_id' => $firma_id
            ]));

            \Log::info('PersonelController@store BASARILI', ['personel_id' => $personel->id]);

            return response()->json(['success' => true, 'personel' => $personel]);

        } catch (\Exception $e) {
            \Log::error('PersonelController@store BEKLENMEDIK HATA', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Kayıt hatası: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $personel = Personel::withoutGlobalScopes()->findOrFail($id);
        $personel->load([
            'izinler' => function($q) { $q->withoutGlobalScopes(); },
            'izinler.izinTuru' => function($q) { $q->withoutGlobalScopes(); },
            'avansKesintiler' => function($q) { $q->withoutGlobalScopes(); },
            'primKazanclar' => function($q) { $q->withoutGlobalScopes(); },
            'zimmetler' => function($q) { $q->withoutGlobalScopes(); },
            'dosyalar',
            'pdksKayitlari' => function($q) {
                $q->withoutGlobalScopes()->orderBy('created_at', 'desc')->limit(50);
            }
        ]);

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
            $kidem = (int) $giris->diffInYears(now());
            $yillikHak = 14; // varsayılan
            if ($kidem >= 15) $yillikHak = 26;
            elseif ($kidem >= 5) $yillikHak = 20;

            $yillikDusenTurIds = \App\Models\IzinTuru::withoutGlobalScopes()
                ->where('yillik_izinden_duser_mi', true)
                ->pluck('id')
                ->toArray();

            $kullanilanIzin = $personel->izinler
                ->where('durum', 'onaylandi')
                ->whereIn('izin_turu_id', $yillikDusenTurIds)
                ->whereNotNull('gun_sayisi')
                ->sum('gun_sayisi');

            // Bu yıl kullanılan
            $buYilKullanilan = $personel->izinler
                ->where('durum', 'onaylandi')
                ->whereIn('izin_turu_id', $yillikDusenTurIds)
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
        \Log::info('PersonelController@update ISTEK', [
            'personel_id' => $personel->id,
            'data' => $request->all()
        ]);

        try {
        $validated = $request->validate([
            'kart_no' => 'nullable|string|max:100',
            'ad' => 'required|string|max:100',
            'soyad' => 'required|string|max:100',
            'sicil_no' => 'nullable|string|max:100',
            'ssk_no' => 'nullable|string|max:100',
            'unvan' => 'nullable|string|max:100',
            'sirket' => 'nullable|string|max:255',
            'bolum' => 'nullable|string|max:255',
            'sube_id' => 'nullable|integer|exists:subeler,id',
            'ozel_kod' => 'nullable|string|max:100',
            'departman' => 'nullable|string|max:255',
            'servis_kodu' => 'nullable|string|max:255',
            'hesap_gurubu' => 'nullable|string|max:255',
            'agi' => 'nullable|string|max:100',
            'aylik_ucret' => 'nullable|numeric',
            'elden_odeme' => 'nullable|numeric',
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
            'aylik_puantaj_parametre_id' => 'nullable|exists:aylik_puantaj_parametreleri,id',
            'vardiya_id' => 'nullable|exists:vardiyalar,id',
            'tc_no' => 'nullable|digits:11',
            'iban_no' => 'nullable|string|max:34',
            'adres' => 'nullable|string',
            'acil_kisi_adi' => 'nullable|string|max:255',
            'acil_kisi_telefonu' => 'nullable|string|max:20',
            'yemek_tipi' => 'nullable|in:kart,ucret',
            'yemek_kart_no' => 'nullable|string|max:50',
            'yemek_ucreti' => 'nullable|numeric|min:0',
            'ulasim_tipi' => 'nullable|in:servis,yol_parasi_gunluk,yol_parasi_aylik',
            'servis_plaka' => 'nullable|string|max:20',
            'yol_parasi' => 'nullable|numeric|min:0',
            'calisma_grubu_id' => 'nullable|exists:calisma_gruplari,id',
            'mobil_sifre_yeni' => 'nullable|string|min:4|max:20',
        ]);

            $validated['ad_soyad'] = $validated['ad'] . ' ' . $validated['soyad'];

            // Sicil no boşsa mevcut değeri korumak için güncellemeden çıkar
            if (empty($validated['sicil_no'])) {
                unset($validated['sicil_no']);
            }

            // Mobil şifre güncelleme (hash'le)
            if (!empty($validated['mobil_sifre_yeni'])) {
                $personel->mobil_sifre = \Illuminate\Support\Facades\Hash::make($validated['mobil_sifre_yeni']);
            }
            unset($validated['mobil_sifre_yeni']);

            $personel->update($validated);
            $personel->refresh();

            \Log::info('PersonelController@update BASARILI', ['personel_id' => $personel->id]);

            return response()->json(['success' => true, 'personel' => $personel]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('PersonelController@update VALIDATION HATASI', [
                'personel_id' => $personel->id,
                'errors' => $e->errors()
            ]);
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('PersonelController@update BEKLENMEDIK HATA', [
                'personel_id' => $personel->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Kayıt hatası: ' . $e->getMessage()
            ], 500);
        }
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
            @unlink(public_path($personel->resim_yolu));
        }

        $dosyaAdi = 'personel_' . $id . '_' . time() . '.' . $request->resim->extension();
        $path = $request->file('resim')->storeAs('personel', $dosyaAdi, 'public');

        $personel->resim_yolu = 'storage/' . $path;
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

    /**
     * Manuel PDKS kaydı ekleme
     */
    public function pdksEkle(Request $request, $personelId)
    {
        $validated = $request->validate([
            'tarih' => 'required|date|after:2000-01-01|before:2100-01-01',
            'saat' => 'required|date_format:H:i',
            'islem_tipi' => 'required|in:Giriş,Çıkış',
        ]);

        $kayitTarihi = $validated['tarih'] . ' ' . $validated['saat'] . ':00';

        // ═══ YARIM SAAT KURALI ═══
        $sonKayit = PdksKaydi::withoutGlobalScopes()
            ->where('personel_id', $personelId)
            ->orderByDesc('kayit_tarihi')
            ->first();

        if ($sonKayit) {
            $sonZaman = \Carbon\Carbon::parse($sonKayit->kayit_tarihi);
            $yeniZaman = \Carbon\Carbon::parse($kayitTarihi);
            $farkDakika = abs($yeniZaman->diffInMinutes($sonZaman));
            if ($farkDakika < 30) {
                return response()->json([
                    'success' => false,
                    'message' => 'Son kayıtla arasında en az 30 dakika olmalıdır. (Fark: ' . $farkDakika . ' dk)',
                ], 422);
            }
        }

        $kayit = PdksKaydi::create([
            'firma_id' => Auth::user()->firma_id ?? 1,
            'personel_id' => $personelId,
            'kayit_tarihi' => $kayitTarihi,
            'islem_tipi' => $validated['islem_tipi'],
            'ham_veri' => ['kaynak' => 'Manuel Düzeltme', 'ekleyen' => Auth::user()->name ?? 'Admin'],
        ]);

        return response()->json(['success' => true, 'message' => 'Kayıt eklendi.', 'kayit' => $kayit]);
    }

    /**
     * PDKS kaydı güncelleme
     */
    public function pdksGuncelle(Request $request, $personelId, $kayitId)
    {
        $validated = $request->validate([
            'tarih' => 'required|date|after:2000-01-01|before:2100-01-01',
            'saat' => 'required|date_format:H:i',
            'islem_tipi' => 'required|in:Giriş,Çıkış',
        ]);

        $kayit = PdksKaydi::withoutGlobalScopes()->findOrFail($kayitId);
        $kayit->kayit_tarihi = $validated['tarih'] . ' ' . $validated['saat'] . ':00';
        $kayit->islem_tipi = $validated['islem_tipi'];
        $kayit->save();

        return response()->json(['success' => true, 'message' => 'Kayıt güncellendi.']);
    }

    /**
     * PDKS kaydı silme
     */
    public function pdksSil($personelId, $kayitId)
    {
        $kayit = PdksKaydi::withoutGlobalScopes()->findOrFail($kayitId);
        $kayit->delete();

        return response()->json(['success' => true, 'message' => 'Kayıt silindi.']);
    }
}
