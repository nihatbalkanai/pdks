<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    firma: Object,
    kullanicilar: Array,
    kayitIstatistik: Array,
    departmanDagilimi: Array,
});

const rolEtiketleri = {
    'admin': { label: 'Yönetici', color: 'bg-red-100 text-red-700' },
    'kullanici': { label: 'Kullanıcı', color: 'bg-blue-100 text-blue-700' },
    'muhasebe': { label: 'Muhasebe', color: 'bg-green-100 text-green-700' },
    'ik': { label: 'İK', color: 'bg-purple-100 text-purple-700' },
    'izleyici': { label: 'İzleyici', color: 'bg-gray-100 text-gray-700' },
};

const formatTarih = (t) => t ? new Date(t).toLocaleDateString('tr-TR') : '-';

// Impersonate
const impersonate = async () => {
    const result = await Swal.fire({
        title: 'Firma Olarak Giriş',
        html: `<b>${props.firma.firma_adi}</b> firmasının admin paneline geçiş yapılacak.`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        confirmButtonText: 'Giriş Yap',
        cancelButtonText: 'İptal',
    });
    if (result.isConfirmed) {
        try {
            await axios.post(route('super-admin.firmalar.impersonate', props.firma.id));
            window.location.href = '/dashboard';
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Hata', text: e.response?.data?.message || 'Bir hata oluştu' });
        }
    }
};

// Abonelik bilgileri
const abonelikDurumu = computed(() => {
    if (!props.firma.durum) return { text: 'Pasif', color: 'text-red-600', bg: 'bg-red-50' };
    if (!props.firma.abonelik_bitis_tarihi) return { text: 'Sınırsız', color: 'text-green-600', bg: 'bg-green-50' };
    const kalan = Math.ceil((new Date(props.firma.abonelik_bitis_tarihi) - new Date()) / (1000 * 60 * 60 * 24));
    if (kalan <= 0) return { text: 'Süresi Dolmuş', color: 'text-red-600', bg: 'bg-red-50' };
    if (kalan <= 30) return { text: `${kalan} gün kaldı`, color: 'text-amber-600', bg: 'bg-amber-50' };
    return { text: `${kalan} gün kaldı`, color: 'text-green-600', bg: 'bg-green-50' };
});

// Son 30 gün toplam kayıt
const toplamKayit30 = computed(() => {
    return (props.kayitIstatistik || []).reduce((sum, k) => sum + k.sayi, 0);
});
</script>

<template>
    <Head :title="`${firma.firma_adi} - Firma Detay`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('super-admin.index')" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </Link>
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white font-bold text-lg">
                        {{ firma.firma_adi?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ firma.firma_adi }}</h2>
                        <p class="text-xs text-gray-500">Firma Detay ve Yönetim</p>
                    </div>
                </div>
                <button @click="impersonate" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-sm font-semibold rounded-lg hover:from-emerald-600 hover:to-teal-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    Firma Olarak Giriş Yap
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Üst Bilgi Kartları -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                        <div class="text-3xl font-extrabold text-indigo-600">{{ firma.personeller_count || 0 }}</div>
                        <div class="text-xs text-gray-500 mt-1 font-medium">Personel</div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                        <div class="text-3xl font-extrabold text-blue-600">{{ firma.kullanicilar_count || 0 }}</div>
                        <div class="text-xs text-gray-500 mt-1 font-medium">Kullanıcı</div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                        <div class="text-3xl font-extrabold text-emerald-600">{{ firma.cihazlar_count || 0 }}</div>
                        <div class="text-xs text-gray-500 mt-1 font-medium">Cihaz</div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                        <div class="text-3xl font-extrabold text-amber-600">{{ toplamKayit30 }}</div>
                        <div class="text-xs text-gray-500 mt-1 font-medium">Son 30g Kayıt</div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                        <div :class="abonelikDurumu.color" class="text-lg font-extrabold">{{ abonelikDurumu.text }}</div>
                        <div class="text-xs text-gray-500 mt-1 font-medium">Abonelik</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Firma Bilgileri -->
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                        <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path></svg>
                            Firma Bilgileri
                        </h3>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Firma Adı</dt>
                                <dd class="font-semibold text-gray-900">{{ firma.firma_adi }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Vergi No</dt>
                                <dd class="font-mono text-gray-700">{{ firma.vergi_no || '-' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Vergi Dairesi</dt>
                                <dd class="text-gray-700">{{ firma.vergi_dairesi || '-' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Paket</dt>
                                <dd><span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">{{ firma.paket_tipi || 'Tanımsız' }}</span></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Abonelik Bitiş</dt>
                                <dd class="font-mono text-gray-700">{{ formatTarih(firma.abonelik_bitis_tarihi) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Durum</dt>
                                <dd>
                                    <span v-if="firma.durum" class="text-green-600 font-bold text-xs bg-green-50 px-2 py-0.5 rounded-full">Aktif</span>
                                    <span v-else class="text-red-600 font-bold text-xs bg-red-50 px-2 py-0.5 rounded-full">Pasif</span>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Kayıt Tarihi</dt>
                                <dd class="font-mono text-gray-700 text-xs">{{ formatTarih(firma.created_at) }}</dd>
                            </div>
                            <div v-if="firma.adres">
                                <dt class="text-gray-500 mb-1">Adres</dt>
                                <dd class="text-gray-700 text-xs">{{ firma.adres }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Kullanıcılar -->
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                        <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>
                            Kullanıcılar ({{ kullanicilar?.length || 0 }})
                        </h3>
                        <div class="space-y-2 max-h-80 overflow-y-auto">
                            <div v-for="k in kullanicilar" :key="k.id" class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50 hover:bg-indigo-50 transition">
                                <div>
                                    <div class="font-semibold text-sm text-gray-900">{{ k.ad_soyad }}</div>
                                    <div class="text-xs text-gray-500">{{ k.eposta }}</div>
                                </div>
                                <span :class="rolEtiketleri[k.rol]?.color || 'bg-gray-100 text-gray-700'" class="px-2 py-0.5 rounded-full text-[11px] font-bold">
                                    {{ rolEtiketleri[k.rol]?.label || k.rol }}
                                </span>
                            </div>
                            <div v-if="!kullanicilar?.length" class="text-center text-gray-400 text-sm py-6">
                                Henüz kullanıcı yok
                            </div>
                        </div>
                    </div>

                    <!-- Departman Dağılımı -->
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                        <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path></svg>
                            Departman Dağılımı
                        </h3>
                        <div class="space-y-2 max-h-80 overflow-y-auto">
                            <div v-for="(dept, i) in departmanDagilimi" :key="i" class="flex items-center justify-between p-2.5">
                                <span class="text-sm text-gray-700">{{ dept.bolum || 'Tanımsız' }}</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-100 rounded-full h-2">
                                        <div class="bg-emerald-500 h-2 rounded-full" :style="{ width: Math.min((dept.sayi / (firma.personeller_count || 1)) * 100, 100) + '%' }"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-600 w-8 text-right">{{ dept.sayi }}</span>
                                </div>
                            </div>
                            <div v-if="!departmanDagilimi?.length" class="text-center text-gray-400 text-sm py-6">
                                Departman verisi yok
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
