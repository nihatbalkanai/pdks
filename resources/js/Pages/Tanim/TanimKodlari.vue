<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({ tip: String, baslik: String, kodlar: Array });

const selectedIndex = ref(props.kodlar.length > 0 ? 0 : -1);
const editMode = ref(false);

const form = useForm({ kod: '', aciklama: '' });

const selected = computed(() => {
    if (selectedIndex.value >= 0 && selectedIndex.value < props.kodlar.length) {
        return props.kodlar[selectedIndex.value];
    }
    return null;
});

// Satıra tıklayınca seç ve formu doldur
const selectRow = (i) => {
    selectedIndex.value = i;
    const k = props.kodlar[i];
    form.kod = k.kod;
    form.aciklama = k.aciklama;
    editMode.value = false;
};

// Yeni kayıt
const yeniKayit = () => {
    // Sonraki kodu otomatik bul
    const maxKod = props.kodlar.reduce((max, k) => {
        const n = parseInt(k.kod);
        return isNaN(n) ? max : Math.max(max, n);
    }, 0);
    form.kod = String(maxKod + 1);
    form.aciklama = '';
    editMode.value = true;
    selectedIndex.value = -1;
};

// Kaydet (yeni veya güncelle)
const kaydet = () => {
    if (!form.kod || !form.aciklama) {
        Swal.fire('Uyarı', 'Kod ve açıklama zorunludur.', 'warning');
        return;
    }

    if (editMode.value && selectedIndex.value === -1) {
        // Yeni kayıt
        form.post(route('tanim.kodlar.store', props.tip), {
            onSuccess: () => { editMode.value = false; Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Eklendi', showConfirmButton: false, timer: 1500 }); },
            onError: (e) => Swal.fire('Hata', Object.values(e).flat().join('<br>'), 'error'),
        });
    } else if (selected.value) {
        // Güncelleme
        form.put(route('tanim.kodlar.update', { tip: props.tip, id: selected.value.id }), {
            onSuccess: () => { editMode.value = false; Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Güncellendi', showConfirmButton: false, timer: 1500 }); },
            onError: (e) => Swal.fire('Hata', Object.values(e).flat().join('<br>'), 'error'),
        });
    }
};

// Düzenle
const duzenle = () => {
    if (!selected.value) { Swal.fire('Uyarı', 'Düzenlenecek kaydı seçin.', 'warning'); return; }
    editMode.value = true;
};

// Sil
const sil = () => {
    if (!selected.value) { Swal.fire('Uyarı', 'Silinecek kaydı seçin.', 'warning'); return; }
    Swal.fire({
        title: 'Kaydı Sil',
        html: `<b>${selected.value.kod} - ${selected.value.aciklama}</b> silinecek. Emin misiniz?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sil',
        cancelButtonText: 'İptal',
    }).then(r => {
        if (r.isConfirmed) {
            router.delete(route('tanim.kodlar.destroy', { tip: props.tip, id: selected.value.id }), {
                onSuccess: () => { selectedIndex.value = -1; form.reset(); },
            });
        }
    });
};

// İptal
const iptal = () => {
    editMode.value = false;
    if (selected.value) {
        form.kod = selected.value.kod;
        form.aciklama = selected.value.aciklama;
    } else {
        form.reset();
    }
};
</script>

<template>
<Head :title="baslik" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <!-- Başlık -->
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
                <div class="flex items-center">
                    <svg v-if="tip==='sirket'" class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <svg v-else-if="tip==='departman'" class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <svg v-else-if="tip==='bolum'" class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                    <svg v-else-if="tip==='odeme'" class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    <svg v-else class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    <h2 class="font-bold text-sm text-gray-800">{{ baslik }}</h2>
                    <span class="ml-2 bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded text-[10px] font-bold">{{ kodlar.length }} kayıt</span>
                </div>
            </div>

            <!-- Tablo -->
            <div class="flex-1 overflow-y-auto bg-[#c8d8ec]">
                <table class="w-full text-xs border-collapse">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-[#f0c860]">
                            <th class="py-1.5 px-3 text-left border border-gray-400 font-bold w-20">Kod</th>
                            <th class="py-1.5 px-3 text-left border border-gray-400 font-bold">Açıklama</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(k, i) in kodlar" :key="k.id"
                            @click="selectRow(i)"
                            class="cursor-pointer transition"
                            :class="selectedIndex === i ? 'bg-[#f0c860] font-bold' : 'bg-[#d4e2f4] hover:bg-[#bfd0e8]'">
                            <td class="py-1 px-3 border border-gray-300 text-right">{{ k.kod }}</td>
                            <td class="py-1 px-3 border border-gray-300">{{ k.aciklama }}</td>
                        </tr>
                        <tr v-if="kodlar.length === 0">
                            <td colspan="2" class="py-16 text-center text-gray-500 bg-[#d4e2f4]">
                                &lt;Gösterilecek Bilgi yok&gt;
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Alt Düzenleme Alanı -->
            <div class="border-t border-gray-400 bg-gray-100 px-4 py-3">
                <div class="flex gap-3 items-end mb-3">
                    <div class="w-20">
                        <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Kod</label>
                        <input v-model="form.kod" :disabled="!editMode && selected !== null" class="w-full border-gray-300 rounded-sm py-1.5 px-2 text-xs text-right disabled:bg-gray-50" />
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Açıklama</label>
                        <input v-model="form.aciklama" :disabled="!editMode && selected !== null" class="w-full border-gray-300 rounded-sm py-1.5 px-2 text-xs disabled:bg-gray-50" @keyup.enter="editMode ? kaydet() : null" />
                    </div>
                </div>

                <!-- Butonlar -->
                <div class="flex items-center justify-center gap-1.5">
                    <button @click="yeniKayit" class="w-9 h-9 flex items-center justify-center border border-gray-400 rounded bg-gradient-to-b from-[#f8f0d8] to-[#e8d8b0] hover:from-[#ffe8a8] hover:to-[#e0c888] shadow-sm" title="Yeni Kayıt">
                        <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </button>
                    <button @click="sil" class="w-9 h-9 flex items-center justify-center border border-gray-400 rounded bg-gradient-to-b from-[#f8f0d8] to-[#e8d8b0] hover:from-[#ffe8a8] hover:to-[#e0c888] shadow-sm" title="Sil">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                    </button>
                    <button @click="duzenle" class="w-9 h-9 flex items-center justify-center border border-gray-400 rounded bg-gradient-to-b from-[#f8f0d8] to-[#e8d8b0] hover:from-[#ffe8a8] hover:to-[#e0c888] shadow-sm" title="Düzenle">
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </button>
                    <button @click="kaydet" class="w-9 h-9 flex items-center justify-center border border-gray-400 rounded bg-gradient-to-b from-[#f8f0d8] to-[#e8d8b0] hover:from-[#ffe8a8] hover:to-[#e0c888] shadow-sm" title="Kaydet">
                        <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </button>
                    <button @click="iptal" class="w-9 h-9 flex items-center justify-center border border-gray-400 rounded bg-gradient-to-b from-[#f8f0d8] to-[#e8d8b0] hover:from-[#ffe8a8] hover:to-[#e0c888] shadow-sm" title="İptal">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <button @click="$inertia.reload()" class="w-9 h-9 flex items-center justify-center border border-gray-400 rounded bg-gradient-to-b from-[#f8f0d8] to-[#e8d8b0] hover:from-[#ffe8a8] hover:to-[#e0c888] shadow-sm" title="Yenile">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
