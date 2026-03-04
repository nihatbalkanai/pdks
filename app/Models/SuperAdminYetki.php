<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperAdminYetki extends Model
{
    protected $table = 'super_admin_yetkileri';

    protected $fillable = [
        'kullanici_id',
        'yetkiler',
    ];

    protected $casts = [
        'yetkiler' => 'array',
    ];

    public function kullanici()
    {
        return $this->belongsTo(Kullanici::class);
    }
}
