<?php

namespace App\Services;

use App\Models\AylikPuantajParametresi;
use App\Models\GunlukPuantajParametresi;
use App\Models\PdksGunlukOzet;
use App\Models\PdksKayit;
use App\Models\Personel;
use App\Models\PersonelCalismaPlan;
use App\Models\PersonelIzin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PDKS Hesaplama Servisi
 * Puantaj parametreleri, çalışma planı ve izin yönetimi arasındaki
 * senkronizasyonu sağlayan merkezi servis.
 */
class PdksHesaplamaServisi
{
    /**
     * Bir personelin belirli bir gün için günlük özetini hesaplar.
     * Puantaj parametresine göre geç kalma / erken çıkma / durum belirler.
     */
    public function gunlukOzetHesapla(int $personelId, string $tarih, int $firmaId): ?PdksGunlukOzet
    {
        $personel = Personel::withoutGlobalScopes()->find($personelId);
        if (!$personel) return null;

        $tarihObj = Carbon::parse($tarih);

        // 1. Çalışma planını kontrol et — izinli mi?
        $plan = PersonelCalismaPlan::where('personel_id', $personelId)
            ->where('tarih', $tarihObj->format('Y-m-d'))
            ->first();

        if ($plan && in_array($plan->tur, ['izin', 'resmi_tatil', 'tatil'])) {
            // İzinli / tatil günü — PDKS kaydı beklenmez
            return PdksGunlukOzet::updateOrCreate(
                ['personel_id' => $personelId, 'tarih' => $tarihObj->format('Y-m-d')],
                [
                    'firma_id'              => $firmaId,
                    'ilk_giris'             => null,
                    'son_cikis'             => null,
                    'toplam_calisma_suresi' => 0,
                    'durum'                 => $plan->tur === 'izin' ? 'izinli' : 'tatil',
                ]
            );
        }

        // 2. PDKS kayıtlarını çek
        $kayitlar = PdksKayit::where('personel_id', $personelId)
            ->where('firma_id', $firmaId)
            ->whereDate('kayit_tarihi', $tarihObj)
            ->orderBy('kayit_tarihi')
            ->get();

        if ($kayitlar->isEmpty()) {
            // Hiç kayıt yok — gelmedi
            return PdksGunlukOzet::updateOrCreate(
                ['personel_id' => $personelId, 'tarih' => $tarihObj->format('Y-m-d')],
                [
                    'firma_id'              => $firmaId,
                    'ilk_giris'             => null,
                    'son_cikis'             => null,
                    'toplam_calisma_suresi' => 0,
                    'durum'                 => 'gelmedi',
                ]
            );
        }

        // 3. Giriş ve çıkış kayıtlarını belirle
        $girisler = $kayitlar->where('islem_tipi', 'giris');
        $cikislar = $kayitlar->where('islem_tipi', 'cikis');

        $ilkGiris = $girisler->first()?->kayit_tarihi;
        $sonCikis  = $cikislar->last()?->kayit_tarihi;

        // Eksik kart kontrolü
        if (!$ilkGiris && $sonCikis) {
            return PdksGunlukOzet::updateOrCreate(
                ['personel_id' => $personelId, 'tarih' => $tarihObj->format('Y-m-d')],
                [
                    'firma_id'              => $firmaId,
                    'ilk_giris'             => null,
                    'son_cikis'             => Carbon::parse($sonCikis),
                    'toplam_calisma_suresi' => 0,
                    'durum'                 => 'eksik_giris',
                ]
            );
        }

        if ($ilkGiris && !$sonCikis) {
            return PdksGunlukOzet::updateOrCreate(
                ['personel_id' => $personelId, 'tarih' => $tarihObj->format('Y-m-d')],
                [
                    'firma_id'              => $firmaId,
                    'ilk_giris'             => Carbon::parse($ilkGiris),
                    'son_cikis'             => null,
                    'toplam_calisma_suresi' => 0,
                    'durum'                 => 'eksik_cikis',
                ]
            );
        }

        // 4. Puantaj parametresini al
        $parametre = null;
        if ($personel->puantaj_parametre_id) {
            $parametre = GunlukPuantajParametresi::find($personel->puantaj_parametre_id);
        }

        $girisZaman = Carbon::parse($ilkGiris);
        $cikisZaman = Carbon::parse($sonCikis);

        // Toplam çalışma süresi (dakika)
        $toplamDakika = max(0, $girisZaman->diffInMinutes($cikisZaman));

        // Mola düş
        if ($parametre && $parametre->mola_suresi > 0) {
            $toplamDakika = max(0, $toplamDakika - $parametre->mola_suresi);
        }

        // 5. Durum belirle
        $durum = 'geldi';

        if ($parametre) {
            // Mesai başlangıç saati
            $mesaiBaslangic = $this->saatToCarbon($parametre->iceri_giris_saati, $tarihObj);
            $mesaiBitis     = $this->saatToCarbon($parametre->disari_cikis_saati, $tarihObj);

            // Geç gelme toleransı
            $gecTolerans = $this->toleransDakika($parametre->gec_gelme_toleransi);

            // Geç kaldı mı?
            if ($girisZaman->gt($mesaiBaslangic->copy()->addMinutes($gecTolerans))) {
                $durum = 'geç kaldı';
            }

            // Erken çıktı mı?
            $erkenTolerans = $this->toleransDakika($parametre->erken_cikma_toleransi);
            if ($cikisZaman->lt($mesaiBitis->copy()->subMinutes($erkenTolerans))) {
                $durum = 'erken_cikis';
            }

            // Geç gelme cezası uygula
            if ($durum === 'geç kaldı' && $parametre->gec_gelme_cezasi > 0) {
                $toplamDakika = max(0, $toplamDakika - $parametre->gec_gelme_cezasi);
            }

            // Erken çıkma cezası
            if ($durum === 'erken_cikis' && $parametre->erken_cikma_cezasi > 0) {
                $toplamDakika = max(0, $toplamDakika - $parametre->erken_cikma_cezasi);
            }
        } else {
            // Parametre yoksa varsayılan 08:30 mesai başlangıcı
            $varsayilanBaslangic = $tarihObj->copy()->setTime(8, 30);
            if ($girisZaman->gt($varsayilanBaslangic)) {
                $durum = 'geç kaldı';
            }
        }

        // 6. Fazla mesai hesapla (aylık puantaj parametresinden günlük saat al)
        $fazlaMesaiDakika = 0;
        $aylikParam = AylikPuantajParametresi::withoutGlobalScopes()
            ->where('firma_id', $firmaId)
            ->first();

        // Personelin aylık puantaj parametresini bul
        if ($personel->puantaj_parametre_id) {
            $aylikParam = AylikPuantajParametresi::withoutGlobalScopes()->find($personel->puantaj_parametre_id) ?? $aylikParam;
        }

        if ($aylikParam) {
            $standartGunlukDakika = $aylikParam->gunluk_calisma_saati * 60; // 7.5 saat = 450 dk
            if ($toplamDakika > $standartGunlukDakika) {
                $fazlaMesaiDakika = $toplamDakika - $standartGunlukDakika;
            }
        }

        return PdksGunlukOzet::updateOrCreate(
            ['personel_id' => $personelId, 'tarih' => $tarihObj->format('Y-m-d')],
            [
                'firma_id'              => $firmaId,
                'ilk_giris'             => $girisZaman,
                'son_cikis'             => $cikisZaman,
                'toplam_calisma_suresi' => $toplamDakika,
                'fazla_mesai_dakika'    => $fazlaMesaiDakika,
                'durum'                 => $durum,
            ]
        );
    }

