<?php

namespace App\Http\Controllers;

use App\Models\ZamanlanmisBildirim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ZamanlanmisBildirimController extends Controller
{
    public function index()
    {
        $firma_id = Auth::user()->firma_id ?? 1;

        $bildirimler = ZamanlanmisBildirim::where('firma_id', $firma_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('TopluIslemler/ZamanlanmisBildirimler', [
            'bildirimler' => $bildirimler,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ad' => 'required|string|max:255',
            'tip' => 'required|in:maas_gunu,dogum_gunu,bayram,ozel_tarih,genel',
            'kanal' => 'required|in:sms,email,her_ikisi',
            'konu' => 'nullable|string|max:255',
            'mesaj_sablonu' => 'required|string',
            'gun' => 'nullable|integer|min:1|max:31',
            'saat' => 'required|string',
            'ozel_tarih' => 'nullable|date',
            'aktif' => 'boolean',
        ]);

        $validated['firma_id'] = Auth::user()->firma_id ?? 1;

        ZamanlanmisBildirim::create($validated);

        return response()->json(['success' => true, 'message' => 'Zamanlanmış bildirim oluşturuldu.']);
    }

    public function update(Request $request, $id)
    {
        $bildirim = ZamanlanmisBildirim::findOrFail($id);

        $validated = $request->validate([
            'ad' => 'required|string|max:255',
            'tip' => 'required|in:maas_gunu,dogum_gunu,bayram,ozel_tarih,genel',
            'kanal' => 'required|in:sms,email,her_ikisi',
            'konu' => 'nullable|string|max:255',
            'mesaj_sablonu' => 'required|string',
            'gun' => 'nullable|integer|min:1|max:31',
            'saat' => 'required|string',
            'ozel_tarih' => 'nullable|date',
            'aktif' => 'boolean',
        ]);

        $bildirim->update($validated);

        return response()->json(['success' => true, 'message' => 'Bildirim güncellendi.']);
    }

    public function toggleAktif($id)
    {
        $bildirim = ZamanlanmisBildirim::findOrFail($id);
        $bildirim->aktif = !$bildirim->aktif;
        $bildirim->save();

        return response()->json(['success' => true, 'message' => $bildirim->aktif ? 'Bildirim aktif edildi.' : 'Bildirim pasif yapıldı.']);
    }

    public function destroy($id)
    {
        ZamanlanmisBildirim::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Bildirim silindi.']);
    }
}
