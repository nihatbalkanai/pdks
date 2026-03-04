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
        // Eğer kullanıcı giriş yapmışsa ve sistem admini değilse
        if (Auth::hasUser() && Auth::user()->rol !== 'admin') {
            $builder->where($model->getTable() . '.firma_id', Auth::user()->firma_id);
            
            // Kullanıcı sadece şube müdürü ise ve tabloda sube_id varsa (fillable içindeyse veya model methodu varsa)
            if (Auth::user()->rol === 'sube_muduru' && in_array('sube_id', $model->getFillable())) {
                $builder->where($model->getTable() . '.sube_id', Auth::user()->sube_id);
            }
        }
    }
}
