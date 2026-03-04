<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AbonelikKontrol
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->rol !== 'admin') {
            $firma = auth()->user()->firma;
            if ($firma && $firma->abonelik_bitis_tarihi && now()->startOfDay()->greaterThan(\Carbon\Carbon::parse($firma->abonelik_bitis_tarihi)->startOfDay())) {
                auth()->logout();
                return redirect()->route('login')->withErrors(['email' => 'Firmanızın abonelik süresi dolmuştur. Lütfen sistem yöneticinizle veya techsend.io destek ile iletişime geçin.']);
            }
        }
        return $next($request);
    }
}
