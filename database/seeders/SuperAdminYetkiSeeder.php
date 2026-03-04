<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminYetkiSeeder extends Seeder
{
    public function run(): void
    {
        // Sistemdeki mevcut admin kullanıcılarını bul
        $admins = \App\Models\Kullanici::where('rol', 'admin')->get();

        foreach ($admins as $admin) {
            \App\Models\SuperAdminYetki::updateOrCreate(
                ['kullanici_id' => $admin->id],
                ['yetkiler' => ['*']] // Tam yetki: ['*']
            );
        }
    }
}
