<?php

namespace App\Services;

use App\Models\PdksCihazi;
use App\Models\Personel;
use App\Models\PdksKayit;
use Illuminate\Support\Facades\Log;

class PdksKayitService
{
    /**
     * Cihazdan gelen veriyi isler ve kaydeder.
     * 
     * @param array $data
     * @return array
     */
    public function kaydet(array $data)
    {
        try {
            // Global Scope'ları atla: Çünkü API dışarıdan geliyor, Auth::user() yok. Cihaz kendi firmasını belirler.
            // 10.000 firma yükünde anlık milyon sorguyu önlemek için Redis (Cache) bellekleme
            $cihaz = \Illuminate\Support\Facades\Cache::remember("cihaz_{$data['cihaz_seri_no']}", now()->addHours(12), function () use ($data) {
                return PdksCihazi::withoutGlobalScopes()
                                ->where('seri_no', $data['cihaz_seri_no'])
                                ->first();
            });

            if (!$cihaz) {
                Log::warning('Yetkisiz veya bulunamayan cihaz verisi geldi.', ['seri_no' => $data['cihaz_seri_no']]);
                return ['status' => false, 'message' => 'Cihaz bulunamadı.'];
            }

            // Personeli bul, cihazın firmasıyla eşleşmek zorunda
            // Milyonlarca girişte veritabanını dondurmamak için 1 günlük cache
            $personel = \Illuminate\Support\Facades\Cache::remember("personel_{$data['personel_sicil_no']}_firma_{$cihaz->firma_id}", now()->addHours(24), function () use ($data, $cihaz) {
                return Personel::withoutGlobalScopes()
                                ->where('sicil_no', $data['personel_sicil_no'])
                                ->where('firma_id', $cihaz->firma_id)
                                ->first();
            });

            if (!$personel) {
                Log::warning('Personel bulunamadı veya cihaza ait değil.', ['sicil_no' => $data['personel_sicil_no']]);
                return ['status' => false, 'message' => 'Personel bulunamadı.'];
            }

            // Pdks kaydını oluştur
            $kayit = PdksKayit::create([
                'firma_id' => $cihaz->firma_id,
                'cihaz_id' => $cihaz->id,
                'personel_id' => $personel->id,
                'kayit_tarihi' => $data['tarih'],
                'islem_tipi' => $data['islem_tipi'],
                'ham_veri' => json_encode($data)
            ]);

            // Cihazın son aktivite tarihini güncelle (Live status tracking)
            $cihaz->update(['son_aktivite_tarihi' => now()]);

            // Arka planda anlık günlük özet performans tablosunu güncelle 
            // (10 binlerce veride dashboard'u dondurmamak için indexli özel tablo yazılıyor)
            \App\Jobs\GunlukOzetGuncelleJob::dispatch($kayit);

            // O anda Reverb (WebSocket) üzerinden ilgili firmaya dinleme sinyali at
            event(new \App\Events\PdksKayitEklendi($cihaz->firma_id, $kayit->toArray()));

            return ['status' => true, 'message' => 'Kayıt başarıyla alındı.'];
        } catch (\Exception $e) {
            Log::error('PDKS verisi kaydedilirken hata: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Sunucu hatası.', 'error' => $e->getMessage()];
        }
    }
}
