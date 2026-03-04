<?php

namespace Database\Seeders;

use App\Models\TanimKodu;
use Illuminate\Database\Seeder;

class DemoTanimKodlariSeeder extends Seeder
{
    public function run(): void
    {
        $firma = \App\Models\Firma::first();
        if (!$firma) {
            $this->command->error('Firma bulunamadı!');
            return;
        }

        $veriler = [
            'sirket' => [
                ['kod' => '0', 'aciklama' => 'ERBAG ELEKTRONİK'],
            ],
            'departman' => [
                ['kod' => '1', 'aciklama' => 'GENEL MÜDÜRLÜK'],
                ['kod' => '2', 'aciklama' => 'İNSAN KAYNAKLARI'],
                ['kod' => '3', 'aciklama' => 'MUHASEBE'],
                ['kod' => '4', 'aciklama' => 'ÜRETİM'],
                ['kod' => '5', 'aciklama' => 'SATIŞ VE PAZARLAMA'],
            ],
            'bolum' => [
                ['kod' => '1', 'aciklama' => 'MUHASEBE'],
                ['kod' => '2', 'aciklama' => 'SATIŞ VE PAZARLAMA'],
                ['kod' => '3', 'aciklama' => 'SEKRETERYA'],
                ['kod' => '4', 'aciklama' => 'GRAFİK TASARIM'],
                ['kod' => '5', 'aciklama' => 'USTA BAŞI'],
                ['kod' => '6', 'aciklama' => 'BASKI ELEMANI'],
                ['kod' => '7', 'aciklama' => 'MONTAJ ELEMANI'],
                ['kod' => '8', 'aciklama' => 'KESİM ELEMANI'],
                ['kod' => '9', 'aciklama' => 'ÇAY-TEMİZLİK'],
                ['kod' => '10', 'aciklama' => 'ŞUBE ELEMANI'],
                ['kod' => '11', 'aciklama' => 'DEPO'],
            ],
            'odeme' => [
                ['kod' => '1', 'aciklama' => 'TEB'],
                ['kod' => '2', 'aciklama' => 'HALKBANK'],
                ['kod' => '3', 'aciklama' => 'ELDEN'],
            ],
            'servis' => [
                ['kod' => '1', 'aciklama' => 'MERKEZ SERVİS'],
                ['kod' => '2', 'aciklama' => 'BÖLGE SERVİS'],
            ],
        ];

        $toplam = 0;
        foreach ($veriler as $tip => $kayitlar) {
            foreach ($kayitlar as $kayit) {
                TanimKodu::updateOrCreate(
                    ['firma_id' => $firma->id, 'tip' => $tip, 'kod' => $kayit['kod']],
                    ['aciklama' => $kayit['aciklama']]
                );
                $toplam++;
            }
        }

        $this->command->info("Demo tanım kodları oluşturuldu: {$toplam} kayıt.");
    }
}
