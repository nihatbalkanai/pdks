<?php

namespace App\Services;

use App\Models\Firma;
use App\Models\PaketTanimi;
use App\Models\Kullanici;
use App\Models\Personel;
use App\Models\PdksCihazi;

class PaketLimitService
{
    /**
     * Belirli bir firmanın paket limitlerini kontrol et
     * @return array ['durum' => bool, 'mesaj' => string, 'limitler' => array]
     */
    public static function kontrol(int $firmaId): array
    {
        $firma = Firma::find($firmaId);
        if (!$firma) {
            return ['durum' => false, 'mesaj' => 'Firma bulunamadı', 'limitler' => []];
        }

        $paket = PaketTanimi::where('ad', $firma->paket_tipi)->first();
        if (!$paket) {
            return ['durum' => true, 'mesaj' => 'Paket tanımı bulunamadı, limit uygulanmıyor', 'limitler' => []];
        }

        $mevcutPersonel = Personel::where('firma_id', $firmaId)->count();
        $mevcutKullanici = Kullanici::withoutGlobalScopes()->where('firma_id', $firmaId)->count();
        $mevcutCihaz = PdksCihazi::where('firma_id', $firmaId)->count();

        $limitler = [
            'personel' => [
                'mevcut' => $mevcutPersonel,
                'limit' => $paket->max_personel,
                'asim' => $paket->max_personel > 0 && $mevcutPersonel >= $paket->max_personel,
                'yuzde' => $paket->max_personel > 0 ? round(($mevcutPersonel / $paket->max_personel) * 100) : 0,
            ],
            'kullanici' => [
                'mevcut' => $mevcutKullanici,
                'limit' => $paket->max_kullanici,
                'asim' => $paket->max_kullanici > 0 && $mevcutKullanici >= $paket->max_kullanici,
                'yuzde' => $paket->max_kullanici > 0 ? round(($mevcutKullanici / $paket->max_kullanici) * 100) : 0,
            ],
            'cihaz' => [
                'mevcut' => $mevcutCihaz,
                'limit' => $paket->max_cihaz,
                'asim' => $paket->max_cihaz > 0 && $mevcutCihaz >= $paket->max_cihaz,
                'yuzde' => $paket->max_cihaz > 0 ? round(($mevcutCihaz / $paket->max_cihaz) * 100) : 0,
            ],
        ];

        $herhangiAsim = $limitler['personel']['asim'] || $limitler['kullanici']['asim'] || $limitler['cihaz']['asim'];

        return [
            'durum' => !$herhangiAsim,
            'paket' => $paket->ad,
            'mesaj' => $herhangiAsim ? 'Paket limitine ulaşıldı. Lütfen paketinizi yükseltin.' : 'Limitler dahilinde.',
            'limitler' => $limitler,
        ];
    }

    /**
     * Belirli bir kaynak için ekleme yapılabilir mi?
     */
    public static function eklenebilirMi(int $firmaId, string $kaynak): array
    {
        $kontrol = self::kontrol($firmaId);

        if (!isset($kontrol['limitler'][$kaynak])) {
            return ['izin' => true, 'mesaj' => ''];
        }

        $limit = $kontrol['limitler'][$kaynak];

        if ($limit['limit'] == 0) {
            return ['izin' => true, 'mesaj' => '']; // Sınırsız
        }

        if ($limit['mevcut'] >= $limit['limit']) {
            $paket = $kontrol['paket'] ?? 'Mevcut';
            return [
                'izin' => false,
                'mesaj' => "{$paket} paketinde maksimum {$limit['limit']} {$kaynak} ekleyebilirsiniz. Mevcut: {$limit['mevcut']}. Lütfen paketinizi yükseltin."
            ];
        }

        return ['izin' => true, 'mesaj' => ''];
    }
}
