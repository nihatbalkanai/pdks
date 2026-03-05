<?php

namespace App\Http\Controllers;

use App\Models\PersonelIzin;
use App\Models\PersonelCalismaPlan;
use App\Models\IzinTuru;
use App\Models\Personel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Services\PdksHesaplamaServisi;

class PersonelIzinYonetimController extends Controller
{
    public function index(Request $request)
    {
        $firma_id = Auth::user()->firma_id ?? 1;

        $personeller = Personel::where('firma_id', $firma_id)
            ->where('durum', true)
            ->orderBy('ad')
            ->select('id', 'ad', 'soyad', 'sicil_no', 'departman')
            ->get();

        $izinTurleri = IzinTuru::where('firma_id', $firma_id)
            ->where('aktif_mi', true)
            ->orderBy('ad')
            ->get();

        return Inertia::render('HesapParam/PersonelIzinYonetim', [
            'personeller' => $personeller,
            'izinTurleri' => $izinTurleri,
        ]);
    }

    /**
     * Personelin izinlerini getir (filtreli)
     */
    public function izinGetir(Request $request, $personelId)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $yil = $request->get('yil', now()->year);

        $izinler = PersonelIzin::where('firma_id', $firma_id)
            ->where('personel_id', $personelId)
            ->whereYear('tarih', $yil)
            ->with(['izinTuru'])
            ->orderBy('tarih', 'desc')
            ->get();

