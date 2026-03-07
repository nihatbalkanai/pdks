<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaketTanimi extends Model
{
    use HasFactory;

    protected $table = 'paket_tanimlari';

    protected $fillable = [
        'ad', 'kod', 'max_personel', 'max_kullanici', 'max_cihaz',
        'aylik_fiyat', 'yillik_fiyat', 'ozellikler', 'aciklama',
        'renk', 'sira', 'aktif',
    ];

    protected $casts = [
        'ozellikler' => 'array',
        'aylik_fiyat' => 'decimal:2',
        'yillik_fiyat' => 'decimal:2',
        'aktif' => 'boolean',
    ];

    /**
     * Limit açıklaması (0 = Sınırsız)
     */
    public function limitAciklama($deger)
    {
        return $deger == 0 ? 'Sınırsız' : number_format($deger);
    }

    /**
     * Firmalar ilişkisi
     */
    public function firmalar()
    {
        return $this->hasMany(Firma::class, 'paket_tipi', 'ad');
    }
}
