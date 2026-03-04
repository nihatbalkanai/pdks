<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BordroAlani extends Model
{
    use SoftDeletes;

    protected $table = 'bordro_alanlari';

    protected $fillable = [
        'firma_id', 'kod', 'aciklama', 'gun', 'saat', 'ucret', 'bordro_tipi',
    ];

    protected $casts = [
        'kod' => 'integer',
        'gun' => 'boolean',
        'saat' => 'boolean',
        'ucret' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new FirmaScope);
    }
}
