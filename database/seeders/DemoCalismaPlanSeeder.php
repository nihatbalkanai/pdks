<?php

use Illuminate\Database\Seeder;
use App\Models\Vardiya;
use App\Models\CalismaGrubu;
use App\Models\CalismaPlan;
use App\Models\Firma;
use Carbon\Carbon;

class DemoCalismaPlanSeeder extends Seeder
{
    public function run(): void
    {
        $firma = Firma::first();
        if (!$firma) return;

        // Demo Vardiyalar
        $vardiyalar = [
            ['ad' => 'HAFTA İÇİ MAVİ YAKA',  'baslangic_saati' => '08:00', 'bitis_saati' => '17:00', 'renk' => '#3B82F6'],
            ['ad' => 'HAFTA SONU MAVİ YAKA',  'baslangic_saati' => '08:00', 'bitis_saati' => '13:00', 'renk' => '#60A5FA'],
            ['ad' => 'HAFTA İÇİ BEYAZ YAKA', 'baslangic_saati' => '09:00', 'bitis_saati' => '18:00', 'renk' => '#10B981'],
            ['ad' => 'GECE VARDİYASI',        'baslangic_saati' => '22:00', 'bitis_saati' => '06:00', 'renk' => '#6366F1'],
        ];

        $vardiyaIdMap = [];
        foreach ($vardiyalar as $v) {
            $bas = Carbon::createFromFormat('H:i', $v['baslangic_saati']);
            $bit = Carbon::createFromFormat('H:i', $v['bitis_saati']);
            $sure = abs($bit->diffInMinutes($bas));

            $vardiya = Vardiya::updateOrCreate(
                ['firma_id' => $firma->id, 'ad' => $v['ad']],
                [
                    'baslangic_saati' => $v['baslangic_saati'],
                    'bitis_saati'     => $v['bitis_saati'],
                    'toplam_sure'     => $sure,
                    'renk'            => $v['renk'],
                    'durum'           => true,
                ]
            );
            $vardiyaIdMap[$v['ad']] = $vardiya->id;
        }

        // Demo Çalışma Grupları
        $gruplar = [
            ['aciklama' => 'MAVİ YAKA',  'hesap_parametresi' => 'GENEL AYLIK PAR.', 'hafta_ici' => 'HAFTA İÇİ MAVİ YAKA',  'hafta_sonu' => 'HAFTA SONU MAVİ YAKA'],
            ['aciklama' => 'BEYAZ YAKA', 'hesap_parametresi' => 'GENEL AYLIK PAR.', 'hafta_ici' => 'HAFTA İÇİ BEYAZ YAKA', 'hafta_sonu' => null],
        ];

        foreach ($gruplar as $g) {
            $grup = CalismaGrubu::updateOrCreate(
                ['firma_id' => $firma->id, 'aciklama' => $g['aciklama']],
                ['hesap_parametresi' => $g['hesap_parametresi'], 'durum' => true]
            );

            // 2025 yılı çalışma planı oluştur
            $yil = 2025;
            $baslangic = Carbon::create($yil, 1, 1);
            $bitis     = Carbon::create($yil, 12, 31);

            $current = $baslangic->copy();
            while ($current->lte($bitis)) {
                $vardiyaAd = $current->isWeekday() ? $g['hafta_ici'] : $g['hafta_sonu'];
                $vardiyaId = $vardiyaAd ? ($vardiyaIdMap[$vardiyaAd] ?? null) : null;

                CalismaPlan::updateOrCreate(
                    ['calisma_grubu_id' => $grup->id, 'tarih' => $current->format('Y-m-d')],
                    [
                        'firma_id'   => $firma->id,
                        'vardiya_id' => $vardiyaId,
                        'tur'        => $current->isWeekend() ? 'tatil' : 'is_gunu',
                    ]
                );
                $current->addDay();
            }

            echo "'{$g['aciklama']}' grubu için 2025 planı oluşturuldu.\n";
        }
    }
}
