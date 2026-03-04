<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolYetkiKontrol
{
    /**
     * Kullanıcının rolüne göre yetki kontrolü yapar.
     * Kullanım: middleware('rol.yetki:personel_islemleri')
     */
    public function handle(Request $request, Closure $next, string $yetki): Response
    {
        if (!auth()->check()) {
            abort(403, 'Giriş yapmalısınız.');
        }

        $rol = auth()->user()->rol ?? 'kullanici';
        $yetkiler = config("yetkiler.{$rol}", []);

        if (!($yetkiler[$yetki] ?? false)) {
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                return back()->with('error', 'Bu işlem için yetkiniz bulunmuyor.');
            }
            abort(403, 'Bu alana erişim yetkiniz bulunmuyor.');
        }

        return $next($request);
    }
}
