<?php

namespace App\Http\Controllers;

use App\Models\PdksKaydi;
use App\Models\PdksCihazi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CihazTransferController extends Controller
{
    public function index()
    {
        $cihazlar = PdksCihazi::withoutGlobalScopes()->get();

        // Hatalı kayıtlar: tanımsız kart vs.
        $hataliKayitlar = PdksKaydi::withoutGlobalScopes()
            ->whereNull('personel_id')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($k) {
                return [
                    'id' => $k->id,
                    'kart_no' => json_decode($k->ham_veri, true)['kart_no'] ?? '-',
                    'durum' => 1,
                    'tarih' => date('Y/m/d', strtotime($k->kayit_tarihi)),
                    'saat' => date('H:i', strtotime($k->kayit_tarihi)),
                    'hata' => 'Tanımsız Kart',
                    'cihaz' => $k->cihaz_id ?? '-',
                    'neden_kodu' => '000',
                ];
            });

        $toplamKayit = PdksKaydi::withoutGlobalScopes()->count();
        $hataliSayisi = PdksKaydi::withoutGlobalScopes()->whereNull('personel_id')->count();

        return Inertia::render('CihazTransfer/Index', [
            'cihazlar' => $cihazlar,
            'hataliKayitlar' => $hataliKayitlar,
            'toplamKayit' => $toplamKayit,
            'hataliSayisi' => $hataliSayisi,
        ]);
    }

    public function deleteHatali()
    {
        PdksKaydi::withoutGlobalScopes()->whereNull('personel_id')->delete();
        return response()->json(['success' => true, 'message' => 'Tüm hatalı kayıtlar silindi.']);
    }
}
