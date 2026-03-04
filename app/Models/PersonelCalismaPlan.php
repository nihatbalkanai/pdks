<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;

class PersonelCalismaPlan extends Model
{
    protected $table = 'personel_calisma_planlari';

    protected $fillable = [
        'firma_id', 'personel_id', 'tarih', 'vardiya_id', 'tur', 'aciklama',
    ];

    protected $casts = [
        'tarih' => 'date',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new FirmaScope);
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class);
    }

    public function vardiya()
    {
        return $this->belongsTo(Vardiya::class);
    }
}
