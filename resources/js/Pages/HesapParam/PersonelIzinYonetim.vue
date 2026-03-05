<script setup>
import { ref, computed, watch, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const hesaplaYukleniyor = ref(false);
const atlananGunler = ref([]);
const izinTuruBilgisi = ref(null); // {hafta_sonu_haric, resmi_tatil_haric, max_gun}

const props = defineProps({ personeller: Array, izinTurleri: Array });

const seciliPersonelId = ref(null);
const yil = ref(new Date().getFullYear());
const izinler = ref([]);
const yukleniyor = ref(false);
const modalAcik = ref(false);
const arama = ref('');

const formatTarih = (d) => { if(!d) return '-'; const dt = new Date(d); return dt.toLocaleDateString('tr-TR'); };
const durumRenk = { beklemede: 'bg-yellow-100 text-yellow-800', onaylandi: 'bg-green-100 text-green-800', reddedildi: 'bg-red-100 text-red-800' };
const durumLabel = { beklemede: 'Beklemede', onaylandi: 'Onaylandı', reddedildi: 'Reddedildi' };

const seciliPersonel = computed(() => props.personeller.find(p => p.id === seciliPersonelId.value));
const filtreliPersoneller = computed(() => {
    if (!arama.value) return props.personeller;
    const q = arama.value.toLowerCase();
    return props.personeller.filter(p => `${p.ad} ${p.soyad} ${p.sicil_no || ''}`.toLowerCase().includes(q));
});

// Özet istatistikler
const ozet = computed(() => {
    const toplam = izinler.value.length;
    const onaylanan = izinler.value.filter(i => i.durum === 'onaylandi').length;
    const bekleyen = izinler.value.filter(i => i.durum === 'beklemede').length;
    const toplamGun = izinler.value.filter(i => i.durum === 'onaylandi').reduce((s, i) => s + parseFloat(i.gun_sayisi || 0), 0);
    return { toplam, onaylanan, bekleyen, toplamGun };
});

const izinGetir = async () => {
    if (!seciliPersonelId.value) return;
    yukleniyor.value = true;
    try {
        const res = await axios.get(route('tanim.personel-izin.izin-getir', seciliPersonelId.value), { params: { yil: yil.value } });
        izinler.value = res.data;
    } catch(e) { console.error(e); }
    yukleniyor.value = false;
};

watch([seciliPersonelId, yil], () => izinGetir());

const form = reactive({
    id: null,
    personel_id: null,
    izin_turu_id: '',
    tarih: '',
    bitis_tarihi: '',
    izin_tipi: 'gunluk',
    giris_saati: '',
    cikis_saati: '',
    gun_sayisi: 1,
    aciklama: '',
    durum: 'beklemede',
});

const yeniIzin = () => {
    // form reset (reactive)
    form.id = null;
    form.personel_id = seciliPersonelId.value;
    form.tarih = new Date().toISOString().split('T')[0];
    form.bitis_tarihi = form.tarih;
    form.izin_tipi = 'gunluk';
    form.durum = 'beklemede';
    form.gun_sayisi = 1;
    modalAcik.value = true;
};

const duzenle = (izin) => {
    form.id = izin.id;
    form.personel_id = izin.personel_id;
    form.izin_turu_id = izin.izin_turu_id;
    form.tarih = izin.tarih?.substring(0, 10);
    form.bitis_tarihi = izin.bitis_tarihi?.substring(0, 10) || form.tarih;
    form.izin_tipi = izin.izin_tipi;
    form.giris_saati = izin.giris_saati || '';
    form.cikis_saati = izin.cikis_saati || '';
    form.gun_sayisi = izin.gun_sayisi;
    form.aciklama = izin.aciklama || '';
    form.durum = izin.durum;
    modalAcik.value = true;
};

const kaydet = async () => {
    try {
        if (form.id) {
            await axios.put(route('tanim.personel-izin.update', form.id), { ...form });
        } else {
            await axios.post(route('tanim.personel-izin.store'), { ...form });
        }
        modalAcik.value = false;
        izinGetir();
        Swal.fire({toast:true, position:'top-end', icon:'success', title: form.id ? 'Güncellendi' : 'Kaydedildi', showConfirmButton:false, timer:1200});
    } catch(e) {
        Swal.fire('Hata', e.response?.data?.message || 'İşlem başarısız', 'error');
    }
};

const sil = (id) => {
    Swal.fire({title:'Emin misiniz?', icon:'warning', showCancelButton:true, confirmButtonText:'Evet, Sil'}).then(async (r) => {
        if (r.isConfirmed) {
            try {
                await axios.delete(route('tanim.personel-izin.destroy', id));
                izinGetir();
                Swal.fire({toast:true, position:'top-end', icon:'success', title:'Silindi', showConfirmButton:false, timer:1200});
            } catch(e) {
                Swal.fire('Hata', e.response?.data?.message || 'Silinemedi', 'error');
            }
        }
    });
};

const durumDegistir = async (id, durum) => {
    try {
        await axios.post(route('tanim.personel-izin.durum', id), { durum });
        izinGetir();
        Swal.fire({toast:true, position:'top-end', icon:'success', title: durum === 'onaylandi' ? 'Onaylandı' : 'Reddedildi', showConfirmButton:false, timer:1200});
    } catch(e) { Swal.fire('Hata', 'Bir hata oluştu', 'error'); }
};

// API tabanlı dinamik hesaplama — resmi tatiller ve hafta sonları dikkate alır
let hesaplaTimer = null;
let apiLock = false;

async function hesaplaIzinTarihleri({ kaynakField }) {
    if (apiLock || !form.izin_turu_id || !form.tarih || form.izin_tipi !== 'gunluk') return;
    hesaplaYukleniyor.value = true;
    apiLock = true;
    try {
        const payload = {
            izin_turu_id: form.izin_turu_id,
            tarih: form.tarih,
        };
        if (kaynakField === 'gun_sayisi' && form.gun_sayisi > 0) {
            payload.gun_sayisi = form.gun_sayisi;
        } else if (kaynakField === 'bitis_tarihi' && form.bitis_tarihi) {
            payload.bitis_tarihi = form.bitis_tarihi;
        } else {
            apiLock = false;
            hesaplaYukleniyor.value = false;
            return;
        }
        const res = await axios.post(route('tanim.personel-izin.hesapla'), payload);
        apiLock = false;
        if (kaynakField === 'gun_sayisi') {
            form.bitis_tarihi = res.data.bitis_tarihi;
        } else {
            form.gun_sayisi = res.data.gun_sayisi;
        }
        atlananGunler.value = res.data.atlanan_gunler || [];
        izinTuruBilgisi.value = res.data;
    } catch(e) {
        apiLock = false;
        console.error(e);
    }
    hesaplaYukleniyor.value = false;
}

// Debounce: kullanıcı yazmayı bitirince çalış
watch(() => form.gun_sayisi, (val) => {
    if (apiLock || !val || val < 0.5) return;
    clearTimeout(hesaplaTimer);
    hesaplaTimer = setTimeout(() => hesaplaIzinTarihleri({ kaynakField: 'gun_sayisi' }), 600);
});

watch(() => form.bitis_tarihi, (val) => {
    if (apiLock || !val) return;
    clearTimeout(hesaplaTimer);
    hesaplaTimer = setTimeout(() => hesaplaIzinTarihleri({ kaynakField: 'bitis_tarihi' }), 600);
});

watch(() => form.tarih, () => {
    if (form.gun_sayisi > 0) {
        clearTimeout(hesaplaTimer);
        hesaplaTimer = setTimeout(() => hesaplaIzinTarihleri({ kaynakField: 'gun_sayisi' }), 600);
    }
});

// İzin türü değişince bilgileri sıfırla ve yeniden hesapla
watch(() => form.izin_turu_id, () => {
    atlananGunler.value = [];
    izinTuruBilgisi.value = null;
    if (form.gun_sayisi > 0 && form.tarih) {
        clearTimeout(hesaplaTimer);
        hesaplaTimer = setTimeout(() => hesaplaIzinTarihleri({ kaynakField: 'gun_sayisi' }), 300);
    }
});
</script>

<template>
<Head title="Personel İzin Yönetimi" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full overflow-hidden">
            <div class="bg-gradient-to-r from-[#e8f5e9] to-[#c8e6c9] border-b border-gray-400 px-4 py-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <h2 class="font-bold text-sm text-gray-800">Personel İzin Yönetimi</h2>
            </div>

            <div class="flex flex-1 overflow-hidden">
                <!-- SOL: Personel Listesi -->
                <div class="w-64 border-r border-gray-300 bg-white flex flex-col">
                    <div class="px-2 py-2 border-b border-gray-200"><input v-model="arama" type="text" placeholder="Personel ara..." class="w-full text-xs rounded border-gray-300 px-2 py-1"></div>
                    <div class="flex-1 overflow-y-auto">
                        <div v-for="p in filtreliPersoneller" :key="p.id" @click="seciliPersonelId = p.id"
                            class="px-3 py-2 text-sm cursor-pointer border-b border-gray-100 transition"
                            :class="seciliPersonelId === p.id ? 'bg-green-100 font-bold border-l-4 border-green-500' : 'hover:bg-gray-50'">
                            <div>{{ p.ad }} {{ p.soyad }}</div>
                            <div class="text-xs text-gray-400">{{ p.sicil_no }} • {{ p.departman }}</div>
                        </div>
                    </div>
                </div>

                <!-- SAĞ: İzin Listesi -->
                <div class="flex-1 flex flex-col bg-gray-50 overflow-hidden">
                    <div v-if="!seciliPersonelId" class="flex-1 flex items-center justify-center text-gray-400">
                        <p class="text-sm">Soldaki listeden bir personel seçin.</p>
                    </div>

                    <template v-if="seciliPersonelId">
                        <!-- Üst Bar -->
                        <div class="border-b border-gray-300 bg-white px-4 py-2 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-sm">{{ seciliPersonel?.ad }} {{ seciliPersonel?.soyad }}</span>
                                <select v-model="yil" class="text-xs rounded border-gray-300 py-1">
                                    <option v-for="y in [2024,2025,2026,2027]" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>
                            <button @click="yeniIzin" class="bg-green-600 hover:bg-green-700 text-white rounded px-3 py-1.5 text-xs shadow flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Yeni İzin
                            </button>
                        </div>

                        <!-- Özet Kartlar -->
                        <div class="px-4 py-2 flex gap-3">
                            <div class="bg-blue-50 rounded-lg px-3 py-2 flex-1 text-center"><div class="text-lg font-bold text-blue-700">{{ ozet.toplam }}</div><div class="text-[10px] text-blue-500">Toplam İzin</div></div>
                            <div class="bg-green-50 rounded-lg px-3 py-2 flex-1 text-center"><div class="text-lg font-bold text-green-700">{{ ozet.onaylanan }}</div><div class="text-[10px] text-green-500">Onaylanan</div></div>
                            <div class="bg-yellow-50 rounded-lg px-3 py-2 flex-1 text-center"><div class="text-lg font-bold text-yellow-700">{{ ozet.bekleyen }}</div><div class="text-[10px] text-yellow-500">Bekleyen</div></div>
                            <div class="bg-purple-50 rounded-lg px-3 py-2 flex-1 text-center"><div class="text-lg font-bold text-purple-700">{{ ozet.toplamGun }}</div><div class="text-[10px] text-purple-500">Toplam Gün</div></div>
                        </div>

                        <!-- Tablo -->
                        <div class="flex-1 overflow-auto px-4 pb-3">
                            <table class="w-full text-xs text-left bg-white border border-gray-200 rounded">
                                <thead class="bg-gray-100 text-gray-600 uppercase text-[10px]">
                                    <tr>
                                        <th class="px-2 py-1.5 border-b">İzin Türü</th>
                                        <th class="px-2 py-1.5 border-b">Başlangıç</th>
                                        <th class="px-2 py-1.5 border-b">Bitiş</th>
                                        <th class="px-2 py-1.5 border-b text-center">Gün</th>
                                        <th class="px-2 py-1.5 border-b text-center">Tip</th>
                                        <th class="px-2 py-1.5 border-b">Açıklama</th>
                                        <th class="px-2 py-1.5 border-b text-center">Durum</th>
                                        <th class="px-2 py-1.5 border-b text-right">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="izin in izinler" :key="izin.id" class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="px-2 py-1.5 font-medium">{{ izin.izin_turu?.ad || izin.tatil_tipi || '-' }}</td>
                                        <td class="px-2 py-1.5">{{ formatTarih(izin.tarih) }}</td>
                                        <td class="px-2 py-1.5">{{ formatTarih(izin.bitis_tarihi) }}</td>
                                        <td class="px-2 py-1.5 text-center font-bold">{{ izin.gun_sayisi }}</td>
                                        <td class="px-2 py-1.5 text-center">{{ izin.izin_tipi === 'saatlik' ? '⏰ Saatlik' : '📅 Günlük' }}</td>
                                        <td class="px-2 py-1.5 text-gray-500 max-w-[150px] truncate">{{ izin.aciklama || '-' }}</td>
                                        <td class="px-2 py-1.5 text-center">
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="durumRenk[izin.durum]">{{ durumLabel[izin.durum] }}</span>
                                        </td>
                                        <td class="px-2 py-1.5 text-right flex gap-1 justify-end">
                                            <button v-if="izin.durum==='beklemede'" @click="durumDegistir(izin.id, 'onaylandi')" class="text-green-600 hover:text-green-800 text-[10px] font-bold">✓ Onayla</button>
                                            <button v-if="izin.durum==='beklemede'" @click="durumDegistir(izin.id, 'reddedildi')" class="text-red-500 hover:text-red-700 text-[10px] font-bold">✗ Red</button>
                                            <button @click="duzenle(izin)" class="text-blue-500 hover:text-blue-700 p-0.5"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572z"></path></svg></button>
                                            <button @click="sil(izin.id)" class="text-red-400 hover:text-red-600 p-0.5"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                        </td>
                                    </tr>
                                    <tr v-if="!izinler.length"><td colspan="8" class="px-2 py-6 text-center text-gray-400">{{ yukleniyor ? 'Yükleniyor...' : 'Bu yıla ait izin kaydı bulunmamaktadır.' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div v-if="modalAcik" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded shadow-xl w-[520px] border border-gray-300 overflow-hidden">
            <div class="bg-gray-100 px-4 py-3 border-b flex justify-between items-center"><h3 class="font-bold">{{ form.id ? 'İzin Düzenle' : 'Yeni İzin Kaydı' }}</h3><button @click="modalAcik=false" class="text-gray-500 text-xl">&times;</button></div>
            <div class="p-4 space-y-3">
                <div><label class="block text-xs font-bold mb-1">İzin Türü *</label>
                    <select v-model="form.izin_turu_id" class="w-full text-sm rounded border-gray-300">
                        <option value="">— Seçin —</option>
                        <option v-for="t in izinTurleri" :key="t.id" :value="t.id">{{ t.ad }}</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-bold mb-1">Başlangıç *</label><input v-model="form.tarih" type="date" class="w-full text-sm rounded border-gray-300"></div>
                    <div>
                        <label class="block text-xs font-bold mb-1">Bitiş
                            <span v-if="hesaplaYukleniyor" class="ml-1 text-blue-500 text-[10px] animate-pulse">⟳ Hesaplanıyor...</span>
                        </label>
                        <input v-model="form.bitis_tarihi" type="date" class="w-full text-sm rounded border-gray-300">
                    </div>
                </div>

                <!-- Türk İş Kanunu Bilgi Paneli -->
                <div v-if="izinTuruBilgisi" class="rounded-lg border p-2 text-[11px] space-y-1"
                    :class="atlananGunler.length > 0 ? 'bg-blue-50 border-blue-200 text-blue-800' : 'bg-green-50 border-green-200 text-green-800'">
                    <div class="font-bold flex items-center gap-1">
                        ⚖️ Türk İş Kanunu — {{ izinTuruBilgisi.izin_turu_ad }}
                    </div>
                    <div v-if="izinTuruBilgisi.hafta_sonu_haric" class="flex items-center gap-1">
                        <span class="text-orange-500">⚠</span> Hafta sonları izin gün sayısına dahil edilmez (İş K. m.56)
                    </div>
                    <div v-if="izinTuruBilgisi.resmi_tatil_haric" class="flex items-center gap-1">
                        <span class="text-red-500">⚠</span> Resmi tatiller izin gün sayısına dahil edilmez (İş K. m.56)
                    </div>
                    <div v-if="izinTuruBilgisi.max_gun" class="flex items-center gap-1">
                        <span class="text-purple-500">ℹ</span> Maksimum {{ izinTuruBilgisi.max_gun }} gün
                    </div>
                    <div v-if="atlananGunler.length > 0" class="mt-1 pt-1 border-t border-blue-200">
                        <div class="font-semibold">Atlanan {{ atlananGunler.length }} gün (bu günler izinden sayılmaz):</div>
                        <div class="flex flex-wrap gap-1 mt-0.5">
                            <span v-for="g in atlananGunler.slice(0,10)" :key="g.tarih"
                                class="bg-white rounded px-1.5 py-0.5 border border-blue-200 text-[10px]">
                                {{ new Date(g.tarih).toLocaleDateString('tr-TR') }}
                                <span class="text-gray-400">({{ g.neden }})</span>
                            </span>
                            <span v-if="atlananGunler.length > 10" class="text-gray-500">+{{ atlananGunler.length - 10 }} daha...</span>
                        </div>
                    </div>
                    <div v-else-if="!izinTuruBilgisi.hafta_sonu_haric && !izinTuruBilgisi.resmi_tatil_haric" class="text-gray-500">
                        ℹ Bu izin türünde takvim günü olarak sayılır (hafta sonu ve tatiller dahil)
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div><label class="block text-xs font-bold mb-1">İzin Tipi</label>
                        <select v-model="form.izin_tipi" class="w-full text-sm rounded border-gray-300"><option value="gunluk">Günlük</option><option value="saatlik">Saatlik</option></select>
                    </div>
                    <div><label class="block text-xs font-bold mb-1">Gün Sayısı
                        <span v-if="hesaplaYukleniyor" class="ml-1 text-blue-500 animate-pulse">⟳</span>
                    </label><input v-model.number="form.gun_sayisi" type="number" step="0.5" min="0.5" class="w-full text-sm rounded border-gray-300"></div>
                    <div><label class="block text-xs font-bold mb-1">Durum</label>
                        <select v-model="form.durum" class="w-full text-sm rounded border-gray-300"><option value="beklemede">Beklemede</option><option value="onaylandi">Onaylandı</option><option value="reddedildi">Reddedildi</option></select>
                    </div>
                </div>
                <div v-if="form.izin_tipi === 'saatlik'" class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-bold mb-1">Başlangıç Saati</label><input v-model="form.giris_saati" type="time" class="w-full text-sm rounded border-gray-300"></div>
                    <div><label class="block text-xs font-bold mb-1">Bitiş Saati</label><input v-model="form.cikis_saati" type="time" class="w-full text-sm rounded border-gray-300"></div>
                </div>
                <div><label class="block text-xs font-bold mb-1">Açıklama</label><textarea v-model="form.aciklama" rows="2" class="w-full text-sm rounded border-gray-300" placeholder="Rapor no, izin sebebi vb."></textarea></div>
            </div>
            <div class="bg-gray-50 p-3 border-t flex justify-end gap-2">
                <button @click="modalAcik=false" class="px-3 py-1.5 border bg-white rounded text-sm hover:bg-gray-100">İptal</button>
                <button @click="kaydet" class="px-3 py-1.5 bg-green-600 text-white rounded text-sm hover:bg-green-700">Kaydet</button>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
