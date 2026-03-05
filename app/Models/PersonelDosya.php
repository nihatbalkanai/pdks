<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonelDosya extends Model
{
    protected $table = 'personel_dosyalar';

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
        'dosya_adi',
        'dosya_yolu',
        'dosya_tipi',
        'boyut',
    ];

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }
}
