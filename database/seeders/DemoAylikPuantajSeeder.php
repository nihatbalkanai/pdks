<?php

namespace Database\Seeders;

use App\Models\AylikPuantajParametresi;
use App\Models\Firma;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoAylikPuantajSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $firmaId = Firma::first()->id ?? 1;

        $parametreler = [
            [
                'firma_id' => $firmaId,
                'hesap_parametresi_adi' => 'BEYAZ YAKA (Aylık/Sabit)',
                'aylik_calisma_saati' => 225,
                'haftalik_calisma_saati' => 45,
                'gunluk_calisma_saati' => 7.5,
                'eksik_gun_kesintisi_yapilacak_mi' => true,
                'fazla_mesai_carpani' => 1.5,
                'tatil_mesai_carpani' => 2.0,
                'resmi_tatil_mesai_carpani' => 2.0,
                'durum' => true,
            ],
            [
                'firma_id' => $firmaId,
                'hesap_parametresi_adi' => 'MAVİ YAKA (Saatlik/Vardiyalı)',
                'aylik_calisma_saati' => 225,
                'haftalik_calisma_saati' => 45,
                'gunluk_calisma_saati' => 7.5,
                'eksik_gun_kesintisi_yapilacak_mi' => true,
                'fazla_mesai_carpani' => 1.5,
                'tatil_mesai_carpani' => 1.5, // Mavi yaka Cumartesi vb duruma göre bazen x1.5 olur
                'resmi_tatil_mesai_carpani' => 2.0,
                'durum' => true,
            ],
            [
                'firma_id' => $firmaId,
                'hesap_parametresi_adi' => 'PART-TIME (Yarı Zamanlı)',
                'aylik_calisma_saati' => 120, // 30 saat haftalık x 4
                'haftalik_calisma_saati' => 30,
                'gunluk_calisma_saati' => 6.0, // Günlük 6 saatten 5 gün veya vs
                'eksik_gun_kesintisi_yapilacak_mi' => true,
                'fazla_mesai_carpani' => 1.5,
                'tatil_mesai_carpani' => 1.5,
                'resmi_tatil_mesai_carpani' => 2.0,
                'durum' => true,
            ],
            [
                'firma_id' => $firmaId,
                'hesap_parametresi_adi' => 'ÖZEL GÜVENLİK (12/24)',
                'aylik_calisma_saati' => 240, // Fazlası mesai
                'haftalik_calisma_saati' => 60,
                'gunluk_calisma_saati' => 12.0,
                'eksik_gun_kesintisi_yapilacak_mi' => true,
                'fazla_mesai_carpani' => 1.5,
                'tatil_mesai_carpani' => 2.0,
                'resmi_tatil_mesai_carpani' => 2.0,
                'durum' => true,
            ],
        ];

        // Firma scope bypass ediyoruz sadece yaratırken veya firma_id vererek
        foreach ($parametreler as $parametre) {
            AylikPuantajParametresi::withoutGlobalScopes()->updateOrCreate(
                [
                    'firma_id' => $firmaId, 
                    'hesap_parametresi_adi' => $parametre['hesap_parametresi_adi']
                ],
                $parametre
            );
        }
    }
}
