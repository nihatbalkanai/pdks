<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Models\PdksCihazi;
use App\Models\PdksGunlukOzet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard istatistiklerini yüksek performanslı özet tablosundan getirir.
     */
    public function index(): Response
    {
        $firmaId = Auth::user()->firma_id ?? 1;
        $bugun = Carbon::today()->toDateString();
        
        // Cihaz Durumları (Cihazlar zaten az sayıda kayıttır, bellekte taramak performans açısından uygundur)
        $cihazlar = PdksCihazi::where('firma_id', $firmaId)->get();
        $cihazSayisi = $cihazlar->count();
        $aktifCihazSayisi = $cihazlar->filter(function($c) {
             return $c->son_aktivite_tarihi && Carbon::parse($c->son_aktivite_tarihi)->diffInMinutes(now()) <= 5;
        })->count();

        // Bugün İçeride Olanlar (Performanslı okuma)
        // Giriş yapmış ama henüz çıkış okutmamış olanlar: ilk_giris NOT null ve son_cikis NULL
        $iceridekiSayisi = PdksGunlukOzet::where('firma_id', $firmaId)
            ->where('tarih', $bugun)
            ->whereNotNull('ilk_giris')
            ->whereNull('son_cikis')
            ->count();
            
        // Gec Kalanlar (Performanslı okuma)
        $gecKalanSayisi = PdksGunlukOzet::where('firma_id', $firmaId)
            ->where('tarih', $bugun)
            ->where('durum', 'geç kaldı')
            ->count();

        // Anlık son 5 PDKS Hareketi (WebSocket ile anlık yenilenmek üzere arayüze taşınır)
        $sonHareketler = \App\Models\PdksKayit::with('personel')
            ->where('firma_id', $firmaId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'iceridekiler' => $iceridekiSayisi,
            'gecKalanlar' => $gecKalanSayisi,
            'toplamCihaz' => $cihazSayisi,
            'aktifCihaz' => $aktifCihazSayisi,
            'sonCihazlar' => $cihazlar->take(5),
            'sonHareketler' => $sonHareketler,
        ]);
    }
}
