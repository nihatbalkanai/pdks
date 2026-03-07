<script setup>
import { ref, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ kayitlar: Array, personeller: Array });
const isModalOpen = ref(false);

const form = reactive({ personel_id: '', tur: 'sozlu_uyari', olay_tarihi: '', olay_aciklamasi: '', alinan_onlem: '', personel_bilgilendirildi: false, bilgilendirme_tarihi: '', notlar: '' });

const turler = [
    { value: 'sozlu_uyari', label: '🗣️ Sözlü Uyarı', renk: 'bg-amber-100 text-amber-700' },
    { value: 'yazili_uyari', label: '📝 Yazılı Uyarı', renk: 'bg-orange-100 text-orange-700' },
    { value: 'kinama', label: '⚠️ Kınama', renk: 'bg-red-100 text-red-700' },
    { value: 'ucret_kesintisi', label: '💰 Ücret Kesintisi', renk: 'bg-purple-100 text-purple-700' },
    { value: 'fesih_uyarisi', label: '🚨 Fesih Uyarısı', renk: 'bg-red-200 text-red-800' },
    { value: 'diger', label: '📋 Diğer', renk: 'bg-gray-100 text-gray-600' },
];

const getTur = (val) => turler.find(t => t.value === val) || turler[5];
const toTitleCase = (str) => str ? str.toLocaleLowerCase('tr-TR').replace(/(^|\s)(\S)/g, (m, s, c) => s + c.toLocaleUpperCase('tr-TR')) : '';

const submit = () => {
    if (!form.personel_id || !form.olay_tarihi || !form.olay_aciklamasi) { Swal.fire('Uyarı', 'Zorunlu alanları doldurun.', 'warning'); return; }
    router.post(route('ik.disiplin.kaydet'), { ...form }, {
        onSuccess: () => { isModalOpen.value = false; Swal.fire('Başarılı', 'Disiplin kaydı oluşturuldu.', 'success'); }
    });
};
</script>

<template>
<Head title="Disiplin Kayıtları" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#fee2e2] to-[#fca5a5] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
                <h2 class="font-bold text-sm text-gray-800">⚖️ Disiplin Kayıtları</h2>
                <button @click="isModalOpen = true" class="px-3 py-1 bg-red-600 text-white rounded text-xs font-semibold hover:bg-red-700">+ Yeni Kayıt</button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase font-semibold sticky top-0">
                        <tr><th class="py-2 px-3 text-left">Personel</th><th class="py-2 px-3 text-center">Tür</th><th class="py-2 px-3 text-center">Olay Tarihi</th><th class="py-2 px-3 text-left">Olay Açıklaması</th><th class="py-2 px-3 text-left">Alınan Önlem</th><th class="py-2 px-3 text-center">Bilgilendirildi</th><th class="py-2 px-3 text-left">Kaydeden</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="k in kayitlar" :key="k.id" class="border-t border-gray-100 hover:bg-red-50 transition">
                            <td class="py-2 px-3 font-bold text-gray-800">{{ k.personel_adi }}</td>
                            <td class="py-2 px-3 text-center"><span :class="getTur(k.tur).renk" class="px-2 py-0.5 rounded-full text-[10px] font-bold whitespace-nowrap">{{ getTur(k.tur).label }}</span></td>
                            <td class="py-2 px-3 text-center text-gray-600">{{ k.olay_tarihi }}</td>
                            <td class="py-2 px-3 text-gray-700 truncate max-w-[200px]" :title="k.olay_aciklamasi">{{ k.olay_aciklamasi }}</td>
                            <td class="py-2 px-3 text-gray-500 truncate max-w-[150px]">{{ k.alinan_onlem || '—' }}</td>
                            <td class="py-2 px-3 text-center"><span v-if="k.personel_bilgilendirildi" class="text-green-600 font-bold">✅ Evet</span><span v-else class="text-gray-400">Hayır</span></td>
                            <td class="py-2 px-3 text-gray-500">{{ k.kaydeden_adi }}</td>
                        </tr>
                        <tr v-if="!kayitlar.length"><td colspan="7" class="py-8 text-center text-gray-400">Kayıt bulunamadı</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <Modal :show="isModalOpen" @close="isModalOpen = false" maxWidth="xl">
        <div class="p-6 max-h-[80vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 mb-4">⚖️ Yeni Disiplin Kaydı</h3>
            <form @submit.prevent="submit" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Personel *</label>
                        <select v-model="form.personel_id" required class="w-full border-gray-300 rounded text-sm">
                            <option value="">Seçiniz</option>
                            <option v-for="p in personeller" :key="p.id" :value="p.id">{{ toTitleCase(p.ad + ' ' + p.soyad) }}</option>
                        </select>
                    </div>
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">İşlem Türü *</label>
                        <select v-model="form.tur" required class="w-full border-gray-300 rounded text-sm">
                            <option v-for="t in turler" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                </div>
                <div><label class="block text-xs font-semibold text-gray-700 mb-1">Olay Tarihi *</label><input v-model="form.olay_tarihi" type="date" required class="w-full border-gray-300 rounded text-sm" /></div>
                <div><label class="block text-xs font-semibold text-gray-700 mb-1">Olay Açıklaması *</label><textarea v-model="form.olay_aciklamasi" rows="3" required class="w-full border-gray-300 rounded text-sm"></textarea></div>
                <div><label class="block text-xs font-semibold text-gray-700 mb-1">Alınan Önlem</label><textarea v-model="form.alinan_onlem" rows="2" class="w-full border-gray-300 rounded text-sm"></textarea></div>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" v-model="form.personel_bilgilendirildi" class="rounded border-gray-300 text-red-600" /><span class="text-xs font-semibold">Personel bilgilendirildi</span></label>
                    <input v-if="form.personel_bilgilendirildi" v-model="form.bilgilendirme_tarihi" type="date" class="border-gray-300 rounded text-sm" />
                </div>
                <div><label class="block text-xs font-semibold text-gray-700 mb-1">Notlar</label><textarea v-model="form.notlar" rows="2" class="w-full border-gray-300 rounded text-sm"></textarea></div>
                <div class="flex justify-end gap-2"><button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-gray-200 rounded text-sm">İptal</button><button type="submit" class="px-4 py-2 bg-red-600 text-white rounded text-sm font-semibold hover:bg-red-700">Kaydet</button></div>
            </form>
        </div>
    </Modal>
</AuthenticatedLayout>
</template>
