<script setup>
import { ref, computed, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    izinTurleri: Array,
    resmiTatiller: Object,
});

// Yerel reactive listeler — sayfa yenilenmeden güncellenir
const izinListesi = ref([...(props.izinTurleri || [])]);
const tatilListesi = ref({ ...(props.resmiTatiller || {}) });

const activeTab = ref('izin');
const currentYear = ref(new Date().getFullYear());
const isAiLoading = ref(false);
const yukleniyor = ref(false);

// --- İZİN TÜRLERİ ---
const izinModal = reactive({ acik: false, id: null, ad: '', ucret_kesintisi_yapilacak_mi: false, yillik_izinden_duser_mi: false, aktif_mi: true, errors: {} });

const openIzinModal = (item = null) => {
    Object.assign(izinModal, { acik: true, errors: {},
        id: item?.id || null,
        ad: item?.ad || '',
        ucret_kesintisi_yapilacak_mi: !!item?.ucret_kesintisi_yapilacak_mi,
        yillik_izinden_duser_mi: !!item?.yillik_izinden_duser_mi,
        aktif_mi: item ? !!item.aktif_mi : true,
    });
};

const saveIzin = async () => {
    if (!izinModal.ad.trim()) { izinModal.errors = { ad: 'İzin adı zorunludur.' }; return; }
    yukleniyor.value = true;
    try {
        const payload = { ad: izinModal.ad, ucret_kesintisi_yapilacak_mi: izinModal.ucret_kesintisi_yapilacak_mi, yillik_izinden_duser_mi: izinModal.yillik_izinden_duser_mi, aktif_mi: izinModal.aktif_mi };
        if (izinModal.id) {
            const res = await axios.put(route('tanim.tatil-izin.izin-turu-update', izinModal.id), payload);
            const idx = izinListesi.value.findIndex(x => x.id === izinModal.id);
            if (idx >= 0) izinListesi.value[idx] = res.data.item;
            Swal.fire({toast:true, position:'top-end', icon:'success', title:'Güncellendi', showConfirmButton:false, timer:1400});
        } else {
            const res = await axios.post(route('tanim.tatil-izin.izin-turu-store'), payload);
            izinListesi.value.push(res.data.item);
            Swal.fire({toast:true, position:'top-end', icon:'success', title:'Eklendi', showConfirmButton:false, timer:1400});
        }
        izinModal.acik = false;
    } catch(e) {
        izinModal.errors = e.response?.data?.errors || {};
        if (e.response?.data?.message) Swal.fire('Hata', e.response.data.message, 'error');
    }
    yukleniyor.value = false;
};

const deleteIzin = (id) => {
    Swal.fire({title: 'Emin misiniz?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Evet, Sil', cancelButtonText: 'İptal'}).then(async (res) => {
        if (res.isConfirmed) {
            try {
                await axios.delete(route('tanim.tatil-izin.izin-turu-destroy', id));
                izinListesi.value = izinListesi.value.filter(x => x.id !== id);
                Swal.fire({toast:true, position:'top-end', icon:'success', title:'Silindi', showConfirmButton:false, timer:1200});
            } catch(e) { Swal.fire('Hata', 'Silinemedi', 'error'); }
        }
    });
};

// --- RESMİ TATİLLER ---
const tatilModal = reactive({ acik: false, id: null, tarih: '', ad: '', tur: 'Resmi Tatil', yarim_gun_mu: false, errors: {} });

const openTatilModal = (item = null) => {
    const defaultTarih = new Date(currentYear.value, 0, 1).toISOString().split('T')[0];
    Object.assign(tatilModal, { acik: true, errors: {},
        id: item?.id || null,
        tarih: item?.tarih ? item.tarih.substring(0, 10) : defaultTarih,
        ad: item?.ad || '',
        tur: item?.tur || 'Resmi Tatil',
        yarim_gun_mu: !!item?.yarim_gun_mu,
    });
};

