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
        'standart_ay_gunu',
        'eksik_gun_kesintisi_yapilacak_mi',
        'fazla_mesai_carpani',
        'tatil_mesai_carpani',
        'resmi_tatil_mesai_carpani',
        'fazla_mesai_tolerans_dakika',
        'gun_fark_hesapla',
        'ssk_rapor_toplama_dahil',
        'durum',
    ];

    protected $casts = [
        'eksik_gun_kesintisi_yapilacak_mi' => 'boolean',
        'durum' => 'boolean',
        'aylik_calisma_saati' => 'integer',
        'haftalik_calisma_saati' => 'integer',
        'standart_ay_gunu' => 'integer',
        'gunluk_calisma_saati' => 'decimal:2',
        'fazla_mesai_carpani' => 'decimal:2',
        'tatil_mesai_carpani' => 'decimal:2',
        'resmi_tatil_mesai_carpani' => 'decimal:2',
        'fazla_mesai_tolerans_dakika' => 'integer',
        'gun_fark_hesapla' => 'boolean',
        'ssk_rapor_toplama_dahil' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new FirmaScope);
    }
}
