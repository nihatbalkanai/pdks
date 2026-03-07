<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureGate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ozellik): Response
    {
        $kullanici = $request->user();
        
        // Super admin her şeye erişebilir
        if (!$kullanici || $kullanici->rol === 'admin') {
            return $next($request);
        }

        // Firma yoksa atla
        if (!$kullanici->firma_id) {
            return $next($request);
        }

        $firma = $kullanici->firma;
        $paket = $firma ? $firma->paket : null;

        $sahipMi = false;
        
        // Paket atanmamışsa tüm özellikler açık (demo/geliştirme)
        if (!$paket) {
            $sahipMi = true;
        } elseif (is_array($paket->ozellikler)) {
            if (in_array($ozellik, $paket->ozellikler)) {
                $sahipMi = true;
            }
        }
        
        // Enterprise her şeye sahip
        if ($paket && $paket->paket_adi === 'Enterprise') {
            $sahipMi = true;
        }

        if (!$sahipMi) {
            return redirect()->route('dashboard')->with('error', "Bu özellik ($ozellik) mevcut paketinizde bulunmamaktadır. Lütfen paketinizi yükseltiniz.");
        }

        return $next($request);
    }
}
