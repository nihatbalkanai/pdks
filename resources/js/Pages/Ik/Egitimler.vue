<script setup>
import { ref, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ egitimler: Array, personeller: Array });
const isModalOpen = ref(false);

const form = reactive({ personel_id: '', egitim_adi: '', egitim_turu: 'ic', kurum: '', baslangic_tarihi: '', bitis_tarihi: '', sure_saat: '', sertifika_no: '', sertifika_gecerlilik: '', durum: 'planlanmis', notlar: '' });

const turLabel = { ic: '🏢 İç Eğitim', dis: '🏫 Dış Eğitim', online: '💻 Online', sertifika: '📜 Sertifika' };
const durumRenk = { planlanmis: 'bg-blue-100 text-blue-700', devam_ediyor: 'bg-amber-100 text-amber-700', tamamlandi: 'bg-green-100 text-green-700', iptal: 'bg-gray-100 text-gray-500' };
const durumLabel2 = { planlanmis: '📋 Planlandı', devam_ediyor: '🔄 Devam Ediyor', tamamlandi: '✅ Tamamlandı', iptal: '🚫 İptal' };

const toTitleCase = (str) => str ? str.toLocaleLowerCase('tr-TR').replace(/(^|\s)(\S)/g, (m, s, c) => s + c.toLocaleUpperCase('tr-TR')) : '';

const submit = () => {
    if (!form.personel_id || !form.egitim_adi || !form.baslangic_tarihi) { Swal.fire('Uyarı', 'Zorunlu alanları doldurun.', 'warning'); return; }
    router.post(route('ik.egitimler.kaydet'), { ...form }, {
        onSuccess: () => { isModalOpen.value = false; Swal.fire('Başarılı', 'Eğitim kaydı oluşturuldu.', 'success'); }
    });
};
</script>

<template>
<Head title="Eğitim Takibi" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#dbeafe] to-[#93c5fd] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
                <h2 class="font-bold text-sm text-gray-800">🎓 Eğitim Takibi</h2>
                <button @click="isModalOpen = true" class="px-3 py-1 bg-blue-600 text-white rounded text-xs font-semibold hover:bg-blue-700">+ Yeni Eğitim</button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase font-semibold sticky top-0">
                        <tr><th class="py-2 px-3 text-left">Personel</th><th class="py-2 px-3 text-left">Eğitim Adı</th><th class="py-2 px-3 text-center">Tür</th><th class="py-2 px-3 text-left">Kurum</th><th class="py-2 px-3 text-center">Tarih</th><th class="py-2 px-3 text-center">Süre</th><th class="py-2 px-3 text-center">Sertifika</th><th class="py-2 px-3 text-center">Durum</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in egitimler" :key="e.id" class="border-t border-gray-100 hover:bg-blue-50 transition">
                            <td class="py-2 px-3 font-bold text-gray-800">{{ e.personel_adi }}</td>
                            <td class="py-2 px-3 text-gray-700">{{ e.egitim_adi }}</td>
                            <td class="py-2 px-3 text-center">{{ turLabel[e.egitim_turu] || e.egitim_turu }}</td>
                            <td class="py-2 px-3 text-gray-600">{{ e.kurum || '—' }}</td>
                            <td class="py-2 px-3 text-center text-gray-600">{{ e.baslangic_tarihi }}{{ e.bitis_tarihi ? ' → ' + e.bitis_tarihi : '' }}</td>
                            <td class="py-2 px-3 text-center text-gray-600">{{ e.sure_saat ? e.sure_saat + ' saat' : '—' }}</td>
                            <td class="py-2 px-3 text-center">
                                <span v-if="e.sertifika_no" class="text-green-600 font-bold">{{ e.sertifika_no }}</span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="py-2 px-3 text-center"><span :class="durumRenk[e.durum]" class="px-2 py-0.5 rounded-full text-[10px] font-bold">{{ durumLabel2[e.durum] }}</span></td>
                        </tr>
                        <tr v-if="!egitimler.length"><td colspan="8" class="py-8 text-center text-gray-400">Kayıt bulunamadı</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <Modal :show="isModalOpen" @close="isModalOpen = false" maxWidth="xl">
        <div class="p-6 max-h-[80vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 mb-4">🎓 Yeni Eğitim Kaydı</h3>
            <form @submit.prevent="submit" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Personel *</label>
                        <select v-model="form.personel_id" required class="w-full border-gray-300 rounded text-sm">
                            <option value="">Seçiniz</option>
                            <option v-for="p in personeller" :key="p.id" :value="p.id">{{ toTitleCase(p.ad + ' ' + p.soyad) }}</option>
                        </select>
                    </div>
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Eğitim Adı *</label><input v-model="form.egitim_adi" type="text" required class="w-full border-gray-300 rounded text-sm" /></div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Tür</label>
                        <select v-model="form.egitim_turu" class="w-full border-gray-300 rounded text-sm">
                            <option value="ic">İç Eğitim</option><option value="dis">Dış Eğitim</option><option value="online">Online</option><option value="sertifika">Sertifika</option>
                        </select>
                    </div>
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Kurum</label><input v-model="form.kurum" type="text" class="w-full border-gray-300 rounded text-sm" /></div>
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Süre (Saat)</label><input v-model="form.sure_saat" type="number" class="w-full border-gray-300 rounded text-sm" /></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Başlangıç *</label><input v-model="form.baslangic_tarihi" type="date" required class="w-full border-gray-300 rounded text-sm" /></div>
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Bitiş</label><input v-model="form.bitis_tarihi" type="date" class="w-full border-gray-300 rounded text-sm" /></div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Sertifika No</label><input v-model="form.sertifika_no" type="text" class="w-full border-gray-300 rounded text-sm" /></div>
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Geçerlilik</label><input v-model="form.sertifika_gecerlilik" type="date" class="w-full border-gray-300 rounded text-sm" /></div>
                    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Durum</label>
                        <select v-model="form.durum" class="w-full border-gray-300 rounded text-sm">
                            <option value="planlanmis">Planlandı</option><option value="devam_ediyor">Devam Ediyor</option><option value="tamamlandi">Tamamlandı</option><option value="iptal">İptal</option>
                        </select>
                    </div>
                </div>
                <div><label class="block text-xs font-semibold text-gray-700 mb-1">Notlar</label><textarea v-model="form.notlar" rows="2" class="w-full border-gray-300 rounded text-sm"></textarea></div>
                <div class="flex justify-end gap-2"><button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-gray-200 rounded text-sm">İptal</button><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm font-semibold hover:bg-blue-700">Kaydet</button></div>
            </form>
        </div>
    </Modal>
</AuthenticatedLayout>
</template>
