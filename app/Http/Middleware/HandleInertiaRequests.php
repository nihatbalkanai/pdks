<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'firma' => $request->user()?->firma?->loadMissing('paket'),
                'bildirimler' => $request->user() ? $request->user()->unreadNotifications()->latest()->take(5)->get() : [],
                'can' => $request->user() ? [
                    'sistem_yonetimi' => $request->user()->can('sistem_yonetimi'),
                    'firma_tam_yetki' => $request->user()->can('firma_tam_yetki'),
                    'sube_islem_yetkisi' => $request->user()->can('sube_islem_yetkisi'),
                    'personel_islem_yetkisi' => $request->user()->can('personel_islem_yetkisi'),
                ] : [],
            ],
        ];
    }
}
