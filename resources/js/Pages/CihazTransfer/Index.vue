<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    cihazlar: Array,
    hataliKayitlar: Array,
    toplamKayit: Number,
    hataliSayisi: Number,
});

const selectedCihaz = ref(props.cihazlar?.[0]?.id || null);
const selectedRow = ref(null);
const transferProgress = ref(0);
const isTransferring = ref(false);

const selectedCihazInfo = computed(() => {
    return props.cihazlar?.find(c => c.id === selectedCihaz.value);
});

const hataliYuzdesi = computed(() => {
    if (props.toplamKayit === 0) return '0 %';
    return ((props.hataliSayisi / props.toplamKayit) * 100).toFixed(1) + ' %';
});

const simulateTransfer = () => {
    isTransferring.value = true;
    transferProgress.value = 0;
    const interval = setInterval(() => {
        transferProgress.value += Math.random() * 15;
        if (transferProgress.value >= 100) {
            transferProgress.value = 100;
            clearInterval(interval);
            isTransferring.value = false;
            Swal.fire({
                title: 'Transfer Tamamlandı!',
                text: 'Veriler başarıyla bilgisayara aktarıldı.',
                icon: 'success',
                timer: 2000,
            });
        }
    }, 300);
};

const processToDatabase = () => {
    Swal.fire({
        title: 'İşleniyor...',
        text: 'Giriş-çıkış bilgileri veritabanına işleniyor.',
        icon: 'info',
        timer: 2000,
        showConfirmButton: false,
    }).then(() => {
        Swal.fire({
            title: 'Tamamlandı!',
            text: 'Veriler veritabanına işlendi.',
            icon: 'success',
            timer: 1500,
        });
    });
};

