<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaketSeeder extends Seeder
{
    public function run(): void
    {
        $baslangic = \App\Models\Paket::create([
            'paket_adi' => 'Başlangıç',
            'fiyat' => 499.00,
            'ozellikler' => ['personel_yonetimi', 'cihaz_yonetimi', 'basit_raporlar']
        ]);

        $kurumsal = \App\Models\Paket::create([
            'paket_adi' => 'Kurumsal',
            'fiyat' => 1499.00,
            'ozellikler' => ['personel_yonetimi', 'cihaz_yonetimi', 'basit_raporlar', 'şube_yönetimi', 'servis_takibi', 'iletisim_motoru']
        ]);

        $enterprise = \App\Models\Paket::create([
            'paket_adi' => 'Enterprise',
            'fiyat' => 4999.00,
            'ozellikler' => ['*'] // Enterprise hepsi açık
        ]);

        // Mevcut tüm firmaları rastgele paketlere dağıt (Sistemde data varsa)
        \App\Models\Firma::chunk(500, function ($firmalar) use ($baslangic, $kurumsal, $enterprise) {
            $paketler = [$baslangic->id, $kurumsal->id, $enterprise->id];
            foreach ($firmalar as $firma) {
                // Sadece paket atanmamışlara ata (eğer varsa)
                if(!$firma->paket_id) {
                    $firma->paket_id = $paketler[array_rand($paketler)];
                    $firma->save();
                }
            }
        });
    }
}
