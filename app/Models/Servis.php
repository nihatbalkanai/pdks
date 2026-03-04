<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servis extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids, \Illuminate\Database\Eloquent\SoftDeletes, \App\Models\Scopes\FirmaScopeTrait;

    protected $table = 'servisler';

    protected $fillable = [
        'firma_id',
        'plaka',
        'sofor',
        'guzergah',
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

    public function hareketler()
    {
        return $this->hasMany(ServisHareket::class);
    }
}
