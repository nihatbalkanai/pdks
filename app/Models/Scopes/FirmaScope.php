<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class FirmaScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Eğer kullanıcı giriş yapmışsa
        if (Auth::hasUser()) {
            $user = Auth::user();

            // Mobil API: Personel modeli ile giriş yapılmışsa
            if ($user instanceof \App\Models\Personel) {
                $builder->where($model->getTable() . '.firma_id', $user->firma_id);
                return;
            }

            // Web Panel: Kullanıcı modeli ile giriş yapılmışsa
            if ($user->rol !== 'admin') {
                $builder->where($model->getTable() . '.firma_id', $user->firma_id);

                // Kullanıcı sadece şube müdürü ise
                if ($user->rol === 'sube_muduru' && in_array('sube_id', $model->getFillable())) {
                    $builder->where($model->getTable() . '.sube_id', $user->sube_id);
                }
            }
        }
    }
}
