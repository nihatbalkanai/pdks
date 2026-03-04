<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PdksKayit extends Model
{
    use SoftDeletes, HasUuids;

    protected $table = 'pdks_kayitlari';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'firma_id',
        'cihaz_id',
        'personel_id',
        'kayit_tarihi',
        'islem_tipi',
        'ham_veri',
    ];

    protected $casts = [
        'kayit_tarihi' => 'datetime',
        'ham_veri' => 'array',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new FirmaScope);
    }

    public function firma()
    {
        return $this->belongsTo(Firma::class, 'firma_id');
    }

    public function cihaz()
    {
        return $this->belongsTo(PdksCihazi::class, 'cihaz_id');
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id');
    }
}
