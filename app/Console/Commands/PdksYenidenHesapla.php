<?php

namespace App\Console\Commands;

use App\Services\PdksHesaplamaServisi;
use App\Models\Personel;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * PDKS günlük özetlerini puantaj parametrelerine göre yeniden hesaplar.
 * Kullanım: php artisan pdks:yeniden-hesapla
 * Seçenekler:
 *   --ay=3 --yil=2026   → Belirli bir ay için
 *   --personel=5        → Belirli bir personel için
 *   --tarih=2026-03-05  → Belirli bir gün için
 */
class PdksYenidenHesapla extends Command
{
    protected $signature = 'pdks:yeniden-hesapla
                            {--firma=1 : Firma ID}
                            {--ay= : Hesaplanacak ay (1-12)}
                            {--yil= : Hesaplanacak yıl}
                            {--personel= : Belirli personel ID}
                            {--tarih= : Belirli bir tarih (Y-m-d)}';

    protected $description = 'PDKS günlük özetlerini puantaj parametrelerine göre yeniden hesaplar';

    public function handle(): int
    {
        $servis  = new PdksHesaplamaServisi();
        $firmaId = (int) $this->option('firma');

        // Belirli bir tarih
        if ($tarih = $this->option('tarih')) {
            if ($personelId = $this->option('personel')) {
                $this->info("Personel #{$personelId} için {$tarih} hesaplanıyor...");
                $servis->gunlukOzetHesapla((int) $personelId, $tarih, $firmaId);
                $this->info("✅ Tamamlandı.");
                return 0;
            }

            $count = $servis->tumPersonellerGunlukHesapla($tarih, $firmaId);
            $this->info("✅ {$tarih} için {$count} personel hesaplandı.");
            return 0;
        }

        // Aylık hesaplama
        $yil = (int) ($this->option('yil') ?? Carbon::now()->year);
        $ay  = (int) ($this->option('ay') ?? Carbon::now()->month);

        if ($personelId = $this->option('personel')) {
            $this->info("Personel #{$personelId} için {$ay}/{$yil} hesaplanıyor...");
            $count = $servis->aylikHesapla((int) $personelId, $yil, $ay, $firmaId);
            $this->info("✅ {$count} gün hesaplandı.");
            return 0;
        }

        // Tüm aktif personeller
        $personeller = Personel::withoutGlobalScopes()
            ->where('firma_id', $firmaId)
            ->where('durum', true)
            ->get();

        $bar = $this->output->createProgressBar($personeller->count());
        $bar->start();

        $toplamGun = 0;
        foreach ($personeller as $p) {
            $toplamGun += $servis->aylikHesapla($p->id, $yil, $ay, $firmaId);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ {$personeller->count()} personel, {$toplamGun} gün hesaplandı.");

        return 0;
    }
}
