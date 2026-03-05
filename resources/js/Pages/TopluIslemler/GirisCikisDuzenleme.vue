<script setup>
import { ref, computed, watch, reactive } from 'vue';
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
const selectedPersonelIds = ref([]);
const showPersonelSelector = ref(false);
const searchPersonel = ref('');
const editableKayitlar = ref([...(props.kayitlar || [])]);

// Tarih değiştiğinde sayfayı yenile
const changeTarih = () => {
    router.get(route('toplu-islemler.giris-cikis-duzenleme'), { tarih: tarih.value }, { preserveState: false });
};

// Personel filtreleme
const filteredPersoneller = computed(() => {
    if (!searchPersonel.value) return props.personeller;
    const q = searchPersonel.value.toLowerCase();
    return props.personeller.filter(p =>
        (p.kart_no || '').toLowerCase().includes(q) ||
        (p.ad || '').toLowerCase().includes(q) ||
        (p.soyad || '').toLowerCase().includes(q)
    );
});

const toggleAllPersonel = () => {
    selectedPersonelIds.value = selectedPersonelIds.value.length === filteredPersoneller.value.length
        ? [] : filteredPersoneller.value.map(p => p.id);
};

// Personel seçimini tabloya ekle
const addPersonelToTable = () => {
    selectedPersonelIds.value.forEach(pid => {
        const exists = editableKayitlar.value.find(k => k.personel_id === pid);
        if (!exists) {
            const p = props.personeller.find(pp => pp.id === pid);
            if (p) {
                editableKayitlar.value.push({
                    personel_id: p.id,
                    kart_no: p.kart_no,
                    ad: p.ad,
                    soyad: p.soyad,
                    giris_saati: '',
                    cikis_saati: '',
                });
            }
        }
    });
    showPersonelSelector.value = false;
    selectedPersonelIds.value = [];
};

// Satır seçimi
const selectedRow = ref(null);

// Kaydet
const saveAll = () => {
    if (editableKayitlar.value.length === 0) {
        Swal.fire('Uyarı', 'Kaydedilecek kayıt yok.', 'warning'); return;
    }
    const form = reactive({
        tarih: tarih.value,
        kayitlar: editableKayitlar.value.map(k => ({
            personel_id: k.personel_id,
            giris_saati: k.giris_saati || null,
            cikis_saati: k.cikis_saati || null,
        })),
    });
    axios.post(route('toplu-islemler.giris-cikis-duzenleme.kaydet'), { ...form }).catch(e => Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu', 'error'));
};

// Tarih aralığı sil
const deleteTarih = () => {
    Swal.fire({
        title: 'Emin misiniz?',
        text: `${tarih.value} tarihindeki TÜM giriş-çıkış kayıtları silinecek!`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', confirmButtonText: 'Evet, Sil!', cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('toplu-islemler.giris-cikis-duzenleme.tarih-sil')).catch(e => Swal.fire('Hata', e.response?.data?.message || 'Silinemedi', 'error'), {
                data: { tarih: tarih.value },
                onSuccess: () => { editableKayitlar.value = []; Swal.fire('Silindi!', 'Tarih aralığındaki kayıtlar silindi.', 'success'); }
            });
        }
    });
};

// Satır sil
const removeRow = (index) => { editableKayitlar.value.splice(index, 1); };

// Tüm giriş/çıkış saatlerini toplu ayarla
const bulkGiris = ref('08:30');
const bulkCikis = ref('18:00');
const applyBulkGiris = () => { editableKayitlar.value.forEach(k => k.giris_saati = bulkGiris.value); };
const applyBulkCikis = () => { editableKayitlar.value.forEach(k => k.cikis_saati = bulkCikis.value); };
</script>

