<?php

namespace App\Models;

use App\Models\Scopes\FirmaScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Kullanici extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasUuids;

    protected $table = 'kullanicilar';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'firma_id',
        'sube_id',
        'ad_soyad',
        'eposta',
        'sifre',
        'rol',
    ];

    protected $appends = [
        'name',
        'email',
    ];

    protected $hidden = [
        'sifre',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'sifre' => 'hashed',
        ];
    }

    public function getAuthPassword()
    {
        return $this->sifre;
    }
    
    public function getAuthPasswordName()
    {
        return 'sifre';
    }

    public function getEmailForPasswordReset()
    {
        return $this->eposta;
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getNameAttribute()
    {
        return $this->ad_soyad;
    }

    public function getEmailAttribute()
    {
        return $this->eposta;
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

    public function superAdminYetki()
    {
        return $this->hasOne(SuperAdminYetki::class, 'kullanici_id');
    }
}
