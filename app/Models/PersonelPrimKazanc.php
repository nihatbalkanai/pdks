<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonelPrimKazanc extends Model
{
    protected $table = 'personel_prim_kazanclari';

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
        'tutar',
        'aciklama',
        'bordro_alani',
    ];

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }
}
