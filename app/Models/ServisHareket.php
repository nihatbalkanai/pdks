<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServisHareket extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids, \App\Models\Scopes\FirmaScopeTrait;

    protected $table = 'servis_hareketleri';

    protected $fillable = [
        'firma_id',
        'servis_id',
        'personel_id',
        'binis_zamani',
        'hareket_tipi',
    ];

    protected $casts = [
        'binis_zamani' => 'datetime',
    ];

    public function firma()
    {
        return $this->belongsTo(Firma::class);
    }
    
    public function servis()
    {
        return $this->belongsTo(Servis::class);
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class);
    }
}
