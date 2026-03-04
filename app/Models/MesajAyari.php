<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MesajAyari extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\Concerns\HasUuids, \Illuminate\Database\Eloquent\SoftDeletes, \App\Models\Scopes\FirmaScopeTrait;

    protected $table = 'mesaj_ayarlari';

    protected $fillable = [
        'firma_id',
        'kanal',
        'api_anahtari',
        'gonderici_basligi',
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
