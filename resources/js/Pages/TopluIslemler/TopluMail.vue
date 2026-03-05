<script setup>
import { ref, computed, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({ personeller: Array });
const search = ref('');
const selectedIds = ref([]);
const progress = ref(0);
const isProcessing = ref(false);

const form = reactive({
    personel_ids: [],
    konu: '',
    mesaj: '',
});

const filtered = computed(() => {
    if (!search.value) return props.personeller;
    const q = search.value.toLowerCase();
    return props.personeller.filter(p => (p.kart_no || '').toLowerCase().includes(q) || (p.ad || '').toLowerCase().includes(q) || (p.soyad || '').toLowerCase().includes(q) || (p.email || '').toLowerCase().includes(q));
});

const emailsizSayisi = computed(() => selectedIds.value.filter(id => {
    const p = props.personeller.find(pp => pp.id === id);
    return p && !p.email;
}).length);

const toggleAll = () => { selectedIds.value = selectedIds.value.length === filtered.value.length ? [] : filtered.value.map(p => p.id); };

const konuSablonlari = [
    { konu: 'Geç Kalma Uyarısı', mesaj: 'Sayın {ad} {soyad},\n\nMesai saatlerinize uymanız gerekmektedir. Geç kalmalarınız kayıt altına alınmaktadır.\n\nSaygılarımızla,\nİnsan Kaynakları' },
    { konu: 'İzin Onayı', mesaj: 'Sayın {ad} {soyad},\n\nİzin talebiniz onaylanmıştır.\n\nSaygılarımızla,\nİnsan Kaynakları' },
    { konu: 'Maaş Bildirimi', mesaj: 'Sayın {ad} {soyad},\n\nMaaş bordronuz hazırlanmıştır. Detaylar için İK departmanı ile iletişime geçebilirsiniz.\n\nSaygılarımızla,\nMuhasebe' },
    { konu: 'Genel Duyuru', mesaj: 'Sayın {ad} {soyad},\n\nAşağıdaki duyuruyu dikkatinize sunuyoruz:\n\n[Duyuru metni buraya yazılacak]\n\nSaygılarımızla,\nYönetim' },
];

const sablonSec = (s) => { form.konu = s.konu; form.mesaj = s.mesaj; };

const submit = () => {
    if (selectedIds.value.length === 0) { Swal.fire('Uyarı', 'Lütfen en az bir personel seçin.', 'warning'); return; }
    if (!form.konu) { Swal.fire('Uyarı', 'Lütfen e-posta konusu yazın.', 'warning'); return; }
    if (!form.mesaj) { Swal.fire('Uyarı', 'Lütfen e-posta içeriği yazın.', 'warning'); return; }
    if (emailsizSayisi.value > 0) {
        Swal.fire({ title: 'Uyarı', html: `<b>${emailsizSayisi.value}</b> personelin e-posta adresi yok.<br>Bu kişiler atlanarak gönderim yapılacak. Devam edilsin mi?`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Gönder', cancelButtonText: 'İptal' }).then(r => { if (r.isConfirmed) doSend(); });
    } else { doSend(); }
};

const doSend = () => {
    form.personel_ids = selectedIds.value;
    isProcessing.value = true; progress.value = 0;
    const iv = setInterval(() => { progress.value += 3; if (progress.value >= 90) clearInterval(iv); }, 100);
    axios.post(route('toplu-islemler.toplu-mail.gonder'), { ...form }).then(() => { clearInterval(iv); progress.value = 100; isProcessing.value = false; Swal.fire('Başarılı!', 'E-postalar gönderildi.', 'success'); }).catch(e => Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu', 'error'));
};
</script>

<template>
<Head title="Toplu E-Posta Gönderimi" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                <h2 class="font-bold text-sm text-gray-800">Toplu E-Posta Gönderimi</h2>
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
                                <div class="text-[10px]" :class="p.email ? 'text-blue-600' : 'text-red-400'">{{ p.email || 'E-posta yok' }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- SAĞ: Mail İçeriği -->
                <div class="flex-1 p-5 overflow-y-auto">
                    <div class="mb-5">
                        <span class="text-lg font-bold text-green-700">Adım 1</span>
                        <span class="ml-3 text-sm text-gray-600">E-posta göndermek istediğiniz personelleri seçin</span>
                        <div class="mt-2 flex gap-2">
                            <div class="bg-blue-50 border border-blue-200 rounded px-3 py-2 text-xs text-blue-700 font-semibold">{{ selectedIds.length }} personel seçildi</div>
                            <div v-if="emailsizSayisi > 0" class="bg-red-50 border border-red-200 rounded px-3 py-2 text-xs text-red-600 font-semibold">⚠ {{ emailsizSayisi }} kişinin e-postası yok</div>
                        </div>
                    </div>

                    <div class="mb-5 border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-green-700">Adım 2</span>
                        <span class="ml-3 text-sm text-gray-600">E-posta içeriğini oluşturun</span>

                        <div class="mt-3 flex gap-1 flex-wrap">
                            <button v-for="(s, i) in konuSablonlari" :key="i" @click="sablonSec(s)" class="bg-gray-100 border border-gray-300 rounded px-2 py-1 text-[10px] hover:bg-blue-50 hover:border-blue-300">
                                {{ s.konu }}
                            </button>
                        </div>

                        <div class="mt-3 max-w-lg space-y-3">
                            <div>
                                <label class="text-xs font-semibold">Konu:</label>
                                <input v-model="form.konu" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs mt-1" placeholder="E-posta konusu" />
                            </div>
                            <div>
                                <label class="text-xs font-semibold">İçerik:</label>
                                <textarea v-model="form.mesaj" rows="8" class="w-full border-gray-300 rounded-sm py-2 px-3 text-xs mt-1" placeholder="E-posta içeriğini yazın... {ad}, {soyad} değişkenlerini kullanabilirsiniz."></textarea>
                            </div>
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
                <button @click="submit" :disabled="isProcessing" class="flex items-center bg-blue-600 text-white border border-blue-700 rounded-sm px-4 py-1.5 text-xs hover:bg-blue-700 shadow-sm font-semibold disabled:opacity-50">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    E-Posta Gönder
                </button>
                <div v-if="isProcessing" class="flex-1 max-w-xs"><div class="bg-gray-200 rounded-full h-3"><div class="bg-blue-500 h-3 rounded-full transition-all" :style="{width: progress+'%'}"></div></div></div>
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
