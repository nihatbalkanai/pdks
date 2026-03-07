<script setup>
import { ref, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({ firma: Object, cihazlar: Array, sonHareketler: Array, qrKodlar: Array });
const activeTab = ref('ayarlar');

const form = reactive({
    firma_kodu: props.firma?.firma_kodu || '',
    lokasyon_enlem: props.firma?.lokasyon_enlem || '',
    lokasyon_boylam: props.firma?.lokasyon_boylam || '',
    geofence_yaricap: props.firma?.geofence_yaricap || 100,
    wifi_ssid: props.firma?.wifi_ssid || '',
    mobil_giris_aktif: !!props.firma?.mobil_giris_aktif,
    qr_kod_aktif: !!props.firma?.qr_kod_aktif,
    gps_zorunlu: props.firma?.gps_zorunlu !== false,
    selfie_zorunlu: !!props.firma?.selfie_zorunlu,
    logo: null,
});

const qrForm = reactive({ konum_adi: 'Ana Giriş', gecerlilik_dakika: 480 });
const yeniQrKod = ref('');

const kaydet = () => {
    router.post(route('baglanti.mobil.ayarlar'), { ...form }, {
        forceFormData: true,
        onSuccess: () => Swal.fire('Başarılı', 'Ayarlar kaydedildi.', 'success'),
    });
};

const fileChanged = (e) => {
    form.logo = e.target.files[0];
};

const cihazDurumDegistir = async (id, aktif) => {
    await axios.put(route('baglanti.mobil.cihaz.durum', id), { aktif });
    router.reload();
};

const cihazSil = async (id) => {
    const r = await Swal.fire({ title: 'Emin misiniz?', text: 'Cihaz kaydı silinecek', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sil' });
    if (!r.isConfirmed) return;
    await axios.delete(route('baglanti.mobil.cihaz.sil', id));
    Swal.fire('Silindi', '', 'success');
    router.reload();
};

const qrOlustur = async () => {
    const res = await axios.post(route('baglanti.mobil.qr-kod'), { ...qrForm });
    yeniQrKod.value = res.data.kod;
    Swal.fire('QR Kod Oluşturuldu', 'QR kod başarıyla oluşturuldu.', 'success');
    router.reload();
};

const tipRenk = { giris: 'bg-green-100 text-green-700', cikis: 'bg-red-100 text-red-700' };
const yontemLabel = { gps: '📍 GPS', qr: '📸 QR Kod', wifi: '📶 WiFi', beacon: '🔵 Beacon', manual: '✋ Manuel' };
</script>

<template>
<Head title="Mobil Bağlantı Ayarları" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-violet-100 to-purple-100 border-b border-gray-400 px-4 py-2 flex items-center justify-between">
                <h2 class="font-bold text-sm text-gray-800">📱 Mobil Bağlantı Yönetimi</h2>
                <div class="flex gap-1">
                    <button v-for="t in [['ayarlar','⚙️ Ayarlar'],['cihazlar','📱 Cihazlar'],['hareketler','📋 Hareketler'],['qr','📸 QR Kod']]" :key="t[0]" @click="activeTab = t[0]" :class="activeTab === t[0] ? 'bg-white shadow text-violet-700' : 'text-gray-600 hover:bg-white/50'" class="px-3 py-1 rounded text-xs font-semibold transition">{{ t[1] }}</button>
                </div>
            </div>

            <!-- TAB: AYARLAR -->
            <div v-show="activeTab === 'ayarlar'" class="flex-1 overflow-y-auto p-5">
                <div class="max-w-3xl mx-auto space-y-6">
                    <!-- Firma Kodu -->
                    <div class="bg-violet-50 border border-violet-200 rounded-xl p-5">
                        <h3 class="text-sm font-bold text-violet-700 mb-3">🔑 Firma Kodu</h3>
                        <p class="text-xs text-gray-500 mb-2">Personeller uygulamaya giriş yaparken bu kodu kullanır</p>
                        <div class="flex items-center gap-3">
                            <input v-model="form.firma_kodu" type="text" class="border-gray-300 rounded text-lg font-black tracking-widest uppercase w-48 text-center" maxlength="20" />
                            <span class="bg-violet-200 text-violet-700 px-3 py-1 rounded text-xs font-bold">Personele verin</span>
                        </div>
                    </div>

                    <!-- Firma Logo -->
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div v-if="firma?.logo_yolu" class="w-16 h-16 rounded overflow-hidden shadow-sm bg-white flex-shrink-0">
                            <img :src="`/storage/${firma.logo_yolu}`" alt="Firma Logo" class="w-full h-full object-contain" />
                        </div>
                        <div v-else class="w-16 h-16 rounded shadow-sm bg-gray-200 flex-shrink-0 flex items-center justify-center text-xl">🏢</div>
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-gray-700 mb-1">Firma Logosu (Uygulama İçi)</h3>
                            <input type="file" @change="fileChanged" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-violet-100 file:text-violet-700 hover:file:bg-violet-200 cursor-pointer" />
                        </div>
                    </div>

                    <!-- Mobil Giriş -->
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                        <h3 class="text-sm font-bold text-gray-700 mb-3">📱 Mobil Giriş Ayarları</h3>
                        <div class="space-y-3">
                            <label class="flex items-center justify-between"><span class="text-sm">Mobil giriş/çıkış aktif</span><input type="checkbox" v-model="form.mobil_giris_aktif" class="rounded text-violet-600 w-5 h-5" /></label>
                            <label class="flex items-center justify-between"><span class="text-sm">GPS konum kontrolü zorunlu</span><input type="checkbox" v-model="form.gps_zorunlu" class="rounded text-violet-600 w-5 h-5" /></label>
                            <label class="flex items-center justify-between"><span class="text-sm">QR kod ile giriş aktif</span><input type="checkbox" v-model="form.qr_kod_aktif" class="rounded text-violet-600 w-5 h-5" /></label>
                            <label class="flex items-center justify-between"><span class="text-sm">Selfie zorunlu (yakında)</span><input type="checkbox" v-model="form.selfie_zorunlu" class="rounded text-violet-600 w-5 h-5" /></label>
                        </div>
                    </div>

                    <!-- Lokasyon -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                        <h3 class="text-sm font-bold text-blue-700 mb-3">📍 Firma Lokasyonu & Geofence</h3>
                        <div class="grid grid-cols-3 gap-3 mb-3">
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Enlem (Latitude)</label><input v-model="form.lokasyon_enlem" type="number" step="0.0000001" class="w-full border-gray-300 rounded text-sm" placeholder="41.0082" /></div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Boylam (Longitude)</label><input v-model="form.lokasyon_boylam" type="number" step="0.0000001" class="w-full border-gray-300 rounded text-sm" placeholder="28.9784" /></div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Yarıçap (metre)</label><input v-model="form.geofence_yaricap" type="number" min="10" max="5000" class="w-full border-gray-300 rounded text-sm" /></div>
                        </div>
                        <p class="text-[10px] text-blue-600">💡 Google Maps'ten koordinatları alabilirsiniz: Haritada konum sağ tıklayın → Koordinatları kopyalayın</p>
                    </div>

                    <!-- WiFi -->
                    <div class="bg-cyan-50 border border-cyan-200 rounded-xl p-5">
                        <h3 class="text-sm font-bold text-cyan-700 mb-3">📶 WiFi Ayarları</h3>
                        <div><label class="block text-xs font-semibold text-gray-600 mb-1">İzin Verilen WiFi Ağ Adı (SSID)</label><input v-model="form.wifi_ssid" type="text" class="w-full border-gray-300 rounded text-sm" placeholder="FirmaWiFi" /></div>
                        <p class="text-[10px] text-cyan-600 mt-1">WiFi tanımlıysa, personel bu ağa bağlıyken konum kontrolü bypass edilir</p>
                    </div>

                    <button @click="kaydet" class="w-full py-2.5 bg-violet-600 text-white rounded-lg font-semibold hover:bg-violet-700 transition">💾 Ayarları Kaydet</button>
                </div>
            </div>

            <!-- TAB: CİHAZLAR -->
            <div v-show="activeTab === 'cihazlar'" class="flex-1 overflow-y-auto p-4">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase font-semibold sticky top-0">
                        <tr><th class="py-2 px-3 text-left">Personel</th><th class="py-2 px-3 text-left">Cihaz</th><th class="py-2 px-3 text-center">Platform</th><th class="py-2 px-3 text-center">Son Giriş</th><th class="py-2 px-3 text-center">Durum</th><th class="py-2 px-3 text-right">İşlem</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in cihazlar" :key="c.id" class="border-t border-gray-100 hover:bg-violet-50 transition">
                            <td class="py-2 px-3 font-bold text-gray-800">{{ c.personel_adi }}</td>
                            <td class="py-2 px-3 text-gray-600"><div class="font-semibold">{{ c.cihaz_adi || 'Bilinmeyen' }}</div><div class="text-[10px] text-gray-400 truncate max-w-[150px]">{{ c.cihaz_id }}</div></td>
                            <td class="py-2 px-3 text-center"><span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="c.platform === 'ios' ? 'bg-gray-800 text-white' : 'bg-green-100 text-green-700'">{{ c.platform === 'ios' ? '🍎 iOS' : '🤖 Android' }}</span></td>
                            <td class="py-2 px-3 text-center text-gray-500">{{ c.son_giris ? new Date(c.son_giris).toLocaleString('tr-TR') : '—' }}</td>
                            <td class="py-2 px-3 text-center"><span :class="c.aktif ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-0.5 rounded text-[10px] font-bold">{{ c.aktif ? '✅ Aktif' : '🚫 Pasif' }}</span></td>
                            <td class="py-2 px-3 text-right">
                                <button v-if="c.aktif" @click="cihazDurumDegistir(c.id, false)" class="text-amber-600 hover:text-amber-800 mr-1">Devre Dışı</button>
                                <button v-else @click="cihazDurumDegistir(c.id, true)" class="text-green-600 hover:text-green-800 mr-1">Aktifleştir</button>
                                <button @click="cihazSil(c.id)" class="text-red-500 hover:text-red-700">Sil</button>
                            </td>
                        </tr>
                        <tr v-if="!cihazlar.length"><td colspan="6" class="py-8 text-center text-gray-400">Henüz kayıtlı cihaz yok</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- TAB: HAREKETLER -->
            <div v-show="activeTab === 'hareketler'" class="flex-1 overflow-y-auto p-4">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase font-semibold sticky top-0">
                        <tr><th class="py-2 px-3 text-left">Personel</th><th class="py-2 px-3 text-center">Tip</th><th class="py-2 px-3 text-center">Yöntem</th><th class="py-2 px-3 text-center">Mesafe</th><th class="py-2 px-3 text-center">Zaman</th><th class="py-2 px-3 text-center">IP</th><th class="py-2 px-3 text-center">Sahte?</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="h in sonHareketler" :key="h.id" class="border-t border-gray-100 hover:bg-gray-50" :class="h.sahte_konum_algilandi ? 'bg-red-50' : ''">
                            <td class="py-2 px-3 font-bold text-gray-800">{{ h.personel_adi }}</td>
                            <td class="py-2 px-3 text-center"><span :class="tipRenk[h.tip]" class="px-2 py-0.5 rounded text-[10px] font-bold">{{ h.tip === 'giris' ? '🟢 Giriş' : '🔴 Çıkış' }}</span></td>
                            <td class="py-2 px-3 text-center">{{ yontemLabel[h.dogrulama_yontemi] || h.dogrulama_yontemi }}</td>
                            <td class="py-2 px-3 text-center">{{ h.mesafe_metre ? h.mesafe_metre + 'm' : '—' }}</td>
                            <td class="py-2 px-3 text-center text-gray-600">{{ new Date(h.created_at).toLocaleString('tr-TR') }}</td>
                            <td class="py-2 px-3 text-center text-gray-400">{{ h.ip_adresi || '—' }}</td>
                            <td class="py-2 px-3 text-center"><span v-if="h.sahte_konum_algilandi" class="text-red-600 font-bold">⚠️ EVET</span><span v-else class="text-gray-300">—</span></td>
                        </tr>
                        <tr v-if="!sonHareketler.length"><td colspan="7" class="py-8 text-center text-gray-400">Henüz mobil hareket yok</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- TAB: QR KOD -->
            <div v-show="activeTab === 'qr'" class="flex-1 overflow-y-auto p-5">
                <div class="max-w-2xl mx-auto space-y-6">
                    <div class="bg-violet-50 border border-violet-200 rounded-xl p-5">
                        <h3 class="text-sm font-bold text-violet-700 mb-3">📸 Yeni QR Kod Oluştur</h3>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Konum Adı</label><input v-model="qrForm.konum_adi" type="text" class="w-full border-gray-300 rounded text-sm" placeholder="Ana Giriş" /></div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Geçerlilik (dk)</label><input v-model="qrForm.gecerlilik_dakika" type="number" min="5" max="1440" class="w-full border-gray-300 rounded text-sm" /></div>
                        </div>
                        <button @click="qrOlustur" class="px-4 py-2 bg-violet-600 text-white rounded text-sm font-semibold hover:bg-violet-700">QR Kod Oluştur</button>
                    </div>

                    <div v-if="yeniQrKod" class="bg-green-50 border border-green-200 rounded-xl p-5 text-center">
                        <h4 class="text-sm font-bold text-green-700 mb-2">✅ QR Kod Oluşturuldu</h4>
                        <div class="bg-white border-2 border-dashed border-green-300 rounded-lg p-4 inline-block">
                            <div class="text-xs text-gray-500 mb-1">QR Kod İçeriği:</div>
                            <div class="font-mono text-sm font-bold text-gray-800 break-all">{{ yeniQrKod }}</div>
                        </div>
                        <p class="text-[10px] text-green-600 mt-2">Bu kodu QR kod oluşturucu ile görsele dönüştürün ve giriş noktasına asın</p>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-2">📋 Mevcut QR Kodlar</h4>
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500 uppercase font-semibold"><tr><th class="py-2 px-3 text-left">Konum</th><th class="py-2 px-3 text-left">Kod</th><th class="py-2 px-3 text-center">Geçerlilik</th><th class="py-2 px-3 text-center">Durum</th></tr></thead>
                            <tbody>
                                <tr v-for="q in qrKodlar" :key="q.id" class="border-t border-gray-100">
                                    <td class="py-2 px-3 font-semibold">{{ q.konum_adi }}</td>
                                    <td class="py-2 px-3 font-mono text-gray-600 truncate max-w-[200px]">{{ q.kod }}</td>
                                    <td class="py-2 px-3 text-center text-gray-600">{{ new Date(q.gecerlilik_bitis).toLocaleString('tr-TR') }}</td>
                                    <td class="py-2 px-3 text-center"><span :class="new Date(q.gecerlilik_bitis) > new Date() && q.aktif ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-0.5 rounded text-[10px] font-bold">{{ new Date(q.gecerlilik_bitis) > new Date() && q.aktif ? '✅ Aktif' : '⏰ Süresi Doldu' }}</span></td>
                                </tr>
                                <tr v-if="!qrKodlar.length"><td colspan="4" class="py-8 text-center text-gray-400">QR kod oluşturulmamış</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
