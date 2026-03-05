<script setup>
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Chart as ChartJS, registerables } from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(...registerables);

const props = defineProps({
    metrikler: Object,
    sistem: Object,
    firmalar: Object,
    yavasSorgular: Array,
    adminler: Array,
    can: Object
});

// Sistem Yükü Grafiği Konfigürasyonu
const chartData = computed(() => ({
    labels: props.sistem.grafik.labels,
    datasets: [
        {
            label: 'Sistem Yükü (%)',
            data: props.sistem.grafik.data,
            fill: true,
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.2)',
            tension: 0.4,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        y: { beginAtZero: true, max: 100 }
    }
};

// Firma Yönetimi Modalı
const isModalOpen = ref(false);
const editingFirma = ref(null);
const form = reactive({
    abonelik_bitis_tarihi: '',
    paket_tipi: 'Ücretsiz',
    durum: true
});

const openEditModal = (firma) => {
    editingFirma.value = firma;
    form.abonelik_bitis_tarihi = firma.abonelik_bitis_tarihi ? firma.abonelik_bitis_tarihi.split('T')[0] : '';
    form.paket_tipi = firma.paket_tipi || 'Ücretsiz';
    form.durum = firma.durum;
    isModalOpen.value = true;
};

const saveAbonelik = () => {
    form.post(route('super-admin.firmalar.abonelik', editingFirma.value.id), {
        onSuccess: () => {
            isModalOpen.value = false;
        }
    });
};

// Admin Yetki Modalı
const isAdminModalOpen = ref(false);
const editingAdmin = ref(null);
const adminForm = reactive({
    yetkiler: []
});

const openAdminModal = (adminUser) => {
    editingAdmin.value = adminUser;
    adminForm.yetkiler = adminUser.super_admin_yetki?.yetkiler || [];
    isAdminModalOpen.value = true;
};

const saveAdminYetki = () => {
    adminForm.post(route('super-admin.adminler.yetki', editingAdmin.value.id), {
        onSuccess: () => {
            isAdminModalOpen.value = false;
            editingAdmin.value = null;
        }
    });
};

</script>

<template>
    <Head title="Super Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Super Admin: Yönetim Merkezi</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Üst Metrikler -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-indigo-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
                        <div class="text-sm font-medium opacity-80 uppercase tracking-widest">Aktif Firma Sayısı</div>
                        <div class="mt-2 text-4xl font-extrabold">{{ metrikler.aktifFirma }}</div>
                    </div>
                    
                    <div class="bg-blue-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
                        <div class="text-sm font-medium opacity-80 uppercase tracking-widest">Ağdaki Toplam Cihaz</div>
                        <div class="mt-2 text-4xl font-extrabold">{{ metrikler.toplamCihaz }}</div>
                    </div>
                    
                    <div class="bg-emerald-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
                        <div class="text-sm font-medium opacity-80 uppercase tracking-widest">Son 24 Saatlik DB İşlemi</div>
                        <div class="mt-2 text-4xl font-extrabold">{{ metrikler.sonKayitSayisi }} H.</div>
                    </div>

                    <div class="bg-purple-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
                        <div class="text-sm font-medium opacity-80 uppercase tracking-widest">Kuyruk (Queue) Yükü</div>
                        <div class="mt-2 text-4xl font-extrabold flex items-center">
                            {{ metrikler.kuyrukBekleyen }}
                            <svg v-if="metrikler.kuyrukBekleyen > 0" class="w-6 h-6 ml-2 animate-spin text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Sistem Sağlık İzleme (Pulse Benzeri) -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4 flex items-center">
                                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse mr-2"></span> Canlı Kaynak Tüketimi
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between text-xs font-semibold mb-1 text-gray-600">
                                        <span>CPU Yükü (Tahmini)</span>
                                        <span>%{{ sistem.cpu }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full transition-all duration-1000" :style="{ width: Math.min(sistem.cpu, 100) + '%' }"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs font-semibold mb-1 text-gray-600">
                                        <span>RAM Kullanımı (PHP)</span>
                                        <span>{{ sistem.ram }} MB</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full transition-all duration-1000" :style="{ width: Math.min((sistem.ram / 128) * 100, 100) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Grafik -->
                    <div class="lg:col-span-3 bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4">Son 7 Günlük Sistem Stres Grafiği</h3>
                        <div class="h-64 w-full relative">
                            <Line :data="chartData" :options="chartOptions" />
                        </div>
                    </div>
                </div>

                <!-- Firma Yönetimi Listesi -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Kayıtlı Firmalar & Abonelik Yönetimi</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-600 uppercase text-xs font-bold">
                                        <th class="py-3 px-4 rounded-tl-lg">Firma Adı</th>
                                        <th class="py-3 px-4">Paket</th>
                                        <th class="py-3 px-4">Abonelik Bitiş</th>
                                        <th class="py-3 px-4">Durum</th>
                                        <th class="py-3 px-4 rounded-tr-lg text-right">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="firma in firmalar.data" :key="firma.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                                        <td class="py-3 px-4 font-semibold text-gray-900">{{ firma.firma_adi }}</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-bold">{{ firma.paket_tipi }}</span>
                                        </td>
                                        <td class="py-3 px-4 font-mono text-gray-600">
                                            {{ firma.abonelik_bitis_tarihi ? new Date(firma.abonelik_bitis_tarihi).toLocaleDateString('tr-TR') : 'Sınırsız/Tanımsız' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span v-if="firma.durum" class="text-green-600 font-bold bg-green-50 px-2 py-1 rounded">Aktif</span>
                                            <span v-else class="text-red-500 font-bold bg-red-50 px-2 py-1 rounded">Pasif (Askıda)</span>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <button v-if="can?.odemeleri_yonet" @click="openEditModal(firma)" class="text-indigo-600 hover:text-indigo-900 font-medium bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded transition">Yönet</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Super Admin Yöneticileri Listesi -->
                <div v-if="can?.admin_yonetimi" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Sistem Yöneticileri (Super Admins)</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-600 uppercase text-xs font-bold">
                                        <th class="py-3 px-4 rounded-tl-lg">Admin Adı</th>
                                        <th class="py-3 px-4">E-posta</th>
                                        <th class="py-3 px-4">Yetkiler</th>
                                        <th class="py-3 px-4 rounded-tr-lg text-right">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="admin in adminler" :key="admin.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                                        <td class="py-3 px-4 font-semibold text-gray-900">{{ admin.ad_soyad }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ admin.eposta }}</td>
                                        <td class="py-3 px-4">
                                            <span v-if="admin.super_admin_yetki?.yetkiler?.includes('*')" class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-bold">Tam Yetkili</span>
                                            <span v-else-if="!admin.super_admin_yetki?.yetkiler?.length" class="text-gray-400 italic">Yetkisi Yok</span>
                                            <div v-else class="flex gap-1 flex-wrap">
                                                <span v-for="yetki in admin.super_admin_yetki.yetkiler" :key="yetki" class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                                    {{ yetki.replace(/_/g, ' ') }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <button @click="openAdminModal(admin)" class="text-purple-600 hover:text-purple-900 font-medium bg-purple-50 hover:bg-purple-100 px-3 py-1 rounded transition">Yetkilendir</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Yavaş Sorgu Analizi (Log) -->
            <div v-if="can?.teknik_loglar_gorme" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-red-600 mb-4 flex items-center border-b pb-2">
                        <svg class="w-5 h-5 mr-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Veritabanı Analizi (Slow Queries > 10ms)
                    </h3>
                    <div v-if="yavasSorgular?.length === 0" class="text-sm font-medium text-green-600">
                        Mükemmel! Sistem genelinde 10.000 firmada hiçbir yavaş sorgu algılanmadı.
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="sorgu in yavasSorgular" :key="sorgu.id" class="bg-red-50 border border-red-100 p-3 rounded text-xs font-mono text-gray-700 break-words">
                            <span class="text-red-700 font-bold">[{{ new Date(sorgu.tarih).toLocaleString('tr-TR') }}]</span>
                            {{ sorgu.detay }}
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Yönetim Modalı -->
        <Modal :show="isModalOpen" @close="isModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900">Abonelik Düzenle: {{ editingFirma?.firma_adi }}</h2>
                
                <form @submit.prevent="saveAbonelik" class="mt-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Paket Tipi</label>
                        <select v-model="form.paket_tipi" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="Ücretsiz">Ücretsiz</option>
                            <option value="Standart">Standart</option>
                            <option value="Pro">Pro</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Abonelik Bitiş Tarihi</label>
                        <input type="date" v-model="form.abonelik_bitis_tarihi" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" v-model="form.durum" id="durum_cb" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                        <label for="durum_cb" class="ml-2 text-sm text-gray-700 font-medium">Sisteme Erişimi Aktif (Durdurmak için kaldırın)</label>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="isModalOpen = false" class="mr-3 bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">İptal</button>
                        <button type="submit" :disabled="false" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Değişiklikleri Kaydet</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Admin Yetki Modalı -->
        <Modal :show="isAdminModalOpen" @close="isAdminModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900">Admin Yetkileri Düzenle: {{ editingAdmin?.ad_soyad }}</h2>
                
                <form @submit.prevent="saveAdminYetki" class="mt-6 space-y-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 border-b pb-2 mb-3">Sistem Erişim İzinleri</label>
                        
                        <div class="flex items-center">
                            <input type="checkbox" v-model="adminForm.yetkiler" value="*" id="isim_tam_yetki" class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500" />
                            <label for="isim_tam_yetki" class="ml-2 text-sm text-gray-900 font-bold">Tam Yetki (Tüm Sistem)</label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" v-model="adminForm.yetkiler" value="firmalari_gorme" id="isim_firma" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <label for="isim_firma" class="ml-2 text-sm text-gray-700">Firmaları ve Temel İstatistikleri Görme</label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" v-model="adminForm.yetkiler" value="odemeleri_yonet" id="isim_odeme" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <label for="isim_odeme" class="ml-2 text-sm text-gray-700">Ödemeleri ve Paketleri Yönetme</label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" v-model="adminForm.yetkiler" value="teknik_loglar" id="isim_loglar" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <label for="isim_loglar" class="ml-2 text-sm text-gray-700 text-red-600">Teknik Loglara (Yavaş Sorgular vb.) Erişme</label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" v-model="adminForm.yetkiler" value="admin_yonetimi" id="isim_admin" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <label for="isim_admin" class="ml-2 text-sm text-gray-700 font-medium">Diğer Adminleri Yönetme Yetkisi</label>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end bg-gray-50 -mx-6 -mb-6 p-4 rounded-b-lg">
                        <button type="button" @click="isAdminModalOpen = false" class="mr-3 bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">İptal</button>
                        <button type="submit" :disabled="adminForm.processing" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Yetkileri Kaydet</button>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
