<script setup>
import { ref, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({ alanlar: Array });

// props.alanlar'ı reaktif hale getiriyoruz ki ekleme silme işlemlerinde liste güncellensin
const liste = ref([...(props.alanlar || [])]);

const seciliId = ref(null);
const modalAcik = ref(false);

const bordroTipleri = [
    { value: 'normal_calisma', label: 'NORMAL ÇALIŞMA' },
    { value: 'fazla_mesai', label: 'Fazla Mesai' },
    { value: 'bilgi', label: 'Bilgi (Ücrete eklenmez)' },
    { value: 'diger_hesaplar_arti', label: 'Diğer Hesaplar (+)' },
    { value: 'diger_hesaplar_eksi', label: 'Diğer Hesaplar (-)' },
    { value: 'normal_ek_mesai', label: 'Normal EK mesailer' },
];

const tipLabel = (val) => {
    const t = bordroTipleri.find(x => x.value === val);
    return t ? t.label : val;
};

const form = reactive({
    id: null,
    kod: '',
    aciklama: '',
    gun: false,
    saat: false,
    ucret: false,
    bordro_tipi: 'normal_calisma',
});

const secSatir = (alan) => {
    seciliId.value = alan.id;
    form.id = alan.id;
    form.kod = alan.kod;
    form.aciklama = alan.aciklama;
    form.gun = !!alan.gun;
    form.saat = !!alan.saat;
    form.ucret = !!alan.ucret;
    form.bordro_tipi = alan.bordro_tipi;
};

const yeniAlan = () => {
    form.id = null;
    form.gun = false;
    form.saat = false;
    form.ucret = false;
    form.bordro_tipi = 'normal_calisma';
    const maxKod = liste.value.reduce((m, a) => Math.max(m, a.kod), 0);
    form.kod = maxKod + 1;
    seciliId.value = null;
    modalAcik.value = true;
};

const kaydet = () => {
    if (form.id) {
        // Düzenleme (PUT) - form.put yerine axios.put kullanılmalı (form inertijs formu değil)
        axios.put(route('tanim.bordro-alanlari.update', form.id), { ...form })
            .then((res) => {
                // Listede güncelle
                const index = liste.value.findIndex(a => a.id === form.id);
                if (index !== -1) {
                    liste.value[index] = res.data.item || { ...form };
                }
                Swal.fire({toast:true, position:'top-end', icon:'success', title:'Güncellendi', showConfirmButton:false, timer:1500});
                router.reload(); // Değişikliklerin diğer yerlere yansıması için
            })
            .catch(e => Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu', 'error'));
    } else {
        // Yeni Kayıt (POST)
        axios.post(route('tanim.bordro-alanlari.store'), { ...form })
            .then((res) => {
                modalAcik.value = false;
                // Listeye ekle
                if (res.data.item) {
                    liste.value.push(res.data.item);
                } else {
                    liste.value.push({ ...form, id: res.data.id || Date.now() });
                }
                Swal.fire({toast:true, position:'top-end', icon:'success', title:'Eklendi', showConfirmButton:false, timer:1500});
                router.reload();
            })
            .catch(e => Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu', 'error'));
    }
};

const sil = (id) => {
    Swal.fire({title: 'Emin misiniz?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Evet, Sil'}).then((res) => {
        if(res.isConfirmed) {
            axios.delete(route('tanim.bordro-alanlari.destroy', id))
                .then(() => {
                    // Listeden çıkar
                    liste.value = liste.value.filter(a => a.id !== id);
                    seciliId.value = null;
                    Swal.fire({toast:true, position:'top-end', icon:'success', title:'Silindi', showConfirmButton:false, timer:1500});
                    router.reload();
                })
                .catch(e => Swal.fire('Hata', e.response?.data?.message || 'Silinemedi', 'error'));
        }
    });
};
</script>

<template>
<Head title="Bordro Alanları" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full overflow-hidden">

            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <h2 class="font-bold text-xs text-gray-800">Bordro Alanları</h2>
            </div>

            <div class="flex flex-1 overflow-hidden">
                <!-- Tablo -->
                <div class="flex-1 flex flex-col">
                    <div class="flex-1 overflow-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-yellow-50 text-gray-700 text-xs uppercase sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 border-b border-yellow-200 w-16">Kod</th>
                                    <th class="px-3 py-2 border-b border-yellow-200">Açıklama</th>
                                    <th class="px-3 py-2 border-b border-yellow-200 text-center w-12">Gün</th>
                                    <th class="px-3 py-2 border-b border-yellow-200 text-center w-12">Saat</th>
                                    <th class="px-3 py-2 border-b border-yellow-200 text-center w-12">Ücret</th>
                                    <th class="px-3 py-2 border-b border-yellow-200">Bordro Tipi</th>
                                    <th class="px-3 py-2 border-b border-yellow-200 text-right w-16">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="a in liste" :key="a.id"
                                    @click="secSatir(a)"
                                    class="border-b border-gray-100 cursor-pointer transition"
                                    :class="seciliId === a.id ? 'bg-yellow-100 font-semibold' : 'hover:bg-gray-50'">
                                    <td class="px-3 py-1.5 font-bold text-gray-600">{{ a.kod }}</td>
                                    <td class="px-3 py-1.5">{{ a.aciklama }}</td>
                                    <td class="px-3 py-1.5 text-center"><input type="checkbox" :checked="a.gun" disabled class="rounded text-blue-600 pointer-events-none" /></td>
                                    <td class="px-3 py-1.5 text-center"><input type="checkbox" :checked="a.saat" disabled class="rounded text-blue-600 pointer-events-none" /></td>
                                    <td class="px-3 py-1.5 text-center"><input type="checkbox" :checked="a.ucret" disabled class="rounded text-blue-600 pointer-events-none" /></td>
                                    <td class="px-3 py-1.5 text-xs text-gray-600">{{ tipLabel(a.bordro_tipi) }}</td>
                                    <td class="px-3 py-1.5 text-right">
                                        <button @click.stop="sil(a.id)" class="text-red-500 hover:text-red-700 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                    </td>
                                </tr>
                                <tr v-if="!liste.length"><td colspan="7" class="px-3 py-6 text-center text-gray-400">Kayıt bulunamadı.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Alt düzenleme paneli -->
                    <div v-if="seciliId" class="border-t border-gray-300 bg-gray-50 px-4 py-3">
                        <div class="flex items-end gap-4 flex-wrap">
                            <div class="w-20"><label class="block text-xs font-bold mb-1">Kod:</label><input v-model.number="form.kod" type="number" class="w-full text-xs rounded border-gray-300 bg-yellow-50 font-bold"></div>
                            <div class="flex-1 min-w-[200px]"><label class="block text-xs font-bold mb-1">Açıklama:</label><input v-model="form.aciklama" type="text" class="w-full text-xs rounded border-gray-300"></div>
                            <div class="w-48"><label class="block text-xs font-bold mb-1">Bordro Tipi:</label>
                                <select v-model="form.bordro_tipi" class="w-full text-xs rounded border-gray-300"><option v-for="t in bordroTipleri" :key="t.value" :value="t.value">{{ t.label }}</option></select>
                            </div>
                            <div class="flex items-center gap-4 pb-0.5">
                                <label class="flex items-center gap-1"><input v-model="form.gun" type="checkbox" class="rounded text-blue-600"><span class="text-xs font-bold">Gün</span></label>
                                <label class="flex items-center gap-1"><input v-model="form.saat" type="checkbox" class="rounded text-blue-600"><span class="text-xs font-bold">Saat</span></label>
                                <label class="flex items-center gap-1"><input v-model="form.ucret" type="checkbox" class="rounded text-blue-600"><span class="text-xs font-bold">Ücret</span></label>
                            </div>
                            <button @click="kaydet" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs shadow transition">Güncelle</button>
                        </div>
                    </div>

                    <!-- Alt butonlar -->
                    <div class="border-t border-gray-300 bg-gray-100 px-3 py-2 flex gap-1">
                        <div class="flex-1"></div>
                        <button @click="yeniAlan" class="bg-green-500 hover:bg-green-600 text-white rounded p-1.5" title="Yeni"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg></button>
                        <button @click="seciliId && sil(seciliId)" :disabled="!seciliId" class="bg-red-500 hover:bg-red-600 text-white rounded p-1.5 disabled:opacity-40" title="Sil"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Yeni Alan Modal -->
    <div v-if="modalAcik" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded shadow-xl w-[450px] border border-gray-300 overflow-hidden">
            <div class="bg-gray-100 px-4 py-3 border-b border-gray-300 flex justify-between items-center"><h3 class="font-bold">Yeni Bordro Alanı</h3><button @click="modalAcik=false" class="text-gray-500 text-xl">&times;</button></div>
            <div class="p-4 space-y-3">
                <div class="flex gap-3">
                    <div class="w-20"><label class="block text-xs font-bold mb-1">Kod *</label><input v-model.number="form.kod" type="number" class="w-full text-xs rounded border-gray-300"></div>
                    <div class="flex-1"><label class="block text-xs font-bold mb-1">Açıklama *</label><input v-model="form.aciklama" type="text" class="w-full text-xs rounded border-gray-300" placeholder="NORMAL ÇALIŞMA"></div>
                </div>
                <div><label class="block text-xs font-bold mb-1">Bordro Tipi</label><select v-model="form.bordro_tipi" class="w-full text-xs rounded border-gray-300"><option v-for="t in bordroTipleri" :key="t.value" :value="t.value">{{ t.label }}</option></select></div>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-1.5"><input v-model="form.gun" type="checkbox" class="rounded text-blue-600"><span class="text-xs font-medium">Gün</span></label>
                    <label class="flex items-center gap-1.5"><input v-model="form.saat" type="checkbox" class="rounded text-blue-600"><span class="text-xs font-medium">Saat</span></label>
                    <label class="flex items-center gap-1.5"><input v-model="form.ucret" type="checkbox" class="rounded text-blue-600"><span class="text-xs font-medium">Ücret</span></label>
                </div>
            </div>
            <div class="bg-gray-50 p-3 border-t border-gray-200 flex justify-end gap-2">
                <button @click="modalAcik=false" class="px-3 py-1.5 border bg-white rounded text-xs hover:bg-gray-100">İptal</button>
                <button @click="kaydet" class="px-3 py-1.5 bg-yellow-600 text-white rounded text-xs hover:bg-yellow-700">Kaydet</button>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
