<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({ personeller: Array });
const search = ref('');
const selectedIds = ref([]);
const progress = ref(0);
const isProcessing = ref(false);

const form = useForm({
    personel_ids: [],
    agi_degeri: '',
});

const agiSecenekleri = [
    { label: 'Bekar', value: 'Bekar' },
    { label: 'Evli Eşi Çalışan', value: 'Evli Eşi Çalışan' },
    { label: 'Evli Eşi Çalışmayan', value: 'Evli Eşi Çalışmayan' },
    { label: 'Evli Eşi Çalışmayan 1 Çocuk', value: 'Evli Eşi Çalışmayan 1 Çocuk' },
    { label: 'Evli Eşi Çalışmayan 2 Çocuk', value: 'Evli Eşi Çalışmayan 2 Çocuk' },
    { label: 'Evli Eşi Çalışmayan 3 Çocuk', value: 'Evli Eşi Çalışmayan 3 Çocuk' },
    { label: 'Evli Eşi Çalışmayan 4+ Çocuk', value: 'Evli Eşi Çalışmayan 4+ Çocuk' },
    { label: 'Evli Eşi Çalışan 1 Çocuk', value: 'Evli Eşi Çalışan 1 Çocuk' },
    { label: 'Evli Eşi Çalışan 2 Çocuk', value: 'Evli Eşi Çalışan 2 Çocuk' },
    { label: 'Evli Eşi Çalışan 3 Çocuk', value: 'Evli Eşi Çalışan 3 Çocuk' },
    { label: 'Evli Eşi Çalışan 4+ Çocuk', value: 'Evli Eşi Çalışan 4+ Çocuk' },
];

const filtered = computed(() => {
    if (!search.value) return props.personeller;
    const q = search.value.toLowerCase();
    return props.personeller.filter(p => (p.kart_no || '').toLowerCase().includes(q) || (p.ad || '').toLowerCase().includes(q) || (p.soyad || '').toLowerCase().includes(q));
});

const toggleAll = () => { selectedIds.value = selectedIds.value.length === filtered.value.length ? [] : filtered.value.map(p => p.id); };

const submit = () => {
    if (selectedIds.value.length === 0) { Swal.fire('Uyarı', 'Lütfen en az bir personel seçin.', 'warning'); return; }
    if (!form.agi_degeri) { Swal.fire('Uyarı', 'Lütfen AGİ değeri seçin.', 'warning'); return; }
    form.personel_ids = selectedIds.value;
    isProcessing.value = true; progress.value = 0;
    const iv = setInterval(() => { progress.value += 10; if (progress.value >= 90) clearInterval(iv); }, 100);
    form.post(route('toplu-islemler.agi-atama.uygula'), {
        onSuccess: () => { clearInterval(iv); progress.value = 100; isProcessing.value = false; Swal.fire('Başarılı!', `${selectedIds.value.length} personele AGİ atandı.`, 'success'); },
        onError: () => { clearInterval(iv); isProcessing.value = false; Swal.fire('Hata', 'İşlem sırasında hata oluştu.', 'error'); }
    });
};
</script>

<template>
<Head title="Toplu AGİ Atama" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2">
                <h2 class="font-bold text-sm text-gray-800">Toplu AGİ Atama</h2>
            </div>
            <div class="flex flex-1 overflow-hidden">
                <div class="w-56 border-r border-gray-400 flex flex-col bg-gray-50">
                    <div class="p-2 border-b border-gray-300"><input v-model="search" type="text" placeholder="🔍 Ara..." class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs" /></div>
                    <div class="px-2 py-1 border-b border-gray-200 flex items-center">
                        <input type="checkbox" @change="toggleAll" :checked="selectedIds.length === filtered.length && filtered.length > 0" class="mr-1.5 rounded-sm border-gray-300 text-blue-600 w-3 h-3" />
                        <span class="text-[10px] text-gray-500">Tümünü Seç ({{ selectedIds.length }}/{{ filtered.length }})</span>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <label v-for="p in filtered" :key="p.id" class="flex items-center px-2 py-1 text-xs cursor-pointer border-b border-gray-100 hover:bg-blue-50" :class="{'bg-blue-100': selectedIds.includes(p.id)}">
                            <input type="checkbox" :value="p.id" v-model="selectedIds" class="mr-1.5 rounded-sm border-gray-300 text-blue-600 w-3 h-3" />
                            <div class="flex-1 min-w-0"><div class="truncate font-medium">{{ p.ad }} {{ p.soyad }}</div><div class="text-[10px] text-gray-500">{{ p.kart_no }}</div></div>
                        </label>
                    </div>
                </div>

                <div class="flex-1 p-5 overflow-y-auto">
                    <div class="mb-5">
                        <span class="text-lg font-bold text-green-700">Adım 1</span>
                        <span class="ml-3 text-sm text-gray-600">AGİ atamak istediğiniz personelleri seçin</span>
                        <div class="mt-2 bg-blue-50 border border-blue-200 rounded px-3 py-2 text-xs text-blue-700 font-semibold">{{ selectedIds.length }} personel seçildi</div>
                    </div>

                    <div class="mb-5 border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-green-700">Adım 2</span>
                        <span class="ml-3 text-sm text-gray-600">AGİ değerini seçin</span>
                        <div class="mt-3 max-w-md">
                            <label class="text-xs font-semibold mb-1 block">AGİ Durumu:</label>
                            <select v-model="form.agi_degeri" class="w-full border-gray-300 rounded-sm py-1.5 px-2 text-xs">
                                <option value="">Seçiniz...</option>
                                <option v-for="a in agiSecenekleri" :key="a.value" :value="a.value">{{ a.label }}</option>
                            </select>
                            <div v-if="form.agi_degeri" class="mt-3 bg-green-50 border border-green-200 rounded px-3 py-2 text-xs text-green-700">
                                <strong>Seçilen AGİ:</strong> {{ form.agi_degeri }}
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-green-700">Adım 3</span>
                        <span class="ml-3 text-sm text-gray-600">Bilgilerinizi kontrol ettikten sonra işlemi başlatın.</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 px-4 py-2 bg-gray-100 border-t border-gray-400">
                <button @click="submit" :disabled="isProcessing" class="flex items-center bg-white border border-gray-400 rounded-sm px-4 py-1.5 text-xs hover:bg-gray-50 shadow-sm font-semibold disabled:opacity-50">
                    <svg class="w-4 h-4 mr-1.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    İşlemi başlat &gt;
                </button>
                <div v-if="isProcessing" class="flex-1 max-w-xs"><div class="bg-gray-200 rounded-full h-3"><div class="bg-green-500 h-3 rounded-full transition-all" :style="{width: progress+'%'}"></div></div></div>
                <div class="ml-auto">
                    <button @click="$inertia.visit(route('dashboard'))" class="flex items-center bg-white border border-gray-400 rounded-sm px-4 py-1.5 text-xs hover:bg-gray-50 shadow-sm text-red-600 font-semibold">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>İptal
                    </button>
                </div>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
