<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalismaPlan extends Model
{
    protected $table = 'calisma_planlari';

    protected $fillable = [
        'firma_id', 'calisma_grubu_id', 'tarih', 'vardiya_id', 'tur',
    ];

    protected $casts = [
        'tarih' => 'date',
    ];

    public function grup()
    {
        return $this->belongsTo(CalismaGrubu::class, 'calisma_grubu_id');
    }

    public function vardiya()
    {
        return $this->belongsTo(Vardiya::class, 'vardiya_id');
    }
}
