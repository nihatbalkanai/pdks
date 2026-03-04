<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StresTestiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Stres Testi Hazırlığı Başlıyor: 10 Firm, 10 Cihaz, 2.000 Personel ve 50.000 Kayıt...');
        
        // 1. Firmalar
        $firmalar = [];
        for ($i = 0; $i < 10; $i++) {
            $firmalar[] = \App\Models\Firma::create([
                'firma_adi' => 'Stres Firması ' . ($i + 1),
                'vergi_no' => rand(1000000000, 9999999999),
                'vergi_dairesi' => 'Merkez',
                'adres' => 'Stres Test Adresi',
                'durum' => true,
            ])->id;
        }

        // 2. Cihazlar
        $cihazlar = [];
        foreach ($firmalar as $firmaId) {
            $cihaz = \App\Models\PdksCihazi::create([
                'firma_id' => $firmaId,
                'seri_no' => 'DEV-' . rand(1000, 9999) . '-' . $firmaId,
                'cihaz_modeli' => 'Stres Z-X1',
            ]);
            // Cihaz için Sanctum Token Üretimi
            $cihaz->createToken('cihaz-token')->plainTextToken;
            $cihazlar[] = $cihaz->id;
        }

        // 3. Personeller (2000 adet)
        $personeller = [];
        for ($i = 0; $i < 2000; $i++) {
            $personel = \App\Models\Personel::create([
                'firma_id' => $firmalar[array_rand($firmalar)],
                'ad_soyad' => 'Test Personel ' . $i,
                'sicil_no' => 'SCL' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'bolum' => 'Üretim',
                'durum' => 'aktif',
            ]);
            $personeller[] = $personel;
        }

        $this->command->info('Ana veriler oluşturuldu. Şimdi 50.000 satır giriş-çıkış logu basılıyor (Chunk mantığı ile)...');

        // 4. Stres Kayıtları (50.000 Kayıt, Chunk 5000'er adet DB yormamak için DB Builder)
        $types = ['giriş', 'çıkış'];
        $chunkSize = 5000;
        
        // Disable Model Events and Scopes for pure DB inserts to make it incredibly fast
        for ($chunk = 0; $chunk < 10; $chunk++) { // 10 * 5000 = 50.000
            $veriler = [];
            for ($i = 0; $i < $chunkSize; $i++) {
                $randomPers = $personeller[array_rand($personeller)];
                $tarih = now()->subDays(rand(0, 30))->addHours(rand(0, 15))->addMinutes(rand(0, 59));
                
                $veriler[] = [
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'firma_id' => $randomPers->firma_id,
                    'cihaz_id' => $cihazlar[array_rand($cihazlar)],
                    'personel_id' => $randomPers->id,
                    'islem_tipi' => $types[array_rand($types)],
                    'ham_veri' => json_encode(['stres_test_veri' => true]),
                    'kayit_tarihi' => $tarih,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            \Illuminate\Support\Facades\DB::table('pdks_kayitlari')->insert($veriler);
            $this->command->info("5.000 satır işlendi... (Toplam: " . (($chunk + 1) * 5000) . ")");
        }

        $this->command->info('10.000 firma verisi (Simüle) boyutuna ölçeklenebilecek Stres Seeder Başarıyla Tamamlandı!');
    }
}