const saveTatil = async () => {
    if (!tatilModal.tarih || !tatilModal.ad.trim()) { tatilModal.errors = { ad: 'Tarih ve ad zorunludur.' }; return; }
    yukleniyor.value = true;
    try {
        const payload = { tarih: tatilModal.tarih, ad: tatilModal.ad, tur: tatilModal.tur, yarim_gun_mu: tatilModal.yarim_gun_mu };
        const res = await axios.post(route('tanim.tatil-izin.resmi-tatil-store'), payload);
        const item = res.data.item;
        const yil = new Date(item.tarih).getFullYear().toString();
        if (!tatilListesi.value[yil]) tatilListesi.value[yil] = [];
        const idx = tatilListesi.value[yil].findIndex(x => x.id === item.id || x.tarih === item.tarih);
        if (idx >= 0) tatilListesi.value[yil][idx] = item;
        else tatilListesi.value[yil].push(item);
        tatilListesi.value[yil].sort((a,b) => a.tarih.localeCompare(b.tarih));
        tatilModal.acik = false;
        Swal.fire({toast:true, position:'top-end', icon:'success', title:'Kaydedildi', showConfirmButton:false, timer:1400});
    } catch(e) {
        tatilModal.errors = e.response?.data?.errors || {};
    }
    yukleniyor.value = false;
};

const deleteTatil = (id) => {
    Swal.fire({title: 'Emin misiniz?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Evet, Sil', cancelButtonText: 'İptal'}).then(async (res) => {
        if (res.isConfirmed) {
            try {
                await axios.delete(route('tanim.tatil-izin.resmi-tatil-destroy', id));
                const yil = currentYear.value.toString();
                if (tatilListesi.value[yil]) tatilListesi.value[yil] = tatilListesi.value[yil].filter(x => x.id !== id);
                Swal.fire({toast:true, position:'top-end', icon:'success', title:'Silindi', showConfirmButton:false, timer:1200});
            } catch(e) { Swal.fire('Hata', 'Silinemedi', 'error'); }
        }
    });
};

// AI Tatil Üret — AJAX ile
const aiTatilUret = () => {
    Swal.fire({
        title: `${currentYear.value} Tatillerini Üret`,
        text: `Yapay zeka ${currentYear.value} yılı Türkiye tatillerini getirecek. Onaylıyor musunuz?`,
        icon: 'info', showCancelButton: true, confirmButtonText: 'Evet, Üret', cancelButtonText: 'İptal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            isAiLoading.value = true;
            Swal.fire({ title: 'Lütfen Bekleyin...', html: 'Yapay zeka çalışıyor...<br><span style="font-size:11px;color:#666;">10-15 saniye sürebilir</span>', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const res = await axios.post(route('tanim.tatil-izin.ai-uret'), { yil: currentYear.value });
                const { tatiller, yil, message } = res.data;
                tatilListesi.value[yil] = tatiller;
                Swal.fire({toast:true, position:'top-end', icon:'success', title: message, showConfirmButton:false, timer:3000});
            } catch(e) {
                Swal.fire('Hata', e.response?.data?.message || 'Bir hata oluştu', 'error');
            }
            isAiLoading.value = false;
        }
    });
};

const mevcutYillar = computed(() => {
    const keys = Object.keys(tatilListesi.value || {});
    const buYil = new Date().getFullYear().toString();
    if (!keys.includes(buYil)) keys.push(buYil);
    return keys.sort((a,b) => b - a);
});

const aktifYilTatilleri = computed(() => tatilListesi.value[currentYear.value] || []);

const formatDate = (d) => { if(!d) return ''; return new Date(d).toLocaleDateString('tr-TR'); };
</script>

