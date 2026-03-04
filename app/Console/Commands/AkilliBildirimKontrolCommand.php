<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AkilliBildirimKontrolCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdks:akilli-bildirim';

    protected $description = 'Firmaların akıllı bildirim kurallarını kontrol eder ve gecikenleri TechSend üzerinden veya lokal log olarak haber verir.';

    public function handle()
    {
        $suan = now()->format('H:i');
        
        // Şu anki tetiklenme saatinde aktif olan kuralları çek
        $kurallar = \App\Models\BildirimKurali::withoutGlobalScopes()
            ->with(['firma' => function ($query) { $query->withoutGlobalScopes(); }])
            ->where('durum', true)
            ->whereRaw("TIME_FORMAT(tetikleme_saati, '%H:%i') = ?", [$suan])
            ->get();

        foreach ($kurallar as $kural) {
            $firmaId = $kural->firma_id;
            
            $aranacakDurum = $kural->kural_tipi; // 'gelmedi', 'geç kaldı'
            
            // Günlük özetlerden bu personelleri bul
            $personeller = \App\Models\PdksGunlukOzet::withoutGlobalScopes()
                ->where('firma_id', $firmaId)
                ->where('tarih', now()->toDateString())
                ->where('durum', $aranacakDurum)
                ->get();
            
            if ($personeller->isEmpty()) {
                continue;
            }

            // Personel id'leri ile adları bul
            $personelIdler = $personeller->pluck('personel_id')->toArray();
            $personelKayitlari = \App\Models\Personel::withoutGlobalScopes()
                ->whereIn('id', $personelIdler)
                ->get();

            $personelİsimleri = $personelKayitlari->pluck('ad_soyad')->implode(', ');
            $mesaj = "PDKS Bildirimi ({$kural->firma->firma_adi}): DURUM [{$aranacakDurum}] => {$personelİsimleri}";

            // Mesaj ayarları Job içine dispatch edilir
            if ($kural->alici_telefon) {
                \App\Jobs\BildirimGonderJob::dispatch($firmaId, 'sms', $kural->alici_telefon, $mesaj);
            }
            if ($kural->alici_eposta) {
                \App\Jobs\BildirimGonderJob::dispatch($firmaId, 'email', $kural->alici_eposta, $mesaj);
            }
        }

        $this->info("Akıllı bildirim kontrolü [$suan] başarıyla tarandı.");
    }
}
