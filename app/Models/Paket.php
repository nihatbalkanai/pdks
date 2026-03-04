<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paket extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $table = 'paketler';

    protected $fillable = [
        'paket_adi',
        'fiyat',
        'ozellikler',
    ];

    protected $casts = [
        'ozellikler' => 'array',
        'fiyat' => 'decimal:2',
    ];

    public function firmalar()
    {
        return $this->hasMany(Firma::class);
    }
    
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
