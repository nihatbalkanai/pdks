<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'abonelik' => \App\Http\Middleware\AbonelikKontrol::class,
            'superadmin' => \App\Http\Middleware\SuperAdminKontrol::class,
            'rol.yetki' => \App\Http\Middleware\RolYetkiKontrol::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'hata' => true,
                    'mesaj' => 'Sunucu ile iletişimde bir sorun oluştu, lütfen teknik ekibe bildiriniz.',
                ], 500);
            }

            // Arayüz kullanıcıları için özel Inertia hata sayfası
            if ($request->wantsJson()) { // Vue/Inertia requests
                return Inertia\Inertia::render('Error', [
                    'durum' => 500,
                    'mesaj' => 'Sistemde beklenmeyen bir hata oluştu ve teknik ekibe otomatik olarak iletildi.'
                ])->toResponse($request)->setStatusCode(500);
            }
        });
    })->create();
