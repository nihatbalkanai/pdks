<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.Kullanici.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('firma.{id}', function ($user, $id) {
    return (int) $user->firma_id === (int) $id;
});
