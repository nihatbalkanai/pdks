<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SistemLog extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\Concerns\HasUuids, \App\Models\Scopes\FirmaScopeTrait;

    protected $table = 'sistem_loglari';

    protected $fillable = [
        'firma_id',
        'kullanici_id',
        'islem',
        'detay',
        'ip_adresi',
        'tarih'
    ];

    public function kullanici()
    {
        return $this->belongsTo(Kullanici::class);
    }

    public static function logCustom($islem, $detay = null)
    {
        if (auth()->check()) {
            return self::create([
                'firma_id' => auth()->user()->firma_id,
                'kullanici_id' => auth()->id(),
                'islem' => $islem,
                'detay' => $detay,
                'ip_adresi' => request()->ip(),
                'tarih' => now()
            ]);
        }
    }
}
