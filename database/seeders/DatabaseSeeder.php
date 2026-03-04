<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Örnek bir firma oluştur
        $firma = \App\Models\Firma::create([
            'firma_adi' => 'Demo Şirketi A.Ş.',
            'vergi_no' => '1234567890',
            'vergi_dairesi' => 'Merkez',
            'adres' => 'Örnek Mah. Test Sok. No:1',
            'durum' => true,
        ]);

        // 2. Bu firmaya bağlı bir yönetici kullanıcı oluştur
        \App\Models\Kullanici::create([
            'firma_id' => $firma->id,
            'ad_soyad' => 'Sistem Yöneticisi',
            'eposta' => 'admin@admin.com',
            'sifre' => bcrypt('password'), // Şifre: password
            'rol' => 'yonetici',
        ]);
        
        $this->command->info('Örnek firma ve yönetici kullanıcısı (admin@admin.com / password) oluşturuldu.');
    }
}
