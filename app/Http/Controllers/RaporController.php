<?php

namespace App\Http\Controllers;

use App\Models\PdksGunlukOzet;
use App\Models\PdksKayit;
use App\Models\Personel;
use App\Models\PersonelAvansKesinti;
use App\Models\PersonelIzin;
use App\Models\PersonelPrimKazanc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RaporController extends Controller
{
    // Firma ID yardımcı
    private function firmaId() { return Auth::user()->firma_id ?? 1; }

    // Tarih filtresi varsayılanları
    private function tarihler(Request $r)
    {
        return [
            $r->input('baslangic', now()->startOfMonth()->toDateString()),
            $r->input('bitis', now()->toDateString()),
        ];
    }

    // Personeller listesi (filtre dropdown için)
    private function personelListesi()
    {
        return Personel::select('id', 'kart_no', 'ad', 'soyad')
            ->orderBy('kart_no')
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'ad_soyad' => $p->kart_no . ' - ' . $p->ad . ' ' . $p->soyad]);
    }

    /**
     * Genel rapor görüntüleme (tüm 22 rapor tek Vue sayfası kullanır)
     */
    private function raporGoster(Request $request, string $raporNo, string $baslik, $query, array $kolonlar, ?string $personelFiltre = null)
    {
        [$baslangic, $bitis] = $this->tarihler($request);
        $personelId = $request->input('personel_id');

        if ($personelId && $personelFiltre === 'personel_id') {
            $query->where('personel_id', $personelId);
        }

        $veriler = $query->get();

        return Inertia::render('Raporlar/RaporGoster', [
            'raporNo'     => $raporNo,
            'baslik'      => $baslik,
            'veriler'     => $veriler,
            'kolonlar'    => $kolonlar,
            'personeller' => $this->personelListesi(),
            'filtreler'   => [
                'baslangic'   => $baslangic,
                'bitis'       => $bitis,
                'personel_id' => $personelId,
            ],
        ]);
    }

    // =================================================================
    // 01. Genel Bazda Giriş Çıkış Listesi
    // =================================================================
    public function r01(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $query = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->whereNotNull('ilk_giris')
            ->orderBy('tarih', 'desc');

        return $this->raporGoster($request, '01', 'Genel Bazda Giriş Çıkış Listesi', $query, [
            ['key' => 'personel.kart_no', 'label' => 'KartNo'],
            ['key' => 'personel.ad', 'label' => 'Ad'],
            ['key' => 'personel.soyad', 'label' => 'Soyad'],
            ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
            ['key' => 'ilk_giris', 'label' => 'Giriş', 'type' => 'time'],
            ['key' => 'son_cikis', 'label' => 'Çıkış', 'type' => 'time'],
            ['key' => 'toplam_calisma_suresi', 'label' => 'Süre', 'type' => 'dakika'],
            ['key' => 'durum', 'label' => 'Durum', 'type' => 'badge'],
        ]);
    }

    // =================================================================
    // 02. Kişi Bazında Giriş Çıkış Listesi
    // =================================================================
    public function r02(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $query = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->whereNotNull('ilk_giris')
            ->orderBy('tarih', 'desc');

        return $this->raporGoster($request, '02', 'Kişi Bazında Giriş Çıkış Listesi', $query, [
            ['key' => 'personel.kart_no', 'label' => 'KartNo'],
            ['key' => 'personel.ad', 'label' => 'Ad'],
            ['key' => 'personel.soyad', 'label' => 'Soyad'],
            ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
            ['key' => 'ilk_giris', 'label' => 'Giriş', 'type' => 'time'],
            ['key' => 'son_cikis', 'label' => 'Çıkış', 'type' => 'time'],
            ['key' => 'toplam_calisma_suresi', 'label' => 'Süre', 'type' => 'dakika'],
            ['key' => 'durum', 'label' => 'Durum', 'type' => 'badge'],
        ], 'personel_id');
    }

    // =================================================================
    // 03. Genel Bazda Geç Kalanlar Listesi
    // =================================================================
    public function r03(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $query = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->where('durum', 'geç kaldı')
            ->orderBy('tarih', 'desc');

        return $this->raporGoster($request, '03', 'Genel Bazda Geç Kalanlar Listesi', $query, [
            ['key' => 'personel.kart_no', 'label' => 'KartNo'],
            ['key' => 'personel.ad', 'label' => 'Ad'],
            ['key' => 'personel.soyad', 'label' => 'Soyad'],
            ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
            ['key' => 'ilk_giris', 'label' => 'Giriş Saati', 'type' => 'time'],
            ['key' => 'toplam_calisma_suresi', 'label' => 'Süre', 'type' => 'dakika'],
        ]);
    }

    // 04. Kişi Bazında Geç Kalanlar
    public function r04(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $query = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->where('durum', 'geç kaldı')
            ->orderBy('tarih', 'desc');

        return $this->raporGoster($request, '04', 'Kişi Bazında Geç Kalanlar Listesi', $query, [
            ['key' => 'personel.kart_no', 'label' => 'KartNo'],
            ['key' => 'personel.ad', 'label' => 'Ad'],
            ['key' => 'personel.soyad', 'label' => 'Soyad'],
            ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
            ['key' => 'ilk_giris', 'label' => 'Giriş Saati', 'type' => 'time'],
        ], 'personel_id');
    }

    // 05. Genel Bazda Erken Çıkanlar
    public function r05(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $query = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->where('durum', 'erken_cikis')
            ->orderBy('tarih', 'desc');

        return $this->raporGoster($request, '05', 'Genel Bazda Erken Çıkanlar Listesi', $query, [
            ['key' => 'personel.kart_no', 'label' => 'KartNo'],
            ['key' => 'personel.ad', 'label' => 'Ad'],
            ['key' => 'personel.soyad', 'label' => 'Soyad'],
            ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
            ['key' => 'son_cikis', 'label' => 'Çıkış Saati', 'type' => 'time'],
        ]);
    }

    // 06. Kişi Bazında Erken Çıkanlar
    public function r06(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $query = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->where('durum', 'erken_cikis')
            ->orderBy('tarih', 'desc');

        return $this->raporGoster($request, '06', 'Kişi Bazında Erken Çıkanlar Listesi', $query, [
            ['key' => 'personel.kart_no', 'label' => 'KartNo'],
            ['key' => 'personel.ad', 'label' => 'Ad'],
            ['key' => 'personel.soyad', 'label' => 'Soyad'],
            ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
            ['key' => 'son_cikis', 'label' => 'Çıkış Saati', 'type' => 'time'],
        ], 'personel_id');
    }

    // 07. Mesaiye Kalanlar
    public function r07(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $query = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->whereNotNull('son_cikis')
            ->whereRaw("TIME(son_cikis) > '17:30:00'")
            ->orderBy('tarih', 'desc');

        return $this->raporGoster($request, '07', 'Mesaiye Kalanlar Listesi', $query, [
            ['key' => 'personel.kart_no', 'label' => 'KartNo'],
            ['key' => 'personel.ad', 'label' => 'Ad'],
            ['key' => 'personel.soyad', 'label' => 'Soyad'],
            ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
            ['key' => 'ilk_giris', 'label' => 'Giriş', 'type' => 'time'],
            ['key' => 'son_cikis', 'label' => 'Çıkış', 'type' => 'time'],
            ['key' => 'toplam_calisma_suresi', 'label' => 'Süre', 'type' => 'dakika'],
        ]);
    }

    // 08. Devamsızlar Listesi
    public function r08(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $query = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->where('durum', 'gelmedi')
            ->orderBy('tarih', 'desc');

        return $this->raporGoster($request, '08', 'Devamsızlar Listesi', $query, [
            ['key' => 'personel.kart_no', 'label' => 'KartNo'],
            ['key' => 'personel.ad', 'label' => 'Ad'],
            ['key' => 'personel.soyad', 'label' => 'Soyad'],
            ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
            ['key' => 'durum', 'label' => 'Durum', 'type' => 'badge'],
        ]);
    }

    // 09. Girişte Kart Kullanmayı Unutanlar
    public function r09(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $query = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->where('durum', 'eksik_giris')
            ->orderBy('tarih', 'desc');

        return $this->raporGoster($request, '09', 'Girişte Kart Kullanmayı Unutanlar', $query, [
            ['key' => 'personel.kart_no', 'label' => 'KartNo'],
            ['key' => 'personel.ad', 'label' => 'Ad'],
            ['key' => 'personel.soyad', 'label' => 'Soyad'],
            ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
            ['key' => 'son_cikis', 'label' => 'Çıkış Saati', 'type' => 'time'],
        ]);
    }

    // 10. Çıkışta Kart Kullanmayı Unutanlar
    public function r10(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $query = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->where('durum', 'eksik_cikis')
            ->orderBy('tarih', 'desc');

        return $this->raporGoster($request, '10', 'Çıkışta Kart Kullanmayı Unutanlar', $query, [
            ['key' => 'personel.kart_no', 'label' => 'KartNo'],
            ['key' => 'personel.ad', 'label' => 'Ad'],
            ['key' => 'personel.soyad', 'label' => 'Soyad'],
            ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
            ['key' => 'ilk_giris', 'label' => 'Giriş Saati', 'type' => 'time'],
        ]);
    }

    // 11. Giriş yada Çıkışta Unutanlar
    public function r11(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $query = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->whereIn('durum', ['eksik_giris', 'eksik_cikis'])
            ->orderBy('tarih', 'desc');

        return $this->raporGoster($request, '11', 'Giriş yada Çıkışta Unutanlar', $query, [
            ['key' => 'personel.kart_no', 'label' => 'KartNo'],
            ['key' => 'personel.ad', 'label' => 'Ad'],
            ['key' => 'personel.soyad', 'label' => 'Soyad'],
            ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
            ['key' => 'ilk_giris', 'label' => 'Giriş', 'type' => 'time'],
            ['key' => 'son_cikis', 'label' => 'Çıkış', 'type' => 'time'],
            ['key' => 'durum', 'label' => 'Durum', 'type' => 'badge'],
        ]);
    }

    // 12. Şuan İçerideki Personeller
    public function r12(Request $request)
    {
        $query = PdksGunlukOzet::with('personel')
            ->where('tarih', now()->toDateString())
            ->whereNotNull('ilk_giris')
            ->whereNull('son_cikis')
            ->orderBy('ilk_giris');

        return Inertia::render('Raporlar/RaporGoster', [
            'raporNo'     => '12',
            'baslik'      => 'Şuan İçerideki Personeller',
            'veriler'     => $query->get(),
            'kolonlar'    => [
                ['key' => 'personel.kart_no', 'label' => 'KartNo'],
                ['key' => 'personel.ad', 'label' => 'Ad'],
                ['key' => 'personel.soyad', 'label' => 'Soyad'],
                ['key' => 'ilk_giris', 'label' => 'Giriş Saati', 'type' => 'time'],
            ],
            'personeller' => $this->personelListesi(),
            'filtreler'   => ['baslangic' => now()->toDateString(), 'bitis' => now()->toDateString()],
            'tarihGizle'  => true,
        ]);
    }

    // 13. Elle Müdahale Yapılmış Hareketler
    public function r13(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $kayitlar = PdksKayit::with('personel')
            ->where('islem_tipi', 'elle_duzenleme')
            ->whereBetween('kayit_tarihi', [$bas . ' 00:00:00', $bit . ' 23:59:59'])
            ->orderBy('kayit_tarihi', 'desc')
            ->get()
            ->map(fn($k) => [
                'personel' => $k->personel,
                'tarih'    => $k->kayit_tarihi->format('Y-m-d'),
                'saat'     => $k->kayit_tarihi->format('H:i'),
                'aciklama' => $k->ham_veri['aciklama'] ?? '-',
            ]);

        return Inertia::render('Raporlar/RaporGoster', [
            'raporNo'     => '13',
            'baslik'      => 'Elle Müdahale Yapılmış Hareketler',
            'veriler'     => $kayitlar,
            'kolonlar'    => [
                ['key' => 'personel.kart_no', 'label' => 'KartNo'],
                ['key' => 'personel.ad', 'label' => 'Ad'],
                ['key' => 'personel.soyad', 'label' => 'Soyad'],
                ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
                ['key' => 'saat', 'label' => 'Saat'],
                ['key' => 'aciklama', 'label' => 'Açıklama'],
            ],
            'personeller' => $this->personelListesi(),
            'filtreler'   => ['baslangic' => $bas, 'bitis' => $bit],
        ]);
    }

    // 14. Personellerin Not Bilgileri
    public function r14(Request $request)
    {
        $personeller = Personel::select('id', 'kart_no', 'ad', 'soyad', 'notlar')
            ->whereNotNull('notlar')
            ->where('notlar', '!=', '')
            ->orderBy('kart_no')
            ->get()
            ->map(fn($p) => ['personel' => $p, 'kart_no' => $p->kart_no, 'ad' => $p->ad, 'soyad' => $p->soyad, 'notlar' => $p->notlar]);

        return Inertia::render('Raporlar/RaporGoster', [
            'raporNo'     => '14',
            'baslik'      => 'Personellerin Not Bilgileri',
            'veriler'     => $personeller,
            'kolonlar'    => [
                ['key' => 'kart_no', 'label' => 'KartNo'],
                ['key' => 'ad', 'label' => 'Ad'],
                ['key' => 'soyad', 'label' => 'Soyad'],
                ['key' => 'notlar', 'label' => 'Not'],
            ],
            'personeller' => $this->personelListesi(),
            'filtreler'   => [],
            'tarihGizle'  => true,
        ]);
    }

    // 15. Personellerin İrtibat Bilgileri
    public function r15(Request $request)
    {
        $personeller = Personel::select('id', 'kart_no', 'ad', 'soyad', 'telefon', 'email', 'bolum')
            ->orderBy('kart_no')
            ->get()
            ->map(fn($p) => ['kart_no' => $p->kart_no, 'ad' => $p->ad, 'soyad' => $p->soyad, 'telefon' => $p->telefon ?? '-', 'email' => $p->email ?? '-', 'bolum' => $p->bolum ?? '-']);

        return Inertia::render('Raporlar/RaporGoster', [
            'raporNo'     => '15',
            'baslik'      => 'Personellerin İrtibat Bilgileri',
            'veriler'     => $personeller,
            'kolonlar'    => [
                ['key' => 'kart_no', 'label' => 'KartNo'],
                ['key' => 'ad', 'label' => 'Ad'],
                ['key' => 'soyad', 'label' => 'Soyad'],
                ['key' => 'telefon', 'label' => 'Telefon'],
                ['key' => 'email', 'label' => 'E-posta'],
                ['key' => 'bolum', 'label' => 'Bölüm'],
            ],
            'personeller' => $this->personelListesi(),
            'filtreler'   => [],
            'tarihGizle'  => true,
        ]);
    }

    // 16. İşe Giren Personeller
    public function r16(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $personeller = Personel::select('id', 'kart_no', 'ad', 'soyad', 'giris_tarihi', 'bolum', 'unvan')
            ->whereBetween('giris_tarihi', [$bas, $bit])
            ->orderBy('giris_tarihi', 'desc')
            ->get()
            ->map(fn($p) => ['kart_no' => $p->kart_no, 'ad' => $p->ad, 'soyad' => $p->soyad, 'tarih' => $p->giris_tarihi, 'bolum' => $p->bolum ?? '-', 'unvan' => $p->unvan ?? '-']);

        return Inertia::render('Raporlar/RaporGoster', [
            'raporNo'  => '16',
            'baslik'   => 'İşe Giren Personeller',
            'veriler'  => $personeller,
            'kolonlar' => [
                ['key' => 'kart_no', 'label' => 'KartNo'],
                ['key' => 'ad', 'label' => 'Ad'],
                ['key' => 'soyad', 'label' => 'Soyad'],
                ['key' => 'tarih', 'label' => 'İşe Giriş Tarihi', 'type' => 'date'],
                ['key' => 'bolum', 'label' => 'Bölüm'],
                ['key' => 'unvan', 'label' => 'Ünvan'],
            ],
            'personeller' => $this->personelListesi(),
            'filtreler'   => ['baslangic' => $bas, 'bitis' => $bit],
        ]);
    }

    // 17. İşten Ayrılan Personeller
    public function r17(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $personeller = Personel::withTrashed()
            ->select('id', 'kart_no', 'ad', 'soyad', 'giris_tarihi', 'cikis_tarihi', 'bolum')
            ->whereNotNull('cikis_tarihi')
            ->orderBy('cikis_tarihi', 'desc')
            ->get()
            ->map(fn($p) => ['kart_no' => $p->kart_no, 'ad' => $p->ad, 'soyad' => $p->soyad, 'giris_tarihi' => $p->giris_tarihi, 'cikis_tarihi' => $p->cikis_tarihi, 'bolum' => $p->bolum ?? '-']);

        return Inertia::render('Raporlar/RaporGoster', [
            'raporNo'  => '17',
            'baslik'   => 'İşten Ayrılan Personeller',
            'veriler'  => $personeller,
            'kolonlar' => [
                ['key' => 'kart_no', 'label' => 'KartNo'],
                ['key' => 'ad', 'label' => 'Ad'],
                ['key' => 'soyad', 'label' => 'Soyad'],
                ['key' => 'giris_tarihi', 'label' => 'İşe Giriş', 'type' => 'date'],
                ['key' => 'cikis_tarihi', 'label' => 'İşten Çıkış', 'type' => 'date'],
                ['key' => 'bolum', 'label' => 'Bölüm'],
            ],
            'personeller' => $this->personelListesi(),
            'filtreler'   => [],
            'tarihGizle'  => true,
        ]);
    }

    // 18. Tatil Günü Çalışanlar
    public function r18(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $query = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->whereNotNull('ilk_giris')
            ->whereRaw("DAYOFWEEK(tarih) IN (1, 7)") // Pazar=1, Cumartesi=7
            ->orderBy('tarih', 'desc');

        return $this->raporGoster($request, '18', 'Tatil Günü Çalışanlar', $query, [
            ['key' => 'personel.kart_no', 'label' => 'KartNo'],
            ['key' => 'personel.ad', 'label' => 'Ad'],
            ['key' => 'personel.soyad', 'label' => 'Soyad'],
            ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
            ['key' => 'ilk_giris', 'label' => 'Giriş', 'type' => 'time'],
            ['key' => 'son_cikis', 'label' => 'Çıkış', 'type' => 'time'],
            ['key' => 'toplam_calisma_suresi', 'label' => 'Süre', 'type' => 'dakika'],
        ]);
    }

    // 19. İzin Kullananlar
    public function r19(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $izinler = PersonelIzin::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->orderBy('tarih', 'desc')
            ->get()
            ->map(fn($i) => [
                'personel'     => $i->personel,
                'tarih'        => $i->tarih,
                'bitis_tarihi' => $i->bitis_tarihi,
                'gun_sayisi'   => $i->gun_sayisi,
                'aciklama'     => $i->aciklama ?? '-',
                'durum'        => $i->durum,
            ]);

        return Inertia::render('Raporlar/RaporGoster', [
            'raporNo'  => '19',
            'baslik'   => 'İzin Kullananlar',
            'veriler'  => $izinler,
            'kolonlar' => [
                ['key' => 'personel.kart_no', 'label' => 'KartNo'],
                ['key' => 'personel.ad', 'label' => 'Ad'],
                ['key' => 'personel.soyad', 'label' => 'Soyad'],
                ['key' => 'tarih', 'label' => 'Başlangıç', 'type' => 'date'],
                ['key' => 'bitis_tarihi', 'label' => 'Bitiş', 'type' => 'date'],
                ['key' => 'gun_sayisi', 'label' => 'Gün'],
                ['key' => 'aciklama', 'label' => 'Açıklama'],
                ['key' => 'durum', 'label' => 'Durum', 'type' => 'badge'],
            ],
            'personeller' => $this->personelListesi(),
            'filtreler'   => ['baslangic' => $bas, 'bitis' => $bit],
        ]);
    }

    // 20. Avans Listesi
    public function r20(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $kesintiler = PersonelAvansKesinti::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->orderBy('tarih', 'desc')
            ->get()
            ->map(fn($k) => [
                'personel'     => $k->personel,
                'tarih'        => $k->tarih,
                'tutar'        => $k->tutar,
                'aciklama'     => $k->aciklama ?? '-',
                'bordro_alani' => $k->bordro_alani ?? '-',
            ]);

        return Inertia::render('Raporlar/RaporGoster', [
            'raporNo'  => '20',
            'baslik'   => 'Avans Listesi',
            'veriler'  => $kesintiler,
            'kolonlar' => [
                ['key' => 'personel.kart_no', 'label' => 'KartNo'],
                ['key' => 'personel.ad', 'label' => 'Ad'],
                ['key' => 'personel.soyad', 'label' => 'Soyad'],
                ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
                ['key' => 'tutar', 'label' => 'Tutar', 'type' => 'tutar'],
                ['key' => 'aciklama', 'label' => 'Açıklama'],
                ['key' => 'bordro_alani', 'label' => 'Bordro Alanı'],
            ],
            'personeller' => $this->personelListesi(),
            'filtreler'   => ['baslangic' => $bas, 'bitis' => $bit],
        ]);
    }

    // 21. Prim Listesi
    public function r21(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $primler = PersonelPrimKazanc::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->orderBy('tarih', 'desc')
            ->get()
            ->map(fn($k) => [
                'personel'     => $k->personel,
                'tarih'        => $k->tarih,
                'tutar'        => $k->tutar,
                'aciklama'     => $k->aciklama ?? '-',
                'bordro_alani' => $k->bordro_alani ?? '-',
            ]);

        return Inertia::render('Raporlar/RaporGoster', [
            'raporNo'  => '21',
            'baslik'   => 'Prim Listesi',
            'veriler'  => $primler,
            'kolonlar' => [
                ['key' => 'personel.kart_no', 'label' => 'KartNo'],
                ['key' => 'personel.ad', 'label' => 'Ad'],
                ['key' => 'personel.soyad', 'label' => 'Soyad'],
                ['key' => 'tarih', 'label' => 'Tarih', 'type' => 'date'],
                ['key' => 'tutar', 'label' => 'Tutar', 'type' => 'tutar'],
                ['key' => 'aciklama', 'label' => 'Açıklama'],
                ['key' => 'bordro_alani', 'label' => 'Bordro Alanı'],
            ],
            'personeller' => $this->personelListesi(),
            'filtreler'   => ['baslangic' => $bas, 'bitis' => $bit],
        ]);
    }

    // 22. Aylık Devam Listesi
    public function r22(Request $request)
    {
        [$bas, $bit] = $this->tarihler($request);
        $ozetler = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$bas, $bit])
            ->orderBy('personel_id')
            ->orderBy('tarih')
            ->get();

        // Personel bazında gruplama
        $grouped = $ozetler->groupBy('personel_id')->map(function ($items) {
            $p = $items->first()->personel;
            $geldi = $items->where('durum', 'geldi')->count() + $items->where('durum', 'geç kaldı')->count();
            $gelmedi = $items->where('durum', 'gelmedi')->count();
            $eksik = $items->whereIn('durum', ['eksik_giris', 'eksik_cikis'])->count();
            $toplamDk = $items->sum('toplam_calisma_suresi');
            return [
                'kart_no'     => $p->kart_no ?? '-',
                'ad'          => $p->ad ?? '-',
                'soyad'       => $p->soyad ?? '-',
                'geldi'       => $geldi,
                'gelmedi'     => $gelmedi,
                'eksik_kart'  => $eksik,
                'toplam_sure' => $toplamDk,
            ];
        })->values();

        return Inertia::render('Raporlar/RaporGoster', [
            'raporNo'  => '22',
            'baslik'   => 'Aylık Devam Listesi',
            'veriler'  => $grouped,
            'kolonlar' => [
                ['key' => 'kart_no', 'label' => 'KartNo'],
                ['key' => 'ad', 'label' => 'Ad'],
                ['key' => 'soyad', 'label' => 'Soyad'],
                ['key' => 'geldi', 'label' => 'Geldiği Gün'],
                ['key' => 'gelmedi', 'label' => 'Gelmediği Gün'],
                ['key' => 'eksik_kart', 'label' => 'Eksik Kart'],
                ['key' => 'toplam_sure', 'label' => 'Toplam Süre', 'type' => 'dakika'],
            ],
            'personeller' => $this->personelListesi(),
            'filtreler'   => ['baslangic' => $bas, 'bitis' => $bit],
        ]);
    }
}
