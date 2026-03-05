<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Demo rapor verileri oluşturur.
 * Mevcut 10 personel üzerinden 30 günlük PDKS kayıtları,
 * günlük özetler ve izin verileri üretir.
 */
class DemoRaporVerisiSeeder extends Seeder
{
    public function run(): void
    {
        $firmaId = 1;

        // Mevcut personelleri çek
        $personeller = DB::table('personeller')
            ->where('firma_id', $firmaId)
            ->whereNull('deleted_at')
            ->get();

        if ($personeller->isEmpty()) {
            $this->command->error('Personel bulunamadı! Önce personel ekleyin.');
            return;
        }

        // Mevcut bir cihaz ID'si bul veya oluştur
        $cihazId = DB::table('pdks_cihazlari')->where('firma_id', $firmaId)->value('id');
        if (!$cihazId) {
            $cihazId = DB::table('pdks_cihazlari')->insertGetId([
                'uuid'       => Str::uuid(),
                'firma_id'   => $firmaId,
                'cihaz_adi'  => 'Demo PDKS Cihazı',
                'seri_no'    => 'DEMO-001',
                'konum'      => 'Ana Giriş',
                'durum'      => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Eski demo verileri temizle
        DB::table('pdks_kayitlari')->where('firma_id', $firmaId)->delete();
        DB::table('pdks_gunluk_ozetler')->where('firma_id', $firmaId)->delete();

        $this->command->info('Eski veriler temizlendi. Demo veriler üretiliyor...');

        $baslangic = Carbon::now()->subDays(30)->startOfDay();
        $bitis     = Carbon::yesterday()->endOfDay();

        $pdksKayitlari   = [];
        $gunlukOzetler   = [];

        // Mesai saatleri
        $mesaiBaslangic = '08:00';
        $mesaiBitis     = '17:00';

        $current = $baslangic->copy();

        while ($current->lte($bitis)) {
            // Hafta sonlarını atla
            if ($current->isWeekend()) {
                // Bazı personeller hafta sonu çalışsın (tatil günü çalışanlar raporu için)
                $tatilCalisanlar = $personeller->random(rand(0, 2));
                foreach ($tatilCalisanlar as $p) {
                    $giris = $current->copy()->setHour(9)->setMinute(rand(0, 30));
                    $cikis = $current->copy()->setHour(rand(13, 16))->setMinute(rand(0, 59));
                    $calismaDk = $giris->diffInMinutes($cikis);

                    $pdksKayitlari[] = [
                        'uuid'          => Str::uuid(),
                        'firma_id'      => $firmaId,
                        'cihaz_id'      => $cihazId,
                        'personel_id'   => $p->id,
                        'kayit_tarihi'  => $giris,
                        'islem_tipi'    => 'giris',
                        'ham_veri'      => null,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                    $pdksKayitlari[] = [
                        'uuid'          => Str::uuid(),
                        'firma_id'      => $firmaId,
                        'cihaz_id'      => $cihazId,
                        'personel_id'   => $p->id,
                        'kayit_tarihi'  => $cikis,
                        'islem_tipi'    => 'cikis',
                        'ham_veri'      => null,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                    $gunlukOzetler[] = [
                        'uuid'                   => Str::uuid(),
                        'firma_id'               => $firmaId,
                        'personel_id'            => $p->id,
                        'tarih'                  => $current->format('Y-m-d'),
                        'ilk_giris'              => $giris,
                        'son_cikis'              => $cikis,
                        'toplam_calisma_suresi'  => $calismaDk,
                        'durum'                  => 'geldi',
                        'created_at'             => now(),
                        'updated_at'             => now(),
                    ];
                }
                $current->addDay();
                continue;
            }

            foreach ($personeller as $idx => $p) {
                $rand = rand(1, 100);

                // %8 ihtimalle gelmedi (devamsız)
                if ($rand <= 8) {
                    $gunlukOzetler[] = [
                        'uuid'                   => Str::uuid(),
                        'firma_id'               => $firmaId,
                        'personel_id'            => $p->id,
                        'tarih'                  => $current->format('Y-m-d'),
                        'ilk_giris'              => null,
                        'son_cikis'              => null,
                        'toplam_calisma_suresi'  => 0,
                        'durum'                  => 'gelmedi',
                        'created_at'             => now(),
                        'updated_at'             => now(),
                    ];
                    continue;
                }

                // %5 ihtimalle sadece giriş kaydı var (çıkışta kart unutmuş)
                if ($rand <= 13) {
                    $giris = $current->copy()->setHour(rand(7, 8))->setMinute(rand(0, 59));
                    $pdksKayitlari[] = [
                        'uuid'          => Str::uuid(),
                        'firma_id'      => $firmaId,
                        'cihaz_id'      => $cihazId,
                        'personel_id'   => $p->id,
                        'kayit_tarihi'  => $giris,
                        'islem_tipi'    => 'giris',
                        'ham_veri'      => null,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                    $gunlukOzetler[] = [
                        'uuid'                   => Str::uuid(),
                        'firma_id'               => $firmaId,
                        'personel_id'            => $p->id,
                        'tarih'                  => $current->format('Y-m-d'),
                        'ilk_giris'              => $giris,
                        'son_cikis'              => null,
                        'toplam_calisma_suresi'  => 0,
                        'durum'                  => 'eksik_cikis',
                        'created_at'             => now(),
                        'updated_at'             => now(),
                    ];
                    continue;
                }

                // %3 ihtimalle sadece çıkış (girişte kart unutmuş)
                if ($rand <= 16) {
                    $cikis = $current->copy()->setHour(rand(16, 18))->setMinute(rand(0, 59));
                    $pdksKayitlari[] = [
                        'uuid'          => Str::uuid(),
                        'firma_id'      => $firmaId,
                        'cihaz_id'      => $cihazId,
                        'personel_id'   => $p->id,
                        'kayit_tarihi'  => $cikis,
                        'islem_tipi'    => 'cikis',
                        'ham_veri'      => null,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                    $gunlukOzetler[] = [
                        'uuid'                   => Str::uuid(),
                        'firma_id'               => $firmaId,
                        'personel_id'            => $p->id,
                        'tarih'                  => $current->format('Y-m-d'),
                        'ilk_giris'              => null,
                        'son_cikis'              => $cikis,
                        'toplam_calisma_suresi'  => 0,
                        'durum'                  => 'eksik_giris',
                        'created_at'             => now(),
                        'updated_at'             => now(),
                    ];
                    continue;
                }

                // Normal giriş-çıkış
                // %25 geç geliyor (08:15-09:30 arası)
                if ($rand <= 40) {
                    $girisH = 8; $girisM = rand(15, 59);
                    if ($rand <= 30) { $girisH = 9; $girisM = rand(0, 30); }
                    $durum = 'geç kaldı';
                } else {
                    // Zamanında geldi (07:30-08:00)
                    $girisH = rand(7, 8);
                    $girisM = ($girisH == 7) ? rand(30, 59) : rand(0, 5);
                    $durum = 'geldi';
                }

                $giris = $current->copy()->setHour($girisH)->setMinute($girisM)->setSecond(rand(0, 59));

                // Çıkış saati belirleme
                // %15 erken çıkıyor (15:00-16:59)
                if ($rand >= 85) {
                    $cikisH = rand(15, 16); $cikisM = rand(0, 59);
                    $durum = ($durum === 'geç kaldı') ? 'geç kaldı' : 'erken_cikis';
                }
                // %12 mesaiye kalıyor (18:00-20:00)
                elseif ($rand >= 73) {
                    $cikisH = rand(18, 20); $cikisM = rand(0, 59);
                }
                // Normal çıkış (17:00-17:30)
                else {
                    $cikisH = 17; $cikisM = rand(0, 30);
                }

                $cikis = $current->copy()->setHour($cikisH)->setMinute($cikisM)->setSecond(rand(0, 59));
                $calismaDk = $giris->diffInMinutes($cikis);

                // PDKS Kayıtları
                $pdksKayitlari[] = [
                    'uuid'          => Str::uuid(),
                    'firma_id'      => $firmaId,
                    'cihaz_id'      => $cihazId,
                    'personel_id'   => $p->id,
                    'kayit_tarihi'  => $giris,
                    'islem_tipi'    => 'giris',
                    'ham_veri'      => null,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
                $pdksKayitlari[] = [
                    'uuid'          => Str::uuid(),
                    'firma_id'      => $firmaId,
                    'cihaz_id'      => $cihazId,
                    'personel_id'   => $p->id,
                    'kayit_tarihi'  => $cikis,
                    'islem_tipi'    => 'cikis',
                    'ham_veri'      => null,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                // Günlük Özet
                $gunlukOzetler[] = [
                    'uuid'                   => Str::uuid(),
                    'firma_id'               => $firmaId,
                    'personel_id'            => $p->id,
                    'tarih'                  => $current->format('Y-m-d'),
                    'ilk_giris'              => $giris,
                    'son_cikis'              => $cikis,
                    'toplam_calisma_suresi'  => $calismaDk,
                    'durum'                  => $durum,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ];
            }

            $current->addDay();
        }

        // Toplu ekleme (chunk'lar halinde)
        foreach (array_chunk($pdksKayitlari, 100) as $chunk) {
            DB::table('pdks_kayitlari')->insert($chunk);
        }
        $this->command->info('PDKS Kayıtları: ' . count($pdksKayitlari) . ' kayıt oluşturuldu.');

        foreach (array_chunk($gunlukOzetler, 100) as $chunk) {
            DB::table('pdks_gunluk_ozetler')->insert($chunk);
        }
        $this->command->info('Günlük Özetler: ' . count($gunlukOzetler) . ' kayıt oluşturuldu.');

        // İzin Kayıtları (5 personele rastgele izin)
        $izinTuruId = DB::table('izin_turleri')->where('firma_id', $firmaId)->value('id');
        $izinler = [];
        $izinPersoneller = $personeller->random(5);
        foreach ($izinPersoneller as $p) {
            $izinTarih = Carbon::now()->subDays(rand(5, 25));
            $izinler[] = [
                'uuid'          => Str::uuid(),
                'firma_id'      => $firmaId,
                'personel_id'   => $p->id,
                'izin_turu_id'  => $izinTuruId,
                'tarih'         => $izinTarih->format('Y-m-d'),
                'bitis_tarihi'  => $izinTarih->copy()->addDays(rand(1, 3))->format('Y-m-d'),
                'gun_sayisi'    => rand(1, 3),
                'izin_tipi'     => 'gunluk',
                'aciklama'      => ['Yıllık izin', 'Hastalık izni', 'Mazeret izni', 'Evlilik izni'][rand(0, 3)],
                'durum'         => ['beklemede', 'onaylandi', 'onaylandi'][rand(0, 2)],
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }
        if (!empty($izinler)) {
            DB::table('personel_izinler')->insert($izinler);
            $this->command->info('İzin Kayıtları: ' . count($izinler) . ' kayıt oluşturuldu.');
        }

        // Personel notları ve iletişim bilgileri güncelle (rapor 14-15 için)
        $notlar = [
            'Performansı yüksek, terfi adayı.',
            'Geç kalma sorunu var, uyarıldı.',
            'Proje liderliği başarılı.',
            null,
            'Yeni işe başladı, oryantasyon devam ediyor.',
            null,
            'İş güvenliği eğitimi tamamlandı.',
            null,
            'Yabancı dil sertifikası mevcut.',
            null,
        ];
        $telefonlar = [
            '0532 111 22 33', '0541 222 33 44', '0555 333 44 55',
            '0544 444 55 66', '0533 555 66 77', '0542 666 77 88',
            '0553 777 88 99', '0534 888 99 00', '0545 999 00 11',
            '0554 000 11 22',
        ];
        foreach ($personeller as $i => $p) {
            DB::table('personeller')->where('id', $p->id)->update([
                'notlar'  => $notlar[$i] ?? null,
                'telefon' => $telefonlar[$i] ?? null,
                'email'   => strtolower(str_replace(' ', '', $p->ad)) . '@firma.com',
            ]);
        }
        $this->command->info('Personel notları ve iletişim bilgileri güncellendi.');

        // 1 personeli işten ayrılmış olarak işaretle (rapor 17 için)
        DB::table('personeller')->where('id', $personeller->last()->id)->update([
            'cikis_tarihi' => Carbon::now()->subDays(10)->format('Y-m-d'),
        ]);
        $this->command->info('1 personel işten ayrılmış olarak işaretlendi.');

        // Elle müdahale kayıtları (rapor 13 için)
        $elleKayitlar = [];
        for ($i = 0; $i < 5; $i++) {
            $p = $personeller->random();
            $elleKayitlar[] = [
                'uuid'          => Str::uuid(),
                'firma_id'      => $firmaId,
                'cihaz_id'      => $cihazId,
                'personel_id'   => $p->id,
                'kayit_tarihi'  => Carbon::now()->subDays(rand(1, 20))->setHour(rand(8, 17))->setMinute(rand(0, 59)),
                'islem_tipi'    => 'elle_duzenleme',
                'ham_veri'      => json_encode(['aciklama' => 'Manuel düzeltme', 'degistiren' => 'Sistem Yöneticisi']),
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }
        DB::table('pdks_kayitlari')->insert($elleKayitlar);
        $this->command->info('Elle müdahale kayıtları: 5 kayıt oluşturuldu.');

        // Bugün için "şuan içeridekiler" simülasyonu (rapor 12)
        $bugunPersoneller = $personeller->take(7); // 7 kişi bugün gelmiş
        foreach ($bugunPersoneller as $p) {
            $giris = Carbon::today()->setHour(rand(7, 8))->setMinute(rand(0, 30));
            DB::table('pdks_kayitlari')->insert([
                'uuid'          => Str::uuid(),
                'firma_id'      => $firmaId,
                'cihaz_id'      => $cihazId,
                'personel_id'   => $p->id,
                'kayit_tarihi'  => $giris,
                'islem_tipi'    => 'giris',
                'ham_veri'      => null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            DB::table('pdks_gunluk_ozetler')->insert([
                'uuid'                   => Str::uuid(),
                'firma_id'               => $firmaId,
                'personel_id'            => $p->id,
                'tarih'                  => Carbon::today()->format('Y-m-d'),
                'ilk_giris'              => $giris,
                'son_cikis'              => null, // Henüz çıkış yapmamış = İçeride
                'toplam_calisma_suresi'  => 0,
                'durum'                  => 'geldi',
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }
        $this->command->info('Bugün içeridekiler: ' . $bugunPersoneller->count() . ' kişi simüle edildi.');

        $this->command->info('✅ Demo rapor verileri başarıyla oluşturuldu!');
    }
}
