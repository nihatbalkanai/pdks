<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TanimKodu extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tanim_kodlari';

    protected $fillable = [
        'firma_id',
        'tip',
        'kod',
        'aciklama',
        'durum',
    ];

    protected $casts = [
        'durum' => 'boolean',
    ];

    public function firma()
    {
        return $this->belongsTo(Firma::class);
    }

    // Tiplere göre scope
    public function scopeTip($query, $tip)
    {
        return $query->where('tip', $tip);
    }
}
