<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $rol = $user?->rol ?? 'kullanici';
        $yetkiler = $user ? config("yetkiler.{$rol}", []) : [];

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'firma' => $user?->firma?->loadMissing('paket'),
                'bildirimler' => $user ? $user->unreadNotifications()->latest()->take(5)->get() : [],
                'yetkiler' => $yetkiler,
                'isSuperAdmin' => $user && $user->rol === 'admin' && $user->superAdminYetki?->yetkiler && count($user->superAdminYetki->yetkiler) > 0,
            ],
            'impersonate' => [
                'aktif' => session()->has('impersonate_super_admin_id'),
                'firmaAdi' => session()->get('impersonate_firma_adi'),
            ],
            'platformDuyurulari' => $user ? \Illuminate\Support\Facades\DB::table('platform_duyurulari')
                ->where('created_at', '>=', now()->subDays(30))
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get() : [],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
        ];
    }
}
