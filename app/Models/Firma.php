<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Firma extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'firmalar';

    protected $fillable = [
        'paket_id',
        'firma_adi',
        'vergi_no',
        'vergi_dairesi',
        'adres',
        'durum',
        'abonelik_bitis_tarihi',
        'paket_tipi',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function paket()
    {
        return $this->belongsTo(Paket::class, 'paket_id');
    }
}
