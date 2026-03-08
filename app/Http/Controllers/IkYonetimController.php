<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Personel;

class IkYonetimController extends Controller
{
    // ═══════════════════ İZİN TALEPLERİ ═══════════════════
    public function izinTalepleri()
    {
        $firmaId = Auth::user()->firma_id;
        $talepler = DB::table('izin_talepleri')
            ->leftJoin('personeller', 'izin_talepleri.personel_id', '=', 'personeller.id')
            ->leftJoin('kullanicilar as onaylayan', 'izin_talepleri.onaylayan_id', '=', 'onaylayan.id')
            ->where('izin_talepleri.firma_id', $firmaId)
            ->select('izin_talepleri.*', DB::raw("CONCAT(personeller.ad, ' ', personeller.soyad) as personel_adi"), 'onaylayan.ad_soyad as onaylayan_adi')
            ->orderBy('izin_talepleri.created_at', 'desc')
            ->limit(200)->get();

        $personeller = Personel::withoutGlobalScopes()
            ->select('id', 'ad', 'soyad', 'kart_no')
            ->where('firma_id', $firmaId)->get();

        return Inertia::render('Ik/IzinTalepleri', [
            'talepler' => $talepler,
            'personeller' => $personeller,
        ]);
    }

    public function izinTalebiOlustur(Request $request)
    {
        $v = $request->validate([
            'personel_id' => 'required|integer',
            'izin_turu' => 'required|string',
            'baslangic_tarihi' => 'required|date|after:2000-01-01|before:2100-01-01',
            'bitis_tarihi' => 'required|date|before:2100-01-01|after_or_equal:baslangic_tarihi',
            'aciklama' => 'nullable|string|max:1000',
        ]);

        $baslangic = \Carbon\Carbon::parse($v['baslangic_tarihi']);
        $bitis = \Carbon\Carbon::parse($v['bitis_tarihi']);
        $gunSayisi = min($baslangic->diffInDays($bitis) + 1, 365);

        DB::table('izin_talepleri')->insert([
            'firma_id' => Auth::user()->firma_id,
            'personel_id' => $v['personel_id'],
            'talep_eden_id' => Auth::id(),
            'izin_turu' => $v['izin_turu'],
            'baslangic_tarihi' => $v['baslangic_tarihi'],
            'bitis_tarihi' => $v['bitis_tarihi'],
            'gun_sayisi' => $gunSayisi,
            'aciklama' => $v['aciklama'] ?? null,
            'durum' => 'beklemede',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'İzin talebi oluşturuldu.');
    }

    public function izinTalebiIslem(Request $request, $id)
    {
        $v = $request->validate([
            'durum' => 'required|in:onaylandi,reddedildi,iptal',
            'red_nedeni' => 'nullable|string|max:500',
        ]);

        DB::table('izin_talepleri')->where('id', $id)->update([
            'durum' => $v['durum'],
            'onaylayan_id' => Auth::id(),
            'onay_tarihi' => now(),
            'red_nedeni' => $v['red_nedeni'] ?? null,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'İzin talebi güncellendi.']);
    }

    // ═══════════════════ PERFORMANS DEĞERLENDİRME ═══════════════════
    public function performans()
    {
        $firmaId = Auth::user()->firma_id;
        $degerlendirmeler = DB::table('performans_degerlendirmeleri')
            ->leftJoin('personeller', 'performans_degerlendirmeleri.personel_id', '=', 'personeller.id')
            ->where('performans_degerlendirmeleri.firma_id', $firmaId)
            ->select('performans_degerlendirmeleri.*', DB::raw("CONCAT(personeller.ad, ' ', personeller.soyad) as personel_adi"), 'personeller.departman')
            ->orderBy('performans_degerlendirmeleri.created_at', 'desc')
            ->limit(200)->get();

        $personeller = Personel::withoutGlobalScopes()
            ->select('id', 'ad', 'soyad', 'kart_no', 'departman')
            ->where('firma_id', $firmaId)->get();

        return Inertia::render('Ik/Performans', [
            'degerlendirmeler' => $degerlendirmeler,
            'personeller' => $personeller,
        ]);
    }

    public function performansKaydet(Request $request)
    {
        $v = $request->validate([
            'personel_id' => 'required|integer',
            'donem' => 'required|string|max:20',
            'donem_tipi' => 'required|in:aylik,ceyrek,yillik',
            'is_kalitesi' => 'required|integer|min:1|max:10',
            'verimlilik' => 'required|integer|min:1|max:10',
            'iletisim' => 'required|integer|min:1|max:10',
            'sorumluluk' => 'required|integer|min:1|max:10',
            'takim_calismasi' => 'required|integer|min:1|max:10',
            'liderlik' => 'required|integer|min:1|max:10',
            'yaraticilik' => 'required|integer|min:1|max:10',
            'devam_durum' => 'required|integer|min:1|max:10',
            'guclu_yonler' => 'nullable|string',
            'gelistirilecek_yonler' => 'nullable|string',
            'hedefler' => 'nullable|string',
            'notlar' => 'nullable|string',
        ]);

        $genelPuan = round(($v['is_kalitesi'] + $v['verimlilik'] + $v['iletisim'] + $v['sorumluluk'] +
            $v['takim_calismasi'] + $v['liderlik'] + $v['yaraticilik'] + $v['devam_durum']) / 8, 2);

        DB::table('performans_degerlendirmeleri')->insert([
            'firma_id' => Auth::user()->firma_id,
            'degerlendiren_id' => Auth::id(),
            'genel_puan' => $genelPuan,
            ...$v,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'Performans değerlendirmesi kaydedildi.');
    }

    // ═══════════════════ EĞİTİM TAKİBİ ═══════════════════
    public function egitimler()
    {
        $firmaId = Auth::user()->firma_id;
        $egitimler = DB::table('egitim_kayitlari')
            ->leftJoin('personeller', 'egitim_kayitlari.personel_id', '=', 'personeller.id')
            ->where('egitim_kayitlari.firma_id', $firmaId)
            ->select('egitim_kayitlari.*', DB::raw("CONCAT(personeller.ad, ' ', personeller.soyad) as personel_adi"))
            ->orderBy('egitim_kayitlari.created_at', 'desc')
            ->limit(200)->get();

        $personeller = Personel::withoutGlobalScopes()
            ->select('id', 'ad', 'soyad', 'kart_no')
            ->where('firma_id', $firmaId)->get();

        return Inertia::render('Ik/Egitimler', [
            'egitimler' => $egitimler,
            'personeller' => $personeller,
        ]);
    }

    public function egitimKaydet(Request $request)
    {
        $v = $request->validate([
            'personel_id' => 'required|integer',
            'egitim_adi' => 'required|string|max:255',
            'egitim_turu' => 'required|in:ic,dis,online,sertifika',
            'kurum' => 'nullable|string|max:255',
            'baslangic_tarihi' => 'required|date|after:2000-01-01|before:2100-01-01',
            'bitis_tarihi' => 'nullable|date',
            'sure_saat' => 'nullable|integer|min:1',
            'sertifika_no' => 'nullable|string|max:100',
            'sertifika_gecerlilik' => 'nullable|date',
            'durum' => 'required|in:planlanmis,devam_ediyor,tamamlandi,iptal',
            'notlar' => 'nullable|string',
        ]);

        DB::table('egitim_kayitlari')->insert([
            'firma_id' => Auth::user()->firma_id,
            ...$v,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'Eğitim kaydı oluşturuldu.');
    }

    // ═══════════════════ DİSİPLİN KAYITLARI ═══════════════════
    public function disiplin()
    {
        $firmaId = Auth::user()->firma_id;
        $kayitlar = DB::table('disiplin_kayitlari')
            ->leftJoin('personeller', 'disiplin_kayitlari.personel_id', '=', 'personeller.id')
            ->leftJoin('kullanicilar', 'disiplin_kayitlari.kaydeden_id', '=', 'kullanicilar.id')
            ->where('disiplin_kayitlari.firma_id', $firmaId)
            ->select('disiplin_kayitlari.*', DB::raw("CONCAT(personeller.ad, ' ', personeller.soyad) as personel_adi"), 'kullanicilar.ad_soyad as kaydeden_adi')
            ->orderBy('disiplin_kayitlari.olay_tarihi', 'desc')
            ->limit(200)->get();

        $personeller = Personel::withoutGlobalScopes()
            ->select('id', 'ad', 'soyad', 'kart_no')
            ->where('firma_id', $firmaId)->get();

        return Inertia::render('Ik/Disiplin', [
            'kayitlar' => $kayitlar,
            'personeller' => $personeller,
        ]);
    }

    public function disiplinKaydet(Request $request)
    {
        $v = $request->validate([
            'personel_id' => 'required|integer',
            'tur' => 'required|in:sozlu_uyari,yazili_uyari,kinama,ucret_kesintisi,fesih_uyarisi,diger',
            'olay_tarihi' => 'required|date|after:2000-01-01|before:2100-01-01',
            'olay_aciklamasi' => 'required|string|max:2000',
            'alinan_onlem' => 'nullable|string|max:1000',
            'personel_bilgilendirildi' => 'boolean',
            'bilgilendirme_tarihi' => 'nullable|date',
            'notlar' => 'nullable|string',
        ]);

        DB::table('disiplin_kayitlari')->insert([
            'firma_id' => Auth::user()->firma_id,
            'kaydeden_id' => Auth::id(),
            ...$v,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'Disiplin kaydı oluşturuldu.');
    }

    // ═══════════════════ KIDEM/İHBAR HESAPLAYICI ═══════════════════
    public function kidemHesaplayici()
    {
        return Inertia::render('Ik/KidemHesaplayici');
    }
}
