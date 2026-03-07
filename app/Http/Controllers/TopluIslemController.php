<?php

namespace App\Http\Controllers;

use App\Models\Personel;
use App\Models\PersonelIzin;
use App\Models\PersonelAvansKesinti;
use App\Models\PersonelPrimKazanc;
use App\Models\PdksKaydi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TopluIslemController extends Controller
{
    private function getPersoneller()
    {
        return Personel::withoutGlobalScopes()->select('id','kart_no','ad','soyad','aylik_ucret','gunluk_ucret')->get();
    }

    // Toplu Maaş Artırımı
    public function maasArtirimi()
    {
        return Inertia::render('TopluIslemler/MaasArtirimi', ['personeller' => $this->getPersoneller()]);
    }

    public function maasArtirimiUygula(Request $request)
    {
        $request->validate([
            'personel_ids' => 'required|array|min:1',
            'tarih' => 'required|date',
            'tip' => 'required|in:oran,miktar',
            'deger' => 'required|numeric|min:0',
        ]);

        $sayac = 0;
        foreach ($request->personel_ids as $id) {
            $p = Personel::withoutGlobalScopes()->find($id);
            if (!$p) continue;
            if ($request->tip === 'oran') {
                $p->aylik_ucret = $p->aylik_ucret * (1 + $request->deger / 100);
                $p->gunluk_ucret = $p->gunluk_ucret * (1 + $request->deger / 100);
            } else {
                $p->aylik_ucret = ($p->aylik_ucret ?? 0) + $request->deger;
                $p->gunluk_ucret = ($p->gunluk_ucret ?? 0) + ($request->deger / 30);
            }
            $p->save();
            $sayac++;
        }

        return response()->json(['success' => true, 'message' => '$sayac personelin maaşı güncellendi.']);
    }

    // Toplu Hareket
    public function hareket()
    {
        return Inertia::render('TopluIslemler/Hareket', ['personeller' => $this->getPersoneller()]);
    }

    public function hareketUygula(Request $request)
    {
        $request->validate([
            'personel_ids' => 'required|array|min:1',
            'baslangic_tarihi' => 'required|date',
            'bitis_tarihi' => 'required|date|after_or_equal:baslangic_tarihi',
            'giris_saati' => 'nullable|string',
            'cikis_saati' => 'nullable|string',
        ]);

        $sayac = 0;
        $baslangic = \Carbon\Carbon::parse($request->baslangic_tarihi);
        $bitis = \Carbon\Carbon::parse($request->bitis_tarihi);

        foreach ($request->personel_ids as $pid) {
            $personel = Personel::withoutGlobalScopes()->find($pid);
            if (!$personel) continue;

            for ($tarih = $baslangic->copy(); $tarih->lte($bitis); $tarih->addDay()) {
                if ($request->giris_saati) {
                    PdksKaydi::create([
                        'firma_id' => $personel->firma_id,
                        'personel_id' => $pid,
                        'kayit_tarihi' => $tarih->format('Y-m-d') . ' ' . $request->giris_saati . ':00',
                        'islem_tipi' => 'Giriş',
                        'ham_veri' => json_encode(['toplu' => true]),
                    ]);
                }
                if ($request->cikis_saati) {
                    PdksKaydi::create([
                        'firma_id' => $personel->firma_id,
                        'personel_id' => $pid,
                        'kayit_tarihi' => $tarih->format('Y-m-d') . ' ' . $request->cikis_saati . ':00',
                        'islem_tipi' => 'Çıkış',
                        'ham_veri' => json_encode(['toplu' => true]),
                    ]);
                }
                $sayac++;
            }
        }

        return response()->json(['success' => true, 'message' => '$sayac gün için hareket kayıtları oluşturuldu.']);
    }

    // Toplu İzin
    public function izin()
    {
        return Inertia::render('TopluIslemler/Izin', ['personeller' => $this->getPersoneller()]);
    }

    public function izinUygula(Request $request)
    {
        $request->validate([
            'personel_ids' => 'required|array|min:1',
            'baslangic_tarihi' => 'required|date',
            'bitis_tarihi' => 'required|date|after_or_equal:baslangic_tarihi',
            'tatil_tipi' => 'required|string',
            'aciklama' => 'nullable|string',
            'saatlik_izin' => 'boolean',
            'mevcut_kayitlari_sil' => 'boolean',
        ]);

        $sayac = 0;
        $baslangic = \Carbon\Carbon::parse($request->baslangic_tarihi);
        $bitis = \Carbon\Carbon::parse($request->bitis_tarihi);

        foreach ($request->personel_ids as $pid) {
            $personel = Personel::withoutGlobalScopes()->find($pid);
            if (!$personel) continue;

            if ($request->mevcut_kayitlari_sil) {
                PersonelIzin::where('personel_id', $pid)
                    ->whereBetween('tarih', [$request->baslangic_tarihi, $request->bitis_tarihi])
                    ->delete();
            }

            for ($tarih = $baslangic->copy(); $tarih->lte($bitis); $tarih->addDay()) {
                PersonelIzin::create([
                    'firma_id' => $personel->firma_id,
                    'personel_id' => $pid,
                    'tarih' => $tarih->format('Y-m-d'),
                    'tatil_tipi' => $request->tatil_tipi,
                    'izin_tipi' => $request->saatlik_izin ? 'saatlik' : 'gunluk',
                    'giris_saati' => '08:30',
                    'cikis_saati' => '18:00',
                    'aciklama' => $request->aciklama,
                ]);
                $sayac++;
            }
        }

        return response()->json(['success' => true, 'message' => '$sayac izin kaydı oluşturuldu.']);
    }

    // Toplu Avans
    public function avans()
    {
        return Inertia::render('TopluIslemler/Avans', ['personeller' => $this->getPersoneller()]);
    }

    public function avansUygula(Request $request)
    {
        $request->validate([
            'personel_ids' => 'required|array|min:1',
            'tarih' => 'required|date',
            'tip' => 'required|in:oran,miktar',
            'deger' => 'required|numeric|min:0',
            'aciklama' => 'nullable|string',
            'bordro_alani' => 'required|string',
        ]);

        $sayac = 0;
        foreach ($request->personel_ids as $pid) {
            $personel = Personel::withoutGlobalScopes()->find($pid);
            if (!$personel) continue;

            $tutar = $request->tip === 'oran'
                ? ($personel->aylik_ucret ?? 0) * $request->deger / 100
                : $request->deger;

            PersonelAvansKesinti::create([
                'firma_id' => $personel->firma_id,
                'personel_id' => $pid,
                'tarih' => $request->tarih,
                'tutar' => $tutar,
                'aciklama' => $request->aciklama,
                'bordro_alani' => $request->bordro_alani,
            ]);
            $sayac++;
        }

        return response()->json(['success' => true, 'message' => '$sayac personele avans tanımlandı.']);
    }

    // Toplu Prim
    public function prim()
    {
        return Inertia::render('TopluIslemler/Prim', ['personeller' => $this->getPersoneller()]);
    }

    public function primUygula(Request $request)
    {
        $request->validate([
            'personel_ids' => 'required|array|min:1',
            'tarih' => 'required|date',
            'tip' => 'required|in:oran,miktar',
            'deger' => 'required|numeric|min:0',
            'aciklama' => 'nullable|string',
            'bordro_alani' => 'required|string',
        ]);

        $sayac = 0;
        foreach ($request->personel_ids as $pid) {
            $personel = Personel::withoutGlobalScopes()->find($pid);
            if (!$personel) continue;

            $tutar = $request->tip === 'oran'
                ? ($personel->aylik_ucret ?? 0) * $request->deger / 100
                : $request->deger;

            PersonelPrimKazanc::create([
                'firma_id' => $personel->firma_id,
                'personel_id' => $pid,
                'tarih' => $request->tarih,
                'tutar' => $tutar,
                'aciklama' => $request->aciklama,
                'bordro_alani' => $request->bordro_alani,
            ]);
            $sayac++;
        }

        return response()->json(['success' => true, 'message' => '$sayac personele prim tanımlandı.']);
    }

    // Toplu Yemek Kartı / Yemek Ücreti Atama
    public function yemekAtama()
    {
        $personeller = Personel::withoutGlobalScopes()
            ->select('id','kart_no','ad','soyad','yemek_tipi','yemek_kart_no','yemek_ucreti')
            ->get();
        return Inertia::render('TopluIslemler/YemekAtama', ['personeller' => $personeller]);
    }

    public function yemekAtamaUygula(Request $request)
    {
        $request->validate([
            'personel_ids' => 'required|array|min:1',
            'yemek_tipi' => 'required|in:kart,ucret',
            'yemek_kart_no' => 'nullable|string|max:50',
            'yemek_ucreti' => 'nullable|numeric|min:0',
        ]);

        $sayac = 0;
        foreach ($request->personel_ids as $id) {
            $p = Personel::withoutGlobalScopes()->find($id);
            if (!$p) continue;
            $p->yemek_tipi = $request->yemek_tipi;
            if ($request->yemek_tipi === 'kart') {
                $p->yemek_kart_no = $request->yemek_kart_no;
                $p->yemek_ucreti = null;
            } else {
                $p->yemek_kart_no = null;
                $p->yemek_ucreti = $request->yemek_ucreti;
            }
            $p->save();
            $sayac++;
        }

        return response()->json(['success' => true, 'message' => "$sayac personele yemek tanımı atandı."]);
    }

    // Toplu Servis / Yol Parası Atama
    public function servisYolAtama()
    {
        $personeller = Personel::withoutGlobalScopes()
            ->select('id','kart_no','ad','soyad','ulasim_tipi','servis_plaka','yol_parasi')
            ->get();
        return Inertia::render('TopluIslemler/ServisYolAtama', ['personeller' => $personeller]);
    }

    public function servisYolAtamaUygula(Request $request)
    {
        $request->validate([
            'personel_ids' => 'required|array|min:1',
            'ulasim_tipi' => 'required|in:servis,yol_parasi_gunluk,yol_parasi_aylik',
            'servis_plaka' => 'nullable|string|max:20',
            'yol_parasi' => 'nullable|numeric|min:0',
        ]);

        $sayac = 0;
        foreach ($request->personel_ids as $id) {
            $p = Personel::withoutGlobalScopes()->find($id);
            if (!$p) continue;
            $p->ulasim_tipi = $request->ulasim_tipi;
            if ($request->ulasim_tipi === 'servis') {
                $p->servis_plaka = $request->servis_plaka;
                $p->yol_parasi = null;
            } else {
                $p->servis_plaka = null;
                $p->yol_parasi = $request->yol_parasi;
            }
            $p->save();
            $sayac++;
        }

        return response()->json(['success' => true, 'message' => "$sayac personele ulaşım tanımı atandı."]);
    }

    // Toplu Giriş Çıkış Düzenleme
    public function girisCikisDuzenleme(Request $request)
    {
        $tarih = $request->tarih ?? now()->format('Y-m-d');

        $personeller = Personel::withoutGlobalScopes()->select('id','kart_no','ad','soyad')->get();

        // O tarihteki kayıtları getir
        $kayitlar = PdksKaydi::withoutGlobalScopes()
            ->whereDate('kayit_tarihi', $tarih)
            ->whereNotNull('personel_id')
            ->with('personel:id,kart_no,ad,soyad')
            ->orderBy('personel_id')
            ->orderBy('kayit_tarihi')
            ->get()
            ->groupBy('personel_id')
            ->map(function ($group) {
                $personel = $group->first()->personel;
                $giris = $group->where('islem_tipi', 'Giriş')->first();
                $cikis = $group->where('islem_tipi', 'Çıkış')->first();
                return [
                    'personel_id' => $personel?->id,
                    'kart_no' => $personel?->kart_no ?? '-',
                    'ad' => $personel?->ad ?? '',
                    'soyad' => $personel?->soyad ?? '',
                    'giris_saati' => $giris ? substr($giris->kayit_tarihi, 11, 5) : '',
                    'cikis_saati' => $cikis ? substr($cikis->kayit_tarihi, 11, 5) : '',
                    'giris_id' => $giris?->id,
                    'cikis_id' => $cikis?->id,
                ];
            })->values();

        return Inertia::render('TopluIslemler/GirisCikisDuzenleme', [
            'personeller' => $personeller,
            'kayitlar' => $kayitlar,
            'tarih' => $tarih,
        ]);
    }

    public function girisCikisDuzenlemeKaydet(Request $request)
    {
        $request->validate([
            'tarih' => 'required|date',
            'kayitlar' => 'required|array',
            'kayitlar.*.personel_id' => 'required|integer',
            'kayitlar.*.giris_saati' => 'nullable|string',
            'kayitlar.*.cikis_saati' => 'nullable|string',
        ]);

        $sayac = 0;
        foreach ($request->kayitlar as $k) {
            $personel = Personel::withoutGlobalScopes()->find($k['personel_id']);
            if (!$personel) continue;

            // Mevcut kayıtları sil
            PdksKaydi::withoutGlobalScopes()
                ->where('personel_id', $k['personel_id'])
                ->whereDate('kayit_tarihi', $request->tarih)
                ->delete();

            // Yeni giriş
            if (!empty($k['giris_saati'])) {
                PdksKaydi::create([
                    'firma_id' => $personel->firma_id,
                    'personel_id' => $k['personel_id'],
                    'kayit_tarihi' => $request->tarih . ' ' . $k['giris_saati'] . ':00',
                    'islem_tipi' => 'Giriş',
                    'ham_veri' => json_encode(['duzenleme' => true]),
                ]);
            }
            // Yeni çıkış
            if (!empty($k['cikis_saati'])) {
                PdksKaydi::create([
                    'firma_id' => $personel->firma_id,
                    'personel_id' => $k['personel_id'],
                    'kayit_tarihi' => $request->tarih . ' ' . $k['cikis_saati'] . ':00',
                    'islem_tipi' => 'Çıkış',
                    'ham_veri' => json_encode(['duzenleme' => true]),
                ]);
            }
            $sayac++;
        }

        return response()->json(['success' => true, 'message' => '$sayac personelin giriş-çıkış kayıtları güncellendi.']);
    }

    public function girisCikisTarihSil(Request $request)
    {
        $request->validate(['tarih' => 'required|date']);

        $silinen = PdksKaydi::withoutGlobalScopes()
            ->whereDate('kayit_tarihi', $request->tarih)
            ->delete();

        return response()->json(['success' => true, 'message' => '$silinen kayıt silindi.']);
    }

    // Toplu Mesaj (SMS) Gönderimi
    public function topluMesaj()
    {
        $personeller = Personel::withoutGlobalScopes()
            ->select('id','kart_no','ad','soyad','telefon','gec_kalma_bildirimi')
            ->get();

        return Inertia::render('TopluIslemler/TopluMesaj', [
            'personeller' => $personeller,
        ]);
    }

    public function topluMesajGonder(Request $request)
    {
        $request->validate([
            'personel_ids' => 'required|array|min:1',
            'mesaj' => 'required|string|max:500',
        ]);

        $sayac = 0;
        $hatalar = [];
        foreach ($request->personel_ids as $pid) {
            $p = Personel::withoutGlobalScopes()->find($pid);
            if (!$p) continue;
            if (empty($p->telefon)) {
                $hatalar[] = "{$p->ad} {$p->soyad} - Telefon numarası yok";
                continue;
            }

            // SMS gönderim simülasyonu (gerçek API entegrasyonu için MesajAyari kullanılacak)
            // TODO: SMS API entegrasyonu
            \DB::table('bildirim_loglari')->insert([
                'firma_id' => $p->firma_id,
                'personel_id' => $p->id,
                'kanal' => 'sms',
                'alici' => $p->telefon,
                'mesaj' => $request->mesaj,
                'durum' => 'gonderildi',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $sayac++;
        }

        $msg = "$sayac personele SMS gönderildi.";
        if (count($hatalar) > 0) {
            $msg .= " " . count($hatalar) . " kişiye gönderilemedi.";
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    // Toplu E-Posta Gönderimi
    public function topluMail()
    {
        $personeller = Personel::withoutGlobalScopes()
            ->select('id','kart_no','ad','soyad','email','gec_kalma_bildirimi')
            ->get();

        return Inertia::render('TopluIslemler/TopluMail', [
            'personeller' => $personeller,
        ]);
    }

    public function topluMailGonder(Request $request)
    {
        $request->validate([
            'personel_ids' => 'required|array|min:1',
            'konu' => 'required|string|max:255',
            'mesaj' => 'required|string',
        ]);

        $sayac = 0;
        $hatalar = [];
        foreach ($request->personel_ids as $pid) {
            $p = Personel::withoutGlobalScopes()->find($pid);
            if (!$p) continue;
            if (empty($p->email)) {
                $hatalar[] = "{$p->ad} {$p->soyad} - E-posta adresi yok";
                continue;
            }

            // Mail gönderim simülasyonu (gerçek entegrasyon için Mail facade kullanılacak)
            // TODO: Mail::to($p->email)->send(...)
            \DB::table('bildirim_loglari')->insert([
                'firma_id' => $p->firma_id,
                'personel_id' => $p->id,
                'kanal' => 'email',
                'alici' => $p->email,
                'konu' => $request->konu,
                'mesaj' => $request->mesaj,
                'durum' => 'gonderildi',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $sayac++;
        }

        $msg = "$sayac personele e-posta gönderildi.";
        if (count($hatalar) > 0) {
            $msg .= " " . count($hatalar) . " kişiye gönderilemedi.";
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }
}
