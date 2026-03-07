<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdksKaydi extends Model
{
    protected $table = 'pdks_kayitlari';
    
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
        'cihaz_id',
        'kayit_tarihi',
        'islem_tipi',
        'ham_veri',
    ];

    protected $casts = [
        'ham_veri' => 'array',
        'kayit_tarihi' => 'datetime',
    ];

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }
}
