<script setup>
import { ref, reactive, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ talepler: Array, personeller: Array });
const isModalOpen = ref(false);
const filter = ref('tumu');

const form = reactive({ personel_id: '', izin_turu: '', baslangic_tarihi: '', bitis_tarihi: '', aciklama: '' });

const izinTurleri = [
    { value: 'yillik', label: 'Yıllık İzin' }, { value: 'hastalik', label: 'Hastalık İzni' },
    { value: 'ucretsiz', label: 'Ücretsiz İzin' }, { value: 'mazeret', label: 'Mazeret İzni' },
    { value: 'dogum', label: 'Doğum İzni' }, { value: 'evlilik', label: 'Evlilik İzni' },
    { value: 'olum', label: 'Ölüm İzni' }, { value: 'diger', label: 'Diğer' },
];

const durumRenk = { beklemede: 'bg-amber-100 text-amber-700', onaylandi: 'bg-green-100 text-green-700', reddedildi: 'bg-red-100 text-red-700', iptal: 'bg-gray-100 text-gray-500' };
const durumLabel = { beklemede: '⏳ Beklemede', onaylandi: '✅ Onaylandı', reddedildi: '❌ Reddedildi', iptal: '🚫 İptal' };

const filteredTalepler = computed(() => {
    if (filter.value === 'tumu') return props.talepler;
    return props.talepler.filter(t => t.durum === filter.value);
});

const toTitleCase = (str) => str ? str.toLocaleLowerCase('tr-TR').replace(/(^|\s)(\S)/g, (m, s, c) => s + c.toLocaleUpperCase('tr-TR')) : '';

const submit = () => {
    if (!form.personel_id || !form.izin_turu || !form.baslangic_tarihi || !form.bitis_tarihi) {
        Swal.fire('Uyarı', 'Lütfen tüm zorunlu alanları doldurun.', 'warning'); return;
    }
    router.post(route('ik.izin-talepleri.olustur'), { ...form }, {
        onSuccess: () => { isModalOpen.value = false; Swal.fire('Başarılı', 'İzin talebi oluşturuldu.', 'success'); }
    });
};

const islem = async (id, durum) => {
    let redNedeni = null;
    if (durum === 'reddedildi') {
        const { value } = await Swal.fire({ title: 'Red Nedeni', input: 'textarea', inputPlaceholder: 'Red nedenini yazın...', showCancelButton: true });
        if (!value) return;
        redNedeni = value;
    }
    await axios.put(route('ik.izin-talepleri.islem', id), { durum, red_nedeni: redNedeni });
    Swal.fire('Başarılı', 'İzin talebi güncellendi.', 'success').then(() => router.reload());
};
</script>

<template>
<Head title="İzin Talepleri" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#ccfbf1] to-[#99f6e4] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
                <h2 class="font-bold text-sm text-gray-800">📅 İzin Talep Yönetimi</h2>
                <button @click="isModalOpen = true" class="px-3 py-1 bg-teal-600 text-white rounded text-xs font-semibold hover:bg-teal-700">+ Yeni Talep</button>
            </div>
            <div class="flex gap-2 px-4 py-2 border-b border-gray-200">
                <button v-for="f in [['tumu','Tümü'],['beklemede','Beklemede'],['onaylandi','Onaylı'],['reddedildi','Red']]" :key="f[0]" @click="filter = f[0]" :class="filter === f[0] ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-600'" class="px-3 py-1 rounded text-xs font-semibold">{{ f[1] }}</button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase font-semibold sticky top-0">
                        <tr><th class="py-2 px-3 text-left">Personel</th><th class="py-2 px-3 text-left">İzin Türü</th><th class="py-2 px-3 text-center">Tarih</th><th class="py-2 px-3 text-center">Gün</th><th class="py-2 px-3 text-center">Durum</th><th class="py-2 px-3 text-left">Açıklama</th><th class="py-2 px-3 text-right">İşlem</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in filteredTalepler" :key="t.id" class="border-t border-gray-100 hover:bg-teal-50 transition">
                            <td class="py-2 px-3 font-bold text-gray-800">{{ t.personel_adi }}</td>
                            <td class="py-2 px-3">{{ izinTurleri.find(i => i.value === t.izin_turu)?.label || t.izin_turu }}</td>
                            <td class="py-2 px-3 text-center text-gray-600">{{ t.baslangic_tarihi }} → {{ t.bitis_tarihi }}</td>
                            <td class="py-2 px-3 text-center font-bold">{{ t.gun_sayisi }}</td>
                            <td class="py-2 px-3 text-center"><span :class="durumRenk[t.durum]" class="px-2 py-0.5 rounded-full text-[10px] font-bold">{{ durumLabel[t.durum] }}</span></td>
                            <td class="py-2 px-3 text-gray-500 truncate max-w-[150px]">{{ t.aciklama || '—' }}</td>
                            <td class="py-2 px-3 text-right" v-if="t.durum === 'beklemede'">
                                <button @click="islem(t.id, 'onaylandi')" class="text-green-600 hover:text-green-800 font-bold mr-2">Onayla</button>
                                <button @click="islem(t.id, 'reddedildi')" class="text-red-500 hover:text-red-700 font-bold">Reddet</button>
                            </td>
                            <td v-else class="py-2 px-3 text-right text-gray-400 text-[10px]">{{ t.onaylayan_adi || '' }}</td>
                        </tr>
                        <tr v-if="!filteredTalepler.length"><td colspan="7" class="py-8 text-center text-gray-400">Kayıt bulunamadı</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <Modal :show="isModalOpen" @close="isModalOpen = false">
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">📅 Yeni İzin Talebi</h3>
            <form @submit.prevent="submit" class="space-y-4">
                <div><label class="block text-xs font-semibold text-gray-700 mb-1">Personel *</label>
                    <select v-model="form.personel_id" required class="w-full border-gray-300 rounded text-sm">
                        <option value="">Seçiniz</option>
                        <option v-for="p in personeller" :key="p.id" :value="p.id">{{ toTitleCase(p.ad + ' ' + p.soyad) }}</option>
                    </select>
                </div>
                <div><label class="block text-xs font-semibold text-gray-700 mb-1">İzin Türü *</label>
                    <select v-model="form.izin_turu" required class="w-full border-gray-300 rounded text-sm">
                        <option value="">Seçiniz</option>
                        <option v-for="t in izinTurleri" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Başlangıç *</label><input v-model="form.baslangic_tarihi" type="date" required class="w-full border-gray-300 rounded text-sm" /></div>
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Bitiş *</label><input v-model="form.bitis_tarihi" type="date" required class="w-full border-gray-300 rounded text-sm" /></div>
                </div>
                <div><label class="block text-xs font-semibold text-gray-700 mb-1">Açıklama</label><textarea v-model="form.aciklama" rows="2" class="w-full border-gray-300 rounded text-sm" placeholder="İsteğe bağlı açıklama..."></textarea></div>
                <div class="flex justify-end gap-2"><button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-gray-200 rounded text-sm">İptal</button><button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded text-sm font-semibold hover:bg-teal-700">Oluştur</button></div>
            </form>
        </div>
    </Modal>
</AuthenticatedLayout>
</template>
