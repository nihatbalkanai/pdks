<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PdksKayitService;

class PdksVeriController extends Controller
{
    protected $pdksKayitService;

    public function __construct(PdksKayitService $pdksKayitService)
    {
        $this->pdksKayitService = $pdksKayitService;
    }

    /**
     * Cihazlar tarafından tetiklenen endpoint.
     * JSON formatında veri kabul eder.
     */
    public function store(Request $request)
    {
        // Gelen veriyi doğrula
        $validated = $request->validate([
            'cihaz_seri_no' => 'required|string',
            'personel_sicil_no' => 'required|string',
            'tarih' => 'required|date',
            'islem_tipi' => 'required|in:giriş,çıkış',
        ]);

        $sonuc = $this->pdksKayitService->kaydet($validated);

        if ($sonuc['status']) {
            return response()->json([
                'mesaj' => $sonuc['message']
            ], 200);
        }

        return response()->json([
            'hata' => $sonuc['message'],
            'detay' => $sonuc['error'] ?? null
        ], 400);
    }
}