    /**
     * Bir personelin bir ay boyunca tüm günlük özetlerini hesaplar.
     */
    public function aylikHesapla(int $personelId, int $yil, int $ay, int $firmaId): int
    {
        $baslangic = Carbon::create($yil, $ay, 1)->startOfMonth();
        $bitis     = $baslangic->copy()->endOfMonth();
        $bugun     = Carbon::today();
        $count     = 0;

        $current = $baslangic->copy();
        while ($current->lte($bitis) && $current->lte($bugun)) {
            $this->gunlukOzetHesapla($personelId, $current->format('Y-m-d'), $firmaId);
            $current->addDay();
            $count++;
        }

        return $count;
    }

    /**
     * Tüm aktif personellerin belirli bir gün için özetlerini hesaplar.
     */
    public function tumPersonellerGunlukHesapla(string $tarih, int $firmaId): int
    {
        $personeller = Personel::withoutGlobalScopes()
            ->where('firma_id', $firmaId)
            ->where('durum', true)
            ->pluck('id');

        $count = 0;
        foreach ($personeller as $pid) {
            $this->gunlukOzetHesapla($pid, $tarih, $firmaId);
            $count++;
        }

        return $count;
    }

    /**
     * İzin silindiğinde çalışma planından da ilgili günleri sil.
     */
    public function izinSilSenkron(PersonelIzin $izin): void
    {
        $baslangic = Carbon::parse($izin->tarih);
        $bitis     = Carbon::parse($izin->bitis_tarihi ?? $izin->tarih);

        PersonelCalismaPlan::where('personel_id', $izin->personel_id)
            ->where('tur', 'izin')
            ->whereBetween('tarih', [$baslangic->format('Y-m-d'), $bitis->format('Y-m-d')])
            ->delete();
    }

