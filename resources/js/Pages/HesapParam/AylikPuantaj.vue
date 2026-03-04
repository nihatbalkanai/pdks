<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    parametreler: Array,
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const currentId = ref(null);

const form = useForm({
    hesap_parametresi_adi: '',
    aylik_calisma_saati: 225,
    haftalik_calisma_saati: 45,
    gunluk_calisma_saati: 7.5,
    eksik_gun_kesintisi_yapilacak_mi: true,
    fazla_mesai_carpani: 1.5,
    tatil_mesai_carpani: 2.0,
    resmi_tatil_mesai_carpani: 2.0,
    durum: true,
});

const openModal = (parametre = null) => {
    if (parametre) {
        isEditing.value = true;
        currentId.value = parametre.id;
        form.hesap_parametresi_adi = parametre.hesap_parametresi_adi;
        form.aylik_calisma_saati = parametre.aylik_calisma_saati;
        form.haftalik_calisma_saati = parametre.haftalik_calisma_saati;
        form.gunluk_calisma_saati = parametre.gunluk_calisma_saati;
        form.eksik_gun_kesintisi_yapilacak_mi = !!parametre.eksik_gun_kesintisi_yapilacak_mi;
        form.fazla_mesai_carpani = parametre.fazla_mesai_carpani;
        form.tatil_mesai_carpani = parametre.tatil_mesai_carpani;
        form.resmi_tatil_mesai_carpani = parametre.resmi_tatil_mesai_carpani;
        form.durum = !!parametre.durum;
    } else {
        isEditing.value = false;
        currentId.value = null;
        form.reset();
        form.durum = true;
        form.eksik_gun_kesintisi_yapilacak_mi = true;
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const saveParametre = () => {
    if (isEditing.value) {
        form.put(route('tanim.puantaj-parametreleri.update', currentId.value), {
            onSuccess: () => {
                closeModal();
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Güncellendi', showConfirmButton: false, timer: 1500 });
            },
        });
    } else {
        form.post(route('tanim.puantaj-parametreleri.store'), {
            onSuccess: () => {
                closeModal();
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Eklendi', showConfirmButton: false, timer: 1500 });
            },
        });
    }
};

const deleteParametre = (id) => {
    Swal.fire({
        title: 'Emin misiniz?',
        text: 'Bu parametre tanımını silmek istediğinize emin misiniz?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.delete(route('tanim.puantaj-parametreleri.destroy', id), {
                onSuccess: () => {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Silindi', showConfirmButton: false, timer: 1500 });
                }
            });
        }
    });
};
</script>

