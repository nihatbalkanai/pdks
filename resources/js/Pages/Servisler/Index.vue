<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    servisler: Object,
    bugunBinisler: Number
});

const isModalOpen = ref(false);
const editingServis = ref(null);

const form = useForm({
    plaka: '',
    sofor: '',
    guzergah: '',
    durum: true
});

const openModal = (servis = null) => {
    editingServis.value = servis;
    if(servis) {
        form.plaka = servis.plaka;
        form.sofor = servis.sofor;
        form.guzergah = servis.guzergah;
        form.durum = !!servis.durum;
    } else {
        form.reset();
        form.durum = true;
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const saveServis = () => {
    if(editingServis.value) {
        form.put(route('servisler.update', editingServis.value.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('servisler.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const deleteServis = (id) => {
    if(confirm('Servisi silmek istediğinize emin misiniz?')){
        form.delete(route('servisler.destroy', id));
    }
};
</script>

<template>
    <Head title="Servis (Shuttle) Kontrolü" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Servis Kontrol Modülü</h2>
            <div class="ml-4 text-sm text-gray-500 font-normal">
                Bugünkü Servis Binişleri: <span class="font-bold text-indigo-600">{{ bugunBinisler }}</span> Personel
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg mb-6 p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Araç Filosu & Güzergahlar</h3>
                        <button @click="openModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                            + Yeni Araç Ekle
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                            <thead class="bg-gray-50 uppercase text-xs text-gray-500 font-semibold">
                                <tr>
                                    <th class="py-3 px-6 text-left">Plaka</th>
                                    <th class="py-3 px-6 text-left">Şoför</th>
                                    <th class="py-3 px-6 text-left">Güzergah</th>
                                    <th class="py-3 px-6 text-center">Atanan Personel</th>
                                    <th class="py-3 px-6 text-center">Durum</th>
                                    <th class="py-3 px-6 text-right">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <tr v-for="servis in servisler.data" :key="servis.id" class="border-b border-gray-200 hover:bg-gray-100 transition">
                                    <td class="py-3 px-6 text-left font-bold text-gray-700 bg-gray-50 uppercase">{{ servis.plaka }}</td>
                                    <td class="py-3 px-6 text-left">{{ servis.sofor || '-' }}</td>
                                    <td class="py-3 px-6 text-left truncate max-w-xs" :title="servis.guzergah">{{ servis.guzergah || '-' }}</td>
                                    <td class="py-3 px-6 text-center text-indigo-600 font-bold">
                                        {{ servis.personeller_count }} Kişi
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <span v-if="servis.durum" class="text-green-600 font-bold bg-green-100 px-2 py-1 rounded">Aktif</span>
                                        <span v-else class="text-red-500 font-bold bg-red-100 px-2 py-1 rounded">Servis Dışı</span>
                                    </td>
                                    <td class="py-3 px-6 text-right flex justify-end space-x-2">
                                        <button @click="openModal(servis)" class="text-indigo-500 hover:text-indigo-700">Düzenle</button>
                                        <button @click="deleteServis(servis.id)" class="text-red-500 hover:text-red-700 ml-2">Sil</button>
                                    </td>
                                </tr>
                                <tr v-if="servisler.data.length === 0">
                                    <td colspan="6" class="py-4 text-center text-gray-500">Kayıtlı servis aracı bulunmuyor.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ editingServis ? 'Servis Düzenle' : 'Yeni Servis Aracı Ekle' }}</h3>
                
                <form @submit.prevent="saveServis">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Araç Plakası (Zorunlu)</label>
                            <input v-model="form.plaka" type="text" placeholder="Örn: 34 ABC 123" class="mt-1 block w-full border-gray-300 uppercase rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Şoför Adı (Opsiyonel)</label>
                            <input v-model="form.sofor" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Güzergah Bilgisi (Opsiyonel)</label>
                            <input v-model="form.guzergah" type="text" placeholder="Kadıköy - Pendik vb." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div v-if="editingServis" class="flex items-center">
                            <input type="checkbox" v-model="form.durum" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span class="ms-2 text-sm text-gray-600">Serviste (Aktif)</span>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300 transition">İptal</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition" :disabled="form.processing">
                            Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
