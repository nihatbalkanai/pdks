<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    durum: Number,
    mesaj: String,
});

const baslik = computed(() => {
    return {
        503: 'Servis Hizmet Dışı',
        500: 'Beklenmeyen Sunucu Hatası',
        404: 'Sayfa Bulunamadı',
        403: 'Erişim Yasak',
    }[props.durum] || 'Bir Hata Oluştu';
});

const acklama = computed(() => {
    return props.mesaj || {
        503: 'Şu anda sistem bakım modunda, lütfen daha sonra tekrar deneyin.',
        500: 'Sistemde bir hata oluştu ve teknik ekibe otomatik olarak iletildi. En kısa sürede düzelteceğiz.',
        404: 'Aradığınız sayfa ya da veri bulunamadı.',
        403: 'Bu alana erişim yetkiniz bulunmuyor.',
    }[props.durum];
});
</script>

<template>
    <Head :title="baslik" />

    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-50 p-4">
        <div class="max-w-md w-full text-center space-y-8 bg-white p-10 rounded-xl shadow-lg border border-gray-100">
            <!-- Hata İkonu / Kırmızı Tonlu -->
            <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-red-100">
                <svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            
            <div>
                <h1 class="text-4xl font-extrabold text-gray-900 drop-shadow-sm">{{ durum }}</h1>
                <h2 class="mt-4 text-2xl font-bold text-gray-800">{{ baslik }}</h2>
                <p class="mt-4 text-base text-gray-600">{{ acklama }}</p>
            </div>
            
            <div class="pt-6">
                <Link 
                    href="/" 
                    class="inline-flex items-center justify-center w-full px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition"
                >
                    Ana Sayfaya Dön
                </Link>
            </div>
        </div>
        
        <!-- Destek Linki -->
        <div class="mt-8 text-sm text-gray-500">
            Hata devam ederse <a href="mailto:destek@pdks.test" class="font-medium text-indigo-600 hover:text-indigo-500">teknik destek</a> ile iletişime geçin.
        </div>
    </div>
</template>
