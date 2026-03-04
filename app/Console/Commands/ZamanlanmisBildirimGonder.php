<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ZamanlanmisBildirim;
use App\Models\Personel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZamanlanmisBildirimGonder extends Command
{
    protected $signature = 'pdks:bildirim-gonder';
    protected $description = 'Zamanlanmış bildirimleri (maaş günü, doğum günü, bayram, özel tarih) kontrol edip gönderir.';

    public function handle()
    {
        $bugun = Carbon::today();
        $buSaat = Carbon::now()->format('H:i');

        $bildirimler = ZamanlanmisBildirim::where('aktif', true)->get();

        if ($bildirimler->isEmpty()) {
            $this->info('Aktif zamanlanmış bildirim yok.');
            return;
        }

        foreach ($bildirimler as $bildirim) {
            $gonderilecekMi = false;

            switch ($bildirim->tip) {
                case 'maas_gunu':
                    // Ayın belirli günü kontrolü
                    $gonderilecekMi = ($bugun->day == $bildirim->gun) && ($buSaat === substr($bildirim->saat, 0, 5));
                    break;

                case 'dogum_gunu':
                    // Her gün kontrol - doğum günü olan personellere
                    $gonderilecekMi = ($buSaat === substr($bildirim->saat, 0, 5));
                    break;

                case 'bayram':
                case 'ozel_tarih':
                    // Belirli tarih kontrolü
                    $gonderilecekMi = $bildirim->ozel_tarih && $bugun->isSameDay($bildirim->ozel_tarih) && ($buSaat === substr($bildirim->saat, 0, 5));
                    break;

                case 'genel':
                    // Özel cron ifadesi veya günlük
                    if ($bildirim->gun) {
                        $gonderilecekMi = ($bugun->day == $bildirim->gun) && ($buSaat === substr($bildirim->saat, 0, 5));
                    } else {
                        $gonderilecekMi = ($buSaat === substr($bildirim->saat, 0, 5));
                    }
                    break;
            }

            // Bugün zaten çalıştırıldıysa tekrar çalıştırma
            if ($bildirim->son_calisma && $bildirim->son_calisma->isToday()) {
                $gonderilecekMi = false;
            }

            if ($gonderilecekMi) {
                $this->info("Bildirim gönderiliyor: {$bildirim->ad}");
                $this->bildirimGonder($bildirim);
            }
        }

        $this->info('Bildirim kontrolü tamamlandı.');
    }

    private function bildirimGonder(ZamanlanmisBildirim $bildirim)
    {
        // Hedef personelleri belirle
        $personeller = Personel::withoutGlobalScopes()
            ->where('firma_id', $bildirim->firma_id)
            ->where('durum', true)
            ->get();

        if ($bildirim->tip === 'dogum_gunu') {
            // Sadece bugün doğum günü olanlar
            $personeller = $personeller->filter(function ($p) {
                if (!$p->dogum_tarihi) return false;
                $dogum = Carbon::parse($p->dogum_tarihi);
                return $dogum->month === now()->month && $dogum->day === now()->day;
            });
        }

        $sayac = 0;
        foreach ($personeller as $p) {
            // Mesaj şablonunu kişiselleştir
            $mesaj = str_replace(
                ['{ad}', '{soyad}', '{kart_no}', '{sirket}', '{departman}'],
                [$p->ad, $p->soyad, $p->kart_no, $p->sirket, $p->departman],
                $bildirim->mesaj_sablonu
            );

            $konu = $bildirim->konu ? str_replace(
                ['{ad}', '{soyad}'],
                [$p->ad, $p->soyad],
                $bildirim->konu
            ) : null;

            // SMS gönder
            if (in_array($bildirim->kanal, ['sms', 'her_ikisi']) && $p->telefon) {
                // TODO: Gerçek SMS API entegrasyonu
                DB::table('bildirim_loglari')->insert([
                    'firma_id' => $p->firma_id,
                    'personel_id' => $p->id,
                    'kanal' => 'sms',
                    'alici' => $p->telefon,
                    'konu' => $konu,
                    'mesaj' => $mesaj,
                    'durum' => 'gonderildi',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $sayac++;
            }

            // Email gönder
            if (in_array($bildirim->kanal, ['email', 'her_ikisi']) && $p->email) {
                // TODO: Mail::to($p->email)->send(...)
                DB::table('bildirim_loglari')->insert([
                    'firma_id' => $p->firma_id,
                    'personel_id' => $p->id,
                    'kanal' => 'email',
                    'alici' => $p->email,
                    'konu' => $konu,
                    'mesaj' => $mesaj,
                    'durum' => 'gonderildi',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $sayac++;
            }
        }

        // Son çalışma zamanını güncelle
        $bildirim->son_calisma = now();
        $bildirim->toplam_gonderim += $sayac;
        $bildirim->save();

        $this->info("  → {$sayac} bildirim gönderildi.");
        Log::info("[Zamanlanmış Bildirim] {$bildirim->ad}: {$sayac} gönderim yapıldı.");
    }
}
