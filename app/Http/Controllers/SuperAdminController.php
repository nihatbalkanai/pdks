<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Firma;
use App\Models\PdksCihazi;

class SuperAdminController extends Controller
{
    public function index()
    {
        \Illuminate\Support\Facades\Gate::authorize('firmalari_gorme');
        // Temel Metrikler
        $aktifFirmaSayisi = Firma::where('durum', true)->count();
        $toplamCihaz = PdksCihazi::count();
        $son24SaatKayit = \Illuminate\Support\Facades\DB::table('pdks_kayitlari')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
            
        $firmalar = Firma::orderBy('created_at', 'desc')->paginate(10);
        
        // Sadece teknik log görme yetkisi varsa görebilsin
        $yavasSorgular = \Illuminate\Support\Facades\Gate::allows('teknik_loglar_gorme') ? \Illuminate\Support\Facades\DB::table('sistem_loglari')
            ->where('islem', 'yavas_sorgu')
            ->orderBy('tarih', 'desc')
            ->limit(10)
            ->get() : [];

        // Sistem İzleme (Pulse Simülasyonu - PHP Seviyesi İşlemci/Ram Tüketimi)
        // System Load Average (Linux tabanlı için geçerli, Windows'ta farklı olabilir)
        $cpuLoad = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0;
        
        // Bellek Tüketimi
        $ramKullanimi = memory_get_usage(true) / 1024 / 1024; // MB
        
        // Fake grafik verisi üretelim (Son 7 gün için sistem yükü)
        $grafikVerisi = [
            'labels' => collect(range(6, 0))->map(fn($days) => now()->subDays($days)->format('d M'))->toArray(),
            'data' => collect(range(6, 0))->map(fn() => rand(10, 80))->toArray(), // Stres yükü simülasyonu
        ];

        // Yönetici (Super Admin) Listesi - Sadece yetkisi olanlar
        $adminler = \Illuminate\Support\Facades\Gate::allows('admin_yonetimi') ? \App\Models\Kullanici::with('superAdminYetki')->where('rol', 'admin')->get() : [];

        return Inertia::render('SuperAdmin/Dashboard', [
            'metrikler' => [
                'aktifFirma' => $aktifFirmaSayisi,
                'toplamCihaz' => $toplamCihaz,
                'sonKayitSayisi' => $son24SaatKayit,
                'kuyrukBekleyen' => \Illuminate\Support\Facades\DB::table('jobs')->count()
            ],
            'sistem' => [
                'cpu' => round($cpuLoad * 10, 2), // Demo/Yaklaşık Değer
                'ram' => round($ramKullanimi, 2),
                'grafik' => $grafikVerisi,
            ],
            'firmalar' => $firmalar,
            'yavasSorgular' => $yavasSorgular,
            'adminler' => $adminler,
            'can' => [
                'odemeleri_yonet' => \Illuminate\Support\Facades\Gate::allows('odemeleri_yonet'),
                'teknik_loglar_gorme' => \Illuminate\Support\Facades\Gate::allows('teknik_loglar_gorme'),
                'admin_yonetimi' => \Illuminate\Support\Facades\Gate::allows('admin_yonetimi'),
            ]
        ]);
    }

    public function updateAbonelik(Request $request, $id)
    {
        \Illuminate\Support\Facades\Gate::authorize('odemeleri_yonet');
        $request->validate([
            'abonelik_bitis_tarihi' => 'required|date',
            'paket_tipi' => 'required|in:Ücretsiz,Standart,Pro',
            'durum' => 'required|boolean'
        ]);

        $firma = Firma::findOrFail($id);
        $firma->update([
            'abonelik_bitis_tarihi' => $request->abonelik_bitis_tarihi,
            'paket_tipi' => $request->paket_tipi,
            'durum' => $request->durum
        ]);

        return response()->json(['success' => true, 'message' => 'Firma abonelik detayları güncellendi.']);
    }

    public function updateAdminYetki(Request $request, $id)
    {
        \Illuminate\Support\Facades\Gate::authorize('admin_yonetimi');
        
        $request->validate([
            'yetkiler' => 'array'
        ]);

        $kullanici = \App\Models\Kullanici::where('rol', 'admin')->findOrFail($id);
        
        \App\Models\SuperAdminYetki::updateOrCreate(
            ['kullanici_id' => $kullanici->id],
            ['yetkiler' => $request->yetkiler ?? []]
        );

        return response()->json(['success' => true, 'message' => 'Admin yetkileri başarıyla güncellendi.']);
    }
}
