<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\PaketTanimi;

class WebController extends Controller
{
    public function anasayfa()
    {
        return Inertia::render('Web/Anasayfa', [
            'paketler' => PaketTanimi::where('aktif', true)->orderBy('sira')->get(),
        ]);
    }

    public function hakkimizda()
    {
        return Inertia::render('Web/Hakkimizda');
    }

    public function fiyatlar()
    {
        return Inertia::render('Web/Fiyatlar', [
            'paketler' => PaketTanimi::where('aktif', true)->orderBy('sira')->get(),
        ]);
    }

    public function referanslar()
    {
        return Inertia::render('Web/Referanslar');
    }

    public function haberler()
    {
        return Inertia::render('Web/Haberler');
    }

    public function iletisim()
    {
        return Inertia::render('Web/Iletisim');
    }
}