<template>
<Head title="Toplu Giriş Çıkış Düzenleme" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2">
                <h2 class="font-bold text-sm text-gray-800">Toplu Giriş Çıkış Düzenleme</h2>
            </div>

            <!-- Üst Bar: Tarih + Personel Seç -->
            <div class="flex items-center gap-4 px-4 py-2 bg-gray-50 border-b border-gray-300 text-xs">
                <div class="flex items-center gap-2">
                    <label class="font-semibold">Tarih:</label>
                    <input type="date" v-model="tarih" @change="changeTarih" class="border-gray-300 rounded-sm py-1 px-2 text-xs w-36" />
                </div>
                <button @click="showPersonelSelector = !showPersonelSelector" class="bg-[#e8eef8] border border-gray-400 rounded-sm px-3 py-1 text-xs hover:bg-[#d0dce8] flex items-center font-semibold">
                    <svg class="w-4 h-4 mr-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Personel Seç
                </button>
                <div class="ml-auto flex items-center gap-2 text-gray-500">
                    <span>Toplu Giriş:</span>
                    <input type="time" v-model="bulkGiris" class="border-gray-300 rounded-sm py-0.5 px-1 text-xs w-20" />
                    <button @click="applyBulkGiris" class="bg-green-100 border border-green-300 rounded px-2 py-0.5 text-green-700 hover:bg-green-200">Uygula</button>
                    <span class="ml-2">Toplu Çıkış:</span>
                    <input type="time" v-model="bulkCikis" class="border-gray-300 rounded-sm py-0.5 px-1 text-xs w-20" />
                    <button @click="applyBulkCikis" class="bg-green-100 border border-green-300 rounded px-2 py-0.5 text-green-700 hover:bg-green-200">Uygula</button>
                </div>
            </div>

            <!-- Personel Seçim Paneli -->
            <div v-if="showPersonelSelector" class="border-b border-gray-300 bg-yellow-50 px-4 py-2 flex gap-3">
                <div class="w-64 border border-gray-300 rounded bg-white flex flex-col max-h-48">
                    <input v-model="searchPersonel" type="text" placeholder="🔍 Personel ara..." class="border-b border-gray-200 py-1 px-2 text-xs" />
                    <div class="px-2 py-1 border-b border-gray-100 flex items-center">
                        <input type="checkbox" @change="toggleAllPersonel" :checked="selectedPersonelIds.length === filteredPersoneller.length && filteredPersoneller.length > 0" class="mr-1 w-3 h-3 rounded-sm border-gray-300 text-blue-600" />
                        <span class="text-[10px] text-gray-500">Tümünü Seç</span>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <label v-for="p in filteredPersoneller" :key="p.id" class="flex items-center px-2 py-0.5 text-xs cursor-pointer hover:bg-blue-50">
                            <input type="checkbox" :value="p.id" v-model="selectedPersonelIds" class="mr-1 w-3 h-3 rounded-sm border-gray-300 text-blue-600" />
                            {{ p.kart_no }} - {{ p.ad }} {{ p.soyad }}
                        </label>
                    </div>
                </div>
                <div class="flex flex-col justify-center gap-2">
                    <button @click="addPersonelToTable" class="bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-semibold hover:bg-blue-700">
                        Tabloya Ekle ({{ selectedPersonelIds.length }})
                    </button>
                    <button @click="showPersonelSelector = false" class="bg-gray-300 text-gray-700 px-3 py-1.5 rounded text-xs hover:bg-gray-400">İptal</button>
                </div>
            </div>

            <!-- Tablo -->
            <div class="flex-1 overflow-auto">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-[#d0dcea] sticky top-0 z-10">
                        <tr>
                            <th class="py-1.5 px-2 text-left border border-gray-400 font-bold w-20">KartNo</th>
                            <th class="py-1.5 px-2 text-left border border-gray-400 font-bold">İsim</th>
                            <th class="py-1.5 px-2 text-left border border-gray-400 font-bold">Soyadı</th>
                            <th class="py-1.5 px-2 text-center border border-gray-400 font-bold w-28">Giriş Saati</th>
                            <th class="py-1.5 px-2 text-center border border-gray-400 font-bold w-28">Çıkış Saati</th>
                            <th class="py-1.5 px-2 text-center border border-gray-400 font-bold w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(k, i) in editableKayitlar" :key="k.personel_id"
                            @click="selectedRow = i"
                            class="border-b border-gray-200 hover:bg-blue-50 transition-colors cursor-pointer"
                            :class="{'!bg-yellow-200': selectedRow === i}">
                            <td class="py-0.5 px-2 border-r border-gray-200">{{ k.kart_no }}</td>
                            <td class="py-0.5 px-2 border-r border-gray-200">{{ k.ad }}</td>
                            <td class="py-0.5 px-2 border-r border-gray-200">{{ k.soyad }}</td>
                            <td class="py-0.5 px-1 border-r border-gray-200 text-center">
                                <input type="time" v-model="k.giris_saati" class="border-gray-300 rounded-sm py-0.5 px-1 text-xs w-full text-center" />
                            </td>
                            <td class="py-0.5 px-1 border-r border-gray-200 text-center">
                                <input type="time" v-model="k.cikis_saati" class="border-gray-300 rounded-sm py-0.5 px-1 text-xs w-full text-center" />
                            </td>
                            <td class="py-0.5 px-1 text-center">
                                <button @click.stop="removeRow(i)" class="text-red-500 hover:text-red-700" title="Satırı Kaldır">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="editableKayitlar.length === 0">
                            <td colspan="6" class="py-12 text-center text-gray-400 bg-blue-50">&lt;Gösterilecek Bilgi yok&gt;</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Alt Butonlar -->
            <div class="flex items-center gap-2 px-4 py-2 bg-gray-100 border-t border-gray-400">
                <button @click="deleteTarih" class="flex items-center bg-white border border-gray-400 rounded-sm px-3 py-1.5 text-xs hover:bg-red-50 shadow-sm text-red-600 font-semibold">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    Tarih aralığı sil
                </button>
                <div class="ml-auto flex gap-1">
                    <button @click="showPersonelSelector = true" class="win-btn" title="Personel Ekle">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </button>
                    <button v-if="selectedRow !== null" @click="removeRow(selectedRow); selectedRow = null" class="win-btn" title="Seçili Satırı Kaldır">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                    </button>
                    <button class="win-btn" title="Yukarı Taşı">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                    </button>
                    <button class="win-btn" title="Aşağı Taşı">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <button @click="saveAll" class="win-btn" title="Kaydet">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </button>
                    <button @click="saveAll" class="win-btn" title="Tümünü Kaydet">
                        <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>

<style scoped>
.win-btn {
    @apply w-8 h-8 flex items-center justify-center bg-white border border-gray-400 rounded-sm hover:bg-gray-100 shadow-sm cursor-pointer transition;
}
</style>
