<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonelAvansKesinti extends Model
{
    protected $table = 'personel_avans_kesintileri';

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
        'taksit_grup_id',
        'taksit_no',
        'toplam_taksit',
        'toplam_tutar',
    ];

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }
}
