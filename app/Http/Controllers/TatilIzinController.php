<?php

namespace App\Http\Controllers;

use App\Models\IzinTuru;
use App\Models\ResmiTatil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class TatilIzinController extends Controller
{
    public function index()
    {
        $firma_id = Auth::user()->firma_id ?? 1;

        $izinTurleri = IzinTuru::where('firma_id', $firma_id)->orderBy('ad')->get();
        
        // Yıla göre gruplama (örn 2026, 2025)
        $resmiTatiller = ResmiTatil::where('firma_id', $firma_id)
            ->orderBy('tarih', 'asc')
            ->get()
            ->groupBy('yil');

        return Inertia::render('HesapParam/TatilIzin', [
            'izinTurleri' => $izinTurleri,
            'resmiTatiller' => $resmiTatiller,
        ]);
    }

    // --- İZİN TÜRLERİ ---

    public function izinTuruStore(Request $request)
    {
        $validated = $request->validate([
            'ad' => 'required|string|max:255',
            'ucret_kesintisi_yapilacak_mi' => 'boolean',
            'yillik_izinden_duser_mi' => 'boolean',
            'aktif_mi' => 'boolean',
        ]);

        $validated['firma_id'] = Auth::user()->firma_id ?? 1;

        $izinTuru = IzinTuru::create($validated);

        return response()->json(['success' => true, 'item' => $izinTuru, 'message' => 'İzin türü eklendi.']);
    }

    public function izinTuruUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'ad' => 'required|string|max:255',
            'ucret_kesintisi_yapilacak_mi' => 'boolean',
            'yillik_izinden_duser_mi' => 'boolean',
            'aktif_mi' => 'boolean',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $izinTuru = IzinTuru::where('firma_id', $firma_id)->findOrFail($id);
        
        $izinTuru->update($validated);

        return response()->json(['success' => true, 'item' => $izinTuru->fresh(), 'message' => 'İzin türü güncellendi.']);
    }

    public function izinTuruDestroy($id)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $izinTuru = IzinTuru::where('firma_id', $firma_id)->findOrFail($id);
        $izinTuru->delete();

        return response()->json(['success' => true, 'message' => 'İzin türü silindi.']);
    }

    // --- RESMİ TATİLLER (MANUEL EKLENME) ---

    public function resmiTatilStore(Request $request)
    {
        $validated = $request->validate([
            'tarih' => 'required|date|after:2000-01-01|before:2100-01-01',
            'ad' => 'required|string|max:255',
            'tur' => 'nullable|string|max:100',
            'yarim_gun_mu' => 'boolean',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;
        $validated['firma_id'] = $firma_id;
        $validated['yil'] = date('Y', strtotime($validated['tarih']));

        // Aynı gün iki tatil varsa engelle veya güncelle (unique error fırlatmasın)
        $kontrol = ResmiTatil::where('firma_id', $firma_id)->where('tarih', $validated['tarih'])->first();
        if ($kontrol) {
            $kontrol->update($validated);
            $tatil = $kontrol->fresh();
        } else {
            $tatil = ResmiTatil::create($validated);
        }

        return response()->json(['success' => true, 'item' => $tatil, 'message' => 'Resmi tatil kaydedildi.']);
    }

    public function resmiTatilDestroy($id)
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $resmiTatil = ResmiTatil::where('firma_id', $firma_id)->findOrFail($id);
        $resmiTatil->delete();

        return response()->json(['success' => true, 'message' => 'Tatil silindi.']);
    }

    // --- AI YIL BAZLI RESMİ TATİL ÜRETİMİ ---

    public function aiTatilUret(Request $request)
    {
        $request->validate(['yil' => 'required|integer|min:2020|max:2100']);
        $yil = $request->yil;
        $firma_id = Auth::user()->firma_id ?? 1;

        $apiKey = config('openai.api_key');
        $model = config('openai.model');

        if (empty($apiKey)) {
            return response()->json(['success' => false, 'message' => 'OpenAI API Anahtarı bulunamadı.'], 422);
        }

        $prompt = "Bana $yil yılı için Türkiye'deki tüm resmi ve dini tatilleri (Ramazan Bayramı, Kurban Bayramı, 23 Nisan, 19 Mayıs, 15 Temmuz, 30 Ağustos, 29 Ekim vb.) sadece ve sadece aşağıdaki JSON formatında bir dizi olarak döndür:
[
  {\"tarih\": \"$yil-MM-DD\", \"ad\": \"Tatil Adi (Örn: Ramazan Bayramı 1. Gün)\", \"tur\": \"Dini Bayram\", \"yarim_gun_mu\": false},
  {\"tarih\": \"$yil-MM-DD\", \"ad\": \"Ramazan Bayramı Arifesi\", \"tur\": \"Arife\", \"yarim_gun_mu\": true}
]
Ekstra metin, mardown ticki (```) olmasın doğrudan JSON çevrilebilecek dizi olsun.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Sen sadece ve sadece JSON yanıt döndüren bir uzmansın.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.2, // Doğruluk için düşük sıcaklık
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $aiResponseContent = $data['choices'][0]['message']['content'] ?? '';
                
                // Markdown temizliği (Eğer AI inat edip atarsa)
                $aiResponseContent = str_replace('```json', '', $aiResponseContent);
                $aiResponseContent = str_replace('```', '', $aiResponseContent);
                $aiResponseContent = trim($aiResponseContent);

                $tatiller = json_decode($aiResponseContent, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($tatiller)) {
                    $eklenen = 0;
                    foreach ($tatiller as $tatil) {
                        if (isset($tatil['tarih']) && isset($tatil['ad'])) {
                            ResmiTatil::updateOrCreate(
                                [
                                    'firma_id' => $firma_id,
                                    'tarih' => $tatil['tarih']
                                ],
                                [
                                    'yil' => $yil,
                                    'ad' => $tatil['ad'],
                                    'tur' => $tatil['tur'] ?? 'Resmi Tatil',
                                    'yarim_gun_mu' => isset($tatil['yarim_gun_mu']) ? (bool)$tatil['yarim_gun_mu'] : false,
                                ]
                            );
                            $eklenen++;
                        }
                    }

                    // Güncel tatilleri döndür
                    $tatiller = ResmiTatil::where('firma_id', $firma_id)->where('yil', $yil)->orderBy('tarih')->get();
                    return response()->json(['success' => true, 'message' => "$yil için $eklenen tatil eklendi.", 'yil' => $yil, 'tatiller' => $tatiller]);
                } else {
                    return response()->json(['success' => false, 'message' => 'OpenAI geçersiz JSON döndürdü.'], 422);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'OpenAI API Hatası.'], 422);
            }

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Bağlantı hatası: ' . $e->getMessage()], 500);
        }
    }
}
