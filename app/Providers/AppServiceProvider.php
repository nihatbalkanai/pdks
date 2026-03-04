<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // RBAC: Rol Bazlı Yetkilendirme Kontrolleri
        \Illuminate\Support\Facades\Gate::define('sistem_yonetimi', function ($user) {
            return $user->rol === 'admin'; // Sistem Admin
        });
        
        \Illuminate\Support\Facades\Gate::define('firma_tam_yetki', function ($user) {
            return in_array($user->rol, ['admin', 'firma_patronu', 'yonetici']); // Firma Patronu (Eski yonetici)
        });

        \Illuminate\Support\Facades\Gate::define('sube_islem_yetkisi', function ($user) {
            return in_array($user->rol, ['admin', 'firma_patronu', 'yonetici', 'sube_muduru']);
        });

        \Illuminate\Support\Facades\Gate::define('personel_islem_yetkisi', function ($user) {
            return in_array($user->rol, ['admin', 'firma_patronu', 'yonetici', 'sube_muduru', 'insan_kaynaklari']);
        });

        // Super Admin Granular Yetkileri
        \Illuminate\Support\Facades\Gate::define('firmalari_gorme', function ($user) {
            $yetkiler = $user->superAdminYetki?->yetkiler ?? [];
            return $user->rol === 'admin' && (in_array('firmalari_gorme', $yetkiler) || in_array('*', $yetkiler));
        });

        \Illuminate\Support\Facades\Gate::define('odemeleri_yonet', function ($user) {
            $yetkiler = $user->superAdminYetki?->yetkiler ?? [];
            return $user->rol === 'admin' && (in_array('odemeleri_yonet', $yetkiler) || in_array('*', $yetkiler));
        });

        \Illuminate\Support\Facades\Gate::define('teknik_loglar_gorme', function ($user) {
            $yetkiler = $user->superAdminYetki?->yetkiler ?? [];
            return $user->rol === 'admin' && (in_array('teknik_loglar', $yetkiler) || in_array('*', $yetkiler));
        });

        \Illuminate\Support\Facades\Gate::define('admin_yonetimi', function ($user) {
            $yetkiler = $user->superAdminYetki?->yetkiler ?? [];
            return $user->rol === 'admin' && (in_array('admin_yonetimi', $yetkiler) || in_array('*', $yetkiler));
        });

        // Sistemin log panelini (Log Viewer) sadece "admin" yetkisindeki kullanıcılara açma kısıtlaması
        if (class_exists(\Opcodes\LogViewer\Facades\LogViewer::class)) {
            \Opcodes\LogViewer\Facades\LogViewer::auth(function ($request) {
                if ($request->user() && $request->user()->rol === 'admin') {
                    $yetkiler = $request->user()->superAdminYetki?->yetkiler ?? [];
                    return in_array('teknik_loglar', $yetkiler) || in_array('*', $yetkiler);
                }
                return false;
            });
        }

        // Sistemin 500ms ve üzeri (lokal test için 10ms olsun) sorgularını yakalayıp logla
        \Illuminate\Support\Facades\DB::whenQueryingForLongerThan(10, function ($connection, $event) {
            try {
                // Kendi loop'una girmemesi için log veya firma tabloları esnasındaki yavaşlıkları yoksay
                if (\Illuminate\Support\Str::contains($event->sql, ['sistem_loglari', 'firmalar'])) {
                    return;
                }

                $ilkFirma = \App\Models\Firma::first();
                if($ilkFirma) {
                    \Illuminate\Support\Facades\DB::table('sistem_loglari')->insert([
                        'uuid' => (string) \Illuminate\Support\Str::uuid(),
                        'firma_id' => $ilkFirma->id,
                        'islem' => 'yavas_sorgu',
                        'detay' => "Süre: {$event->time}ms | SQL: {$event->sql}",
                        'tarih' => now()
                    ]);
                }
            } catch (\Throwable $th) {
                // Sessizce hatayı atla (Migration öncesi crashlememesi için)
            }
        });
    }
}
