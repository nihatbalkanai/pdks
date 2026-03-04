<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GunlukPuantajParametresi extends Model
{
    use SoftDeletes;

    protected $table = 'gunluk_puantaj_parametreleri';

    protected $fillable = [
        'firma_id',
        'ad',
        'gun_donum_saati',
        'iceri_giris_saati',
        'disari_cikis_saati',
        'erken_gelme_toleransi',
        'gec_gelme_toleransi',
        'erken_cikma_toleransi',
        'hesaplama_tipi',
        'mola_suresi',
        'gec_gelme_cezasi',
        'erken_cikma_cezasi',
        'durum',
    ];

    protected $casts = [
        'mola_suresi' => 'integer',
        'gec_gelme_cezasi' => 'integer',
        'erken_cikma_cezasi' => 'integer',
        'durum' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new FirmaScope);
    }

    public function bordroAlanlari()
    {
        return $this->hasMany(GunlukPuantajBordroAlani::class, 'gunluk_puantaj_id');
    }
}
