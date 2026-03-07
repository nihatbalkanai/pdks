<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminKontrol
{
    /**
     * Super Admin erişim kontrolü.
     * Sadece rol='admin' VE super_admin_yetkileri tablosunda yetkisi olan kullanıcılar geçebilir.
     * Müşteri firmasının admin'i bu middleware'den GEÇEMEZ.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // 1. Giriş yapmış mı?
        if (!$user) {
            abort(403, 'Bu alana erişim yetkiniz bulunmuyor.');
        }

        // 2. Rolü admin mi?
        if ($user->rol !== 'admin') {
            abort(403, 'Bu alana erişim yetkiniz bulunmuyor.');
        }

        // 3. Super admin yetki kaydı var mı VE en az 1 yetkisi var mı?
        $superAdminYetki = $user->superAdminYetki;
        if (!$superAdminYetki || empty($superAdminYetki->yetkiler)) {
            abort(403, 'Bu alana erişim yetkiniz bulunmuyor. Platform yönetim yetkisi gereklidir.');
        }

        return $next($request);
    }
}
