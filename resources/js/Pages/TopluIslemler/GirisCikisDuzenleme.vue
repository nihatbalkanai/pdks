<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    personeller: Array,
    kayitlar: Array,
    tarih: String,
});

const tarih = ref(props.tarih);
const search = ref('');
const selectedIds = ref([]);
const editableKayitlar = ref([...(props.kayitlar || [])]);
const progress = ref(0);
const isProcessing = ref(false);

const bulkGiris = ref('08:30');
const bulkCikis = ref('18:00');

const toTitleCase = (str) => { if (!str) return ''; return str.toLocaleLowerCase('tr-TR').replace(/(^|\s)(\S)/g, (m, s, c) => s + c.toLocaleUpperCase('tr-TR')); };

// Tarih değiştiğinde sayfayı yenile
const changeTarih = () => {
    router.get(route('toplu-islemler.giris-cikis-duzenleme'), { tarih: tarih.value }, { preserveState: false });
};

// Personel filtreleme
const filtered = computed(() => {
    if (!search.value) return props.personeller;
    const q = search.value.toLocaleLowerCase('tr-TR');
    return props.personeller.filter(p =>
        (p.kart_no || '').toLowerCase().includes(q) ||
        (p.ad || '').toLocaleLowerCase('tr-TR').includes(q) ||
        (p.soyad || '').toLocaleLowerCase('tr-TR').includes(q)
    );
});

const toggleAll = () => {
    selectedIds.value = selectedIds.value.length === filtered.value.length
        ? [] : filtered.value.map(p => p.id);
};

// Seçili personelleri tabloya ekle
const addSelected = () => {
    selectedIds.value.forEach(pid => {
        const exists = editableKayitlar.value.find(k => k.personel_id === pid);
        if (!exists) {
            const p = props.personeller.find(pp => pp.id === pid);
            if (p) {
                editableKayitlar.value.push({
                    personel_id: p.id,
                    kart_no: p.kart_no,
                    ad: p.ad,
                    soyad: p.soyad,
                    giris_saati: bulkGiris.value || '',
                    cikis_saati: bulkCikis.value || '',
                });
            }
        }
    });
};

// Toplu saat uygula
const applyBulkGiris = () => { editableKayitlar.value.forEach(k => k.giris_saati = bulkGiris.value); };
const applyBulkCikis = () => { editableKayitlar.value.forEach(k => k.cikis_saati = bulkCikis.value); };

// Satır sil
const removeRow = (index) => { editableKayitlar.value.splice(index, 1); };

