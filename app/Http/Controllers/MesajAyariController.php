<?php

namespace App\Http\Controllers;

use App\Models\MesajAyari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class MesajAyariController extends Controller
{
    public function mailAyarlari()
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $ayar = MesajAyari::where('firma_id', $firma_id)->where('kanal', 'email')->first();

        return Inertia::render('Tanim/MailAyarlari', [
            'ayar' => $ayar,
        ]);
    }

    public function mailAyarlariKaydet(Request $request)
    {
        $validated = $request->validate([
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer',
            'smtp_sifreleme' => 'nullable|in:ssl,tls,none',
            'smtp_kullanici' => 'required|string|max:255',
            'smtp_sifre' => 'nullable|string|max:255',
            'gonderen_email' => 'required|email|max:255',
            'gonderen_ad' => 'nullable|string|max:255',
            'durum' => 'boolean',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;

        $ayar = MesajAyari::updateOrCreate(
            ['firma_id' => $firma_id, 'kanal' => 'email'],
            array_merge($validated, ['firma_id' => $firma_id, 'kanal' => 'email'])
        );

        return back()->with('success', 'Mail ayarları kaydedildi.');
    }

    public function mailTest(Request $request)
    {
        $request->validate(['test_email' => 'required|email']);

        // TODO: Gerçek SMTP test gönderimi
        // Şimdilik simülasyon
        return back()->with('success', "Test maili {$request->test_email} adresine gönderildi.");
    }

    public function mesajAyarlari()
    {
        $firma_id = Auth::user()->firma_id ?? 1;
        $ayar = MesajAyari::where('firma_id', $firma_id)->where('kanal', 'sms')->first();

        return Inertia::render('Tanim/MesajAyarlari', [
            'ayar' => $ayar,
        ]);
    }

    public function mesajAyarlariKaydet(Request $request)
    {
        $validated = $request->validate([
            'sms_api_url' => 'required|string|max:500',
            'api_anahtari' => 'nullable|string|max:500',
            'sms_kullanici' => 'nullable|string|max:255',
            'sms_sifre' => 'nullable|string|max:255',
            'sms_baslik' => 'nullable|string|max:100',
            'durum' => 'boolean',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;

        $ayar = MesajAyari::updateOrCreate(
            ['firma_id' => $firma_id, 'kanal' => 'sms'],
            array_merge($validated, ['firma_id' => $firma_id, 'kanal' => 'sms'])
        );

        return back()->with('success', 'SMS ayarları kaydedildi.');
    }

    public function mesajTest(Request $request)
    {
        $request->validate(['test_telefon' => 'required|string']);

        // TODO: Gerçek SMS API test gönderimi
        return back()->with('success', "Test mesajı {$request->test_telefon} numarasına gönderildi.");
    }
}
