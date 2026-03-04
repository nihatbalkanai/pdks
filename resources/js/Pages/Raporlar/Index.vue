<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

const props = defineProps({
    raporlar: Object, // Paginated data
    personeller: Array,
    bolumler: Array,
    filtreler: Object,
});

// Arama kutuları State'i
const filters = ref({
    baslangic: props.filtreler.baslangic || '',
    bitis: props.filtreler.bitis || '',
    personel_id: props.filtreler.personel_id || '',
    bolum: props.filtreler.bolum || '',
});

const isLoading = ref(false);

// Değişikliklerde Sunucudan Rapor İsteme (İzleyici Modeli)
const fetchReports = debounce(() => {
    isLoading.value = true;
    router.get(
        route('raporlar.index'),
        filters.value,
        {
            preserveState: true,
            replace: true,
            onFinish: () => (isLoading.value = false),
        }
    );
}, 400);

// Excel Çıktısı (Henüz Arka Plan Yazılmadı, Bildirim Amaçlı Buton)
const exportExcel = () => {
    alert("Excel dışa aktarma işlemi başlatılıyor. Bildirim merkezini takip edebilirsiniz...");
    router.post(route('raporlar.export'), filters.value, { preserveScroll: true });
};

// Toplam çalışma saatini okunabilir formata çeviren optimizasyonlu Computed Property değil, 
// listelerde method veya loop içi direct rendering daha iyidir. Ama isenirse map edilebilir.
const formatSure = (dakika) => {
    if (!dakika) return '-';
    let saat = Math.floor(dakika / 60);
    let kalanDakika = dakika % 60;
    return `${saat}s ${kalanDakika}d`;
};

// Duruma göre renk (Vue tarafında sınıf bazlı computed mapping)
const durumSinifi = (durum) => {
    switch(durum) {
        case 'geldi': return 'bg-green-100 text-green-800';
        case 'geç kaldı': return 'bg-yellow-100 text-yellow-800';
        case 'gelmedi': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const formatTime = (tarih) => {
    if (!tarih) return '-';
    return new Date(tarih).toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Gelişmiş Raporlar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Genel Raporlama & İzleme
                </h2>
                <button @click="exportExcel" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md shadow transition flex items-center">
                    <!-- Excel İkonu SVG -->
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Excel Aktar
                </button>
            </div>
        </template>

        <div class="py-12 relative">
            
            <!-- Global Yükleniyor Uyarısı -->
            <div v-if="isLoading" class="absolute top-4 right-10 flex items-center bg-indigo-100 text-indigo-700 px-4 py-2 rounded shadow opacity-90 transition z-50">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Yükleniyor...
            </div>

            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Arama ve Filtreler -->
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Başlangıç Tarihi -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Başlangıç Tarihi</label>
                            <input type="date" v-model="filters.baslangic" @change="fetchReports" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        
                        <!-- Bitiş Tarihi -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bitiş Tarihi</label>
                            <input type="date" v-model="filters.bitis" @change="fetchReports" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        
                        <!-- Personel Seçimi -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Personel</label>
                            <select v-model="filters.personel_id" @change="fetchReports" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Tümü</option>
                                <option v-for="per in personeller" :key="per.id" :value="per.id">{{ per.ad_soyad }}</option>
                            </select>
                        </div>
                        
                        <!-- Bölüm Filtresi -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bölüm</label>
                            <select v-model="filters.bolum" @change="fetchReports" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Tümü</option>
                                <option v-for="bol in bolumler" :key="bol" :value="bol">{{ bol }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tablo -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse whitespace-nowrap">
                                <thead>
                                    <tr class="border-b bg-gray-50 text-gray-600 text-sm uppercase">
                                        <th class="py-3 px-4">Tarih</th>
                                        <th class="py-3 px-4">Personel Adı</th>
                                        <th class="py-3 px-4 text-center">İlk Giriş</th>
                                        <th class="py-3 px-4 text-center">Son Çıkış</th>
                                        <th class="py-3 px-4 text-center">Toplam Süre</th>
                                        <th class="py-3 px-4 text-center">Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="rapor in raporlar.data" :key="rapor.id" class="border-b hover:bg-gray-50 transition text-sm">
                                        <td class="py-3 px-4 font-medium">{{ new Date(rapor.tarih).toLocaleDateString('tr-TR') }}</td>
                                        <td class="py-3 px-4">
                                            {{ rapor.personel ? rapor.personel.ad_soyad : 'Silinmiş Personel' }}
                                            <div class="text-xs text-gray-400" v-if="rapor.personel">{{ rapor.personel.bolum || 'Bölümsüz' }}</div>
                                        </td>
                                        <td class="py-3 px-4 font-mono text-center">{{ formatTime(rapor.ilk_giris) }}</td>
                                        <td class="py-3 px-4 font-mono text-center">{{ formatTime(rapor.son_cikis) }}</td>
                                        <td class="py-3 px-4 font-bold text-gray-700 text-center">{{ formatSure(rapor.toplam_calisma_suresi) }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <span :class="durumSinifi(rapor.durum)" class="px-2 py-1 rounded-full text-xs font-semibold uppercase tracking-wider">
                                                {{ rapor.durum }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="raporlar.data.length === 0">
                                        <td colspan="6" class="py-8 text-center text-gray-500 font-medium">Bu kriterlere uygun hiçbir raporlama bulunamadı.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Sayfalama (Pagination Component) -->
                        <div class="mt-6 flex justify-center" v-if="raporlar.links && raporlar.links.length > 3">
                            <template v-for="(link, key) in raporlar.links" :key="key">
                                <Link 
                                    v-if="link.url"
                                    :href="link.url" 
                                    v-html="link.label"
                                    class="mx-1 px-3 py-1 border rounded text-sm transition font-medium"
                                    :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 hover:bg-indigo-50 hover:text-indigo-600'"
                                />
                                <span 
                                    v-else 
                                    v-html="link.label" 
                                    class="mx-1 px-3 py-1 border rounded text-sm bg-gray-100 text-gray-400 cursor-not-allowed"
                                />
                            </template>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>
