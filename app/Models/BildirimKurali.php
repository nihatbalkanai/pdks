<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BildirimKurali extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\Concerns\HasUuids, \Illuminate\Database\Eloquent\SoftDeletes, \App\Models\Scopes\FirmaScopeTrait;

    protected $table = 'bildirim_kurallari';

    protected $fillable = [
        'firma_id',
        'kural_tipi',
        'tetikleme_saati',
        'alici_telefon',
        'alici_eposta',
        'durum',
    ];

    protected $casts = [
        'durum' => 'boolean',
    ];

    public function firma()
    {
        return $this->belongsTo(Firma::class);
    }
}
