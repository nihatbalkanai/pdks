<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\HasApiTokens;

class PdksCihazi extends Model
{
    use HasApiTokens, SoftDeletes, HasUuids;

    protected $table = 'pdks_cihazlari';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'firma_id',
        'sube_id',
        'seri_no',
        'cihaz_modeli',
        'son_aktivite_tarihi',
    ];

    /**
     * Unique ID columns mapping
     */
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

    public function sube()
    {
        return $this->belongsTo(Sube::class);
    }
}
