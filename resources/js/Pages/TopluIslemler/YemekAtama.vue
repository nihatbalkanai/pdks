<script setup>
import { ref, computed, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({ personeller: Array });

const search = ref('');
const selectedIds = ref([]);
const yemekTipi = ref('kart');
const progress = ref(0);
const isProcessing = ref(false);

const form = reactive({
    personel_ids: [],
    yemek_tipi: 'kart',
    yemek_kart_no: '',
    yemek_ucreti: '',
});

const filtered = computed(() => {
    if (!search.value) return props.personeller;
    const q = search.value.toLowerCase();
    return props.personeller.filter(p => (p.kart_no || '').toLowerCase().includes(q) || (p.ad || '').toLowerCase().includes(q) || (p.soyad || '').toLowerCase().includes(q));
});

const toggleAll = () => {
    if (selectedIds.value.length === filtered.value.length) { selectedIds.value = []; }
    else { selectedIds.value = filtered.value.map(p => p.id); }
};

const yemekDurum = (p) => {
    if (p.yemek_tipi === 'kart') return `🎫 ${p.yemek_kart_no || '-'}`;
    if (p.yemek_tipi === 'ucret') return `💰 ${Number(p.yemek_ucreti || 0).toLocaleString('tr-TR')} ₺/gün`;
    return '—';
};

const submit = () => {
    if (selectedIds.value.length === 0) { Swal.fire('Uyarı', 'Lütfen en az bir personel seçin.', 'warning'); return; }
    if (yemekTipi.value === 'kart' && !form.yemek_kart_no) { Swal.fire('Uyarı', 'Lütfen yemek kart numarası girin.', 'warning'); return; }
    if (yemekTipi.value === 'ucret' && !form.yemek_ucreti) { Swal.fire('Uyarı', 'Lütfen günlük yemek ücreti girin.', 'warning'); return; }

    form.personel_ids = selectedIds.value;
    form.yemek_tipi = yemekTipi.value;
    isProcessing.value = true;
    progress.value = 0;
    const iv = setInterval(() => { progress.value += 10; if (progress.value >= 90) clearInterval(iv); }, 100);

    axios.post(route('toplu-islemler.yemek-atama.uygula'), { ...form })
        .then(() => {
            clearInterval(iv);
            progress.value = 100;
            isProcessing.value = false;
            Swal.fire('Başarılı!', `${selectedIds.value.length} personele yemek tanımı atandı.`, 'success')
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
<Head title="Toplu Yemek Kartı / Yemek Ücreti Atama" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#fef3c7] to-[#fde68a] border-b border-gray-400 px-4 py-2">
                <h2 class="font-bold text-sm text-gray-800">🍽️ Toplu Yemek Kartı / Yemek Ücreti Atama</h2>
            </div>
            <div class="flex flex-1 overflow-hidden">
                <!-- SOL: Personel Listesi -->
                <div class="w-56 border-r border-gray-400 flex flex-col bg-gray-50">
                    <div class="p-2 border-b border-gray-300">
                        <input v-model="search" type="text" placeholder="🔍 Ara..." class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs" />
                    </div>
                    <div class="px-2 py-1 border-b border-gray-200 flex items-center">
                        <input type="checkbox" @change="toggleAll" :checked="selectedIds.length === filtered.length && filtered.length > 0" class="mr-1.5 rounded-sm border-gray-300 text-amber-600 w-3 h-3" />
                        <span class="text-[10px] text-gray-500">Tümünü Seç ({{ selectedIds.length }}/{{ filtered.length }})</span>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <label v-for="p in filtered" :key="p.id" class="flex items-center px-2 py-1 text-xs cursor-pointer border-b border-gray-100 hover:bg-amber-50" :class="{'bg-amber-100': selectedIds.includes(p.id)}">
                            <input type="checkbox" :value="p.id" v-model="selectedIds" class="mr-1.5 rounded-sm border-gray-300 text-amber-600 w-3 h-3" />
                            <div class="flex-1 min-w-0">
                                <div class="truncate font-medium">{{ p.ad }} {{ p.soyad }}</div>
                                <div class="text-[10px] text-gray-500">{{ yemekDurum(p) }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- SAĞ: Wizard -->
                <div class="flex-1 p-5 overflow-y-auto">
                    <!-- ADIM 1 -->
                    <div class="mb-5">
                        <span class="text-lg font-bold text-amber-700">Adım 1</span>
                        <span class="ml-3 text-sm text-gray-600">Yemek tanımı atamak istediğiniz personelleri seçin...</span>
                        <div class="mt-2 bg-amber-50 border border-amber-200 rounded px-3 py-2 text-xs text-amber-700 font-semibold">
                            {{ selectedIds.length }} personel seçildi
                        </div>
                    </div>

                    <!-- ADIM 2 -->
                    <div class="mb-5 border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-amber-700">Adım 2</span>
                        <span class="ml-3 text-sm text-gray-600">Yemek tipini ve değerini belirleyin...</span>
                        <div class="mt-3 space-y-4 max-w-lg">
                            <!-- Tip Seçimi -->
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 px-4 py-3 rounded-lg border-2 cursor-pointer transition"
                                    :class="yemekTipi === 'kart' ? 'border-amber-500 bg-amber-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" v-model="yemekTipi" value="kart" class="text-amber-600" />
                                    <div>
                                        <div class="text-sm font-bold">🎫 Yemek Kartı</div>
                                        <div class="text-[10px] text-gray-500">Ticket kart numarası atanır</div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 px-4 py-3 rounded-lg border-2 cursor-pointer transition"
                                    :class="yemekTipi === 'ucret' ? 'border-amber-500 bg-amber-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" v-model="yemekTipi" value="ucret" class="text-amber-600" />
                                    <div>
                                        <div class="text-sm font-bold">💰 Yemek Ücreti</div>
                                        <div class="text-[10px] text-gray-500">Günlük yemek parası atanır</div>
                                    </div>
                                </label>
                            </div>

                            <!-- Kart No veya Ücret Girişi -->
                            <div v-if="yemekTipi === 'kart'" class="flex items-center gap-3">
                                <label class="text-xs font-semibold w-28">Kart Numarası:</label>
                                <input v-model="form.yemek_kart_no" type="text" placeholder="1234-5678-9012" class="border-gray-300 rounded-sm py-1.5 px-2 text-sm flex-1 max-w-xs" />
                            </div>
                            <div v-if="yemekTipi === 'ucret'" class="flex items-center gap-3">
                                <label class="text-xs font-semibold w-28">Günlük Ücret:</label>
                                <input v-model="form.yemek_ucreti" type="number" step="0.01" min="0" placeholder="150.00" class="border-gray-300 rounded-sm py-1.5 px-2 text-sm w-32 text-right" />
                                <span class="text-xs text-gray-500">₺ / gün</span>
                            </div>
                        </div>
                    </div>

                    <!-- ADIM 3 -->
                    <div class="border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-amber-700">Adım 3</span>
                        <span class="ml-3 text-sm text-gray-600">Bilgilerinizi kontrol ettikten sonra işlemi başlatın.</span>
                        <div v-if="selectedIds.length > 0" class="mt-3 bg-gray-50 border rounded p-3 text-xs space-y-1">
                            <div><strong>Seçili personel:</strong> {{ selectedIds.length }} kişi</div>
                            <div><strong>Yemek tipi:</strong> {{ yemekTipi === 'kart' ? '🎫 Yemek Kartı' : '💰 Yemek Ücreti' }}</div>
                            <div v-if="yemekTipi === 'kart'"><strong>Kart No:</strong> {{ form.yemek_kart_no || '—' }}</div>
                            <div v-if="yemekTipi === 'ucret'"><strong>Günlük Ücret:</strong> {{ form.yemek_ucreti || '—' }} ₺</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alt Butonlar -->
            <div class="flex items-center gap-3 px-4 py-2 bg-gray-100 border-t border-gray-400">
                <button @click="submit" :disabled="isProcessing" class="flex items-center bg-white border border-gray-400 rounded-sm px-4 py-1.5 text-xs hover:bg-gray-50 shadow-sm font-semibold disabled:opacity-50">
                    <svg class="w-4 h-4 mr-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    İşlemi başlat &gt;
                </button>
                <div v-if="isProcessing" class="flex-1 max-w-xs">
                    <div class="bg-gray-200 rounded-full h-3"><div class="bg-amber-500 h-3 rounded-full transition-all" :style="{width: progress+'%'}"></div></div>
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
