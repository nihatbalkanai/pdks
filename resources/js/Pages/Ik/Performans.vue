<script setup>
import { ref, reactive, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ degerlendirmeler: Array, personeller: Array });
const isModalOpen = ref(false);

const form = reactive({
    personel_id: '', donem: '', donem_tipi: 'ceyrek',
    is_kalitesi: 5, verimlilik: 5, iletisim: 5, sorumluluk: 5,
    takim_calismasi: 5, liderlik: 5, yaraticilik: 5, devam_durum: 5,
    guclu_yonler: '', gelistirilecek_yonler: '', hedefler: '', notlar: ''
});

const kriterler = [
    { key: 'is_kalitesi', label: 'İş Kalitesi', icon: '⭐' },
    { key: 'verimlilik', label: 'Verimlilik', icon: '📈' },
    { key: 'iletisim', label: 'İletişim', icon: '💬' },
    { key: 'sorumluluk', label: 'Sorumluluk', icon: '🎯' },
    { key: 'takim_calismasi', label: 'Takım Çalışması', icon: '🤝' },
    { key: 'liderlik', label: 'Liderlik', icon: '👑' },
    { key: 'yaraticilik', label: 'Yaratıcılık', icon: '💡' },
    { key: 'devam_durum', label: 'Devam/Disiplin', icon: '📅' },
];

const genelPuan = computed(() => {
    return ((form.is_kalitesi + form.verimlilik + form.iletisim + form.sorumluluk + form.takim_calismasi + form.liderlik + form.yaraticilik + form.devam_durum) / 8).toFixed(1);
});

const puanRenk = (puan) => {
    if (puan >= 8) return 'text-green-600';
    if (puan >= 6) return 'text-blue-600';
    if (puan >= 4) return 'text-amber-600';
    return 'text-red-600';
};

const puanLabel = (puan) => {
    if (puan >= 9) return 'Mükemmel';
    if (puan >= 7) return 'İyi';
    if (puan >= 5) return 'Ortalama';
    if (puan >= 3) return 'Geliştirilmeli';
    return 'Yetersiz';
};

const toTitleCase = (str) => str ? str.toLocaleLowerCase('tr-TR').replace(/(^|\s)(\S)/g, (m, s, c) => s + c.toLocaleUpperCase('tr-TR')) : '';

const submit = () => {
    if (!form.personel_id || !form.donem) { Swal.fire('Uyarı', 'Personel ve dönem zorunludur.', 'warning'); return; }
    router.post(route('ik.performans.kaydet'), { ...form }, {
        onSuccess: () => { isModalOpen.value = false; Swal.fire('Başarılı', 'Performans değerlendirmesi kaydedildi.', 'success'); }
    });
};
</script>

<template>
<Head title="Performans Değerlendirme" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#fef3c7] to-[#fde68a] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
                <h2 class="font-bold text-sm text-gray-800">📊 Performans Değerlendirme</h2>
                <button @click="isModalOpen = true" class="px-3 py-1 bg-amber-600 text-white rounded text-xs font-semibold hover:bg-amber-700">+ Yeni Değerlendirme</button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="d in degerlendirmeler" :key="d.id" class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-bold text-sm text-gray-800">{{ d.personel_adi }}</h4>
                            <span class="text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-600">{{ d.donem }}</span>
                        </div>
                        <div class="text-center mb-3">
                            <span :class="puanRenk(d.genel_puan)" class="text-3xl font-black">{{ Number(d.genel_puan).toFixed(1) }}</span>
                            <span class="text-xs text-gray-500 block">/ 10 — {{ puanLabel(d.genel_puan) }}</span>
                        </div>
                        <div class="space-y-1">
                            <div v-for="k in kriterler" :key="k.key" class="flex items-center justify-between text-[10px]">
                                <span class="text-gray-500">{{ k.icon }} {{ k.label }}</span>
                                <div class="flex items-center gap-1">
                                    <div class="w-20 h-1.5 bg-gray-200 rounded-full"><div class="h-1.5 rounded-full transition-all" :class="d[k.key] >= 7 ? 'bg-green-500' : d[k.key] >= 4 ? 'bg-amber-500' : 'bg-red-500'" :style="{width: (d[k.key]*10)+'%'}"></div></div>
                                    <span class="w-4 text-right font-bold">{{ d[k.key] }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="d.guclu_yonler" class="mt-3 text-[10px] text-gray-500"><strong class="text-green-600">Güçlü:</strong> {{ d.guclu_yonler }}</div>
                    </div>
                    <div v-if="!degerlendirmeler.length" class="col-span-full py-12 text-center text-gray-400">Henüz değerlendirme yapılmamış</div>
                </div>
            </div>
        </div>
    </div>

    <Modal :show="isModalOpen" @close="isModalOpen = false" maxWidth="2xl">
        <div class="p-6 max-h-[80vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 mb-4">📊 Yeni Performans Değerlendirme</h3>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Personel *</label>
                        <select v-model="form.personel_id" required class="w-full border-gray-300 rounded text-sm">
                            <option value="">Seçiniz</option>
                            <option v-for="p in personeller" :key="p.id" :value="p.id">{{ toTitleCase(p.ad + ' ' + p.soyad) }}</option>
                        </select>
                    </div>
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Dönem *</label><input v-model="form.donem" type="text" placeholder="2026-Q1" required class="w-full border-gray-300 rounded text-sm" /></div>
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Dönem Tipi</label>
                        <select v-model="form.donem_tipi" class="w-full border-gray-300 rounded text-sm">
                            <option value="aylik">Aylık</option><option value="ceyrek">Çeyreklik</option><option value="yillik">Yıllık</option>
                        </select>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-bold text-sm">Değerlendirme Kriterleri</h4>
                        <div><span :class="puanRenk(genelPuan)" class="text-xl font-black">{{ genelPuan }}</span><span class="text-xs text-gray-500"> / 10</span></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div v-for="k in kriterler" :key="k.key" class="flex items-center gap-2">
                            <span class="text-sm w-6">{{ k.icon }}</span>
                            <label class="text-xs font-semibold text-gray-700 w-24">{{ k.label }}</label>
                            <input type="range" v-model.number="form[k.key]" min="1" max="10" class="flex-1 h-2 accent-amber-500" />
                            <span class="w-6 text-right text-sm font-bold" :class="puanRenk(form[k.key])">{{ form[k.key] }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">💪 Güçlü Yönler</label><textarea v-model="form.guclu_yonler" rows="2" class="w-full border-gray-300 rounded text-sm"></textarea></div>
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">🔧 Geliştirilecek Yönler</label><textarea v-model="form.gelistirilecek_yonler" rows="2" class="w-full border-gray-300 rounded text-sm"></textarea></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">🎯 Hedefler</label><textarea v-model="form.hedefler" rows="2" class="w-full border-gray-300 rounded text-sm"></textarea></div>
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">📝 Notlar</label><textarea v-model="form.notlar" rows="2" class="w-full border-gray-300 rounded text-sm"></textarea></div>
                </div>
                <div class="flex justify-end gap-2"><button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-gray-200 rounded text-sm">İptal</button><button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded text-sm font-semibold hover:bg-amber-700">Kaydet</button></div>
            </form>
        </div>
    </Modal>
</AuthenticatedLayout>
</template>
