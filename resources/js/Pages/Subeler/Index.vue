<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, reactive } from 'vue';
import Modal from '@/Components/Modal.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    subeler: Object
});

const isModalOpen = ref(false);
const editingSube = ref(null);

const form = reactive({
    sube_adi: '',
    lokasyon: '',
    lokasyon_enlem: null,
    lokasyon_boylam: null,
    geofence_yaricap: 200,
    durum: true
});

const openModal = (sube = null) => {
    editingSube.value = sube;
    if(sube) {
        form.sube_adi = sube.sube_adi;
        form.lokasyon = sube.lokasyon;
        form.lokasyon_enlem = sube.lokasyon_enlem;
        form.lokasyon_boylam = sube.lokasyon_boylam;
        form.geofence_yaricap = sube.geofence_yaricap || 200;
        form.durum = !!sube.durum;
    } else {
        form.sube_adi = '';
        form.lokasyon = '';
        form.lokasyon_enlem = null;
        form.lokasyon_boylam = null;
        form.geofence_yaricap = 200;
        form.durum = true;
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    // form reset (reactive)
};

const saveSube = async () => {
    try {
        if(editingSube.value) {
            await axios.put(route('subeler.update', editingSube.value.id), { ...form });
        } else {
            await axios.post(route('subeler.store'), { ...form });
        }
        closeModal();
        Swal.fire({ title: 'Başarılı!', text: 'Şube başarıyla kaydedildi.', icon: 'success', timer: 1500 });
        setTimeout(() => window.location.reload(), 1500);
    } catch(e) {
        let msg = e.response?.data?.message || 'Bir hata oluştu.';
        if (e.response?.data?.errors) {
            msg += '\n' + Object.values(e.response.data.errors).flat().join('\n');
        }
        console.error('Şube kaydetme hatası:', e.response?.data);
        Swal.fire('Hata', msg, 'error');
    }
};

const deleteSube = (id) => {
    if(confirm('Şubeyi silmek istediğinize emin misiniz? DİKKAT: İçindeki personeller silinmez ancak şube atamaları kalkar.')){
        axios.delete(route('subeler.destroy', id)).catch(e => Swal.fire('Hata', e.response?.data?.message || 'Silinemedi', 'error'));
    }
};
</script>

<template>
    <Head title="Şube Yönetimi" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Şube Yönetimi</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg mb-6 p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Kayıtlı Şubeler (Lokasyonlar)</h3>
                        <button @click="openModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                            + Yeni Şube Ekle
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                            <thead class="bg-gray-50 uppercase text-xs text-gray-500 font-semibold">
                                <tr>
                                    <th class="py-3 px-6 text-left">Şube Adı</th>
                                    <th class="py-3 px-6 text-left">Lokasyon</th>
                                    <th class="py-3 px-6 text-center">GPS Konum</th>
                                    <th class="py-3 px-6 text-center">Bağlı Personel</th>
                                    <th class="py-3 px-6 text-center">Bağlı Cihaz</th>
                                    <th class="py-3 px-6 text-center">Durum</th>
                                    <th class="py-3 px-6 text-right">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <tr v-for="sube in subeler.data" :key="sube.id" class="border-b border-gray-200 hover:bg-gray-100 transition">
                                    <td class="py-3 px-6 text-left font-medium">{{ sube.sube_adi }}</td>
                                    <td class="py-3 px-6 text-left">{{ sube.lokasyon || '-' }}</td>
                                    <td class="py-3 px-6 text-center">
                                        <span v-if="sube.lokasyon_enlem && sube.lokasyon_boylam" class="bg-green-100 text-green-800 py-1 px-2 rounded-full text-xs font-bold" :title="sube.lokasyon_enlem + ', ' + sube.lokasyon_boylam">📍 Tanımlı ({{ sube.geofence_yaricap }}m)</span>
                                        <span v-else class="bg-gray-100 text-gray-500 py-1 px-2 rounded-full text-xs">— Yok</span>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-bold">{{ sube.personeller_count }}</span>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <span class="bg-emerald-100 text-emerald-800 py-1 px-3 rounded-full text-xs font-bold">{{ sube.cihazlar_count }}</span>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <span v-if="sube.durum" class="text-green-600 font-bold bg-green-100 px-2 py-1 rounded">Aktif</span>
                                        <span v-else class="text-red-500 font-bold bg-red-100 px-2 py-1 rounded">Pasif</span>
                                    </td>
                                    <td class="py-3 px-6 text-right flex justify-end space-x-2">
                                        <button @click="openModal(sube)" class="text-indigo-500 hover:text-indigo-700">Düzenle</button>
                                        <button @click="deleteSube(sube.id)" class="text-red-500 hover:text-red-700 ml-2">Sil</button>
                                    </td>
                                </tr>
                                <tr v-if="subeler.data.length === 0">
                                    <td colspan="7" class="py-4 text-center text-gray-500">Henüz kaydedilmiş bir şube bulunmuyor.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Şube Ekle/Düzenle Modalı -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ editingSube ? 'Şube Düzenle' : 'Yeni Şube Ekle' }}</h3>
                
                <form @submit.prevent="saveSube">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Şube Adı</label>
                            <input v-model="form.sube_adi" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Lokasyon (Adres - Opsiyonel)</label>
                            <input v-model="form.lokasyon" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="örn: Kadıköy, İstanbul">
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="text-sm font-bold text-blue-700 mb-3">📍 GPS Konum Ayarları (Mobil Giriş Kontrolü)</h4>
                            <p class="text-xs text-gray-500 mb-3">Koordinatları Google Maps'ten alabilirsiniz: Haritaya sağ tıklayın → Koordinatları kopyalayın</p>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">Enlem (Latitude)</label>
                                    <input v-model="form.lokasyon_enlem" type="number" step="0.0000001" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="41.0082">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">Boylam (Longitude)</label>
                                    <input v-model="form.lokasyon_boylam" type="number" step="0.0000001" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="28.9784">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">Yarıçap (metre)</label>
                                    <input v-model="form.geofence_yaricap" type="number" min="10" max="5000" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="200">
                                </div>
                            </div>
                        </div>
                        <div v-if="editingSube" class="flex items-center">
                            <input type="checkbox" v-model="form.durum" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span class="ms-2 text-sm text-gray-600">Aktif Şube</span>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300 transition">İptal</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition" :disabled="false">
                            Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
