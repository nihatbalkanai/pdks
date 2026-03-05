<?php

namespace App\Http\Controllers;

use App\Models\PdksGunlukOzet;
use App\Models\Personel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use App\Jobs\ExcelRaporOlusturJob;

class RaporController extends Controller
{
    /**
     * Gelişmiş Filtreli Rapor Listesi - Performanslı Sorgu Sistemi
     */
    public function index(Request $request): Response
    {
        // Öntanımlı tarih aralığı: Bu ayın başından bugüne
        $baslangic = $request->input('baslangic', now()->startOfMonth()->toDateString());
        $bitis = $request->input('bitis', now()->endOfMonth()->toDateString());
        $personelId = $request->input('personel_id');
        $bolum = $request->input('bolum');
        
        // Eager loading (N+1 Hatasını Önleme) ve Tarih Filtresi. İndeks bazlı.
        $raporQuery = PdksGunlukOzet::with('personel')
            ->whereBetween('tarih', [$baslangic, $bitis]);
            
        // Personel özelinde filtreleme
        if ($personelId) {
            $raporQuery->where('personel_id', $personelId);
        }
        
        // Bölüm filtreleme işlemi (Personel ilişkisi üzerinden "whereHas" kullanılır)
        if ($bolum) {
            $raporQuery->whereHas('personel', function($q) use ($bolum) {
                $q->where('bolum', $bolum);
            });
        }
        
        // Büyük veriyi sayfalayarak döndürür, gereksiz belleği doldurmaz.
        $raporlar = $raporQuery->orderBy('tarih', 'desc')->paginate(50)->withQueryString();
        
        // Form alanlarını doldurmak için gerekli master veriler (Vue tarafına basılır)
        $personeller = Personel::select('id', 'ad_soyad')->orderBy('ad_soyad')->get();
        // Bölümleri dublicate engellemek adına distinct çağırıyoruz. null bölümleri saklıyoruz.
        $bolumler = Personel::select('bolum')->whereNotNull('bolum')->distinct()->pluck('bolum');
        
        return Inertia::render('Raporlar/Index', [
            'raporlar' => $raporlar,
            'personeller' => $personeller,
            'bolumler' => $bolumler,
            // Vue arayüzündeki kutuların state'lerini koruması için mevcut request'i dönüyoruz
            'filtreler' => [
                'baslangic' => $baslangic,
                'bitis' => $bitis,
                'personel_id' => $personelId,
                'bolum' => $bolum,
            ]
        ]);
    }

    /**
     * Excel asenkron indirme isteğini kabul eder.
     */
    public function export(Request $request)
    {
        $filtreler = $request->only(['baslangic', 'bitis', 'personel_id', 'bolum']);

        // Excel raporlama işlemini arka plana gönderiyoruz
        ExcelRaporOlusturJob::dispatch(Auth::id(), $filtreler);

        // Kullanıcıya anlık mesaj (toast vs) döndür
        return response()->json(['success' => true, 'message' => 'Raporunuz arka planda hazırlanıyor. İşlem bittiğinde bildirim alacaksınız.']);
    }
}
