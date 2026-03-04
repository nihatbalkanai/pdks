<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonelIzin extends Model
{
    protected $table = 'personel_izinler';

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $fillable = [
        'firma_id',
        'personel_id',
        'tarih',
        'tatil_tipi',
        'giris_saati',
        'cikis_saati',
        'izin_tipi',
        'aciklama',
    ];

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }
}
