<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({ vardiyalar: Array, gunlukPuantajParametreleri: { type: Array, default: () => [] } });

// Mola süresini günlük puantaj parametrelerinden al (ilk aktif parametrenin mola süresi)
const molaDakika = computed(() => {
    const param = props.gunlukPuantajParametreleri?.[0];
    return param ? (param.mola_suresi || 0) : 0;
});

const localVardiyalar = ref([...(props.vardiyalar || [])]);
const selectedIdx = ref(-1);
const editMode = ref(false);
const emptyForm = { ad: '', baslangic_saati: '08:00', bitis_saati: '17:00', renk: '#3B82F6' };
const form = ref({ ...emptyForm });

const selected = computed(() => selectedIdx.value >= 0 ? localVardiyalar.value[selectedIdx.value] : null);

const selectRow = (i) => {
    selectedIdx.value = i;
    editMode.value = false;
    const v = localVardiyalar.value[i];
    form.value = { ad: v.ad, baslangic_saati: v.baslangic_saati || '08:00', bitis_saati: v.bitis_saati || '17:00', renk: v.renk || '#3B82F6' };
};

const yeniKayit = () => {
    selectedIdx.value = -1;
    editMode.value = true;
    form.value = { ...emptyForm };
};

const duzenle = () => {
    if (!selected.value) { Swal.fire('Uyarı', 'Düzenlenecek vardiyayı seçin.', 'warning'); return; }
    editMode.value = true;
};

const iptal = () => {
    editMode.value = false;
    if (selected.value) {
        const v = selected.value;
        form.value = { ad: v.ad, baslangic_saati: v.baslangic_saati || '08:00', bitis_saati: v.bitis_saati || '17:00', renk: v.renk || '#3B82F6' };
    }
};

const kaydet = async () => {
    if (!form.value.ad) { Swal.fire('Uyarı', 'Vardiya adı zorunludur.', 'warning'); return; }
    try {
        if (selected.value) {
            const res = await axios.put(route('tanim.vardiyalar.update', selected.value.id), form.value);
            localVardiyalar.value[selectedIdx.value] = res.data.vardiya;
        } else {
            const res = await axios.post(route('tanim.vardiyalar.store'), form.value);
            localVardiyalar.value.push(res.data.vardiya);
            selectedIdx.value = localVardiyalar.value.length - 1;
        }
        editMode.value = false;
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Kaydedildi', showConfirmButton: false, timer: 1500 });
    } catch (e) {
        Swal.fire('Hata', 'Kayıt başarısız.', 'error');
    }
};

const sil = async () => {
    if (!selected.value) { Swal.fire('Uyarı', 'Silinecek vardiyayı seçin.', 'warning'); return; }
    const result = await Swal.fire({
        title: 'Vardiyayı Sil',
        html: `<b>${selected.value.ad}</b> silinecek. Emin misiniz?`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', confirmButtonText: 'Sil', cancelButtonText: 'İptal',
    });
    if (!result.isConfirmed) return;
    try {
        await axios.delete(route('tanim.vardiyalar.destroy', selected.value.id));
        localVardiyalar.value.splice(selectedIdx.value, 1);
        selectedIdx.value = -1;
        form.value = { ...emptyForm };
    } catch (e) {
        Swal.fire('Hata', 'Silme başarısız.', 'error');
    }
};

const formatSure = (dk) => {
    if (!dk && dk !== 0) return '-';
    dk = Math.abs(dk);
    const s = Math.floor(dk / 60);
    const m = dk % 60;
    return m > 0 ? `${s}s ${m}dk` : `${s} saat`;
};

const formatNet = (dk) => {
    if (!dk && dk !== 0) return '-';
    const net = Math.abs(dk) - molaDakika.value;
    return formatSure(Math.max(0, net));
};
</script>

