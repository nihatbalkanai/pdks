<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    iceridekiler: {
        type: Number,
        default: 0
    },
    gecKalanlar: {
        type: Number,
        default: 0
    },
    toplamCihaz: {
        type: Number,
        default: 0
    },
    aktifCihaz: {
        type: Number,
        default: 0
    },
    sonCihazlar: {
        type: Array,
        default: () => []
    },
    sonHareketler: {
        type: Array,
        default: () => []
    }
});

// Dinamik değerler için ref oluşturalım (Socket'ten gelenlerle değiştirmek için)
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const iceridekilerState = ref(props.iceridekiler);
const hareketlerState = ref([...props.sonHareketler]);

const page = usePage();

onMounted(() => {
    // Firma kanalında dinleme yap (PdksKayitEklendi eventi)
    if (window.Echo && page.props.auth.user?.firma_id) {
        window.Echo.private(`firma.${page.props.auth.user.firma_id}`)
            .listen('.pdks.kayit.eklendi', (e) => {
                // Sadece spesifik componentleri (hareketler ve istatistikler) backendden tazeleyelim:
                router.reload({
                    only: ['iceridekiler', 'sonHareketler', 'gecKalanlar'],
                    onSuccess: () => {
                        iceridekilerState.value = page.props.iceridekiler;
                        hareketlerState.value = [...page.props.sonHareketler];
                    }
                });
            });
    }
});

onUnmounted(() => {
    if (window.Echo && page.props.auth.user?.firma_id) {
        window.Echo.leave(`firma.${page.props.auth.user.firma_id}`);
    }
});

// Computed formatlar (Optimizasyon maksatlı gereksiz render'ları önler)
const cihazAktiflikOrani = computed(() => {
    if (props.toplamCihaz === 0) return '0%';
    return Math.round((props.aktifCihaz / props.toplamCihaz) * 100) + '%';
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Anlık İzleme Paneli (Dashboard)
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <!-- Özet Kartları -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Şu An İçeride Olanlar -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-indigo-500 relative">
                        <!-- Socket canlı efekti -->
                        <span class="absolute top-2 right-2 flex h-3 w-3">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                        </span>
                        
                        <div class="p-6">
                            <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Şu An İçeride Olanlar</div>
                            <div class="mt-2 text-3xl font-bold text-gray-900">{{ iceridekilerState }}</div>
                            <div class="mt-2 text-sm text-gray-600">Bugüne ait (çıkış yapmayan) kayıtlar</div>
                        </div>
                    </div>

                    <!-- Bugün Geç Kalanlar -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-red-500">
                        <div class="p-6">
                            <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Bugün Geç Kalanlar</div>
                            <div class="mt-2 text-3xl font-bold text-gray-900">{{ gecKalanlar }}</div>
                            <div class="mt-2 text-sm text-gray-600">Mesai başlangıcı: 08:30</div>
                        </div>
                    </div>

                    <!-- Cihaz Durumları -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-green-500">
                        <div class="p-6">
                            <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Cihaz Durumları</div>
                            <div class="mt-2 text-3xl font-bold text-gray-900">{{ aktifCihaz }} <span class="text-xl text-gray-500 font-normal">/ {{ toplamCihaz }}</span></div>
                            <div class="mt-2 text-sm text-gray-600">Ağ üzerinde aktiflik oranı: {{ cihazAktiflikOrani }}</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <!-- Cihaz Listesi Özeti -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Sistemdeki Cihazlar</h3>
                                <Link :href="route('cihazlar.index')" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Tüm Cihazlara Git &rarr;</Link>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 text-gray-600 uppercase">
                                            <th class="py-2 px-4 rounded-tl-md rounded-bl-md">Cihaz Modeli</th>
                                            <th class="py-2 px-4">Seri No</th>
                                            <th class="py-2 px-4 rounded-tr-md rounded-br-md">Son Sinyal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="cihaz in sonCihazlar" :key="cihaz.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                                            <td class="py-3 px-4">{{ cihaz.cihaz_modeli || '-' }}</td>
                                            <td class="py-3 px-4 font-mono">{{ cihaz.seri_no }}</td>
                                            <td class="py-3 px-4">{{ cihaz.son_aktivite_tarihi ? new Date(cihaz.son_aktivite_tarihi).toLocaleString('tr-TR') : 'Hiç Veri Gelmedi' }}</td>
                                        </tr>
                                        <tr v-if="sonCihazlar.length === 0">
                                            <td colspan="3" class="py-4 text-center text-gray-500">Sisteminizde kayıtlı cihaz bulunmuyor.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Son Hareketler -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 drop-shadow-sm flex items-center">
                                    Son Hareketler (Anlık)
                                </h3>
                                <div class="text-xs text-green-500 font-bold tracking-wider uppercase animate-pulse">Canlı</div>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 text-gray-600 uppercase">
                                            <th class="py-2 px-4 rounded-tl-md rounded-bl-md">Personel</th>
                                            <th class="py-2 px-4 text-center">İşlem</th>
                                            <th class="py-2 px-4 rounded-tr-md rounded-br-md text-right">Saat</th>
                                        </tr>
                                    </thead>
                                    <transition-group name="list" tag="tbody">
                                        <tr v-for="hareket in hareketlerState" :key="hareket.id" class="border-b last:border-0 hover:bg-gray-50 transition bg-yellow-50/50">
                                            <td class="py-3 px-4">{{ hareket.personel ? hareket.personel.ad_soyad : 'Bilinmeyen' }}</td>
                                            <td class="py-3 px-4 text-center">
                                                <span :class="hareket.islem_tipi === 'giriş' ? 'text-green-600' : 'text-red-500'" class="font-bold border px-2 py-1 rounded">
                                                    {{ hareket.islem_tipi.toUpperCase() }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 font-mono text-right">{{ new Date(hareket.kayit_tarihi).toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }}</td>
                                        </tr>
                                        <tr v-if="hareketlerState.length === 0">
                                            <td colspan="3" class="py-4 text-center text-gray-500">Sistemde henüz bir hareket yok.</td>
                                        </tr>
                                    </transition-group>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
