<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaketTanimi;

class PaketTanimlariSeeder extends Seeder
{
    public function run(): void
    {
        $paketler = [
            [
                'ad' => 'Ücretsiz',
                'kod' => 'ucretsiz',
                'max_personel' => 10,
                'max_kullanici' => 2,
                'max_cihaz' => 1,
                'aylik_fiyat' => 0,
                'yillik_fiyat' => 0,
                'ozellikler' => ['Temel PDKS', 'Giriş/Çıkış Takibi', 'Basit Raporlar'],
                'aciklama' => 'Küçük işletmeler için ücretsiz başlangıç paketi',
                'renk' => '#94a3b8',
                'sira' => 1,
            ],
            [
                'ad' => 'Standart',
                'kod' => 'standart',
                'max_personel' => 50,
                'max_kullanici' => 5,
                'max_cihaz' => 3,
                'aylik_fiyat' => 299,
                'yillik_fiyat' => 2990,
                'ozellikler' => ['Gelişmiş PDKS', 'İzin Yönetimi', 'Personel Kartları', 'Puantaj Raporları', 'E-posta Bildirimleri'],
                'aciklama' => 'Orta ölçekli işletmeler için ideal paket',
                'renk' => '#3b82f6',
                'sira' => 2,
            ],
            [
                'ad' => 'Pro',
                'kod' => 'pro',
                'max_personel' => 200,
                'max_kullanici' => 15,
                'max_cihaz' => 10,
                'aylik_fiyat' => 599,
                'yillik_fiyat' => 5990,
                'ozellikler' => ['Tam PDKS', 'İzin Yönetimi', 'Maaş Hesaplama', 'Tüm Raporlar', 'Zimmet Takibi', 'SMS Bildirimleri', 'Çoklu Şube'],
                'aciklama' => 'Büyük ölçekli şirketler için profesyonel paket',
                'renk' => '#8b5cf6',
                'sira' => 3,
            ],
            [
                'ad' => 'Enterprise',
                'kod' => 'enterprise',
                'max_personel' => 0, // Sınırsız
                'max_kullanici' => 0,
                'max_cihaz' => 0,
                'aylik_fiyat' => 999,
                'yillik_fiyat' => 9990,
                'ozellikler' => ['Sınırsız Her Şey', 'API Erişimi', 'Özel Entegrasyon', 'Öncelikli Destek', '7/24 Teknik Destek', 'Özel Eğitim', 'SLA Garantisi'],
                'aciklama' => 'Kurumsal firmalara özel, sınırsız ve tam destek paketi',
                'renk' => '#f59e0b',
                'sira' => 4,
            ],
        ];

        foreach ($paketler as $paket) {
            PaketTanimi::updateOrCreate(
                ['kod' => $paket['kod']],
                $paket
            );
        }
    }
}