<template>
<Head title="Vardiya Tanımları" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <!-- Başlık -->
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <h2 class="font-bold text-sm text-gray-800">Vardiya Tanımları</h2>
                    <span class="ml-2 bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded text-[10px] font-bold">{{ localVardiyalar.length }} kayıt</span>
                </div>
            </div>

            <!-- Tablo -->
            <div class="flex-1 overflow-y-auto bg-[#c8d8ec]">
                <table class="w-full text-xs border-collapse">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-[#f0c860]">
                            <th class="py-1.5 px-3 text-left border border-gray-400 font-bold">Vardiya Adı</th>
                            <th class="py-1.5 px-3 text-center border border-gray-400 font-bold w-24">Giriş</th>
                            <th class="py-1.5 px-3 text-center border border-gray-400 font-bold w-24">Çıkış</th>
                            <th class="py-1.5 px-3 text-center border border-gray-400 font-bold w-24">Brüt</th>
                            <th class="py-1.5 px-3 text-center border border-gray-400 font-bold w-20">Mola</th>
                            <th class="py-1.5 px-3 text-center border border-gray-400 font-bold w-24">Net</th>
                            <th class="py-1.5 px-3 text-center border border-gray-400 font-bold w-16">Renk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(v, i) in localVardiyalar" :key="v.id"
                            @click="selectRow(i)"
                            class="cursor-pointer transition"
                            :class="selectedIdx === i ? 'bg-[#f0c860] font-bold' : 'bg-[#d4e2f4] hover:bg-[#bfd0e8]'">
                            <td class="py-1 px-3 border border-gray-300">{{ v.ad }}</td>
                            <td class="py-1 px-3 border border-gray-300 text-center">{{ v.baslangic_saati || '-' }}</td>
                            <td class="py-1 px-3 border border-gray-300 text-center">{{ v.bitis_saati || '-' }}</td>
                            <td class="py-1 px-3 border border-gray-300 text-center text-gray-500">{{ formatSure(v.toplam_sure) }}</td>
                            <td class="py-1 px-3 border border-gray-300 text-center text-orange-600">{{ molaDakika }} dk</td>
                            <td class="py-1 px-3 border border-gray-300 text-center font-bold text-green-700">{{ formatNet(v.toplam_sure) }}</td>
                            <td class="py-1 px-3 border border-gray-300 text-center">
                                <span class="inline-block w-5 h-5 rounded border border-gray-400" :style="{ backgroundColor: v.renk || '#3B82F6' }"></span>
                            </td>
                        </tr>
                        <tr v-if="localVardiyalar.length === 0">
                            <td colspan="7" class="py-16 text-center text-gray-500 bg-[#d4e2f4]">&lt;Gösterilecek Bilgi yok&gt;</td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="molaDakika > 0" class="bg-amber-50 border-t border-amber-200 px-3 py-1.5 text-[10px] text-amber-700">
                    ℹ️ <b>Net Çalışma = Brüt Süre − Mola ({{ molaDakika }} dk)</b> | Mola süresi Günlük Puantaj Parametrelerinden alınır. Puantaj hesaplamasında <b>Net</b> süre kullanılır.
                </div>
            </div>

            <!-- Alt Form -->
            <div class="border-t border-gray-400 bg-gray-100 px-4 py-3">
                <div class="grid grid-cols-4 gap-3 items-end mb-3">
                    <div class="col-span-2">
                        <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Vardiya Adı</label>
                        <input v-model="form.ad" :disabled="!editMode" class="w-full border border-gray-300 rounded-sm py-1.5 px-2 text-xs disabled:bg-gray-50" />
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Giriş Saati</label>
                        <input v-model="form.baslangic_saati" type="time" :disabled="!editMode" class="w-full border border-gray-300 rounded-sm py-1.5 px-2 text-xs disabled:bg-gray-50" />
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Çıkış Saati</label>
                        <input v-model="form.bitis_saati" type="time" :disabled="!editMode" class="w-full border border-gray-300 rounded-sm py-1.5 px-2 text-xs disabled:bg-gray-50" />
                    </div>
                </div>
                <div class="flex items-center gap-3 mb-3">
                    <div>
                        <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Renk</label>
                        <input v-model="form.renk" type="color" :disabled="!editMode" class="w-12 h-8 border border-gray-300 rounded cursor-pointer disabled:opacity-50" />
                    </div>
                </div>

                <!-- Butonlar -->
                <div class="flex items-center justify-center gap-1.5">
                    <button @click="yeniKayit" class="w-9 h-9 flex items-center justify-center border border-gray-400 rounded bg-gradient-to-b from-[#f8f0d8] to-[#e8d8b0] hover:from-[#ffe8a8] shadow-sm" title="Yeni">
                        <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </button>
                    <button @click="sil" class="w-9 h-9 flex items-center justify-center border border-gray-400 rounded bg-gradient-to-b from-[#f8f0d8] to-[#e8d8b0] hover:from-[#ffe8a8] shadow-sm" title="Sil">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                    </button>
                    <button @click="duzenle" class="w-9 h-9 flex items-center justify-center border border-gray-400 rounded bg-gradient-to-b from-[#f8f0d8] to-[#e8d8b0] hover:from-[#ffe8a8] shadow-sm" title="Düzenle">
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </button>
                    <button @click="kaydet" class="w-9 h-9 flex items-center justify-center border border-gray-400 rounded bg-gradient-to-b from-[#f8f0d8] to-[#e8d8b0] hover:from-[#ffe8a8] shadow-sm" title="Kaydet">
                        <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </button>
                    <button @click="iptal" class="w-9 h-9 flex items-center justify-center border border-gray-400 rounded bg-gradient-to-b from-[#f8f0d8] to-[#e8d8b0] hover:from-[#ffe8a8] shadow-sm" title="İptal">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
