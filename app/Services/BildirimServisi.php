<?php

namespace App\Services;

use App\Models\MesajAyari;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BildirimServisi
{
    /**
     * Mesaj tipini ve kanalını çözümleyerek ilgili firmaya ait API ayarlarıyla mesaj gönderir.
     *
     * @param int $firmaId
     * @param string $kanal (sms veya eposta)
     * @param string $alici (telefon veya mail adresi)
     * @param string $mesaj
     * @return bool
     */
    public function gonder($firmaId, $kanal, $alici, $mesaj)
    {
        // Firmanın mesaj ayarlarını al (10.000 firma yükü için Redis/Cache ile önbellekleme)
        $cacheKey = "firma_{$firmaId}_mesaj_ayari_{$kanal}";
        $ayar = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHours(24), function () use ($firmaId, $kanal) {
            return MesajAyari::withoutGlobalScopes()
                ->where('firma_id', $firmaId)
                ->where('kanal', $kanal)
                ->where('durum', true)
                ->first();
        });

        // Eğer api anahtarı tanımlı değilse iptal et (Sessizce geç)
        if (!$ayar || !$ayar->api_anahtari) {
            Log::warning("Firma [{$firmaId}] için {$kanal} ayarı veya API Anahtarı bulunamadı.");
            return false;
        }

        $apiAnahtari = $ayar->api_anahtari;
        $gonderici = $ayar->gonderici_basligi ?? 'PDKS Sistemi';

        try {
            // techsend.io veya benzeri bir sağlayıcının hayali API uç noktası
            $apiUrl = 'https://api.techsend.io/v1/messages/send';

            // Gerçek projede HTTP isteği şuna benzer olacaktır:
            /*
            $response = Http::withToken($apiAnahtari)->post($apiUrl, [
                'type' => $kanal,
                'to' => $alici,
                'sender' => $gonderici,
                'content' => $mesaj
            ]);
            
            return $response->successful();
            */

            // Simülasyon olduğu için Başarılı sayıp Logluyoruz. 
            // 10.000 firma yükünde bu servisin sadece kendi firmasını etkileyecek API anahtarı kullanması veri güvenliği sağlar.
            Log::info("TechSend.io [{$kanal}]: {$alici} adresine gönderildi. Kurum: {$gonderici} | Anahtar: ***" . substr($apiAnahtari, -4) . " | Mesaj: {$mesaj}");

            return true;

        } catch (\Exception $e) {
            Log::error("TechSend.io Gönderim Hatası: " . $e->getMessage());
            return false;
        }
    }
}
