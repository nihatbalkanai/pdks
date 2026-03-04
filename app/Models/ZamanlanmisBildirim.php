<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZamanlanmisBildirim extends Model
{
    use SoftDeletes;

    protected $table = 'zamanlanmis_bildirimler';

    protected $fillable = [
        'firma_id', 'ad', 'tip', 'kanal', 'konu', 'mesaj_sablonu',
        'cron_ifadesi', 'gun', 'saat', 'ozel_tarih', 'aktif',
        'son_calisma', 'toplam_gonderim',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'son_calisma' => 'datetime',
        'ozel_tarih' => 'date',
    ];

    public function firma()
    {
        return $this->belongsTo(Firma::class);
    }
}
