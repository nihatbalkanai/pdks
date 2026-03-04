<?php

namespace Database\Seeders;

use App\Models\Kullanici;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoKullanicilarSeeder extends Seeder
{
    public function run(): void
    {
        // Mevcut firmanın ID'sini bul
        $firma = \App\Models\Firma::first();
        if (!$firma) {
            $this->command->error('Firma bulunamadı! Önce DatabaseSeeder çalıştırın.');
            return;
        }

        $demoKullanicilar = [
            [
                'ad_soyad' => 'Demo Kullanıcı',
                'eposta' => 'kullanici@demo.com',
                'sifre' => Hash::make('123456'),
                'rol' => 'kullanici',
            ],
            [
                'ad_soyad' => 'Demo Muhasebe',
                'eposta' => 'muhasebe@demo.com',
                'sifre' => Hash::make('123456'),
                'rol' => 'muhasebe',
            ],
            [
                'ad_soyad' => 'Demo İK Uzmanı',
                'eposta' => 'ik@demo.com',
                'sifre' => Hash::make('123456'),
                'rol' => 'ik',
            ],
            [
                'ad_soyad' => 'Demo İzleyici',
                'eposta' => 'izleyici@demo.com',
                'sifre' => Hash::make('123456'),
                'rol' => 'izleyici',
            ],
        ];

        foreach ($demoKullanicilar as $data) {
            Kullanici::updateOrCreate(
                ['eposta' => $data['eposta']],
                array_merge($data, ['firma_id' => $firma->id])
            );
        }

        // Mevcut admin kullanıcının rolünü güncelle
        Kullanici::where('firma_id', $firma->id)
            ->where('rol', 'yonetici')
            ->update(['rol' => 'admin']);

        $this->command->info('Demo kullanıcılar oluşturuldu:');
        $this->command->table(
            ['E-Posta', 'Şifre', 'Rol'],
            [
                ['admin@admin.com', 'password', 'Yönetici (admin)'],
                ['kullanici@demo.com', '123456', 'Kullanıcı'],
                ['muhasebe@demo.com', '123456', 'Muhasebe'],
                ['ik@demo.com', '123456', 'İK'],
                ['izleyici@demo.com', '123456', 'İzleyici'],
            ]
        );
    }
}
