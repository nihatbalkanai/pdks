<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({ personeller: Array });
const search = ref('');
const selectedIds = ref([]);
const girisChecked = ref(true);
const cikisChecked = ref(true);
const progress = ref(0);
const isProcessing = ref(false);

const form = useForm({
    personel_ids: [],
    baslangic_tarihi: new Date().toISOString().slice(0, 10),
    bitis_tarihi: new Date().toISOString().slice(0, 10),
    giris_saati: '08:30',
    cikis_saati: '18:00',
});

const filtered = computed(() => {
    if (!search.value) return props.personeller;
    const q = search.value.toLowerCase();
    return props.personeller.filter(p => (p.kart_no || '').toLowerCase().includes(q) || (p.ad || '').toLowerCase().includes(q) || (p.soyad || '').toLowerCase().includes(q));
});

const toggleAll = () => { selectedIds.value = selectedIds.value.length === filtered.value.length ? [] : filtered.value.map(p => p.id); };

const submit = () => {
    if (selectedIds.value.length === 0) { Swal.fire('Uyarı', 'Lütfen en az bir personel seçin.', 'warning'); return; }
    form.personel_ids = selectedIds.value;
    if (!girisChecked.value) form.giris_saati = null;
    if (!cikisChecked.value) form.cikis_saati = null;
    isProcessing.value = true; progress.value = 0;
    const iv = setInterval(() => { progress.value += 10; if (progress.value >= 90) clearInterval(iv); }, 100);
    form.post(route('toplu-islemler.hareket.uygula'), {
        onSuccess: () => { clearInterval(iv); progress.value = 100; isProcessing.value = false; Swal.fire('Başarılı!', 'Hareket kayıtları oluşturuldu.', 'success'); },
        onError: () => { clearInterval(iv); isProcessing.value = false; Swal.fire('Hata', 'İşlem sırasında hata oluştu.', 'error'); }
    });
};
</script>

<template>
<Head title="Toplu Hareket Tanımlamaları" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2">
                <h2 class="font-bold text-sm text-gray-800">Toplu Hareket Tanımlamaları</h2>
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
                        <span class="ml-3 text-sm text-gray-600">Hareket eklemek istediğiniz personelleri seçin</span>
                        <div class="mt-2 bg-blue-50 border border-blue-200 rounded px-3 py-2 text-xs text-blue-700 font-semibold">{{ selectedIds.length }} personel seçildi</div>
                    </div>

                    <div class="mb-5 border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-green-700">Adım 2</span>
                        <span class="ml-3 text-sm text-gray-600">Hareket şartlarını tanımlayınız</span>
                        <div class="mt-3 space-y-3 max-w-lg">
                            <div>
                                <label class="text-xs font-semibold">Tarih:</label>
                                <div class="flex items-center gap-2 mt-1">
                                    <input type="date" v-model="form.baslangic_tarihi" class="border-gray-300 rounded-sm py-1 px-2 text-xs flex-1" />
                                    <span class="text-xs">-</span>
                                    <input type="date" v-model="form.bitis_tarihi" class="border-gray-300 rounded-sm py-1 px-2 text-xs flex-1" />
                                    <span class="text-xs text-gray-500">arası</span>
                                </div>
                            </div>
                            <div class="flex gap-6">
                                <div>
                                    <label class="flex items-center text-xs mb-1"><input type="checkbox" v-model="girisChecked" class="mr-1.5 rounded-sm border-gray-300 text-blue-600 w-3 h-3" /><span class="font-semibold">Giriş Saati:</span></label>
                                    <input v-if="girisChecked" type="time" v-model="form.giris_saati" class="border-gray-300 rounded-sm py-1 px-2 text-xs w-24" />
                                </div>
                                <div>
                                    <label class="flex items-center text-xs mb-1"><input type="checkbox" v-model="cikisChecked" class="mr-1.5 rounded-sm border-gray-300 text-blue-600 w-3 h-3" /><span class="font-semibold">Çıkış Saati:</span></label>
                                    <input v-if="cikisChecked" type="time" v-model="form.cikis_saati" class="border-gray-300 rounded-sm py-1 px-2 text-xs w-24" />
                                </div>
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
