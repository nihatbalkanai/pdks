<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalismaGrubu extends Model
{
    use SoftDeletes;

    protected $table = 'calisma_gruplari';

    protected $fillable = [
        'firma_id', 'aciklama', 'hesap_parametresi', 'durum',
    ];

    protected $casts = [
        'durum' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new FirmaScope);
    }

    public function planlar()
    {
        return $this->hasMany(CalismaPlan::class, 'calisma_grubu_id');
    }
}
