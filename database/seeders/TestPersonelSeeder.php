<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestPersonelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $firm = \App\Models\Firma::first();
        if (!$firm) {
            $firm = \App\Models\Firma::create([
                'firma_adi' => 'Test Firması',
                'vergi_no' => '1234567890',
                'durum' => true
            ]);
        }

        $cihaz = \Illuminate\Support\Facades\DB::table('pdks_cihazlari')->first();
        if (!$cihaz) {
            $cihazId = \Illuminate\Support\Facades\DB::table('pdks_cihazlari')->insertGetId([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'firma_id' => $firm->id,
                'seri_no' => 'SERI-' . rand(1000, 9999),
                'cihaz_modeli' => 'Model X',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $cihaz = \Illuminate\Support\Facades\DB::table('pdks_cihazlari')->where('id', $cihazId)->first();
        }

        $names = [
            ['Ahmet', 'Yılmaz'],
            ['Ayşe', 'Kaya'],
            ['Mehmet', 'Demir'],
            ['Fatma', 'Çelik'],
            ['Ali', 'Şahin'],
            ['Zeynep', 'Öztürk'],
            ['Can', 'Yıldız'],
            ['Elif', 'Aydın'],
            ['Burak', 'Doğan'],
            ['Ceren', 'Arslan']
        ];

        foreach ($names as $index => $name) {
            $kart = str_pad($index + 10, 5, '0', STR_PAD_LEFT);
            $sicil = 'SICIL-' . (1000 + $index);
            
            $personel = \App\Models\Personel::create([
                'firma_id' => $firm->id,
                'kart_no' => $kart,
                'ad' => $name[0],
                'soyad' => $name[1],
                'ad_soyad' => $name[0] . ' ' . $name[1],
                'sicil_no' => $sicil,
                'ssk_no' => '34' . rand(10000000, 99999999),
                'unvan' => 'Uzman',
                'sirket' => 'Merkez',
                'bolum' => 'Yazılım',
                'ozel_kod' => 'OZT',
                'departman' => 'AR-GE',
                'servis_kodu' => 'S-01',
                'hesap_gurubu' => 'Beyaz Yaka',
                'agi' => 'Uygulanıyor',
                'aylik_ucret' => rand(45000, 85000),
                'gunluk_ucret' => rand(1500, 2800),
                'saat_1' => 225,
                'saat_2' => 300,
                'saat_3' => 450,
                'giris_tarihi' => '2023-01-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
                'durum' => true,
                'notlar' => "SSK BRÜT: " . number_format(rand(60000, 90000), 2, ',', '.') . "\nSSK NET: " . number_format(rand(45000, 70000), 2, ',', '.') . "\nBu personel sistem tarafindan otomatik test amacli uretilmistir."
            ]);

            $personel = \App\Models\Personel::where('uuid', $personel->uuid)->first();

            \App\Models\PersonelZimmet::create([
                'firma_id' => $firm->id,
                'personel_id' => $personel->id,
                'kategori' => 'Bilgisayar',
                'bolum_adi' => 'Bilgi İşlem',
                'aciklama' => 'MacBook Pro M1 16GB',
                'verilis_tarihi' => '2023-02-01',
            ]);
            
            \App\Models\PersonelZimmet::create([
                'firma_id' => $firm->id,
                'personel_id' => $personel->id,
                'kategori' => 'Telefon',
                'bolum_adi' => 'İletişim',
                'aciklama' => 'iPhone 13 128GB',
                'verilis_tarihi' => '2023-02-15',
            ]);

            \App\Models\PdksKaydi::create([
                'firma_id' => $firm->id,
                'personel_id' => $personel->id,
                'cihaz_id' => $cihaz->id,
                'kayit_tarihi' => '2023-10-01 08:30:00',
                'islem_tipi' => 'Giriş',
                'ham_veri' => json_encode(['raw' => 'GIRIS']),
            ]);

            \App\Models\PdksKaydi::create([
                'firma_id' => $firm->id,
                'personel_id' => $personel->id,
                'cihaz_id' => $cihaz->id,
                'kayit_tarihi' => '2023-10-01 18:00:00',
                'islem_tipi' => 'Çıkış',
                'ham_veri' => json_encode(['raw' => 'CIKIS']),
            ]);

            \App\Models\PersonelIzin::create([
                'firma_id' => $firm->id,
                'personel_id' => $personel->id,
                'tarih' => '2023-11-05',
                'izin_tipi' => 'gunluk',
                'tatil_tipi' => 'Yıllık İzin',
                'giris_saati' => '08:30:00',
                'cikis_saati' => '18:00:00',
                'aciklama' => 'Yıllık izin kullanıldı.',
            ]);

            \App\Models\PersonelAvansKesinti::create([
                'firma_id' => $firm->id,
                'personel_id' => $personel->id,
                'tarih' => '2023-12-15',
                'bordro_alani' => 'Avans',
                'tutar' => rand(1000, 3000),
                'aciklama' => 'Maaş avansı ödemesi',
            ]);

            \App\Models\PersonelPrimKazanc::create([
                'firma_id' => $firm->id,
                'personel_id' => $personel->id,
                'tarih' => '2024-01-05',
                'bordro_alani' => 'Prim',
                'tutar' => rand(2000, 5000),
                'aciklama' => 'Yıl sonu performans primi',
            ]);
        }
    }
}
