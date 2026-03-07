<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AktiviteLogService
{
    /**
     * Aktivite logu kaydet
     */
    public static function logla(string $islem, ?string $hedefTip = null, $hedefId = null, ?string $detay = null): void
    {
        DB::table('platform_aktivite_loglari')->insert([
            'kullanici_id' => Auth::id(),
            'islem' => $islem,
            'hedef_tip' => $hedefTip,
            'hedef_id' => $hedefId ? (string) $hedefId : null,
            'detay' => $detay,
            'ip_adresi' => request()->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
