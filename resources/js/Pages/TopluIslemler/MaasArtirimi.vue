<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({ personeller: Array });

const search = ref('');
const selectedIds = ref([]);
const artirmaTipi = ref('oran');
const progress = ref(0);
const isProcessing = ref(false);

const form = useForm({
    personel_ids: [],
    tarih: new Date().toISOString().slice(0, 10),
    tip: 'oran',
    deger: '',
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

const submit = () => {
    if (selectedIds.value.length === 0) { Swal.fire('Uyarı', 'Lütfen en az bir personel seçin.', 'warning'); return; }
    if (!form.deger) { Swal.fire('Uyarı', 'Lütfen artırım değeri girin.', 'warning'); return; }
    form.personel_ids = selectedIds.value;
    form.tip = artirmaTipi.value;
    isProcessing.value = true;
    progress.value = 0;
    const iv = setInterval(() => { progress.value += 10; if (progress.value >= 90) clearInterval(iv); }, 100);
    form.post(route('toplu-islemler.maas-artirimi.uygula'), {
        onSuccess: () => { clearInterval(iv); progress.value = 100; isProcessing.value = false; Swal.fire('Başarılı!', `${selectedIds.value.length} personelin maaşı güncellendi.`, 'success'); },
        onError: () => { clearInterval(iv); isProcessing.value = false; Swal.fire('Hata', 'İşlem sırasında hata oluştu.', 'error'); }
    });
};
</script>

<template>
<Head title="Toplu Maaş Artırımı" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2">
                <h2 class="font-bold text-sm text-gray-800">Toplu Maaş Artırımı</h2>
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
                                <div class="truncate font-medium">{{ p.ad }} {{ p.soyad }}</div>
                                <div class="text-[10px] text-gray-500">{{ p.kart_no }} — {{ Number(p.aylik_ucret || 0).toLocaleString('tr-TR') }} ₺</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- SAĞ: Wizard -->
                <div class="flex-1 p-5 overflow-y-auto">
                    <!-- ADIM 1 -->
                    <div class="mb-5">
                        <span class="text-lg font-bold text-green-700">Adım 1</span>
                        <span class="ml-3 text-sm text-gray-600">Maaşını yükseltmek istediğiniz personelleri seçin...</span>
                        <div class="mt-2 bg-blue-50 border border-blue-200 rounded px-3 py-2 text-xs text-blue-700 font-semibold">
                            {{ selectedIds.length }} personel seçildi
                        </div>
                    </div>

                    <!-- ADIM 2 -->
                    <div class="mb-5 border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-green-700">Adım 2</span>
                        <span class="ml-3 text-sm text-gray-600">Yükseltilecek miktar veya oranı düzenleyin...</span>
                        <div class="mt-3 space-y-3 max-w-md">
                            <div class="flex items-center gap-3">
                                <label class="text-xs font-semibold w-16">Tarih:</label>
                                <input type="date" v-model="form.tarih" class="border-gray-300 rounded-sm py-1 px-2 text-xs flex-1" />
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center text-xs cursor-pointer">
                                    <input type="radio" v-model="artirmaTipi" value="oran" class="mr-1.5 text-blue-600" />
                                    <span class="font-semibold">Oran artırımı</span>
                                </label>
                                <span class="text-xs">%</span>
                                <input v-if="artirmaTipi === 'oran'" type="number" v-model="form.deger" placeholder="10" class="border-gray-300 rounded-sm py-1 px-2 text-xs w-24 text-right" />
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center text-xs cursor-pointer">
                                    <input type="radio" v-model="artirmaTipi" value="miktar" class="mr-1.5 text-blue-600" />
                                    <span class="font-semibold">Miktar artırımı</span>
                                </label>
                                <input v-if="artirmaTipi === 'miktar'" type="number" v-model="form.deger" placeholder="5000" class="border-gray-300 rounded-sm py-1 px-2 text-xs w-32 text-right" />
                                <span v-if="artirmaTipi === 'miktar'" class="text-xs">₺</span>
                            </div>
                        </div>
                    </div>

                    <!-- ADIM 3 -->
                    <div class="border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-green-700">Adım 3</span>
                        <span class="ml-3 text-sm text-gray-600">Bilgilerinizi kontrol ettikten sonra işlemi başlatın.</span>
                    </div>
                </div>
            </div>

            <!-- Alt Butonlar -->
            <div class="flex items-center gap-3 px-4 py-2 bg-gray-100 border-t border-gray-400">
                <button @click="submit" :disabled="isProcessing" class="flex items-center bg-white border border-gray-400 rounded-sm px-4 py-1.5 text-xs hover:bg-gray-50 shadow-sm font-semibold disabled:opacity-50">
                    <svg class="w-4 h-4 mr-1.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    İşlemi başlat &gt;
                </button>
                <div v-if="isProcessing" class="flex-1 max-w-xs">
                    <div class="bg-gray-200 rounded-full h-3"><div class="bg-green-500 h-3 rounded-full transition-all" :style="{width: progress+'%'}"></div></div>
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
