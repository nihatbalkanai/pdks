<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Firma extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'firmalar';

    protected $fillable = [
        'paket_id',
        'firma_adi',
        'vergi_no',
        'vergi_dairesi',
        'adres',
        'durum',
        'abonelik_bitis_tarihi',
        'paket_tipi',
    ];

    protected $casts = [
        'durum' => 'boolean',
        'abonelik_bitis_tarihi' => 'date',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function paket()
    {
        return $this->belongsTo(Paket::class, 'paket_id');
    }

    public function kullanicilar()
    {
        return $this->hasMany(Kullanici::class, 'firma_id');
    }

    public function personeller()
    {
        return $this->hasMany(Personel::class, 'firma_id');
    }

    public function cihazlar()
    {
        return $this->hasMany(PdksCihazi::class, 'firma_id');
    }

    // Abonelik aktif mi?
    public function abonelikAktifMi(): bool
    {
        if (!$this->durum) return false;
        if (!$this->abonelik_bitis_tarihi) return true; // Sınırsız
        return $this->abonelik_bitis_tarihi->isFuture();
    }
}
