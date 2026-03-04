<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vardiya extends Model
{
    use SoftDeletes;

    protected $table = 'vardiyalar';

    protected $fillable = [
        'firma_id', 'ad', 'baslangic_saati', 'bitis_saati',
        'toplam_sure', 'renk', 'durum',
    ];

    protected $casts = [
        'durum' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new FirmaScope);
    }

    public function calismaPlanlar()
    {
        return $this->hasMany(CalismaPlan::class, 'vardiya_id');
    }
}
