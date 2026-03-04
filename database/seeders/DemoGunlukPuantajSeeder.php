<?php

namespace Database\Seeders;

use App\Models\Firma;
use App\Models\GunlukPuantajParametresi;
use App\Models\GunlukPuantajBordroAlani;
use Illuminate\Database\Seeder;

class DemoGunlukPuantajSeeder extends Seeder
{
    public function run(): void
    {
        $firmaId = Firma::first()->id ?? 1;

        $parametreler = [
            [
                'ad' => 'HAFTA İÇİ BEYAZ YAKA',
                'gun_donum_saati' => '06:00',
                'iceri_giris_saati' => '08:30',
                'disari_cikis_saati' => '18:00',
                'erken_gelme_toleransi' => '08:00',
                'gec_gelme_toleransi' => '09:00',
                'erken_cikma_toleransi' => '17:30',
                'hesaplama_tipi' => 'normal_toplam',
                'mola_suresi' => 60,
                'gec_gelme_cezasi' => 0,
                'erken_cikma_cezasi' => 0,
                'bordro' => [
                    ['bordro_alani' => 'FAZLA MESAİ %50', 'basla' => '18:00', 'bitis' => '22:00', 'min_sure' => '00:30', 'max_sure' => '04:00', 'ekle' => '00:00', 'carpan' => 150, 'ucret' => 'ucret_1'],
                ],
            ],
            [
                'ad' => 'HAFTA SONU BEYAZ YAKA',
                'gun_donum_saati' => '06:00',
                'iceri_giris_saati' => '08:30',
                'disari_cikis_saati' => '18:00',
                'erken_gelme_toleransi' => '08:00',
                'gec_gelme_toleransi' => '22:22',
                'erken_cikma_toleransi' => '22:22',
                'hesaplama_tipi' => 'normal_toplam',
                'mola_suresi' => 60,
                'gec_gelme_cezasi' => 0,
                'erken_cikma_cezasi' => 0,
                'bordro' => [
                    ['bordro_alani' => 'HAFTA SONU MESAİ %100', 'basla' => '08:30', 'bitis' => '18:00', 'min_sure' => '00:30', 'max_sure' => '22:00', 'ekle' => '00:00', 'carpan' => 200, 'ucret' => 'ucret_1'],
                ],
            ],
            [
                'ad' => 'HAFTA İÇİ MAVİ YAKA',
                'gun_donum_saati' => '06:00',
                'iceri_giris_saati' => '08:00',
                'disari_cikis_saati' => '17:00',
                'erken_gelme_toleransi' => '07:30',
                'gec_gelme_toleransi' => '08:30',
                'erken_cikma_toleransi' => '16:30',
                'hesaplama_tipi' => 'normal_toplam',
                'mola_suresi' => 60,
                'gec_gelme_cezasi' => 0,
                'erken_cikma_cezasi' => 0,
                'bordro' => [
                    ['bordro_alani' => 'FAZLA MESAİ %50', 'basla' => '17:00', 'bitis' => '22:00', 'min_sure' => '00:30', 'max_sure' => '05:00', 'ekle' => '00:00', 'carpan' => 150, 'ucret' => 'ucret_1'],
                ],
            ],
            [
                'ad' => 'HAFTA SONU MAVİ YAKA',
                'gun_donum_saati' => '06:00',
                'iceri_giris_saati' => '08:00',
                'disari_cikis_saati' => '17:00',
                'erken_gelme_toleransi' => '07:30',
                'gec_gelme_toleransi' => '22:22',
                'erken_cikma_toleransi' => '22:22',
                'hesaplama_tipi' => 'normal_toplam',
                'mola_suresi' => 60,
                'gec_gelme_cezasi' => 0,
                'erken_cikma_cezasi' => 0,
                'bordro' => [
                    ['bordro_alani' => 'HAFTA SONU MESAİ %50', 'basla' => '08:00', 'bitis' => '17:00', 'min_sure' => '00:30', 'max_sure' => '22:00', 'ekle' => '00:00', 'carpan' => 150, 'ucret' => 'ucret_1'],
                ],
            ],
            [
                'ad' => 'TELAFİ ÇALIŞMASI',
                'gun_donum_saati' => '06:00',
                'iceri_giris_saati' => '09:00',
                'disari_cikis_saati' => '14:00',
                'erken_gelme_toleransi' => '08:30',
                'gec_gelme_toleransi' => '09:30',
                'erken_cikma_toleransi' => '13:30',
                'hesaplama_tipi' => 'normal_toplam',
                'mola_suresi' => 0,
                'gec_gelme_cezasi' => 0,
                'erken_cikma_cezasi' => 0,
                'bordro' => [],
            ],
        ];

        foreach ($parametreler as $p) {
            $bordroData = $p['bordro'];
            unset($p['bordro']);
            $p['firma_id'] = $firmaId;
            $p['durum'] = true;

            // Firma scope'u devre dışı bırak
            $parametre = GunlukPuantajParametresi::withoutGlobalScopes()
                ->where('firma_id', $firmaId)
                ->where('ad', $p['ad'])
                ->first();

            if ($parametre) {
                $parametre->update($p);
            } else {
                $parametre = GunlukPuantajParametresi::withoutGlobalScopes()->create($p);
            }

            // Refresh ile doğru ID'yi al
            $parametre->refresh();

            foreach ($bordroData as $b) {
                GunlukPuantajBordroAlani::updateOrCreate(
                    ['gunluk_puantaj_id' => $parametre->id, 'bordro_alani' => $b['bordro_alani']],
                    $b
                );
            }
        }
    }
}
