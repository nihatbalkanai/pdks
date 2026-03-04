<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AylikPuantajParametresi extends Model
{
    use SoftDeletes;

    protected $table = 'aylik_puantaj_parametreleri';

    protected $fillable = [
        'firma_id',
        'hesap_parametresi_adi',
        'aylik_calisma_saati',
        'haftalik_calisma_saati',
        'gunluk_calisma_saati',
        'eksik_gun_kesintisi_yapilacak_mi',
        'fazla_mesai_carpani',
        'tatil_mesai_carpani',
        'resmi_tatil_mesai_carpani',
        'durum',
    ];

    protected $casts = [
        'eksik_gun_kesintisi_yapilacak_mi' => 'boolean',
        'durum' => 'boolean',
        'aylik_calisma_saati' => 'integer',
        'haftalik_calisma_saati' => 'integer',
        'gunluk_calisma_saati' => 'decimal:2',
        'fazla_mesai_carpani' => 'decimal:2',
        'tatil_mesai_carpani' => 'decimal:2',
        'resmi_tatil_mesai_carpani' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new FirmaScope);
    }
}