        return response()->json($izinler);
    }

    /**
     * İzin türüne göre resmi tatiller ve hafta sonlarını dikkate alarak
     * bitiş tarihi veya gün sayısını hesapla (Frontend AJAX için)
     */
    public function hesaplaIzinTarihi(Request $request)
    {
        $validated = $request->validate([
            'izin_turu_id' => 'required|integer',
            'tarih' => 'required|date',
            'gun_sayisi' => 'nullable|numeric|min:0.5',
            'bitis_tarihi' => 'nullable|date',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $izinTuru = IzinTuru::withoutGlobalScopes()->find($validated['izin_turu_id']);
        $haftaSonuHaric = $izinTuru?->hafta_sonu_haric_mi ?? false;
        $resmiTatilHaric = $izinTuru?->resmi_tatil_haric_mi ?? false;

        $baslangic = Carbon::parse($validated['tarih']);

        // Resmi tatilleri çek
        $resmiTatilTarihleri = [];
        if ($resmiTatilHaric) {
            $resmiTatilTarihleri = \App\Models\ResmiTatil::where('firma_id', $firma_id)
                ->pluck('tarih')
                ->map(fn($t) => Carbon::parse($t)->format('Y-m-d'))
                ->toArray();
        }

        $gunSayisi = $validated['gun_sayisi'] ?? null;
        $bitisTarihi = $validated['bitis_tarihi'] ?? null;
        $atlananGunler = [];

        if ($gunSayisi && $gunSayisi > 0) {
            // Gün sayısından bitiş tarihi hesapla
            $bitis = $this->hesaplaBitisTarihi($baslangic, ceil($gunSayisi), $haftaSonuHaric, $resmiTatilHaric, $resmiTatilTarihleri);
            $bitisTarihi = $bitis->format('Y-m-d');

            // Atlanan günleri topla (bilgi amaçlı)
            $current = $baslangic->copy();
            while ($current->lte($bitis)) {
                $tarihStr = $current->format('Y-m-d');
                $neden = null;
                if ($haftaSonuHaric && $current->isWeekend()) {
                    $neden = 'Hafta sonu';
                }
                if ($resmiTatilHaric && in_array($tarihStr, $resmiTatilTarihleri)) {
                    $neden = 'Resmi tatil';
                }
                if ($neden) {
                    $atlananGunler[] = ['tarih' => $tarihStr, 'neden' => $neden];
                }
                $current->addDay();
            }
        } elseif ($bitisTarihi) {
            // Bitiş tarihinden gün sayısı hesapla
            $bitis = Carbon::parse($bitisTarihi);
            $gunSayisi = $this->hesaplaIsGunSayisi($baslangic, $bitis, $haftaSonuHaric, $resmiTatilHaric, $resmiTatilTarihleri);
        }

        return response()->json([
            'bitis_tarihi' => $bitisTarihi,
            'gun_sayisi' => $gunSayisi,
            'hafta_sonu_haric' => $haftaSonuHaric,
            'resmi_tatil_haric' => $resmiTatilHaric,
            'max_gun' => $izinTuru?->max_gun,
            'atlanan_gunler' => $atlananGunler,
            'izin_turu_ad' => $izinTuru?->ad,
        ]);
    }

    /**
     * Yeni izin kaydet
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'personel_id' => 'required|integer',
            'izin_turu_id' => 'required|integer',
            'tarih' => 'required|date',
            'bitis_tarihi' => 'nullable|date|after_or_equal:tarih',
            'izin_tipi' => 'required|in:gunluk,saatlik',
            'giris_saati' => 'nullable|string',
            'cikis_saati' => 'nullable|string',
            'gun_sayisi' => 'nullable|numeric|min:0.5',
            'aciklama' => 'nullable|string|max:500',
            'durum' => 'nullable|string',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;

        // İzin türü bilgisini al
        $izinTuru = IzinTuru::withoutGlobalScopes()->find($validated['izin_turu_id']);
        $haftaSonuHaric = $izinTuru?->hafta_sonu_haric_mi ?? false;
        $resmiTatilHaric = $izinTuru?->resmi_tatil_haric_mi ?? false;

        $gunSayisi = $validated['gun_sayisi'] ?? null;
        $baslangic = Carbon::parse($validated['tarih']);

        // Resmi tatilleri çek (hariç tutulacaksa)
        $resmiTatilTarihleri = [];
        if ($resmiTatilHaric) {
            $resmiTatilTarihleri = \App\Models\ResmiTatil::where('firma_id', $firma_id)
                ->pluck('tarih')
                ->map(fn($t) => Carbon::parse($t)->format('Y-m-d'))
                ->toArray();
        }

        if ($gunSayisi !== null && $gunSayisi > 0 && empty($validated['bitis_tarihi'])) {
            // GÜN SAYISINDAN BİTİŞ TARİHİ HESAPLA
            // İş Kanunu: Yıllık izinde hafta sonu ve resmi tatiller sayılmaz
            $validated['bitis_tarihi'] = $this->hesaplaBitisTarihi(
                $baslangic, ceil($gunSayisi), $haftaSonuHaric, $resmiTatilHaric, $resmiTatilTarihleri
            )->format('Y-m-d');
        } elseif (!empty($validated['bitis_tarihi']) && ($gunSayisi === null || $gunSayisi <= 0)) {
            // TARİHLERDEN GÜN SAYISI HESAPLA
            $bitis = Carbon::parse($validated['bitis_tarihi']);
            $gunSayisi = $this->hesaplaIsGunSayisi(
                $baslangic, $bitis, $haftaSonuHaric, $resmiTatilHaric, $resmiTatilTarihleri
            );
        } elseif ($gunSayisi === null) {
            $gunSayisi = 1;
        }

        // Bitiş tarihini belirle
        $bitisTarihi = $validated['bitis_tarihi'] ?? $validated['tarih'];

        // ===== TARİH ÇAKIŞMA KONTROLÜ =====
        $cakisan = PersonelIzin::where('firma_id', $firma_id)
            ->where('personel_id', $validated['personel_id'])
            ->where(function ($q) use ($validated, $bitisTarihi) {
                $q->where(function ($q2) use ($validated, $bitisTarihi) {
                    // Yeni izin mevcut bir izinle çakışıyor mu?
                    $q2->where('tarih', '<=', $bitisTarihi)
                       ->where('bitis_tarihi', '>=', $validated['tarih']);
                });
            })
            ->exists();

        if ($cakisan) {
            return response()->json([
                'success' => false,
                'message' => 'Bu personelin seçilen tarih aralığında zaten bir izni bulunmaktadır. Çakışan tarihleri kontrol ediniz.',
            ], 422);
        }

        $izin = PersonelIzin::create([
            'uuid' => Str::uuid(),
            'firma_id' => $firma_id,
            'personel_id' => $validated['personel_id'],
            'izin_turu_id' => $validated['izin_turu_id'],
            'tarih' => $validated['tarih'],
            'bitis_tarihi' => $validated['bitis_tarihi'] ?? $validated['tarih'],
            'izin_tipi' => $validated['izin_tipi'],
            'giris_saati' => $validated['giris_saati'] ?? null,
            'cikis_saati' => $validated['cikis_saati'] ?? null,
            'gun_sayisi' => $gunSayisi,
            'aciklama' => $validated['aciklama'] ?? null,
            'durum' => $validated['durum'] ?? 'beklemede',
            'onaylayan_id' => ($validated['durum'] ?? '') === 'onaylandi' ? Auth::id() : null,
        ]);

        // Çalışma planına yansıt
        if ($izin->durum === 'onaylandi') {
            $this->syncIzinToCalismaPlan($izin, $izinTuru);
        }

        return response()->json(['success' => true, 'message' => 'İzin kaydedildi.']);
    }

    /**
     * İzin güncelle
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'izin_turu_id' => 'required|integer',
            'tarih' => 'required|date',
            'bitis_tarihi' => 'nullable|date|after_or_equal:tarih',
            'izin_tipi' => 'required|in:gunluk,saatlik',
            'giris_saati' => 'nullable|string',
            'cikis_saati' => 'nullable|string',
            'gun_sayisi' => 'nullable|numeric|min:0.5',
            'aciklama' => 'nullable|string|max:500',
            'durum' => 'nullable|string',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $izin = PersonelIzin::where('firma_id', $firma_id)->findOrFail($id);

        if (($validated['durum'] ?? '') === 'onaylandi' && $izin->durum !== 'onaylandi') {
            $validated['onaylayan_id'] = Auth::id();
        }

        // ===== TARİH ÇAKIŞMA KONTROLÜ (kendisi hariç) =====
        $bitisTarihi = $validated['bitis_tarihi'] ?? $validated['tarih'];
        $cakisan = PersonelIzin::where('firma_id', $firma_id)
            ->where('personel_id', $izin->personel_id)
            ->where('id', '!=', $id)
            ->where('tarih', '<=', $bitisTarihi)
            ->where('bitis_tarihi', '>=', $validated['tarih'])
            ->exists();

        if ($cakisan) {
            return response()->json([
                'success' => false,
                'message' => 'Bu personelin seçilen tarih aralığında başka bir izni bulunmaktadır.',
            ], 422);
        }

        $izin->update($validated);

        // Çalışma planına yansıt
        $izinTuru = IzinTuru::find($validated['izin_turu_id']);
        $this->syncIzinToCalismaPlan($izin, $izinTuru);

        return response()->json(['success' => true, 'message' => 'İzin güncellendi.']);
    }

    /**
 * İzin sil
 */
public function destroy($id)
{
    $firma_id = Auth::user()->firma_id ?? 1;
    $izin = PersonelIzin::where('firma_id', $firma_id)->findOrFail($id);

    // Çalışma planından izin günlerini sil
    $servis = new PdksHesaplamaServisi();
    $servis->izinSilSenkron($izin);

    $izin->delete();

    return response()->json(['success' => true, 'message' => 'İzin silindi.']);
}

    /**
     * İzin onayla/reddet
     */
    public function durumGuncelle(Request $request, $id)
    {
        $validated = $request->validate([
            'durum' => 'required|in:onaylandi,reddedildi,beklemede',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $izin = PersonelIzin::where('firma_id', $firma_id)->findOrFail($id);
        $izin->update([
            'durum' => $validated['durum'],
            'onaylayan_id' => $validated['durum'] === 'onaylandi' ? Auth::id() : null,
        ]);

        // Onaylandıysa çalışma planına yansıt
        if ($validated['durum'] === 'onaylandi') {
            $izinTuru = $izin->izinTuru;
            $this->syncIzinToCalismaPlan($izin, $izinTuru);
        }

        return response()->json(['success' => true]);
    }

    /**
     * İzin günlerini personel çalışma planına yansıt
     * İş Kanunu: Yıllık izinde hafta sonları ve resmi tatiller izin olarak işaretlenmez
     */
    private function syncIzinToCalismaPlan(PersonelIzin $izin, ?IzinTuru $izinTuru): void
    {
        $baslangic = Carbon::parse($izin->tarih);
        $bitis = Carbon::parse($izin->bitis_tarihi ?? $izin->tarih);
        $izinAdi = $izinTuru?->ad ?? 'İzin';
        $haftaSonuHaric = $izinTuru?->hafta_sonu_haric_mi ?? false;
        $resmiTatilHaric = $izinTuru?->resmi_tatil_haric_mi ?? false;

        $firma_id = $izin->firma_id;
        $resmiTatilTarihleri = [];
        if ($resmiTatilHaric) {
            $resmiTatilTarihleri = \App\Models\ResmiTatil::where('firma_id', $firma_id)
                ->pluck('tarih')
                ->map(fn($t) => Carbon::parse($t)->format('Y-m-d'))
                ->toArray();
        }

        $current = $baslangic->copy();
        while ($current->lte($bitis)) {
            $tarihStr = $current->format('Y-m-d');
            $haftaSonu = $current->isWeekend();
            $resmiTatil = in_array($tarihStr, $resmiTatilTarihleri);

            if ($haftaSonuHaric && $haftaSonu) {
                // Hafta sonu — izin olarak işaretleme, tatil olarak bırak
                $current->addDay();
                continue;
            }
            if ($resmiTatilHaric && $resmiTatil) {
                // Resmi tatil — izin olarak işaretleme
                PersonelCalismaPlan::updateOrCreate(
                    ['personel_id' => $izin->personel_id, 'tarih' => $tarihStr],
                    ['firma_id' => $firma_id, 'vardiya_id' => null, 'tur' => 'resmi_tatil', 'aciklama' => 'Resmi Tatil']
                );
                $current->addDay();
                continue;
            }

            PersonelCalismaPlan::updateOrCreate(
                ['personel_id' => $izin->personel_id, 'tarih' => $tarihStr],
                [
                    'firma_id' => $firma_id,
                    'vardiya_id' => null,
                    'tur' => 'izin',
                    'aciklama' => $izinAdi . ($izin->aciklama ? ' - ' . $izin->aciklama : ''),
                ]
            );
            $current->addDay();
        }
    }

    /**
     * Türk İş Kanunu m.56: Gün sayısından bitiş tarihi hesapla
     * Yıllık izinde hafta sonları ve resmi tatiller iş günü olarak sayılmaz
     */
    private function hesaplaBitisTarihi(Carbon $baslangic, int $gunSayisi, bool $haftaSonuHaric, bool $resmiTatilHaric, array $resmiTatilTarihleri): Carbon
    {
        $current = $baslangic->copy();
        $sayac = 0;

        while ($sayac < $gunSayisi) {
            $tarihStr = $current->format('Y-m-d');

            $atla = false;
            if ($haftaSonuHaric && $current->isWeekend()) {
                $atla = true;
            }
            if ($resmiTatilHaric && in_array($tarihStr, $resmiTatilTarihleri)) {
                $atla = true;
            }

            if (!$atla) {
                $sayac++;
            }

            if ($sayac < $gunSayisi) {
                $current->addDay();
            }
        }

        return $current;
    }

    /**
     * Türk İş Kanunu: Tarih aralığındaki fiili iş günü sayısını hesapla
     */
    private function hesaplaIsGunSayisi(Carbon $baslangic, Carbon $bitis, bool $haftaSonuHaric, bool $resmiTatilHaric, array $resmiTatilTarihleri): int
    {
        $current = $baslangic->copy();
        $sayac = 0;

        while ($current->lte($bitis)) {
            $tarihStr = $current->format('Y-m-d');

            $atla = false;
            if ($haftaSonuHaric && $current->isWeekend()) {
                $atla = true;
            }
            if ($resmiTatilHaric && in_array($tarihStr, $resmiTatilTarihleri)) {
                $atla = true;
            }

            if (!$atla) {
                $sayac++;
            }

            $current->addDay();
        }

        return max($sayac, 1);
    }
}