<template>
<Head title="Tatil ve İzin Tanımlamaları" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full overflow-hidden">
            
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <h2 class="font-bold text-sm text-gray-800">Tatil ve İzin Tanımlamaları</h2>
                </div>
            </div>

            <!-- Sekmeler -->
            <div class="flex bg-gray-100 border-b border-gray-300">
                <button @click="activeTab = 'izin'" class="px-5 py-2.5 text-sm font-semibold transition" :class="activeTab === 'izin' ? 'bg-white border-b-2 border-red-500 text-red-700' : 'text-gray-600 hover:bg-gray-200'">İzin Türleri</button>
                <button @click="activeTab = 'resmi'" class="px-5 py-2.5 text-sm font-semibold transition" :class="activeTab === 'resmi' ? 'bg-white border-b-2 border-red-500 text-red-700' : 'text-gray-600 hover:bg-gray-200'">Resmi Tatiller</button>
            </div>

            <div class="flex-1 overflow-auto bg-[#eef2f9] p-4 relative">
                
                <!-- İZİN TÜRLERİ -->
                <div v-if="activeTab === 'izin'" class="max-w-4xl mx-auto bg-white rounded shadow border border-gray-200 p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-700">İzin Türleri Listesi</h3>
                        <button @click="openIzinModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs shadow flex items-center gap-1 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Yeni Tür
                        </button>
                    </div>

                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <tr>
                                <th class="px-3 py-2 border-b border-gray-200">Durum</th>
                                <th class="px-3 py-2 border-b border-gray-200">İzin Adı</th>
                                <th class="px-3 py-2 border-b border-gray-200 text-center">Kesinti (Ücretli mi?)</th>
                                <th class="px-3 py-2 border-b border-gray-200 text-center">Yıllık İzinden Düşer mi?</th>
                                <th class="px-3 py-2 border-b border-gray-200 text-right">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="iz in izinListesi" :key="iz.id" class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-3 py-2"><span :class="iz.aktif_mi ? 'bg-green-500' : 'bg-red-500'" class="w-2.5 h-2.5 rounded-full inline-block"></span></td>
                                <td class="px-3 py-2 font-medium">{{ iz.ad }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span v-if="iz.ucret_kesintisi_yapilacak_mi" class="text-red-600 bg-red-50 px-2 py-0.5 rounded text-[11px] font-bold">Ücretsiz (Kesilir)</span>
                                    <span v-else class="text-green-600 bg-green-50 px-2 py-0.5 rounded text-[11px] font-bold">Ücretli İzin</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <svg v-if="iz.yillik_izinden_duser_mi" class="w-4 h-4 text-orange-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button @click="openIzinModal(iz)" class="text-blue-500 hover:text-blue-700 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572z"></path></svg></button>
                                    <button @click="deleteIzin(iz.id)" class="text-red-500 hover:text-red-700 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                </td>
                            </tr>
                            <tr v-if="!izinTurleri.length"><td colspan="5" class="px-3 py-6 text-center text-gray-500">Kayıt bulunamadı.</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- RESMİ TATİLLER -->
                <div v-if="activeTab === 'resmi'" class="max-w-4xl mx-auto bg-white rounded shadow border border-gray-200 p-4">
                    <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <h3 class="font-bold text-gray-700">Yıllık Resmi Tatiller</h3>
                            <select v-model="currentYear" class="border-gray-300 rounded text-sm py-1 font-bold text-red-700 focus:ring-red-500">
                                <option v-for="y in mevcutYillar" :key="y" :value="y">{{ y }} Yılı</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <!-- AI Butonu -->
                            <button @click="aiTatilUret" :disabled="isAiLoading" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded text-xs shadow flex items-center gap-1.5 transition disabled:opacity-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> 
                                Yapay Zeka ile ({{ currentYear }}) Üret
                            </button>
                            <button @click="openTatilModal()" class="bg-gray-100 border border-gray-300 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded text-xs shadow-sm flex items-center gap-1 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Manuel Ekle
                            </button>
                        </div>
                    </div>

                    <table class="w-full text-sm text-left">
                        <thead class="bg-red-50 text-red-800 uppercase text-xs">
                            <tr>
                                <th class="px-3 py-2 border-b border-red-100 w-24">Tarih</th>
                                <th class="px-3 py-2 border-b border-red-100">Tatil Adı</th>
                                <th class="px-3 py-2 border-b border-red-100">Türü</th>
                                <th class="px-3 py-2 border-b border-red-100 text-center w-24">Yarım Gün</th>
                                <th class="px-3 py-2 border-b border-red-100 text-right w-16">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in aktifYilTatilleri" :key="t.id" class="border-b border-gray-100 hover:bg-red-50/50">
                                <td class="px-3 py-2 font-semibold text-gray-700">{{ formatDate(t.tarih) }}</td>
                                <td class="px-3 py-2">{{ t.ad }}</td>
                                <td class="px-3 py-2 text-xs">
                                    <span class="bg-gray-100 border border-gray-200 px-2 py-0.5 rounded text-gray-600">{{ t.tur }}</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span v-if="t.yarim_gun_mu" class="text-[10px] font-bold bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded border border-orange-200">Arife (1/2)</span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button @click="openTatilModal(t)" class="text-blue-500 hover:text-blue-700 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572z"></path></svg></button>
                                    <button @click="deleteTatil(t.id)" class="text-red-500 hover:text-red-700 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                </td>
                            </tr>
                            <tr v-if="!aktifYilTatilleri.length">
                                <td colspan="5" class="px-3 py-10 text-center text-gray-500">
                                    <div class="mb-2">Bu yıl için tatil kaydı bulunamadı.</div>
                                    <button @click="aiTatilUret" class="text-indigo-600 font-bold hover:underline">Yapay Zeka ile Oluşturun</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <div v-if="izinModal.acik" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded shadow-xl w-[400px] border border-gray-300 overflow-hidden">
            <div class="bg-gray-100 px-4 py-3 border-b border-gray-300 flex justify-between items-center"><h3 class="font-bold">{{ izinModal.id ? 'İzin Düzenle' : 'Yeni İzin Türü' }}</h3><button @click="izinModal.acik=false" class="text-gray-500">&times;</button></div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1">İzin Adı *</label>
                    <input v-model="izinModal.ad" type="text" class="w-full text-sm rounded border-gray-300 focus:border-blue-500" placeholder="Örn: Mazeret İzni">
                    <div v-if="izinModal.errors.ad" class="text-red-500 text-xs">{{ izinModal.errors.ad }}</div>
                </div>
                <div class="p-2 border border-orange-200 bg-orange-50 rounded">
                    <label class="flex items-center"><input v-model="izinModal.ucret_kesintisi_yapilacak_mi" type="checkbox" class="rounded text-orange-600 focus:ring-orange-500"><span class="ml-2 text-sm text-gray-700 font-medium">Maaştan Kesinti Yapılacak (Ücretsiz)</span></label>
                </div>
                <div>
                    <label class="flex items-center"><input v-model="izinModal.yillik_izinden_duser_mi" type="checkbox" class="rounded text-blue-600"><span class="ml-2 text-sm text-gray-700">Yıllık izinden düşülecek</span></label>
                </div>
                <div>
                    <label class="flex items-center"><input v-model="izinModal.aktif_mi" type="checkbox" class="rounded text-green-600"><span class="ml-2 text-sm text-gray-700">Aktif</span></label>
                </div>
            </div>
            <div class="bg-gray-50 p-3 border-t border-gray-200 flex justify-end gap-2">
                <button @click="izinModal.acik=false" class="px-3 py-1.5 border bg-white rounded text-sm text-gray-600 hover:bg-gray-100">İptal</button>
                <button @click="saveIzin" :disabled="yukleniyor" class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 disabled:opacity-50">{{ yukleniyor ? 'Kaydediliyor...' : 'Kaydet' }}</button>
            </div>
        </div>
    </div>

    <!-- Tatil Modal -->
    <div v-if="tatilModal.acik" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded shadow-xl w-[400px] border border-gray-300 overflow-hidden">
            <div class="bg-gray-100 px-4 py-3 border-b border-gray-300 flex justify-between items-center"><h3 class="font-bold">{{ tatilModal.id ? 'Tatil Düzenle' : 'Yeni Tatil Ekle' }}</h3><button @click="tatilModal.acik=false" class="text-gray-500">&times;</button></div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1">Tarih *</label>
                    <input v-model="tatilModal.tarih" type="date" class="w-full text-sm rounded border-gray-300 focus:border-red-500" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Tatil Adı *</label>
                    <input v-model="tatilModal.ad" type="text" class="w-full text-sm rounded border-gray-300 focus:border-red-500" placeholder="Örn: 23 Nisan" required>
                    <div v-if="tatilModal.errors.ad" class="text-red-500 text-xs">{{ tatilModal.errors.ad }}</div>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Türü</label>
                    <input v-model="tatilModal.tur" type="text" class="w-full text-sm rounded border-gray-300 focus:border-red-500" placeholder="Örn: Resmi Tatil">
                </div>
                <div class="bg-orange-50 p-2 border border-orange-200 rounded">
                    <label class="flex items-center"><input v-model="tatilModal.yarim_gun_mu" type="checkbox" class="rounded text-orange-600 border-gray-300"><span class="ml-2 text-sm text-gray-700 font-medium">Bu tatil ARİFE (Yarım Gün) mü?</span></label>
                </div>
            </div>
            <div class="bg-gray-50 p-3 border-t border-gray-200 flex justify-end gap-2">
                <button @click="tatilModal.acik=false" class="px-3 py-1.5 border bg-white rounded text-sm text-gray-600 hover:bg-gray-100">İptal</button>
                <button @click="saveTatil" :disabled="yukleniyor" class="px-3 py-1.5 bg-red-600 text-white rounded text-sm hover:bg-red-700 disabled:opacity-50">{{ yukleniyor ? 'Kaydediliyor...' : 'Kaydet' }}</button>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
