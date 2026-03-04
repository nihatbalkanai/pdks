<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="TechSend.io - Kurumsal PDKS Çözümleri" />
    
    <div class="bg-gray-50 text-gray-800 min-h-screen font-sans selection:bg-indigo-500 selection:text-white">
        <!-- Header -->
        <header class="bg-white border-b shadow-sm relative z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-20">
                <div class="flex items-center gap-2">
                    <ApplicationLogo class="w-10 h-10 text-indigo-600 fill-current" />
                    <span class="text-2xl font-extrabold text-gray-900 tracking-tight">techsend<span class="text-indigo-600">.io</span></span>
                </div>
                
                <nav v-if="canLogin" class="-mx-3 flex flex-1 justify-end items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md px-4 py-2 text-indigo-600 font-bold ring-1 ring-transparent transition hover:bg-indigo-50 focus:outline-none focus-visible:ring-indigo-600"
                    >
                        Panele Git &rarr;
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-md px-4 py-2 font-semibold text-gray-600 transition hover:text-gray-900 focus:outline-none focus-visible:ring-[#FF2D20]"
                        >
                            Log in
                        </Link>
                        <!-- Kayıt kısmını projeye göre opsiyonel tutalım veya kaldıralım -->
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="relative overflow-hidden w-full pt-16 pb-32">
            <!-- Arka Planda Şık Gradient Çemberler -->
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob"></div>
            <div class="absolute top-0 -left-40 w-96 h-96 bg-blue-200 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center pt-20">
                <h1 class="text-6xl font-black text-gray-900 tracking-tight leading-tight">
                    Türkiye'nin En Hızlı <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-blue-500">PDKS Altyapısı</span>
                </h1>
                
                <p class="mt-8 text-xl text-gray-600 max-w-2xl mx-auto font-medium">
                    10.000 Firma ölçeğinde canlı cihaz entegrasyonu, anlık WebSocket takibi ve yapay zeka destekli kurumsal kaynak yönetimi.
                </p>

                <div class="mt-10 flex justify-center gap-4">
                    <Link :href="route('login')" class="px-8 py-4 bg-indigo-600 text-white rounded-lg font-bold shadow-lg hover:shadow-xl hover:bg-indigo-700 transition transform hover:-translate-y-1">
                        Sisteme Giriş Yap
                    </Link>
                    <a href="#features" class="px-8 py-4 bg-white text-gray-900 border border-gray-200 rounded-lg font-bold shadow-sm hover:bg-gray-50 transition">
                        Özellikleri İncele
                    </a>
                </div>
            </div>
        </main>

        <!-- Features -->
        <section id="features" class="py-20 bg-white border-t border-gray-100 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-extrabold text-gray-900">Neden <span class="text-indigo-600">techsend.io</span> PDKS?</h2>
                    <p class="mt-4 text-gray-500 max-w-xl mx-auto">Bulut tabanlı, kesintisiz ve yüksek performanslı mimarimiz sayesinde işletmenizin her anını kontrol altına alın.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <div class="bg-gray-50 p-8 rounded-2xl border border-gray-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Hızlı Veri Akışı</h3>
                        <p class="text-gray-600 leading-relaxed">Sistemimizde kurulan private websocket köprüleriyle, cihazlardan basılan veriler saniyeler içinde anlık dashboard'unuza yansır.</p>
                    </div>

                    <div class="bg-gray-50 p-8 rounded-2xl border border-gray-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">10.000 Firma Ölçekli</h3>
                        <p class="text-gray-600 leading-relaxed">Multi-tenancy global scope yapımızla izolasyonu sağlar, optimize edilmiş kompozit indekslerle binlerce firmaya milyonlarca satır veri sunarız.</p>
                    </div>

                    <div class="bg-gray-50 p-8 rounded-2xl border border-gray-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Gelişmiş API Güvenliği</h3>
                        <p class="text-gray-600 leading-relaxed">Sanctum Token teknolojisi ve donanım imza eşleşmeleri ile veri girişleri %100 güvende tutulur.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center opacity-80 text-sm">
                <div>&copy; 2026 techsend.io | Tüm Hakları Saklıdır.</div>
                <div class="mt-4 md:mt-0 font-mono">
                    Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
  animation: blob 7s infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}
</style>
