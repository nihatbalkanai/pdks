<?php

namespace App\Models\Scopes;

trait FirmaScopeTrait
{
    protected static function booted()
    {
        static::addGlobalScope(new \App\Models\Scopes\FirmaScope);
    }
}
