<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DestekBiletController extends Controller
{
    /**
     * Firma kullanıcısı — Biletlerini listele
     */
    public function index()
    {
        $firmaId = Auth::user()->firma_id;

        $biletler = DB::table('destek_biletleri')
            ->where('firma_id', $firmaId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($biletler);
    }

    /**
     * Firma kullanıcısı — Yeni bilet oluştur
     */
    public function olustur(Request $request)
    {
        $validated = $request->validate([
            'konu' => 'required|string|max:255',
            'mesaj' => 'required|string|max:5000',
            'kategori' => 'required|in:teknik,fatura,genel,ozellik_talebi',
            'oncelik' => 'required|in:dusuk,normal,yuksek,acil',
        ]);

        $user = Auth::user();

        $biletId = DB::table('destek_biletleri')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'firma_id' => $user->firma_id,
            'olusturan_id' => $user->id,
            'konu' => $validated['konu'],
            'kategori' => $validated['kategori'],
            'oncelik' => $validated['oncelik'],
            'durum' => 'acik',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // İlk mesajı ekle
        DB::table('bilet_mesajlari')->insert([
            'bilet_id' => $biletId,
            'gonderen_id' => $user->id,
            'gonderen_tipi' => 'musteri',
            'mesaj' => $validated['mesaj'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Destek talebiniz oluşturuldu. En kısa sürede dönüş yapılacaktır.',
        ]);
    }

    /**
     * Bilet detayı + mesajlar (Firma veya Admin)
     */
    public function detay($id)
    {
        $user = Auth::user();
        $bilet = DB::table('destek_biletleri')->where('id', $id)->first();

        if (!$bilet) {
            return response()->json(['success' => false, 'message' => 'Bilet bulunamadı'], 404);
        }

        // Firma kullanıcısıysa kendi firmasının bileti mi kontrol et
        $isSuperAdmin = $user->superAdminYetki?->yetkiler && count($user->superAdminYetki->yetkiler) > 0;
        if (!$isSuperAdmin && $bilet->firma_id != $user->firma_id) {
            return response()->json(['success' => false, 'message' => 'Yetkisiz erişim'], 403);
        }

        $mesajlar = DB::table('bilet_mesajlari')
            ->leftJoin('kullanicilar', 'bilet_mesajlari.gonderen_id', '=', 'kullanicilar.id')
            ->where('bilet_id', $id)
            ->select('bilet_mesajlari.*', 'kullanicilar.ad_soyad as gonderen_adi')
            ->orderBy('bilet_mesajlari.created_at', 'asc')
            ->get();

        return response()->json([
            'bilet' => $bilet,
            'mesajlar' => $mesajlar,
        ]);
    }

    /**
     * Mesaj gönder (Firma veya Admin)
     */
    public function mesajGonder(Request $request, $id)
    {
        $validated = $request->validate([
            'mesaj' => 'required|string|max:5000',
        ]);

        $user = Auth::user();
        $bilet = DB::table('destek_biletleri')->where('id', $id)->first();

        if (!$bilet) {
            return response()->json(['success' => false, 'message' => 'Bilet bulunamadı'], 404);
        }

        $isSuperAdmin = $user->superAdminYetki?->yetkiler && count($user->superAdminYetki->yetkiler) > 0;

        DB::table('bilet_mesajlari')->insert([
            'bilet_id' => $id,
            'gonderen_id' => $user->id,
            'gonderen_tipi' => $isSuperAdmin ? 'admin' : 'musteri',
            'mesaj' => $validated['mesaj'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Bilet durumunu güncelle
        DB::table('destek_biletleri')->where('id', $id)->update([
            'durum' => $isSuperAdmin ? 'cevaplandi' : 'yanit_bekleniyor',
            'son_yanit_tarihi' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mesajınız gönderildi.',
        ]);
    }

    /**
     * Bilet durumu güncelle (Super Admin)
     */
    public function durumGuncelle(Request $request, $id)
    {
        $validated = $request->validate([
            'durum' => 'required|in:acik,yanit_bekleniyor,cevaplandi,cozuldu,kapatildi',
        ]);

        DB::table('destek_biletleri')->where('id', $id)->update([
            'durum' => $validated['durum'],
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Bilet durumu güncellendi.']);
    }

    /**
     * Super Admin — Tüm biletleri listele
     */
    public function tumBiletler(Request $request)
    {
        $query = DB::table('destek_biletleri')
            ->leftJoin('firmalar', 'destek_biletleri.firma_id', '=', 'firmalar.id')
            ->leftJoin('kullanicilar', 'destek_biletleri.olusturan_id', '=', 'kullanicilar.id')
            ->select(
                'destek_biletleri.*',
                'firmalar.firma_adi',
                'kullanicilar.ad_soyad as olusturan_adi'
            )
            ->whereNull('destek_biletleri.deleted_at');

        if ($request->durum && $request->durum !== 'tumu') {
            $query->where('destek_biletleri.durum', $request->durum);
        }

        $biletler = $query->orderByRaw("FIELD(destek_biletleri.oncelik, 'acil', 'yuksek', 'normal', 'dusuk')")
            ->orderBy('destek_biletleri.created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json($biletler);
    }
}
