<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResmiTatil extends Model
{
    use SoftDeletes;

    protected $table = 'resmi_tatiller';

    protected $fillable = [
        'firma_id',
        'yil',
        'tarih',
        'ad',
        'tur',
        'yarim_gun_mu',
    ];

    protected $casts = [
        'tarih' => 'date',
        'yarim_gun_mu' => 'boolean',
        'yil' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new FirmaScope);
    }
}
