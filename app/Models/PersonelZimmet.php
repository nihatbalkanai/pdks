<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonelZimmet extends Model
{
    protected $table = 'personel_zimmetler';

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
        'kategori',
        'bolum_adi',
        'aciklama',
        'verilis_tarihi',
        'iade_tarihi',
    ];

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }
}
