<script setup>
import { ref, computed, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({ personeller: Array });

const search = ref('');
const selectedIds = ref([]);
const ulasimTipi = ref('servis');
const progress = ref(0);
const isProcessing = ref(false);

const form = reactive({
    personel_ids: [],
    ulasim_tipi: 'servis',
    servis_plaka: '',
    yol_parasi: '',
});

const toTitleCase = (str) => { if (!str) return ''; return str.toLocaleLowerCase('tr-TR').replace(/(^|\s)(\S)/g, (m, s, c) => s + c.toLocaleUpperCase('tr-TR')); };
const filtered = computed(() => {
    if (!search.value) return props.personeller;
    const q = search.value.toLocaleLowerCase('tr-TR');
    return props.personeller.filter(p => (p.kart_no || '').toLowerCase().includes(q) || (p.ad || '').toLocaleLowerCase('tr-TR').includes(q) || (p.soyad || '').toLocaleLowerCase('tr-TR').includes(q));
});

const toggleAll = () => {
    if (selectedIds.value.length === filtered.value.length) { selectedIds.value = []; }
    else { selectedIds.value = filtered.value.map(p => p.id); }
};

const ulasimDurum = (p) => {
    if (p.ulasim_tipi === 'servis') return `🚌 ${p.servis_plaka || '-'}`;
    if (p.ulasim_tipi === 'yol_parasi') return `💵 ${Number(p.yol_parasi || 0).toLocaleString('tr-TR')} ₺/gün`;
    return '—';
};

const submit = () => {
    if (selectedIds.value.length === 0) { Swal.fire('Uyarı', 'Lütfen en az bir personel seçin.', 'warning'); return; }
    if (ulasimTipi.value === 'servis' && !form.servis_plaka) { Swal.fire('Uyarı', 'Lütfen servis plaka numarası girin.', 'warning'); return; }
    if (ulasimTipi.value === 'yol_parasi' && !form.yol_parasi) { Swal.fire('Uyarı', 'Lütfen günlük yol parası girin.', 'warning'); return; }

    form.personel_ids = selectedIds.value;
    form.ulasim_tipi = ulasimTipi.value;
    isProcessing.value = true;
    progress.value = 0;
    const iv = setInterval(() => { progress.value += 10; if (progress.value >= 90) clearInterval(iv); }, 100);

    axios.post(route('toplu-islemler.servis-yol-atama.uygula'), { ...form })
        .then(() => {
            clearInterval(iv);
            progress.value = 100;
            isProcessing.value = false;
            Swal.fire('Başarılı!', `${selectedIds.value.length} personele ulaşım tanımı atandı.`, 'success')
                .then(() => window.location.reload());
        })
        .catch(e => {
            clearInterval(iv);
            isProcessing.value = false;
            Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu', 'error');
        });
};
</script>

<template>
<Head title="Toplu Servis / Yol Parası Atama" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#dbeafe] to-[#bfdbfe] border-b border-gray-400 px-4 py-2">
                <h2 class="font-bold text-sm text-gray-800">🚌 Toplu Servis / Yol Parası Atama</h2>
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
                        <label v-for="p in filtered" :key="p.id" class="flex items-center px-2 py-1 text-xs cursor-pointer border-b border-gray-100 hover:bg-blue-50" :class="{'bg-blue-100': selectedIds.includes(p.id)}">
                            <input type="checkbox" :value="p.id" v-model="selectedIds" class="mr-1.5 rounded-sm border-gray-300 text-blue-600 w-3 h-3" />
                            <div class="flex-1 min-w-0">
                                <div class="truncate font-medium">{{ toTitleCase(p.ad + ' ' + p.soyad) }}</div>
                                <div class="text-[10px] text-gray-500">{{ ulasimDurum(p) }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- SAĞ: Wizard -->
                <div class="flex-1 p-5 overflow-y-auto">
                    <!-- ADIM 1 -->
                    <div class="mb-5">
                        <span class="text-lg font-bold text-blue-700">Adım 1</span>
                        <span class="ml-3 text-sm text-gray-600">Ulaşım tanımı atamak istediğiniz personelleri seçin...</span>
                        <div class="mt-2 bg-blue-50 border border-blue-200 rounded px-3 py-2 text-xs text-blue-700 font-semibold">
                            {{ selectedIds.length }} personel seçildi
                        </div>
                    </div>

                    <!-- ADIM 2 -->
                    <div class="mb-5 border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-blue-700">Adım 2</span>
                        <span class="ml-3 text-sm text-gray-600">Ulaşım tipini ve değerini belirleyin...</span>
                        <div class="mt-3 space-y-4 max-w-lg">
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 px-4 py-3 rounded-lg border-2 cursor-pointer transition"
                                    :class="ulasimTipi === 'servis' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" v-model="ulasimTipi" value="servis" class="text-blue-600" />
                                    <div>
                                        <div class="text-sm font-bold">🚌 Servis Hakkı</div>
                                        <div class="text-[10px] text-gray-500">Personele servis plakası atanır</div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 px-4 py-3 rounded-lg border-2 cursor-pointer transition"
                                    :class="ulasimTipi === 'yol_parasi' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" v-model="ulasimTipi" value="yol_parasi" class="text-blue-600" />
                                    <div>
                                        <div class="text-sm font-bold">💵 Yol Parası</div>
                                        <div class="text-[10px] text-gray-500">Günlük yol parası atanır</div>
                                    </div>
                                </label>
                            </div>

                            <div v-if="ulasimTipi === 'servis'" class="flex items-center gap-3">
                                <label class="text-xs font-semibold w-28">Servis Plakası:</label>
                                <input v-model="form.servis_plaka" type="text" placeholder="34 ABC 123" class="border-gray-300 rounded-sm py-1.5 px-2 text-sm flex-1 max-w-xs" />
                            </div>
                            <div v-if="ulasimTipi === 'yol_parasi'" class="flex items-center gap-3">
                                <label class="text-xs font-semibold w-28">Günlük Ücret:</label>
                                <input v-model="form.yol_parasi" type="number" step="0.01" min="0" placeholder="75.00" class="border-gray-300 rounded-sm py-1.5 px-2 text-sm w-32 text-right" />
                                <span class="text-xs text-gray-500">₺ / gün</span>
                            </div>
                        </div>
                    </div>

                    <!-- ADIM 3 -->
                    <div class="border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-blue-700">Adım 3</span>
                        <span class="ml-3 text-sm text-gray-600">Bilgilerinizi kontrol ettikten sonra işlemi başlatın.</span>
                        <div v-if="selectedIds.length > 0" class="mt-3 bg-gray-50 border rounded p-3 text-xs space-y-1">
                            <div><strong>Seçili personel:</strong> {{ selectedIds.length }} kişi</div>
                            <div><strong>Ulaşım tipi:</strong> {{ ulasimTipi === 'servis' ? '🚌 Servis Hakkı' : '💵 Yol Parası' }}</div>
                            <div v-if="ulasimTipi === 'servis'"><strong>Plaka:</strong> {{ form.servis_plaka || '—' }}</div>
                            <div v-if="ulasimTipi === 'yol_parasi'"><strong>Günlük Ücret:</strong> {{ form.yol_parasi || '—' }} ₺</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alt Butonlar -->
            <div class="flex items-center gap-3 px-4 py-2 bg-gray-100 border-t border-gray-400">
                <button @click="submit" :disabled="isProcessing" class="flex items-center bg-white border border-gray-400 rounded-sm px-4 py-1.5 text-xs hover:bg-gray-50 shadow-sm font-semibold disabled:opacity-50">
                    <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    İşlemi başlat &gt;
                </button>
                <div v-if="isProcessing" class="flex-1 max-w-xs">
                    <div class="bg-gray-200 rounded-full h-3"><div class="bg-blue-500 h-3 rounded-full transition-all" :style="{width: progress+'%'}"></div></div>
                </div>
                <div class="ml-auto">
                    <button @click="$inertia.visit(route('dashboard'))" class="flex items-center bg-white border border-gray-400 rounded-sm px-4 py-1.5 text-xs hover:bg-gray-50 shadow-sm text-red-600 font-semibold">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        İptal
                    </button>
                </div>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
