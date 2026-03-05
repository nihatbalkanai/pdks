<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PdksGunlukOzet extends Model
{
    use SoftDeletes, HasUuids;

    protected $table = 'pdks_gunluk_ozetler';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'firma_id',
        'personel_id',
        'tarih',
        'ilk_giris',
        'son_cikis',
        'toplam_calisma_suresi',
        'fazla_mesai_dakika',
        'durum',
    ];

    protected $casts = [
        'tarih' => 'date',
        'ilk_giris' => 'datetime',
        'son_cikis' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new FirmaScope);
    }

    // İlişkiler
    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }

    public function firma()
    {
        return $this->belongsTo(Firma::class, 'firma_id');
    }
}
