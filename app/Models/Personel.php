<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\HasApiTokens;

class Personel extends Model
{
    use HasApiTokens, SoftDeletes;

    protected $table = 'personeller';

    protected static function booted(): void
    {
        static::addGlobalScope(new FirmaScope);

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
    protected $fillable = [
        'firma_id',
        'sube_id',
        'servis_id',
        'puantaj_parametre_id',
        'aylik_puantaj_parametre_id',
        'kart_no',
        'ad_soyad', // Still keeping ad_soyad for backward compatibility
        'ad',
        'soyad',
        'sicil_no',
        'ssk_no',
        'unvan',
        'sirket',
        'bolum',
        'ozel_kod',
        'departman',
        'servis_kodu',
        'hesap_gurubu',
        'agi',
        'aylik_ucret',
        'elden_odeme',
        'gunluk_ucret',
        'saat_1',
        'saat_2',
        'saat_3',
        'giris_tarihi',
        'cikis_tarihi',
        'resim_yolu',
        'durum',
        'notlar',
        'email',
        'telefon',
        'gec_kalma_bildirimi',
        'dogum_tarihi',
        'tc_no',
        'iban_no',
        'adres',
        'acil_kisi_adi',
        'acil_kisi_telefonu',
        'yemek_tipi',
        'yemek_kart_no',
        'yemek_ucreti',
        'ulasim_tipi',
        'servis_plaka',
        'yol_parasi',
    ];

    public function izinler()
    {
        return $this->hasMany(PersonelIzin::class, 'personel_id');
    }

    public function avansKesintiler()
    {
        return $this->hasMany(PersonelAvansKesinti::class, 'personel_id');
    }

    public function primKazanclar()
    {
        return $this->hasMany(PersonelPrimKazanc::class, 'personel_id');
    }

    public function pdksKayitlari()
    {
        return $this->hasMany(PdksKaydi::class, 'personel_id');
    }

    public function zimmetler()
    {
        return $this->hasMany(PersonelZimmet::class, 'personel_id');
    }

    public function dosyalar()
    {
        return $this->hasMany(PersonelDosya::class, 'personel_id');
    }

    public function firma()
    {
        return $this->belongsTo(Firma::class, 'firma_id');
    }

    public function sube()
    {
        return $this->belongsTo(Sube::class);
    }

    public function servis()
    {
        return $this->belongsTo(Servis::class);
    }

    public function puantajParametresi()
    {
        return $this->belongsTo(GunlukPuantajParametresi::class, 'puantaj_parametre_id');
    }

    public function aylikPuantajParametresi()
    {
        return $this->belongsTo(AylikPuantajParametresi::class, 'aylik_puantaj_parametre_id');
    }
}
