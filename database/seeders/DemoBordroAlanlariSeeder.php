<?php

namespace Database\Seeders;

use App\Models\BordroAlani;
use App\Models\Firma;
use Illuminate\Database\Seeder;

class DemoBordroAlanlariSeeder extends Seeder
{
    public function run(): void
    {
        $firmaId = Firma::first()->id ?? 1;

        $alanlar = [
            ['kod' => 1,  'aciklama' => 'NORMAL ÇALIŞMA',    'gun' => true,  'saat' => true,  'ucret' => true,  'bordro_tipi' => 'normal_calisma'],
            ['kod' => 2,  'aciklama' => 'FAZLA MESAİ %50',   'gun' => false, 'saat' => true,  'ucret' => true,  'bordro_tipi' => 'fazla_mesai'],
            ['kod' => 3,  'aciklama' => 'FAZLA MESAİ %100',  'gun' => false, 'saat' => true,  'ucret' => true,  'bordro_tipi' => 'fazla_mesai'],
            ['kod' => 4,  'aciklama' => 'DEVAMSIZLIK',        'gun' => true,  'saat' => true,  'ucret' => true,  'bordro_tipi' => 'bilgi'],
            ['kod' => 5,  'aciklama' => 'YILLIK İZİN',       'gun' => true,  'saat' => true,  'ucret' => true,  'bordro_tipi' => 'bilgi'],
            ['kod' => 6,  'aciklama' => 'ÜCRETSİZ İZİN',     'gun' => true,  'saat' => true,  'ucret' => true,  'bordro_tipi' => 'bilgi'],
            ['kod' => 7,  'aciklama' => 'AVANS',              'gun' => false, 'saat' => false, 'ucret' => true,  'bordro_tipi' => 'diger_hesaplar_eksi'],
            ['kod' => 8,  'aciklama' => 'HAFTA TATİLİ',       'gun' => true,  'saat' => true,  'ucret' => true,  'bordro_tipi' => 'normal_ek_mesai'],
            ['kod' => 9,  'aciklama' => 'PRİM',               'gun' => false, 'saat' => false, 'ucret' => false, 'bordro_tipi' => 'diger_hesaplar_arti'],
            ['kod' => 10, 'aciklama' => 'YOL PARASI',         'gun' => true,  'saat' => true,  'ucret' => true,  'bordro_tipi' => 'diger_hesaplar_arti'],
            ['kod' => 11, 'aciklama' => 'CEZA',               'gun' => true,  'saat' => true,  'ucret' => true,  'bordro_tipi' => 'diger_hesaplar_eksi'],
            ['kod' => 12, 'aciklama' => 'DEVREDİLEN',         'gun' => false, 'saat' => false, 'ucret' => false, 'bordro_tipi' => 'diger_hesaplar_arti'],
        ];

        foreach ($alanlar as $alan) {
            BordroAlani::withoutGlobalScopes()->updateOrCreate(
                ['firma_id' => $firmaId, 'kod' => $alan['kod']],
                array_merge($alan, ['firma_id' => $firmaId])
            );
        }
    }
}
