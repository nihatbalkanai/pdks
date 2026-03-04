<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PdksCihazi;
use App\Models\Kullanici;
use App\Notifications\CihazCevrimdisiNotification;
use Carbon\Carbon;

class CihazSaglikKontrolu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdks:cihaz-kontrol';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Son 10 dakikadır veri göndermeyen cihazları tespit edip firma yetkililerini uyarır.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 10 Dakika önce
        $zamanSiniri = now()->subMinutes(10);

        // Global scopeları devre dışı bırakarak tüm cihazları komut aracılığıyla tara
        $cihazlar = PdksCihazi::withoutGlobalScopes()
            // Sadece sinyal alan ve zaman limiti öncesinde sinyali kalmış olan cihazlar
            ->whereNotNull('son_aktivite_tarihi')
            ->where('son_aktivite_tarihi', '<', $zamanSiniri)
            ->get();

        if ($cihazlar->isEmpty()) {
            $this->info('Tüm cihazlar aktif veya incelenecek cihaz yok.');
            return;
        }

        foreach ($cihazlar as $cihaz) {
            // İlan edelim (Terminal logu)
            $this->warn("Cihaz pasif! Seri No: {$cihaz->seri_no}");

            // Bu firmaya ait kullanıcılara bildirim yollayalım (Örneğin Rolü Yönetici olanlar)
            // Biz şimdilik tüm firma kullanıcılarına ya da en az 1 yöneticiye gönderelim
            $firmaKullanicilari = Kullanici::withoutGlobalScopes()
                ->where('firma_id', $cihaz->firma_id)
                ->get();

            foreach ($firmaKullanicilari as $user) {
                // Bildirim gönder
                $user->notify(new CihazCevrimdisiNotification($cihaz->seri_no));
            }
        }

        $this->info("Sağlık kontrolü bitti. Toplam: {$cihazlar->count()} cihaz uyarısı.");
    }
}
