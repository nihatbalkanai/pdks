<?php

namespace App\Http\Controllers;

use App\Models\Kullanici;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class KullaniciYonetimController extends Controller
{
    public function index()
    {
        $kullanicilar = Kullanici::withoutGlobalScopes()
            ->where('firma_id', Auth::user()->firma_id ?? 1)
            ->select('id', 'uuid', 'ad_soyad', 'eposta', 'rol', 'created_at', 'updated_at')
            ->orderBy('rol')
            ->orderBy('ad_soyad')
            ->get();

        return Inertia::render('Tanim/Kullanicilar', [
            'kullanicilar' => $kullanicilar,
            'roller' => [
                'admin' => 'Yönetici',
                'kullanici' => 'Kullanıcı',
                'muhasebe' => 'Muhasebe',
                'ik' => 'İnsan Kaynakları',
                'izleyici' => 'İzleyici (Salt Okunur)',
            ],
            'yetkiMatrisi' => config('yetkiler'),
            'yetkiEtiketleri' => [
                'dashboard' => 'Dashboard',
                'personel_islemleri' => 'Personel İşlemleri',
                'personel_kartlari' => 'Personel Kartları',
                'cihaz_islemleri' => 'Cihaz İşlemleri',
                'cihaz_transfer' => 'Cihaz Veri Transferi',
                'raporlar' => 'Raporlar',
                'ek_kazanclar' => 'Ek Kazançlar',
                'avans_kesintiler' => 'Avans/Kesintiler',
                'toplu_islemler' => 'Toplu İşlemler',
                'toplu_maas' => 'Toplu Maaş Artırımı',
                'toplu_hareket' => 'Toplu Hareket',
                'toplu_izin' => 'Toplu İzin',
                'toplu_avans' => 'Toplu Avans',
                'toplu_prim' => 'Toplu Prim',
                'toplu_agi' => 'Toplu AGİ Atama',
                'toplu_giris_cikis' => 'Toplu Giriş Çıkış',
                'toplu_mesaj' => 'Toplu Mesaj',
                'toplu_mail' => 'Toplu Mail',
                'zamanlanmis_bildirimler' => 'Zamanlanmış Bildirimler',
                'tanim_islemleri' => 'Tanım İşlemleri',
                'kullanici_yonetimi' => 'Kullanıcı Yönetimi',
                'mail_ayarlari' => 'Mail Ayarları',
                'mesaj_ayarlari' => 'Mesaj Ayarları',
                'subeler' => 'Şubeler',
                'servisler' => 'Servisler',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ad_soyad' => 'required|string|max:255',
            'eposta' => 'required|email|max:255|unique:kullanicilar,eposta',
            'sifre' => 'required|string|min:6|max:100',
            'rol' => 'required|in:admin,kullanici,muhasebe,ik,izleyici',
        ]);

        $firma_id = Auth::user()->firma_id ?? 1;

        $kullanici = Kullanici::create([
            'firma_id' => $firma_id,
            'ad_soyad' => $validated['ad_soyad'],
            'eposta' => $validated['eposta'],
            'sifre' => Hash::make($validated['sifre']),
            'rol' => $validated['rol'],
        ]);

        return response()->json(['success' => true, 'item' => $kullanici]);
    }

    public function update(Request $request, $id)
    {
        $kullanici = Kullanici::withoutGlobalScopes()->findOrFail($id);

        // Kendi hesabını siliyor mu kontrol
        if ($kullanici->id === Auth::id()) {
            // Kendi rolünü değiştiremez
            $request->merge(['rol' => $kullanici->rol]);
        }

        $validated = $request->validate([
            'ad_soyad' => 'required|string|max:255',
            'eposta' => 'required|email|max:255|unique:kullanicilar,eposta,' . $id,
            'sifre' => 'nullable|string|min:6|max:100',
            'rol' => 'required|in:admin,kullanici,muhasebe,ik,izleyici',
        ]);

        $data = [
            'ad_soyad' => $validated['ad_soyad'],
            'eposta' => $validated['eposta'],
            'rol' => $validated['rol'],
        ];

        if (!empty($validated['sifre'])) {
            $data['sifre'] = Hash::make($validated['sifre']);
        }

        $kullanici->update($data);

        return response()->json(['success' => true, 'item' => $kullanici->fresh()]);
    }

    public function destroy($id)
    {
        $kullanici = Kullanici::withoutGlobalScopes()->findOrFail($id);

        if ($kullanici->id === Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Kendi hesabınızı silemezsiniz.'], 422);
        }

        $kullanici->delete();

        return response()->json(['success' => true]);
    }
}
