<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IzinTuru extends Model
{
    use SoftDeletes;

    protected $table = 'izin_turleri';

    protected $fillable = [
        'firma_id',
        'ad',
        'ucret_kesintisi_yapilacak_mi',
        'yillik_izinden_duser_mi',
        'hafta_sonu_haric_mi',
        'resmi_tatil_haric_mi',
        'max_gun',
        'aktif_mi',
    ];

    protected $casts = [
        'ucret_kesintisi_yapilacak_mi' => 'boolean',
        'yillik_izinden_duser_mi' => 'boolean',
        'hafta_sonu_haric_mi' => 'boolean',
        'resmi_tatil_haric_mi' => 'boolean',
        'max_gun' => 'integer',
        'aktif_mi' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new FirmaScope);
    }
}
