<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sube extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids, \Illuminate\Database\Eloquent\SoftDeletes, \App\Models\Scopes\FirmaScopeTrait;

    protected $table = 'subeler';

    protected $fillable = [
        'firma_id',
        'sube_adi',
        'lokasyon',
        'durum',
    ];

    protected $casts = [
        'durum' => 'boolean',
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
