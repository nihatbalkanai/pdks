<script setup>
import { ref, reactive, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    kazanclar: Array,
    bordroAlanlari: Array,
    personeller: Array,
    filtreler: Object,
});

const liste = ref([...(props.kazanclar || [])]);
const selectedRow = ref(null);
const showAddForm = ref(false);
const editingId = ref(null);

const today = new Date().toISOString().split('T')[0];
const form = reactive({ personel_id: '', tarih: today, tutar: '', aciklama: '', bordro_alani: '', taksitli: false, taksit_sayisi: 2 });

if (props.bordroAlanlari?.length > 0) {
    form.bordro_alani = props.bordroAlanlari[0].aciklama;
}

const taksitTutar = computed(() => {
    if (!form.taksitli || !form.tutar || !form.taksit_sayisi) return 0;
    return (parseFloat(form.tutar) / parseInt(form.taksit_sayisi)).toFixed(2);
});

const filtreBordro = ref(props.filtreler?.bordro_alani || '');
const filtreTarih = ref(props.filtreler?.tarih || '');

const applyFilter = () => {
    router.get(route('ek-kazanclar.index'), {
        bordro_alani: filtreBordro.value || undefined,
        tarih: filtreTarih.value || undefined,
    }, { preserveState: true });
};

const formatTarih = (t) => { if (!t) return ''; return new Date(t).toLocaleDateString('tr-TR'); };
const formatTutar = (t) => { if (!t) return '0,00'; return Number(t).toLocaleString('tr-TR', { minimumFractionDigits: 2 }); };

const openEdit = () => {
    if (!selectedRow.value) return;
    const row = liste.value.find(x => x.id === selectedRow.value);
    if (row) {
        form.personel_id = row.personel_id;
        form.tarih = row.tarih;
        form.tutar = row.tutar;
        form.aciklama = row.aciklama;
        form.bordro_alani = row.bordro_alani;
        form.taksitli = false;
        editingId.value = selectedRow.value;
        showAddForm.value = true;
    }
};

const openNew = () => {
    editingId.value = null;
    form.personel_id = '';
    form.tarih = today;
    form.tutar = '';
    form.aciklama = '';
    form.taksitli = false;
    form.taksit_sayisi = 2;
    if (props.bordroAlanlari?.length > 0) form.bordro_alani = props.bordroAlanlari[0].aciklama;
    showAddForm.value = true;
};

watch(selectedRow, (id) => {
    if (id) {
        const row = liste.value.find(x => x.id === id);
        if (row) form.personel_id = row.personel_id;
    }
});

const submitForm = async () => {
    try {
        let res;
        if (editingId.value) {
            res = await axios.put(route('ek-kazanclar.update', editingId.value), { ...form });
            const index = liste.value.findIndex(x => x.id === editingId.value);
            if (index !== -1) liste.value[index] = res.data.item;
            Swal.fire({toast:true, position:'top-end', icon:'success', title:'Güncellendi', showConfirmButton:false, timer:1200});
            showAddForm.value = false;
            editingId.value = null;
            return;
        } else {
            res = await axios.post(route('ek-kazanclar.store'), { ...form });
        }
        if (res.data.taksitli && res.data.items) {
            res.data.items.forEach(item => liste.value.unshift(item));
            Swal.fire({toast:true, position:'top-end', icon:'success', title:`${res.data.items.length} taksit oluşturuldu`, showConfirmButton:false, timer:2000});
        } else {
            liste.value.unshift(res.data.item);
            Swal.fire({toast:true, position:'top-end', icon:'success', title:'Eklendi', showConfirmButton:false, timer:1200});
        }
        showAddForm.value = false;
        form.tarih = today; form.tutar = ''; form.aciklama = ''; form.taksitli = false; form.taksit_sayisi = 2;
    } catch(e) {
        let errorMsg = e.response?.data?.message || 'Kayıt eklenirken hata oluştu.';
        if (e.response?.status === 422 && e.response?.data?.errors) {
            errorMsg = Object.values(e.response.data.errors).flat().join('<br>');
        }
        Swal.fire({ title: 'Kayıt Hatası', html: errorMsg, icon: 'error' });
    }
};

