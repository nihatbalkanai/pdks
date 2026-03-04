<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    cihazlar: Array,
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    seri_no: '',
    cihaz_modeli: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (cihaz) => {
    isEditing.value = true;
    editingId.value = cihaz.id;
    form.seri_no = cihaz.seri_no;
    form.cihaz_modeli = cihaz.cihaz_modeli;
    form.clearErrors();
    isModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('cihazlar.update', editingId.value), {
            onSuccess: () => isModalOpen.value = false
        });
    } else {
        form.post(route('cihazlar.store'), {
            onSuccess: () => isModalOpen.value = false
        });
    }
};

const deleteCihaz = (cihaz) => {
    if (confirm('Bu cihazı silmek istediğinize emin misiniz?')) {
        form.delete(route('cihazlar.destroy', cihaz.id));
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('tr-TR');
};
</script>

<template>
    <Head title="Cihaz Yönetimi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Cihaz Yönetimi
                </h2>
                <button @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md shadow transition">
                    + Yeni Cihaz
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <!-- Tablo -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b bg-gray-50 text-gray-600 text-sm uppercase">
                                        <th class="py-3 px-4">Cihaz Durumu</th>
                                        <th class="py-3 px-4">Seri No</th>
                                        <th class="py-3 px-4">Cihaz Modeli</th>
                                        <th class="py-3 px-4">Son Görülme</th>
                                        <th class="py-3 px-4 text-right">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="cihaz in cihazlar" :key="cihaz.id" class="border-b hover:bg-gray-50 transition">
                                        <td class="py-3 px-4">
                                            <div class="flex items-center">
                                                <div :class="cihaz.is_aktif ? 'bg-green-500' : 'bg-red-500'" class="w-3 h-3 rounded-full mr-2"></div>
                                                <span :class="cihaz.is_aktif ? 'text-green-700' : 'text-red-700'" class="font-medium text-sm">
                                                    {{ cihaz.is_aktif ? 'Aktif' : 'Pasif' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 font-mono text-sm">{{ cihaz.seri_no }}</td>
                                        <td class="py-3 px-4">{{ cihaz.cihaz_modeli || '-' }}</td>
                                        <td class="py-3 px-4 text-sm">{{ formatDate(cihaz.son_aktivite_tarihi) }}</td>
                                        <td class="py-3 px-4 text-right">
                                            <button @click="openEditModal(cihaz)" class="text-indigo-600 hover:text-indigo-900 mx-2">Düzenle</button>
                                            <button @click="deleteCihaz(cihaz)" class="text-red-600 hover:text-red-900 mx-2">Sil</button>
                                        </td>
                                    </tr>
                                    <tr v-if="cihazlar.length === 0">
                                        <td colspan="5" class="py-6 text-center text-gray-500">Hiç cihaz bulunamadı.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ekle/Düzenle Modal -->
        <Modal :show="isModalOpen" @close="isModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 shrink-0 mb-4">
                    {{ isEditing ? 'Cihaz Düzenle' : 'Yeni Cihaz Ekle' }}
                </h2>

                <form @submit.prevent="submitForm">
                    <div class="mb-4">
                        <InputLabel for="seri_no" value="Seri Numarası" />
                        <TextInput
                            id="seri_no"
                            v-model="form.seri_no"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.seri_no" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <InputLabel for="cihaz_modeli" value="Cihaz Modeli" />
                        <TextInput
                            id="cihaz_modeli"
                            v-model="form.cihaz_modeli"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.cihaz_modeli" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="isModalOpen = false" class="text-gray-600 hover:text-gray-900 px-4 py-2 mx-2 border rounded bg-white transition">
                            İptal
                        </button>
                        <button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow transition disabled:opacity-50">
                            Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