const deleteHatali = () => {
    Swal.fire({
        title: 'Emin misiniz?',
        text: 'Tüm hatalı kayıtlar silinecek!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'İptal',
        confirmButtonText: 'Tümünü Sil',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('cihaz-transfer.hatali-sil')).catch(e => Swal.fire('Hata', e.response?.data?.message || 'Silinemedi', 'error'), {
                onSuccess: () => {
                    Swal.fire({ title: 'Silindi!', text: 'Tüm hatalı kayıtlar silindi.', icon: 'success', timer: 1500 });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Cihazdan Veri Transferi" />
    <AuthenticatedLayout>
        <div class="p-4 h-full flex flex-col">
            <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full max-w-4xl">
                <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex justify-between items-center">
                    <h2 class="font-bold text-sm text-gray-800">Cihazdan Veri Transferi</h2>
                </div>

                <div class="flex-1 overflow-y-auto p-5 space-y-5">
                    <!-- ADIM 1 -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span class="text-lg font-bold text-green-700">Adım 1</span>
                                <span class="ml-3 text-sm text-gray-600">Veri toplama terminalinden bilgileri transfer edin.</span>
                            </div>
                            <button class="bg-white border border-gray-400 px-4 py-1 text-xs rounded-sm hover:bg-gray-50 shadow-sm">Ayarlar</button>
                        </div>
                        <button @click="simulateTransfer"
                            :disabled="isTransferring"
                            class="bg-white border border-gray-400 px-4 py-2 text-xs rounded-sm hover:bg-gray-50 shadow-sm flex items-center disabled:opacity-50">
                            <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            {{ isTransferring ? 'Transfer ediliyor...' : 'Bilgileri bilgisayara transfer et' }}
                        </button>
                        <div v-if="isTransferring" class="mt-2 w-64">
                            <div class="bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full transition-all" :style="{width: transferProgress + '%'}"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ADIM 2 -->
                    <div class="border-t border-gray-300 pt-4">
                        <div class="mb-2">
                            <span class="text-lg font-bold text-green-700">Adım 2</span>
                            <span class="ml-3 text-sm text-gray-600">Transfer edilen dosyayı belirtin.</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <button @click="processToDatabase" class="bg-white border border-gray-400 px-4 py-2 text-xs rounded-sm hover:bg-gray-50 shadow-sm flex items-center">
                                <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>
                                Giriş-çıkış bilgilerini veritabanına işle
                            </button>
                            <div class="text-right">
                                <div class="text-[10px] text-gray-500 mb-0.5">İşlem yapılacak cihaz</div>
                                <div class="bg-blue-100 border border-blue-300 rounded px-4 py-2 font-bold text-sm text-blue-800 min-w-[180px] text-center">
                                    {{ selectedCihazInfo ? (selectedCihazInfo.cihaz_modeli || selectedCihazInfo.seri_no) : 'Cihaz Seçilmedi' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ADIM 3 -->
                    <div class="border-t border-gray-300 pt-4">
                        <div class="mb-2">
                            <span class="text-lg font-bold text-green-700">Adım 3</span>
                            <span class="ml-3 text-sm text-gray-600">Eğer varsa hatalı kayıtları düzeltin</span>
                        </div>

                        <!-- İstatistik -->
                        <div class="flex items-center gap-6 mb-3 text-xs">
                            <div>
                                <span class="text-gray-600">Okunan toplam kayıt sayısı:</span>
                                <span class="font-bold ml-2">{{ toplamKayit }}</span>
                            </div>
                            <div class="flex-1 max-w-[200px]">
                                <div class="bg-gray-200 rounded-full h-4 flex items-center justify-center relative">
                                    <div class="bg-green-400 h-4 rounded-full absolute left-0" :style="{width: (100 - parseFloat(hataliYuzdesi)) + '%'}"></div>
                                    <span class="relative text-[10px] font-bold">{{ hataliYuzdesi }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-xs mb-2">
                            <span class="text-gray-600">Hatalı toplam kayıt sayısı:</span>
                            <span class="font-bold text-red-600 ml-2">{{ hataliSayisi }}</span>
                        </div>

                        <!-- Hatalı Kayıtlar Tablosu -->
                        <div class="border border-gray-400 overflow-auto max-h-[250px] bg-[#e8f4ff]">
                            <table class="w-full text-xs border-collapse">
                                <thead class="bg-[#d0dcea] sticky top-0">
                                    <tr>
                                        <th class="py-1 px-2 text-left border border-gray-400 font-bold">KartNo</th>
                                        <th class="py-1 px-2 text-left border border-gray-400 font-bold">Durum</th>
                                        <th class="py-1 px-2 text-left border border-gray-400 font-bold">Tarih</th>
                                        <th class="py-1 px-2 text-left border border-gray-400 font-bold">Saat</th>
                                        <th class="py-1 px-2 text-left border border-gray-400 font-bold">Hata</th>
                                        <th class="py-1 px-2 text-left border border-gray-400 font-bold">Cihaz</th>
                                        <th class="py-1 px-2 text-left border border-gray-400 font-bold">Ned.K</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="h in hataliKayitlar" :key="h.id"
                                        @click="selectedRow = h.id"
                                        class="cursor-pointer hover:bg-blue-100 transition-colors"
                                        :class="{'!bg-blue-200': selectedRow === h.id}">
                                        <td class="py-1 px-2 border-r border-gray-300">{{ h.kart_no }}</td>
                                        <td class="py-1 px-2 border-r border-gray-300">{{ h.durum }}</td>
                                        <td class="py-1 px-2 border-r border-gray-300">{{ h.tarih }}</td>
                                        <td class="py-1 px-2 border-r border-gray-300">{{ h.saat }}</td>
                                        <td class="py-1 px-2 border-r border-gray-300 text-red-600">{{ h.hata }}</td>
                                        <td class="py-1 px-2 border-r border-gray-300">{{ h.cihaz }}</td>
                                        <td class="py-1 px-2">{{ h.neden_kodu }}</td>
                                    </tr>
                                    <tr v-if="hataliKayitlar.length === 0">
                                        <td colspan="7" class="py-6 text-center text-gray-400">Hatalı kayıt bulunmuyor</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Alt Butonlar -->
                <div class="flex items-center justify-between px-4 py-2 bg-gray-100 border-t border-gray-400">
                    <button @click="deleteHatali" class="flex items-center text-xs text-red-600 hover:text-red-800 font-semibold">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Tüm Hatalı Kayıtları Sil
                    </button>
                    <div class="flex gap-1">
                        <button class="win-btn" title="Kaydet">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        </button>
                        <button class="win-btn" title="Yazdır">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        </button>
                        <button class="win-btn" title="Onayla">
                            <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </button>
                        <button class="win-btn" title="Tümünü Sil">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                        <button class="win-btn" title="Excel">
                            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.win-btn {
    @apply w-8 h-8 flex items-center justify-center bg-white border border-gray-400 rounded-sm hover:bg-gray-100 shadow-sm cursor-pointer transition;
}
</style>