<template>
<Head title="Aylık Puantaj Parametreleri" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full overflow-hidden">
            
            <!-- Başlık -->
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    <h2 class="font-bold text-sm text-gray-800">Aylık Puantaj Parametreleri</h2>
                </div>
                <button @click="openModal()" class="flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded shadow text-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Yeni Ekle
                </button>
            </div>

            <!-- İçerik -->
            <div class="flex-1 overflow-auto bg-[#c8d8ec] p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="param in parametreler" :key="param.id" class="bg-white border border-gray-300 rounded shadow-sm hover:shadow-md transition">
                        <div class="px-4 py-2 border-b border-gray-200 bg-gray-50 flex items-center justify-between rounded-t">
                            <h3 class="font-bold text-gray-800 text-sm truncate pr-2 flex items-center gap-2">
                                <span :class="param.durum ? 'bg-green-500' : 'bg-red-500'" class="w-2.5 h-2.5 rounded-full inline-block"></span>
                                {{ param.hesap_parametresi_adi }}
                            </h3>
                            <div class="flex gap-1">
                                <button @click="openModal(param)" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Düzenle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572z"></path></svg>
                                </button>
                                <button @click="deleteParametre(param.id)" class="p-1 text-red-600 hover:bg-red-50 rounded" title="Sil">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                        <div class="p-4 text-xs">
                            <div class="grid grid-cols-2 gap-y-2 gap-x-4 mb-3 pb-3 border-b border-gray-100">
                                <div class="text-gray-500">Aylık Çalışma:</div><div class="font-medium text-right">{{ param.aylik_calisma_saati }} Saat</div>
                                <div class="text-gray-500">Haftalık Çalışma:</div><div class="font-medium text-right">{{ param.haftalik_calisma_saati }} Saat</div>
                                <div class="text-gray-500">Günlük Çalışma:</div><div class="font-medium text-right">{{ param.gunluk_calisma_saati }} Saat</div>
                                <div class="col-span-2 text-gray-500 flex items-center justify-between mt-1">
                                    Eksik Gün Kesintisi: 
                                    <span :class="param.eksik_gun_kesintisi_yapilacak_mi ? 'text-red-600 bg-red-50 px-2 py-0.5 rounded' : 'text-green-600 bg-green-50 px-2 py-0.5 rounded'">
                                        {{ param.eksik_gun_kesintisi_yapilacak_mi ? 'Evet' : 'Hayır' }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-600 mb-2 underline decoration-gray-300">Mesai Çarpanları</h4>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-gray-500">Fazla Mesai:</span>
                                    <span class="bg-blue-50 text-blue-700 font-bold px-1.5 py-0.5 rounded border border-blue-100">x{{ param.fazla_mesai_carpani }}</span>
                                </div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-gray-500">Tatil Mesai:</span>
                                    <span class="bg-orange-50 text-orange-700 font-bold px-1.5 py-0.5 rounded border border-orange-100">x{{ param.tatil_mesai_carpani }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Resmi Tatil Mesai:</span>
                                    <span class="bg-red-50 text-red-700 font-bold px-1.5 py-0.5 rounded border border-red-100">x{{ param.resmi_tatil_mesai_carpani }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="parametreler.length === 0" class="col-span-full h-32 flex flex-col items-center justify-center text-gray-400 bg-white/50 border border-dashed border-gray-400 rounded">
                        <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        Henüz puantaj parametresi eklenmemiş.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div v-if="isModalOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded shadow-xl w-[500px] border border-gray-300 overflow-hidden flex flex-col max-h-[90vh]">
            <div class="bg-gray-100 px-4 py-3 border-b border-gray-300 flex items-center justify-between">
                <h3 class="font-bold text-gray-800">{{ isEditing ? 'Parametre Düzenle' : 'Yeni Parametre Ekle' }}</h3>
                <button @click="closeModal" class="text-gray-400 hover:text-red-500 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="p-5 overflow-y-auto flex-1">
                <form @submit.prevent="saveParametre" id="paramForm" class="space-y-4">
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Hesap Parametresi Adı <span class="text-red-500">*</span></label>
                        <input v-model="form.hesap_parametresi_adi" type="text" class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required placeholder="Örn: GENEL AYLIK PAR.">
                        <div v-if="form.errors.hesap_parametresi_adi" class="text-red-500 text-xs mt-1">{{ form.errors.hesap_parametresi_adi }}</div>
                        <p class="text-[10px] text-gray-500 mt-1">Bu ad, "Genel Gruplar Çalışma Planı" veya Personel Kartı'ndaki grup/parametre adlarıyla eşleşmelidir.</p>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Aylık Çalışma</label>
                            <div class="relative">
                                <input v-model="form.aylik_calisma_saati" type="number" step="1" class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pr-10" required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-gray-400">Saat</div>
                            </div>
                            <div v-if="form.errors.aylik_calisma_saati" class="text-red-500 text-xs mt-1">{{ form.errors.aylik_calisma_saati }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Haftalık Çalışma</label>
                            <div class="relative">
                                <input v-model="form.haftalik_calisma_saati" type="number" step="1" class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pr-10" required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-gray-400">Saat</div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Günlük Çalışma</label>
                            <div class="relative">
                                <input v-model="form.gunluk_calisma_saati" type="number" step="0.1" class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pr-10" required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-gray-400">Saat</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                        <label class="flex items-center">
                            <input v-model="form.eksik_gun_kesintisi_yapilacak_mi" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 font-medium">Eksik Gün Kesintisi Yapılacak mı?</span>
                        </label>
                        <p class="text-[10px] text-gray-500 ml-6 mt-1">Eğer işaretlenirse, ay içinde eksik çalışılan günler tespit edilip maaştan kesinti yapılır.</p>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-gray-800 border-b pb-1 mb-3">Mesai Çarpanları</h4>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-600 mb-1">Fazla Mesai</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-xs text-blue-600 font-bold">x</div>
                                    <input v-model="form.fazla_mesai_carpani" type="number" step="0.01" class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pl-6" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-600 mb-1">Tatil Mesaisi</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-xs text-orange-600 font-bold">x</div>
                                    <input v-model="form.tatil_mesai_carpani" type="number" step="0.01" class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pl-6" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-600 mb-1">Resmi Tatil Mesai</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-xs text-red-600 font-bold">x</div>
                                    <input v-model="form.resmi_tatil_mesai_carpani" type="number" step="0.01" class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pl-6" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input v-model="form.durum" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Aktif</span>
                        </label>
                    </div>
                </form>
            </div>
            
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-300 flex justify-end gap-2">
                <button type="button" @click="closeModal" class="px-3 py-1.5 border border-gray-300 bg-white text-gray-700 rounded text-sm hover:bg-gray-50 transition">
                    İptal
                </button>
                <button type="submit" form="paramForm" :disabled="form.processing" class="px-4 py-1.5 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition disabled:opacity-75 flex items-center">
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    {{ isEditing ? 'Güncelle' : 'Kaydet' }}
                </button>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
