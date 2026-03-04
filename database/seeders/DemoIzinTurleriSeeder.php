<?php

namespace Database\Seeders;

use App\Models\IzinTuru;
use App\Models\Firma;
use Illuminate\Database\Seeder;

class DemoIzinTurleriSeeder extends Seeder
{
    public function run(): void
    {
        $firmaId = Firma::first()->id ?? 1;

        // Türk İş Kanunu'na göre izin türleri
        // hafta_sonu_haric_mi: true → Hafta sonu günleri izin gün sayısından hariç (İş Kanunu m.56)
        // resmi_tatil_haric_mi: true → Resmi tatil günleri izin gün sayısından hariç (İş Kanunu m.56)
        // Rapor/Hastalık izinleri takvim günü olarak sayılır (SGK kuralı)

        $turler = [
            // Yıllık İzin — İş Kanunu m.53-56: Hafta sonu ve resmi tatiller hariç
            ['ad' => 'Yıllık İzin',        'ucret_kesintisi_yapilacak_mi' => false, 'yillik_izinden_duser_mi' => true,  'hafta_sonu_haric_mi' => true,  'resmi_tatil_haric_mi' => true,  'max_gun' => null],

            // Ücretsiz İzin — İş Kanunu m.56/4: Takvim günü (ücret kesilir)
            ['ad' => 'Ücretsiz İzin',       'ucret_kesintisi_yapilacak_mi' => true,  'yillik_izinden_duser_mi' => false, 'hafta_sonu_haric_mi' => false, 'resmi_tatil_haric_mi' => false, 'max_gun' => null],

            // Mazeret İzni — İş Kanunu m.46: Genellikle 1-3 gün, takvim günü
            ['ad' => 'Mazeret İzni',         'ucret_kesintisi_yapilacak_mi' => false, 'yillik_izinden_duser_mi' => false, 'hafta_sonu_haric_mi' => false, 'resmi_tatil_haric_mi' => false, 'max_gun' => 3],

            // Rapor (Sağlık) — SGK: Takvim günü olarak sayılır (hafta sonu dahil)
            ['ad' => 'Rapor (Sağlık)',       'ucret_kesintisi_yapilacak_mi' => false, 'yillik_izinden_duser_mi' => false, 'hafta_sonu_haric_mi' => false, 'resmi_tatil_haric_mi' => false, 'max_gun' => null],

            // Doğum İzni — İş Kanunu m.74: 16 hafta (8+8), takvim günü
            ['ad' => 'Doğum İzni',           'ucret_kesintisi_yapilacak_mi' => false, 'yillik_izinden_duser_mi' => false, 'hafta_sonu_haric_mi' => false, 'resmi_tatil_haric_mi' => false, 'max_gun' => 112],

            // Babalık İzni — İş Kanunu m.13/2 ve Toplu İş Sözleşmesi: 5 gün, takvim günü
            ['ad' => 'Babalık İzni',         'ucret_kesintisi_yapilacak_mi' => false, 'yillik_izinden_duser_mi' => false, 'hafta_sonu_haric_mi' => false, 'resmi_tatil_haric_mi' => false, 'max_gun' => 5],

            // Evlilik İzni — Toplu İş Sözleşmesi: 3 gün, takvim günü
            ['ad' => 'Evlilik İzni',         'ucret_kesintisi_yapilacak_mi' => false, 'yillik_izinden_duser_mi' => false, 'hafta_sonu_haric_mi' => false, 'resmi_tatil_haric_mi' => false, 'max_gun' => 3],

            // Ölüm İzni — Toplu İş Sözleşmesi: 3 gün, takvim günü
            ['ad' => 'Ölüm İzni',            'ucret_kesintisi_yapilacak_mi' => false, 'yillik_izinden_duser_mi' => false, 'hafta_sonu_haric_mi' => false, 'resmi_tatil_haric_mi' => false, 'max_gun' => 3],

            // Süt İzni — İş Kanunu m.74: Günlük 1.5 saat, saatlik izin
            ['ad' => 'Süt İzni',             'ucret_kesintisi_yapilacak_mi' => false, 'yillik_izinden_duser_mi' => false, 'hafta_sonu_haric_mi' => false, 'resmi_tatil_haric_mi' => false, 'max_gun' => null],

            // Devamsızlık — Ücret kesilir, takvim günü
            ['ad' => 'Devamsızlık',          'ucret_kesintisi_yapilacak_mi' => true,  'yillik_izinden_duser_mi' => false, 'hafta_sonu_haric_mi' => false, 'resmi_tatil_haric_mi' => false, 'max_gun' => null],

            // Telafi Çalışması — İş Kanunu m.64: Max 2 ay içinde telafi
            ['ad' => 'Telafi Çalışması',     'ucret_kesintisi_yapilacak_mi' => false, 'yillik_izinden_duser_mi' => false, 'hafta_sonu_haric_mi' => false, 'resmi_tatil_haric_mi' => false, 'max_gun' => null],

            // İdari İzin — İşveren kararı, takvim günü
            ['ad' => 'İdari İzin',           'ucret_kesintisi_yapilacak_mi' => false, 'yillik_izinden_duser_mi' => false, 'hafta_sonu_haric_mi' => false, 'resmi_tatil_haric_mi' => false, 'max_gun' => null],
        ];

        foreach ($turler as $tur) {
            IzinTuru::withoutGlobalScopes()->updateOrCreate(
                ['firma_id' => $firmaId, 'ad' => $tur['ad']],
                array_merge($tur, ['firma_id' => $firmaId, 'aktif_mi' => true])
            );
        }
    }
}
