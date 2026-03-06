<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonelIzin extends Model
{
    use SoftDeletes;

    protected $table = 'personel_izinler';

    protected static function booted(): void
    {
        static::addGlobalScope(new FirmaScope);

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $fillable = [
        'uuid', 'firma_id', 'personel_id', 'izin_turu_id',
        'tarih', 'bitis_tarihi', 'gun_sayisi',
        'tatil_tipi', 'giris_saati', 'cikis_saati', 'izin_tipi',
        'aciklama',
        'durum',
        'onaylayan_id',
        'belge_yolu',
        'ssk_odeme_tutari',
    ];

    protected $casts = [
        'tarih' => 'date',
        'bitis_tarihi' => 'date',
        'gun_sayisi' => 'decimal:1',
    ];

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }

    public function izinTuru()
    {
        return $this->belongsTo(IzinTuru::class, 'izin_turu_id');
    }

    public function onaylayan()
    {
        return $this->belongsTo(Kullanici::class, 'onaylayan_id');
    }
}
