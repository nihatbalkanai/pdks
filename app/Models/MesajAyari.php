<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MesajAyari extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mesaj_ayarlari';

    protected $fillable = [
        'firma_id',
        'kanal',
        'api_anahtari',
        'durum',
        // SMTP
        'smtp_host',
        'smtp_port',
        'smtp_sifreleme',
        'smtp_kullanici',
        'smtp_sifre',
        'gonderen_email',
        'gonderen_ad',
        // SMS
        'sms_api_url',
        'sms_kullanici',
        'sms_sifre',
        'sms_baslik',
        'sablonlar',
    ];

    protected $casts = [
        'durum' => 'boolean',
        'sablonlar' => 'array',
    ];

    protected $hidden = [
        'smtp_sifre',
        'sms_sifre',
        'api_key',
    ];

    public function firma()
    {
        return $this->belongsTo(Firma::class);
    }
}