const deleteRecord = () => {
    if (!selectedRow.value) return;
    const row = liste.value.find(x => x.id === selectedRow.value);
    Swal.fire({ title: 'Emin misiniz?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonText: 'İptal', confirmButtonText: row?.taksit_grup_id ? 'Sadece Bu Taksiti Sil' : 'Sil',
        ...(row?.taksit_grup_id ? { showDenyButton: true, denyButtonText: 'Tüm Taksitleri Sil', denyButtonColor: '#6b21a8' } : {})
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(route('ek-kazanclar.destroy', selectedRow.value));
                liste.value = liste.value.filter(x => x.id !== selectedRow.value);
                selectedRow.value = null;
                Swal.fire({toast:true, position:'top-end', icon:'success', title:'Silindi', showConfirmButton:false, timer:1200});
            } catch(e) {
                Swal.fire({ title: 'Hata', text: e.response?.data?.message || 'Silme hatası', icon: 'error' });
            }
        } else if (result.isDenied && row?.taksit_grup_id) {
            try {
                const res = await axios.delete(route('ek-kazanclar.destroy-grup', row.taksit_grup_id));
                liste.value = liste.value.filter(x => x.taksit_grup_id !== row.taksit_grup_id);
                selectedRow.value = null;
                Swal.fire({toast:true, position:'top-end', icon:'success', title:`${res.data.silinen_adet} taksit silindi`, showConfirmButton:false, timer:1500});
            } catch(e) {
                Swal.fire({ title: 'Hata', text: e.response?.data?.message || 'Silme hatası', icon: 'error' });
            }
        }
    });
};
</script>

