<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    izinTurleri: Array,
    resmiTatiller: Object, // Yıla göre obj { 2026: [...], 2025: [...] }
});

const activeTab = ref('izin'); // izin, resmi
const currentYear = ref(new Date().getFullYear());
const isAiLoading = ref(false);

// İzin Türleri Form
const izinForm = useForm({
    id: null,
    ad: '',
    ucret_kesintisi_yapilacak_mi: false,
    yillik_izinden_duser_mi: false,
    aktif_mi: true,
});
const izinModalAcik = ref(false);

const openIzinModal = (item = null) => {
    if (item) {
        izinForm.id = item.id;
        izinForm.ad = item.ad;
        izinForm.ucret_kesintisi_yapilacak_mi = !!item.ucret_kesintisi_yapilacak_mi;
        izinForm.yillik_izinden_duser_mi = !!item.yillik_izinden_duser_mi;
        izinForm.aktif_mi = !!item.aktif_mi;
    } else {
        izinForm.reset();
        izinForm.id = null;
        izinForm.aktif_mi = true;
    }
    izinModalAcik.value = true;
};

const saveIzin = () => {
    if (izinForm.id) {
        izinForm.put(route('tanim.tatil-izin.izin-turu-update', izinForm.id), {
            onSuccess: () => { izinModalAcik.value = false; Swal.fire({toast:true, position:'top-end', icon:'success', title:'Güncellendi', showConfirmButton:false, timer:1500}); }
        });
    } else {
        izinForm.post(route('tanim.tatil-izin.izin-turu-store'), {
            onSuccess: () => { izinModalAcik.value = false; Swal.fire({toast:true, position:'top-end', icon:'success', title:'Eklendi', showConfirmButton:false, timer:1500}); }
        });
    }
};

const deleteIzin = (id) => {
    Swal.fire({title: 'Emin misiniz?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Evet, Sil'}).then((res) => {
        if(res.isConfirmed) {
            router.delete(route('tanim.tatil-izin.izin-turu-destroy', id));
        }
    });
};

// Resmi Tatil Form
const tatilForm = useForm({
    id: null,
    tarih: '',
    ad: '',
    tur: 'Resmi Tatil',
    yarim_gun_mu: false,
});
const tatilModalAcik = ref(false);

const openTatilModal = (item = null) => {
    if (item) {
        tatilForm.id = item.id;
        tatilForm.tarih = item.tarih ? item.tarih.substring(0, 10) : '';
        tatilForm.ad = item.ad;
        tatilForm.tur = item.tur || 'Resmi Tatil';
        tatilForm.yarim_gun_mu = !!item.yarim_gun_mu;
    } else {
        tatilForm.reset();
        tatilForm.id = null;
        tatilForm.tur = 'Resmi Tatil';
        // Yeni eklerken default olarak seçili yılın 1 ocağını göster(yada bugünü)
        const date = new Date(currentYear.value, 0, 1);
        tatilForm.tarih = date.toISOString().split('T')[0];
    }
    tatilModalAcik.value = true;
};

const saveTatil = () => {
    tatilForm.post(route('tanim.tatil-izin.resmi-tatil-store'), {
        onSuccess: () => { tatilModalAcik.value = false; Swal.fire({toast:true, position:'top-end', icon:'success', title:'Kaydedildi', showConfirmButton:false, timer:1500}); }
    });
};

const deleteTatil = (id) => {
    Swal.fire({title: 'Emin misiniz?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Evet, Sil'}).then((res) => {
        if(res.isConfirmed) {
            router.delete(route('tanim.tatil-izin.resmi-tatil-destroy', id));
        }
    });
};

