<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GunlukPuantajBordroAlani extends Model
{
    protected $table = 'gunluk_puantaj_bordro_alanlari';

    protected $fillable = [
        'gunluk_puantaj_id',
        'bordro_alani',
        'basla',
        'bitis',
        'min_sure',
        'max_sure',
        'ekle',
        'carpan',
        'ucret',
    ];

    protected $casts = [
        'carpan' => 'integer',
    ];

    public function gunlukPuantaj()
    {
        return $this->belongsTo(GunlukPuantajParametresi::class, 'gunluk_puantaj_id');
    }
}