<template>
    <Head title="Ek Kazançlar" />
    <AuthenticatedLayout>
        <div class="p-4 h-full flex flex-col">
            <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
                <div class="bg-gradient-to-r from-[#d8f0d8] to-[#b8d8b8] border-b border-gray-400 px-4 py-2">
                    <h2 class="font-bold text-sm text-gray-800">Ek Kazançlar</h2>
                </div>

                <!-- Filtre -->
                <div v-show="!showAddForm" class="flex flex-wrap items-center gap-4 px-4 py-2 bg-gray-50 border-b border-gray-300 text-xs transition-all duration-300">
                    <div class="flex items-center">
                        <label class="mr-2 font-semibold">Hazır bordro alanı:</label>
                        <select v-model="filtreBordro" @change="applyFilter" class="border-gray-300 rounded-sm py-1 px-2 text-xs w-40">
                            <option value="">Tümü</option>
                            <option v-for="b in bordroAlanlari" :key="b.id" :value="b.aciklama">{{ b.aciklama }}</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <label class="mr-2 font-semibold">Tarih:</label>
                        <input type="date" v-model="filtreTarih" @change="applyFilter" class="border-gray-300 rounded-sm py-1 px-2 text-xs w-36" />
                    </div>
                    <div class="ml-auto">
                        <button @click="showAddForm = !showAddForm" class="bg-[#e8f8e8] border border-gray-400 rounded-sm px-3 py-1 text-xs hover:bg-[#d0e8d0] flex items-center">
                            <svg class="w-4 h-4 mr-1 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-2-4a2 2 0 114 0 2 2 0 01-4 0zm-2-8a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd"></path></svg>
                            Personel Seçimi
                        </button>
                    </div>
                </div>

                <!-- Yeni Kayıt / Düzenleme Formu -->
                <div v-if="showAddForm" class="px-4 py-3 bg-yellow-50 border-b border-gray-300 text-xs">
                    <div class="flex items-end gap-3 flex-wrap">
                        <div>
                            <label class="block font-semibold mb-1">Personel</label>
                            <select v-model="form.personel_id" class="border-gray-300 rounded-sm py-1 px-2 text-xs w-48">
                                <option value="">Seçiniz</option>
                                <option v-for="p in personeller" :key="p.id" :value="p.id">{{ p.kart_no }} - {{ p.ad }} {{ p.soyad }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Tarih</label>
                            <input type="date" v-model="form.tarih" class="border-gray-300 rounded-sm py-1 px-2 text-xs w-32" />
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Tutar</label>
                            <input type="number" step="0.01" v-model="form.tutar" class="border-gray-300 rounded-sm py-1 px-2 text-xs w-24 text-right" />
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Açıklama</label>
                            <input type="text" v-model="form.aciklama" class="border-gray-300 rounded-sm py-1 px-2 text-xs w-48" />
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Bordro Alanı</label>
                            <select v-model="form.bordro_alani" class="border-gray-300 rounded-sm py-1 px-2 text-xs w-36">
                                <option v-for="b in bordroAlanlari" :key="b.id" :value="b.aciklama">{{ b.aciklama }}</option>
                            </select>
                        </div>
                        <!-- Taksitli checkbox aynı satırda -->
                        <label v-if="!editingId" class="flex items-center gap-1.5 cursor-pointer self-end pb-0.5">
                            <input type="checkbox" v-model="form.taksitli" class="rounded text-green-600 w-3.5 h-3.5" />
                            <span class="font-semibold">Taksitli</span>
                        </label>
                        <template v-if="form.taksitli && !editingId">
                            <div class="flex items-center gap-1 self-end pb-0.5">
                                <label class="font-semibold">Taksit:</label>
                                <input type="number" min="2" max="60" v-model.number="form.taksit_sayisi" class="border-gray-300 rounded-sm py-0.5 px-2 text-xs w-14 text-center" />
                            </div>
                            <div v-if="taksitTutar > 0" class="bg-green-100 border border-green-300 rounded px-2 py-1 text-green-800 font-bold text-[11px] self-end">
                                Aylık: {{ Number(taksitTutar).toLocaleString('tr-TR', {minimumFractionDigits:2}) }} ₺ × {{ form.taksit_sayisi }} ay
                            </div>
                        </template>
                        <button @click="submitForm" class="bg-green-600 text-white px-4 py-1 rounded-sm hover:bg-green-700 font-bold self-end">{{ editingId ? 'Güncelle' : 'Ekle' }}</button>
                        <button @click="showAddForm = false; editingId = null;" class="bg-gray-400 text-white px-3 py-1 rounded-sm hover:bg-gray-500 self-end">İptal</button>
                    </div>
                </div>

                <!-- Tablo -->
                <div class="flex-1 overflow-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead class="bg-[#d0dcea] sticky top-0 z-10">
                            <tr>
                                <th class="py-1.5 px-2 text-left border border-gray-400 font-bold">KartNo</th>
                                <th class="py-1.5 px-2 text-left border border-gray-400 font-bold">İsim</th>
                                <th class="py-1.5 px-2 text-left border border-gray-400 font-bold">Soyadı</th>
                                <th class="py-1.5 px-2 text-right border border-gray-400 font-bold">Maaş</th>
                                <th class="py-1.5 px-2 text-left border border-gray-400 font-bold">Tarih</th>
                                <th class="py-1.5 px-2 text-right border border-gray-400 font-bold">Tutar</th>
                                <th class="py-1.5 px-2 text-left border border-gray-400 font-bold">Açıklama</th>
                                <th class="py-1.5 px-2 text-center border border-gray-400 font-bold w-20">Taksit</th>
                                <th class="py-1.5 px-2 text-left border border-gray-400 font-bold">Bordro Alanı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="k in liste" :key="k.id"
                                @click="selectedRow = k.id"
                                class="cursor-pointer border-b border-gray-200 hover:bg-blue-50 transition-colors"
                                :class="{'!bg-yellow-200 font-semibold': selectedRow === k.id, '!bg-green-50/70': k.taksit_no && selectedRow !== k.id}">
                                <td class="py-1 px-2 border-r border-gray-200">{{ k.kart_no }}</td>
                                <td class="py-1 px-2 border-r border-gray-200">{{ k.isim }}</td>
                                <td class="py-1 px-2 border-r border-gray-200">{{ k.soyad }}</td>
                                <td class="py-1 px-2 border-r border-gray-200 text-right">{{ formatTutar(k.maas) }}</td>
                                <td class="py-1 px-2 border-r border-gray-200">{{ formatTarih(k.tarih) }}</td>
                                <td class="py-1 px-2 border-r border-gray-200 text-right">{{ formatTutar(k.tutar) }}</td>
                                <td class="py-1 px-2 border-r border-gray-200">{{ k.aciklama }}</td>
                                <td class="py-1 px-2 border-r border-gray-200 text-center">
                                    <span v-if="k.taksit_no" class="inline-flex items-center justify-center bg-green-100 text-green-700 border border-green-300 rounded px-1.5 py-0.5 text-[10px] font-bold" title="Taksitli İşlem">
                                        {{ k.taksit_no }}/{{ k.toplam_taksit }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="py-1 px-2">{{ k.bordro_alani }}</td>
                            </tr>
                            <tr v-if="liste.length === 0">
                                <td colspan="9" class="py-8 text-center text-gray-400">Gösterilecek kayıt yok</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Alt Butonlar -->
                <div class="flex items-center justify-end gap-1 px-4 py-2 bg-gray-100 border-t border-gray-400">
                    <button @click="openNew" class="win-btn" title="Yeni Ekle"><svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg></button>
                    <button @click="openEdit" :disabled="!selectedRow" class="win-btn" title="Düzenle"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                    <button class="win-btn" title="Kaydet"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg></button>
                    <button class="win-btn" title="Yazdır"><svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg></button>
                    <button class="win-btn" title="Onayla"><svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></button>
                    <button @click="deleteRecord" class="win-btn" title="Sil"><svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                    <button class="win-btn" title="Excel"><svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg></button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.win-btn {
    @apply w-8 h-8 flex items-center justify-center bg-white border border-gray-400 rounded-sm hover:bg-gray-100 shadow-sm cursor-pointer transition;
}
</style>