// AI Yıl Seçimi
const aiTatilUret = () => {
    let yearToGenerate = currentYear.value; // Ekranda hangi yıl açıksa
    Swal.fire({
        title: `${yearToGenerate} Tatillerini İndir`,
        text: `Yapay zeka ${yearToGenerate} yılına ait Türkiye resmi ve dini tatillerini getirecektir. Onaylıyor musunuz?`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Evet, Üret',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            isAiLoading.value = true;
            Swal.fire({
                title: 'Lütfen Bekleyin...',
                html: 'Yapay zeka Türkiye takvimini analiz edip tatilleri çıkarıyor.<br><span style="font-size:11px;color:#666;">Bu işlem 10-15 saniye sürebilir...</span>',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            router.post(route('tanim.tatil-izin.ai-uret'), { yil: yearToGenerate }, {
                onFinish: () => { isAiLoading.value = false; Swal.close(); },
                onSuccess: (page) => {
                    const flash = page.props.flash || {};
                    if(flash.success) Swal.fire({toast:true, position:'top-end', icon:'success', title: flash.success, showConfirmButton:false, timer:3000});
                    else if(flash.error) Swal.fire('Hata', flash.error, 'error');
                },
                onError: (err) => { Swal.fire('Hata', Object.values(err)[0], 'error'); }
            });
        }
    });
};

const mevcutYillar = computed(() => {
    const keys = Object.keys(props.resmiTatiller || {});
    const buYil = new Date().getFullYear().toString();
    if (!keys.includes(buYil)) keys.push(buYil);
    return keys.sort((a,b) => b - a);
});

const aktifYilTatilleri = computed(() => {
    return props.resmiTatiller[currentYear.value] || [];
});

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
                            <tr v-for="iz in izinTurleri" :key="iz.id" class="border-b border-gray-100 hover:bg-gray-50">
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

    <!-- İzin Modal -->
    <div v-if="izinModalAcik" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded shadow-xl w-[400px] border border-gray-300 overflow-hidden">
            <div class="bg-gray-100 px-4 py-3 border-b border-gray-300 flex justify-between items-center"><h3 class="font-bold">{{ izinForm.id ? 'İzin Düzenle' : 'Yeni İzin Türü' }}</h3><button @click="izinModalAcik=false" class="text-gray-500">&times;</button></div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1">İzin Adı *</label>
                    <input v-model="izinForm.ad" type="text" class="w-full text-sm rounded border-gray-300 focus:border-blue-500" placeholder="Örn: Mazeret İzni">
                    <div v-if="izinForm.errors.ad" class="text-red-500 text-xs">{{ izinForm.errors.ad }}</div>
                </div>
                <div class="p-2 border border-orange-200 bg-orange-50 rounded">
                    <label class="flex items-center"><input v-model="izinForm.ucret_kesintisi_yapilacak_mi" type="checkbox" class="rounded text-orange-600 focus:ring-orange-500"><span class="ml-2 text-sm text-gray-700 font-medium">Maaştan Kesinti Yapılacak (Ücretsiz)</span></label>
                    <p class="text-[10px] text-gray-500 mt-1 ml-6">Çarpanlarda veya aylık puantajda bu günler kesintiye uğrar.</p>
                </div>
                <div>
                    <label class="flex items-center"><input v-model="izinForm.yillik_izinden_duser_mi" type="checkbox" class="rounded text-blue-600"><span class="ml-2 text-sm text-gray-700">Yıllık izinden düşülecek</span></label>
                </div>
                <div>
                    <label class="flex items-center"><input v-model="izinForm.aktif_mi" type="checkbox" class="rounded text-green-600"><span class="ml-2 text-sm text-gray-700">Aktif</span></label>
                </div>
            </div>
            <div class="bg-gray-50 p-3 border-t border-gray-200 flex justify-end gap-2">
                <button @click="izinModalAcik=false" class="px-3 py-1.5 border bg-white rounded text-sm text-gray-600 hover:bg-gray-100">İptal</button>
                <button @click="saveIzin" class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Kaydet</button>
            </div>
        </div>
    </div>

    <!-- Tatil Modal -->
    <div v-if="tatilModalAcik" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded shadow-xl w-[400px] border border-gray-300 overflow-hidden">
            <div class="bg-gray-100 px-4 py-3 border-b border-gray-300 flex justify-between items-center"><h3 class="font-bold">{{ tatilForm.id ? 'Tatil Düzenle' : 'Yeni Tatil Ekle' }}</h3><button @click="tatilModalAcik=false" class="text-gray-500">&times;</button></div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1">Tarih *</label>
                    <input v-model="tatilForm.tarih" type="date" class="w-full text-sm rounded border-gray-300 focus:border-red-500" required>
                    <div v-if="tatilForm.errors.tarih" class="text-red-500 text-xs">{{ tatilForm.errors.tarih }}</div>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Tatil Adı *</label>
                    <input v-model="tatilForm.ad" type="text" class="w-full text-sm rounded border-gray-300 focus:border-red-500" placeholder="Örn: 23 Nisan" required>
                    <div v-if="tatilForm.errors.ad" class="text-red-500 text-xs">{{ tatilForm.errors.ad }}</div>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Türü</label>
                    <input v-model="tatilForm.tur" type="text" class="w-full text-sm rounded border-gray-300 focus:border-red-500" placeholder="Örn: Resmi Tatil">
                </div>
                <div class="bg-orange-50 p-2 border border-orange-200 rounded">
                    <label class="flex items-center"><input v-model="tatilForm.yarim_gun_mu" type="checkbox" class="rounded text-orange-600 border-gray-300"><span class="ml-2 text-sm text-gray-700 font-medium">Bu tatil ARİFE (Yarım Gün) mü?</span></label>
                </div>
            </div>
            <div class="bg-gray-50 p-3 border-t border-gray-200 flex justify-end gap-2">
                <button @click="tatilModalAcik=false" class="px-3 py-1.5 border bg-white rounded text-sm text-gray-600 hover:bg-gray-100">İptal</button>
                <button @click="saveTatil" class="px-3 py-1.5 bg-red-600 text-white rounded text-sm hover:bg-red-700">Kaydet</button>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
