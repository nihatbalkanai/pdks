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
    mesaj: '',
});

const filtered = computed(() => {
    if (!search.value) return props.personeller;
    const q = search.value.toLowerCase();
    return props.personeller.filter(p => (p.kart_no || '').toLowerCase().includes(q) || (p.ad || '').toLowerCase().includes(q) || (p.soyad || '').toLowerCase().includes(q) || (p.telefon || '').includes(q));
});

const telefonluPersoneller = computed(() => filtered.value.filter(p => p.telefon));
const telefonsuzSayisi = computed(() => selectedIds.value.filter(id => {
    const p = props.personeller.find(pp => pp.id === id);
    return p && !p.telefon;
}).length);

const toggleAll = () => { selectedIds.value = selectedIds.value.length === filtered.value.length ? [] : filtered.value.map(p => p.id); };

const sablonlar = [
    'Sayın {ad} {soyad}, mesainize zamanında gelmeniz gerekmektedir.',
    'Sayın {ad} {soyad}, izin talebiniz onaylanmıştır.',
    'Sayın {ad} {soyad}, maaş bordronuz hazırlanmıştır.',
    'Sayın {ad} {soyad}, yarın tatil olacağı bilgisini iletiriz.',
];

const sablonSec = (sablon) => { form.mesaj = sablon; };

const submit = () => {
    if (selectedIds.value.length === 0) { Swal.fire('Uyarı', 'Lütfen en az bir personel seçin.', 'warning'); return; }
    if (!form.mesaj) { Swal.fire('Uyarı', 'Lütfen mesaj yazın.', 'warning'); return; }
    if (telefonsuzSayisi.value > 0) {
        Swal.fire({ title: 'Uyarı', html: `<b>${telefonsuzSayisi.value}</b> personelin telefon numarası yok.<br>Bu kişiler atlanarak gönderim yapılacak. Devam edilsin mi?`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Gönder', cancelButtonText: 'İptal' }).then(r => { if (r.isConfirmed) doSend(); });
    } else { doSend(); }
};

const doSend = () => {
    form.personel_ids = selectedIds.value;
    isProcessing.value = true; progress.value = 0;
    const iv = setInterval(() => { progress.value += 5; if (progress.value >= 90) clearInterval(iv); }, 100);
    form.post(route('toplu-islemler.toplu-mesaj.gonder'), {
        onSuccess: () => { clearInterval(iv); progress.value = 100; isProcessing.value = false; Swal.fire('Başarılı!', 'Mesajlar gönderildi.', 'success'); },
        onError: () => { clearInterval(iv); isProcessing.value = false; Swal.fire('Hata', 'Gönderim sırasında hata oluştu.', 'error'); }
    });
};
</script>

<template>
<Head title="Toplu Mesaj Gönderimi" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                <h2 class="font-bold text-sm text-gray-800">Toplu Mesaj (SMS) Gönderimi</h2>
            </div>
            <div class="flex flex-1 overflow-hidden">
                <!-- SOL: Personel Listesi -->
                <div class="w-56 border-r border-gray-400 flex flex-col bg-gray-50">
                    <div class="p-2 border-b border-gray-300"><input v-model="search" type="text" placeholder="🔍 Ara..." class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs" /></div>
                    <div class="px-2 py-1 border-b border-gray-200 flex items-center">
                        <input type="checkbox" @change="toggleAll" :checked="selectedIds.length === filtered.length && filtered.length > 0" class="mr-1.5 rounded-sm border-gray-300 text-blue-600 w-3 h-3" />
                        <span class="text-[10px] text-gray-500">Tümünü Seç ({{ selectedIds.length }}/{{ filtered.length }})</span>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <label v-for="p in filtered" :key="p.id" class="flex items-center px-2 py-1 text-xs cursor-pointer border-b border-gray-100 hover:bg-blue-50" :class="{'bg-blue-100': selectedIds.includes(p.id)}">
                            <input type="checkbox" :value="p.id" v-model="selectedIds" class="mr-1.5 rounded-sm border-gray-300 text-blue-600 w-3 h-3" />
                            <div class="flex-1 min-w-0">
                                <div class="truncate font-medium">{{ p.ad }} {{ p.soyad }}</div>
                                <div class="text-[10px]" :class="p.telefon ? 'text-green-600' : 'text-red-400'">{{ p.telefon || 'Telefon yok' }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- SAĞ: Mesaj Alanı -->
                <div class="flex-1 p-5 overflow-y-auto">
                    <div class="mb-5">
                        <span class="text-lg font-bold text-green-700">Adım 1</span>
                        <span class="ml-3 text-sm text-gray-600">Mesaj göndermek istediğiniz personelleri seçin</span>
                        <div class="mt-2 flex gap-2">
                            <div class="bg-blue-50 border border-blue-200 rounded px-3 py-2 text-xs text-blue-700 font-semibold">{{ selectedIds.length }} personel seçildi</div>
                            <div v-if="telefonsuzSayisi > 0" class="bg-red-50 border border-red-200 rounded px-3 py-2 text-xs text-red-600 font-semibold">⚠ {{ telefonsuzSayisi }} kişinin telefonu yok</div>
                        </div>
                    </div>

                    <div class="mb-5 border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-green-700">Adım 2</span>
                        <span class="ml-3 text-sm text-gray-600">Mesajınızı yazın veya şablon seçin</span>

                        <div class="mt-3 flex gap-1 flex-wrap">
                            <button v-for="(s, i) in sablonlar" :key="i" @click="sablonSec(s)" class="bg-gray-100 border border-gray-300 rounded px-2 py-1 text-[10px] hover:bg-blue-50 hover:border-blue-300">
                                Şablon {{ i + 1 }}
                            </button>
                        </div>

                        <div class="mt-3 max-w-lg">
                            <textarea v-model="form.mesaj" rows="5" class="w-full border-gray-300 rounded-sm py-2 px-3 text-xs" placeholder="Mesajınızı yazın... {ad}, {soyad} değişkenlerini kullanabilirsiniz."></textarea>
                            <div class="text-right text-[10px] text-gray-400">{{ form.mesaj.length }}/500 karakter</div>
                        </div>
                    </div>

                    <div class="border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-green-700">Adım 3</span>
                        <span class="ml-3 text-sm text-gray-600">Kontrol edip gönderimi başlatın.</span>
                    </div>
                </div>
            </div>

            <!-- Alt Butonlar -->
            <div class="flex items-center gap-3 px-4 py-2 bg-gray-100 border-t border-gray-400">
                <button @click="submit" :disabled="isProcessing" class="flex items-center bg-green-600 text-white border border-green-700 rounded-sm px-4 py-1.5 text-xs hover:bg-green-700 shadow-sm font-semibold disabled:opacity-50">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    SMS Gönder
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
