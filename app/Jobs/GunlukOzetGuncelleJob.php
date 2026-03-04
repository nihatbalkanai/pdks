<?php

namespace App\Jobs;

use App\Models\PdksKayit;
use App\Models\PdksGunlukOzet;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GunlukOzetGuncelleJob implements ShouldQueue
{
    use Queueable;

    protected $pdksKayit;

    /**
     * Yeni bir job nesnesi oluştur.
     */
    public function __construct(PdksKayit $pdksKayit)
    {
        $this->pdksKayit = $pdksKayit;
    }

    /**
     * İşlemi uygula.
     */
    public function handle(): void
    {
        try {
            // Tarihi sadece gün, ay, yıl formatında al
            $tarih = Carbon::parse($this->pdksKayit->kayit_tarihi)->toDateString();

            // Günlük özet tablosunda personelin o güne ait kaydı var mı diye bul, yoksa oluştur.
            // Global bazda erişim yapılması gerekebilir çünkü queue dışarıdan asenkron çalışır (Auth user yoktur).
            $ozet = PdksGunlukOzet::withoutGlobalScopes()->firstOrCreate([
                'firma_id' => $this->pdksKayit->firma_id,
                'personel_id' => $this->pdksKayit->personel_id,
                'tarih' => $tarih,
            ], [
                // Varsayılan ilk değerler (sadece yeni oluşturuluyorsa)
                'durum' => 'geldi'
            ]);

            // İşlem tipine göre giriş veya çıkışı güncelle
            if ($this->pdksKayit->islem_tipi === 'giriş') {
                if (is_null($ozet->ilk_giris) || $this->pdksKayit->kayit_tarihi < $ozet->ilk_giris) {
                    $ozet->ilk_giris = $this->pdksKayit->kayit_tarihi;
                    
                    // Geç kalma kontrolü (Örn: 08:30'dan sonraysa geç kaldı say)
                    $girisSaati = Carbon::parse($this->pdksKayit->kayit_tarihi);
                    $mesaiBaslangic = Carbon::parse($tarih . ' 08:30:00');
                    if ($girisSaati->gt($mesaiBaslangic)) {
                        $ozet->durum = 'geç kaldı';
                    }
                }
            } elseif ($this->pdksKayit->islem_tipi === 'çıkış') {
                if (is_null($ozet->son_cikis) || $this->pdksKayit->kayit_tarihi > $ozet->son_cikis) {
                    $ozet->son_cikis = $this->pdksKayit->kayit_tarihi;
                }
            }

            // Toplam çalışma süresini dakika bazında hesapla (Eğer ilk giriş ve son çıkış varsa)
            if (!is_null($ozet->ilk_giris) && !is_null($ozet->son_cikis)) {
                $giris = Carbon::parse($ozet->ilk_giris);
                $cikis = Carbon::parse($ozet->son_cikis);
                $ozet->toplam_calisma_suresi = $giris->diffInMinutes($cikis);
            }

            $ozet->save();

        } catch (\Exception $e) {
            Log::error('Günlük özet oluşturulurken/güncellenirken hata oluştu: ' . $e->getMessage());
        }
    }
}
