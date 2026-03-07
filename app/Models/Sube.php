<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sube extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes, \App\Models\Scopes\FirmaScopeTrait;

    protected $table = 'subeler';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $fillable = [
        'firma_id',
        'sube_adi',
        'lokasyon',
        'lokasyon_enlem',
        'lokasyon_boylam',
        'geofence_yaricap',
        'durum',
    ];

    protected $casts = [
        'durum' => 'boolean',
        'lokasyon_enlem' => 'decimal:7',
        'lokasyon_boylam' => 'decimal:7',
        'geofence_yaricap' => 'integer',
    ];

    public function firma()
    {
        return $this->belongsTo(Firma::class);
    }
    
    public function personeller()
    {
        return $this->hasMany(Personel::class);
    }

    public function cihazlar()
    {
        return $this->hasMany(PdksCihazi::class);
    }
}
