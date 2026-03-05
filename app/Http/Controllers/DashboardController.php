<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Models\PdksCihazi;
use App\Models\PdksGunlukOzet;
use App\Models\PdksKayit;
use App\Models\Personel;
use App\Models\PersonelIzin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard istatistiklerini yüksek performanslı özet tablosundan getirir.
     */
    public function index(): Response
    {
        $firmaId = Auth::user()->firma_id ?? 1;
        $bugun = Carbon::today()->toDateString();

        // ==========================================
        // 1. BUGÜNKÜ DURUM KARTLARI
        // ==========================================
        $bugunOzetler = PdksGunlukOzet::where('firma_id', $firmaId)
            ->where('tarih', $bugun)
            ->get();

        $toplamPersonel = Personel::where('firma_id', $firmaId)->where('durum', true)->count();
        $geldiSayisi = $bugunOzetler->whereIn('durum', ['geldi', 'geç kaldı', 'erken_cikis'])->whereNotNull('ilk_giris')->count();
        $gelmediSayisi = $toplamPersonel - $geldiSayisi;
        $gecKalanSayisi = $bugunOzetler->where('durum', 'geç kaldı')->count();
        $iceridekiSayisi = $bugunOzetler->whereNotNull('ilk_giris')->filter(fn($o) => $o->son_cikis === null)->count();

        // Cihaz Durumları
        $cihazlar = PdksCihazi::where('firma_id', $firmaId)->get();
        $aktifCihazSayisi = $cihazlar->filter(function($c) {
            return $c->son_aktivite_tarihi && Carbon::parse($c->son_aktivite_tarihi)->diffInMinutes(now()) <= 5;
        })->count();

        // ==========================================
        // 2. SON 7 GÜN DEVAM TRENDİ (Grafik verisi)
        // ==========================================
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $tarih = Carbon::today()->subDays($i);
            // Hafta sonu atla mı? Hayır — hepsini gösterelim, hafta sonlarında 0 olacak
            $gunOzet = PdksGunlukOzet::where('firma_id', $firmaId)
                ->where('tarih', $tarih->toDateString())
                ->get();

            $trend[] = [
                'tarih'    => $tarih->locale('tr')->isoFormat('dd DD.MM'),
                'tarihRaw' => $tarih->toDateString(),
                'geldi'    => $gunOzet->whereIn('durum', ['geldi', 'geç kaldı', 'erken_cikis'])->whereNotNull('ilk_giris')->count(),
                'gelmedi'  => $gunOzet->where('durum', 'gelmedi')->count(),
                'gecKaldi'  => $gunOzet->where('durum', 'geç kaldı')->count(),
                'haftaSonu' => $tarih->isWeekend(),
            ];
        }

        // ==========================================
        // 3. GEÇ KALANLAR LİSTESİ (Bugün İlk 10)
        // ==========================================
        $gecKalanlar = PdksGunlukOzet::with('personel')
            ->where('firma_id', $firmaId)
            ->where('tarih', $bugun)
            ->where('durum', 'geç kaldı')
            ->orderBy('ilk_giris', 'desc')
            ->take(10)
            ->get()
            ->map(fn($o) => [
                'ad_soyad'  => $o->personel ? $o->personel->ad . ' ' . $o->personel->soyad : '-',
                'kart_no'   => $o->personel->kart_no ?? '-',
                'ilk_giris' => $o->ilk_giris ? Carbon::parse($o->ilk_giris)->format('H:i') : '-',
            ]);

        // ==========================================
        // 4. ŞU AN İÇERİDEKİLER LİSTESİ (İlk 10)
        // ==========================================
        $iceridekiler = PdksGunlukOzet::with('personel')
            ->where('firma_id', $firmaId)
            ->where('tarih', $bugun)
            ->whereNotNull('ilk_giris')
            ->whereNull('son_cikis')
            ->orderBy('ilk_giris')
            ->take(10)
            ->get()
            ->map(fn($o) => [
                'ad_soyad'  => $o->personel ? $o->personel->ad . ' ' . $o->personel->soyad : '-',
                'kart_no'   => $o->personel->kart_no ?? '-',
                'ilk_giris' => $o->ilk_giris ? Carbon::parse($o->ilk_giris)->format('H:i') : '-',
            ]);

        // ==========================================
        // 5. YAKLAŞAN İZİNLER (Önümüzdeki 7 gün)
        // ==========================================
        $yaklasanIzinler = PersonelIzin::with('personel')
            ->where('firma_id', $firmaId)
            ->where('durum', 'onaylandi')
            ->where('tarih', '>=', $bugun)
            ->where('tarih', '<=', Carbon::today()->addDays(7)->toDateString())
            ->orderBy('tarih')
            ->take(5)
            ->get()
            ->map(fn($i) => [
                'ad_soyad'    => $i->personel ? $i->personel->ad . ' ' . $i->personel->soyad : '-',
                'tarih'       => Carbon::parse($i->tarih)->format('d.m'),
                'bitis'       => $i->bitis_tarihi ? Carbon::parse($i->bitis_tarihi)->format('d.m') : '-',
                'gun_sayisi'  => $i->gun_sayisi,
                'aciklama'    => $i->aciklama ?? '-',
            ]);

        // ==========================================
        // 6. DOĞUM GÜNÜ BİLDİRİMLERİ (Bu ay)
        // ==========================================
        $buAy = Carbon::now()->month;
        $dogumGunleri = Personel::where('firma_id', $firmaId)
            ->where('durum', true)
            ->whereNotNull('dogum_tarihi')
            ->whereMonth('dogum_tarihi', $buAy)
            ->orderByRaw('DAY(dogum_tarihi)')
            ->take(5)
            ->get()
            ->map(fn($p) => [
                'ad_soyad'     => $p->ad . ' ' . $p->soyad,
                'dogum_tarihi' => Carbon::parse($p->dogum_tarihi)->format('d.m.Y'),
                'gun'          => Carbon::parse($p->dogum_tarihi)->day,
                'bugun_mu'     => Carbon::parse($p->dogum_tarihi)->day === Carbon::today()->day,
            ]);

        // ==========================================
        // 7. SON 5 PDKS HAREKETİ (Anlık)
        // ==========================================
        $sonHareketler = PdksKayit::with('personel')
            ->where('firma_id', $firmaId)
            ->orderBy('kayit_tarihi', 'desc')
            ->take(5)
            ->get()
            ->map(fn($h) => [
                'id'          => $h->id,
                'ad_soyad'    => $h->personel ? $h->personel->ad . ' ' . $h->personel->soyad : '-',
                'islem_tipi'  => $h->islem_tipi,
                'saat'        => Carbon::parse($h->kayit_tarihi)->format('H:i:s'),
                'tarih'       => Carbon::parse($h->kayit_tarihi)->format('d.m'),
            ]);

        return Inertia::render('Dashboard', [
            'toplamPersonel'   => $toplamPersonel,
            'geldiSayisi'      => $geldiSayisi,
            'gelmediSayisi'    => $gelmediSayisi,
            'gecKalanSayisi'   => $gecKalanSayisi,
            'iceridekiSayisi'  => $iceridekiSayisi,
            'toplamCihaz'      => $cihazlar->count(),
            'aktifCihaz'       => $aktifCihazSayisi,
            'trend'            => $trend,
            'gecKalanlar'      => $gecKalanlar,
            'iceridekiler'     => $iceridekiler,
            'yaklasanIzinler'  => $yaklasanIzinler,
            'dogumGunleri'     => $dogumGunleri,
            'sonHareketler'    => $sonHareketler,
        ]);
    }
}