    /**
     * Çalışma planında "izin" olarak işaretlenen günü İzin Yönetimi'ne yansıt.
     */
    public function planIzinSenkron(int $personelId, string $tarih, int $firmaId, ?string $aciklama = null): void
    {
        // Zaten bu tarihte bir izin var mı?
        $mevcutIzin = PersonelIzin::where('personel_id', $personelId)
            ->where('tarih', $tarih)
            ->first();

        if ($mevcutIzin) return; // Zaten var, tekrar oluşturma

        PersonelIzin::create([
            'uuid'         => \Illuminate\Support\Str::uuid(),
            'firma_id'     => $firmaId,
            'personel_id'  => $personelId,
            'tarih'        => $tarih,
            'bitis_tarihi' => $tarih,
            'izin_tipi'    => 'gunluk',
            'gun_sayisi'   => 1,
            'aciklama'     => $aciklama ?? 'Çalışma planından otomatik oluşturuldu',
            'durum'        => 'onaylandi',
        ]);
    }

    // ========== HELPER METOTLAR ==========

    /**
     * Saat string'ini (HH:MM:SS veya HH:MM) belirli tarihteki Carbon nesnesine çevirir.
     */
    private function saatToCarbon(?string $saat, Carbon $tarih): Carbon
    {
        if (!$saat) return $tarih->copy()->setTime(8, 30);
        $parts = explode(':', $saat);
        return $tarih->copy()->setTime(
            (int)($parts[0] ?? 8),
            (int)($parts[1] ?? 30),
            (int)($parts[2] ?? 0)
        );
    }

    /**
     * Tolerans saat değerini dakikaya çevirir.
     * Geç gelme toleransı "09:00:00" formatında — bu mesai başlangıcından
     * kaç saat sonrasına kadar geç sayılmayacağı anlamına gelir.
     * Ancak bazı sistemlerde "00:15:00" formatı 15 dakika tolerans anlamına gelir.
     * Burada dakika olarak hesaplıyoruz (saat * 60 + dakika).
     */
    private function toleransDakika(?string $tolerans): int
    {
        if (!$tolerans) return 0;
        $parts = explode(':', $tolerans);
        $saat  = (int)($parts[0] ?? 0);
        $dk    = (int)($parts[1] ?? 0);

        // Eğer saat > 12 ise muhtemelen "22:22" gibi "sınırsız tolerans" — büyük değer dön
        if ($saat >= 12) return 9999;

        return ($saat * 60) + $dk;
    }
}