// Kaydet
const saveAll = () => {
    if (editableKayitlar.value.length === 0) {
        Swal.fire('Uyarı', 'Kaydedilecek kayıt yok.', 'warning'); return;
    }
    isProcessing.value = true; progress.value = 0;
    const iv = setInterval(() => { progress.value += 10; if (progress.value >= 90) clearInterval(iv); }, 100);
    axios.post(route('toplu-islemler.giris-cikis-duzenleme.kaydet'), {
        tarih: tarih.value,
        kayitlar: editableKayitlar.value.map(k => ({
            personel_id: k.personel_id,
            giris_saati: k.giris_saati || null,
            cikis_saati: k.cikis_saati || null,
        })),
    }).then(() => {
        clearInterval(iv); progress.value = 100; isProcessing.value = false;
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Kaydedildi', showConfirmButton: false, timer: 1500 });
    }).catch(e => { clearInterval(iv); isProcessing.value = false; Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu', 'error'); });
};

// Tarih sil
const deleteTarih = () => {
    Swal.fire({
        title: 'Emin misiniz?',
        text: `${tarih.value} tarihindeki TÜM giriş-çıkış kayıtları silinecek!`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', confirmButtonText: 'Evet, Sil!', cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('toplu-islemler.giris-cikis-duzenleme.tarih-sil'), { data: { tarih: tarih.value } })
                .then(() => { editableKayitlar.value = []; Swal.fire('Silindi!', 'Kayıtlar silindi.', 'success'); })
                .catch(e => Swal.fire('Hata', e.response?.data?.message || 'Silinemedi', 'error'));
        }
    });
};
</script>

<template>
<Head title="Toplu Giriş Çıkış Düzenleme" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">

            <!-- Başlık -->
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h2 class="font-bold text-sm text-gray-800">Toplu Giriş Çıkış Düzenleme</h2>
            </div>

            <div class="flex flex-1 overflow-hidden">

                <!-- SOL: Personel Listesi -->
                <div class="w-56 border-r border-gray-400 flex flex-col bg-gray-50">
                    <div class="p-2 border-b border-gray-300">
                        <input v-model="search" type="text" placeholder="🔍 Ara..." class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs" />
                    </div>
                    <div class="px-2 py-1 border-b border-gray-200 flex items-center">
                        <input type="checkbox" @change="toggleAll" :checked="selectedIds.length === filtered.length && filtered.length > 0" class="mr-1.5 rounded-sm border-gray-300 text-blue-600 w-3 h-3" />
                        <span class="text-[10px] text-gray-500">Tümünü Seç ({{ selectedIds.length }}/{{ filtered.length }})</span>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <label v-for="p in filtered" :key="p.id"
                            class="flex items-center px-2 py-1 text-xs cursor-pointer border-b border-gray-100 hover:bg-blue-50"
                            :class="{'bg-blue-100': selectedIds.includes(p.id)}">
                            <input type="checkbox" :value="p.id" v-model="selectedIds" class="mr-1.5 rounded-sm border-gray-300 text-blue-600 w-3 h-3" />
                            <div class="flex-1 min-w-0">
                                <div class="truncate font-medium">{{ toTitleCase(p.ad + ' ' + p.soyad) }}</div>
                                <div class="text-[10px] text-gray-500">{{ p.kart_no }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- SAĞ: İçerik -->
                <div class="flex-1 flex flex-col overflow-hidden">

                    <!-- Adım 1 + 2 (Üst bar) -->
                    <div class="border-b border-gray-300 bg-gray-50 px-4 py-2.5 flex flex-wrap items-center gap-4 text-xs">

                        <!-- Adım 1: Tarih -->
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-blue-700">Adım 1</span>
                            <span class="text-gray-500">Tarih:</span>
                            <input type="date" v-model="tarih" @change="changeTarih"
                                class="border-gray-300 rounded-sm py-1 px-2 text-xs w-34" />
                        </div>

                        <div class="w-px h-5 bg-gray-300"></div>

                        <!-- Adım 2: Personel Ekle -->
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-blue-700">Adım 2</span>
                            <span class="text-gray-500">{{ selectedIds.length }} personel seçildi</span>
                            <button @click="addSelected"
                                :disabled="selectedIds.length === 0"
                                class="bg-blue-600 hover:bg-blue-700 text-white rounded px-3 py-1 font-semibold disabled:opacity-40 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Tabloya Ekle
                            </button>
                        </div>

                        <div class="w-px h-5 bg-gray-300"></div>

                        <!-- Toplu Saat Uygula -->
                        <div class="flex items-center gap-2 ml-auto">
                            <span class="text-gray-500 font-semibold">Toplu:</span>
                            <span class="text-gray-400">Giriş</span>
                            <input type="time" v-model="bulkGiris" class="border-gray-300 rounded-sm py-0.5 px-1 text-xs w-20" />
                            <button @click="applyBulkGiris" class="bg-green-100 border border-green-300 rounded px-2 py-0.5 text-green-700 hover:bg-green-200 font-semibold">Uygula</button>
                            <span class="text-gray-400">Çıkış</span>
                            <input type="time" v-model="bulkCikis" class="border-gray-300 rounded-sm py-0.5 px-1 text-xs w-20" />
                            <button @click="applyBulkCikis" class="bg-green-100 border border-green-300 rounded px-2 py-0.5 text-green-700 hover:bg-green-200 font-semibold">Uygula</button>
                        </div>
                    </div>

                    <!-- Adım 3: Tablo -->
                    <div class="flex-1 overflow-auto">
                        <table class="w-full text-xs border-collapse">
                            <thead class="bg-[#d0dcea] sticky top-0 z-10">
                                <tr>
                                    <th class="py-1.5 px-2 text-left border border-gray-300 font-bold w-20">Kart No</th>
                                    <th class="py-1.5 px-2 text-left border border-gray-300 font-bold">Ad Soyad</th>
                                    <th class="py-1.5 px-2 text-center border border-gray-300 font-bold w-32">Giriş Saati</th>
                                    <th class="py-1.5 px-2 text-center border border-gray-300 font-bold w-32">Çıkış Saati</th>
                                    <th class="py-1.5 px-2 text-center border border-gray-300 font-bold w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(k, i) in editableKayitlar" :key="k.personel_id"
                                    class="border-b border-gray-200 hover:bg-blue-50 transition-colors">
                                    <td class="py-1 px-2 border-r border-gray-200 text-gray-500">{{ k.kart_no }}</td>
                                    <td class="py-1 px-2 border-r border-gray-200 font-medium">{{ toTitleCase(k.ad + ' ' + k.soyad) }}</td>
                                    <td class="py-0.5 px-1 border-r border-gray-200 text-center">
                                        <input type="time" v-model="k.giris_saati"
                                            class="border-gray-300 rounded-sm py-0.5 px-1 text-xs w-full text-center focus:border-blue-400 focus:ring-1 focus:ring-blue-200" />
                                    </td>
                                    <td class="py-0.5 px-1 border-r border-gray-200 text-center">
                                        <input type="time" v-model="k.cikis_saati"
                                            class="border-gray-300 rounded-sm py-0.5 px-1 text-xs w-full text-center focus:border-blue-400 focus:ring-1 focus:ring-blue-200" />
                                    </td>
                                    <td class="py-0.5 px-1 text-center">
                                        <button @click="removeRow(i)" class="text-red-400 hover:text-red-600 p-0.5" title="Kaldır">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="editableKayitlar.length === 0">
                                    <td colspan="5" class="py-16 text-center text-gray-400 bg-gray-50">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span class="text-sm">Soldaki listeden personel seçip <strong>Tabloya Ekle</strong>'ye tıklayın</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Alt Butonlar -->
                    <div class="flex items-center gap-3 px-4 py-2 bg-gray-100 border-t border-gray-400">
                        <button @click="deleteTarih" class="flex items-center bg-white border border-gray-400 rounded-sm px-3 py-1.5 text-xs hover:bg-red-50 shadow-sm text-red-600 font-semibold">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Tarih Kayıtlarını Sil
                        </button>

                        <span class="text-xs text-gray-500">{{ editableKayitlar.length }} kayıt</span>

                        <div class="ml-auto flex items-center gap-3">
                            <div v-if="isProcessing" class="flex-1 max-w-xs">
                                <div class="bg-gray-200 rounded-full h-2.5"><div class="bg-blue-500 h-2.5 rounded-full transition-all" :style="{width: progress+'%'}"></div></div>
                            </div>
                            <button @click="saveAll" :disabled="isProcessing"
                                class="flex items-center bg-blue-600 hover:bg-blue-700 text-white border border-blue-700 rounded-sm px-4 py-1.5 text-xs shadow-sm font-semibold disabled:opacity-50">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Kaydet
                            </button>
                            <button @click="$inertia.visit(route('dashboard'))" class="flex items-center bg-white border border-gray-400 rounded-sm px-4 py-1.5 text-xs hover:bg-gray-50 shadow-sm text-red-600 font-semibold">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                İptal
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
