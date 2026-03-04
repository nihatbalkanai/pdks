<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Kullanici;
use App\Notifications\RaporHazirNotification;
use Illuminate\Support\Facades\Log;

class ExcelRaporOlusturJob implements ShouldQueue
{
    use Queueable;

    protected $kullaniciId;
    protected $filtreler;

    /**
     * @param int $kullaniciId İsteği yapan kişi
     * @param array $filtreler Forma ait filtreler
     */
    public function __construct(int $kullaniciId, array $filtreler)
    {
        $this->kullaniciId = $kullaniciId;
        $this->filtreler = $filtreler;
    }

    /**
     * İşlemi gerçekleştir.
     */
    public function handle(): void
    {
        try {
            $kullanici = Kullanici::find($this->kullaniciId);
            if (!$kullanici) return;

            $dosyaAdi = 'pdks_rapor_' . date('Y_m_d_H_i') . '_' . \Illuminate\Support\Str::random(4) . '.xlsx';
            
            $sorgu = \App\Models\PdksGunlukOzet::with('personel')
                ->whereBetween('tarih', [
                    $this->filtreler['baslangic'] ?? now()->startOfMonth()->toDateString(), 
                    $this->filtreler['bitis'] ?? now()->endOfMonth()->toDateString()
                ]);

            if (!empty($this->filtreler['personel_id'])) {
                $sorgu->where('personel_id', $this->filtreler['personel_id']);
            }

            if (!empty($this->filtreler['bolum'])) {
                $bolum = $this->filtreler['bolum'];
                $sorgu->whereHas('personel', function($q) use ($bolum) {
                    $q->where('bolum', $bolum);
                });
            }

            \Maatwebsite\Excel\Facades\Excel::store(new \App\Exports\RaporExport($sorgu), 'public/' . $dosyaAdi);

            $dosyaYolu = '/storage/' . $dosyaAdi;

            // Kullanıcıya hem veritabanı üzerinden hem de Echo üzerinden (broadcast) bildirim gönder
            $kullanici->notify(new RaporHazirNotification($dosyaYolu));

        } catch (\Exception $e) {
            Log::error('Rapor oluşturulamadı: ' . $e->getMessage());
        }
    }
}
