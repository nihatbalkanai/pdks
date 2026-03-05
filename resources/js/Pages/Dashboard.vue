<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';

const props = defineProps({
    toplamPersonel: { type: Number, default: 0 },
    geldiSayisi: { type: Number, default: 0 },
    gelmediSayisi: { type: Number, default: 0 },
    gecKalanSayisi: { type: Number, default: 0 },
    iceridekiSayisi: { type: Number, default: 0 },
    toplamCihaz: { type: Number, default: 0 },
    aktifCihaz: { type: Number, default: 0 },
    trend: { type: Array, default: () => [] },
    gecKalanlar: { type: Array, default: () => [] },
    iceridekiler: { type: Array, default: () => [] },
    yaklasanIzinler: { type: Array, default: () => [] },
    dogumGunleri: { type: Array, default: () => [] },
    sonHareketler: { type: Array, default: () => [] },
});

// Devam oranı yüzdesi
const devamOrani = computed(() => {
    if (!props.toplamPersonel) return 0;
    return Math.round((props.geldiSayisi / props.toplamPersonel) * 100);
});

// Trend grafik için max değer
const trendMax = computed(() => {
    if (!props.trend.length) return 1;
    return Math.max(...props.trend.map(t => t.geldi + t.gelmedi), 1);
});

// Saat
const saat = ref('');
const tarihStr = ref('');
onMounted(() => {
    const guncelle = () => {
        const now = new Date();
        saat.value = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        tarihStr.value = now.toLocaleDateString('tr-TR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    };
    guncelle();
    setInterval(guncelle, 1000);
});
</script>

<template>
<Head title="Dashboard" />
<AuthenticatedLayout>
<div class="p-4 h-full flex flex-col overflow-y-auto">

    <!-- Üst Bar: Tarih/Saat -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-bold text-gray-800">📊 Anlık Durum Paneli</h1>
            <p class="text-xs text-gray-500">{{ tarihStr }}</p>
        </div>
        <div class="text-2xl font-mono font-bold text-indigo-600 bg-indigo-50 px-4 py-1.5 rounded-lg border border-indigo-200">
            {{ saat }}
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 1. DURUM KARTLARI (4 Kart) -->
    <!-- ========================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">

        <!-- Toplam Personel -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl p-4 shadow-lg relative overflow-hidden">
            <div class="absolute -right-3 -top-3 w-16 h-16 bg-white/10 rounded-full"></div>
            <div class="text-xs font-medium uppercase tracking-wider opacity-80">Toplam Personel</div>
            <div class="text-3xl font-black mt-1">{{ toplamPersonel }}</div>
            <div class="text-[10px] opacity-70 mt-1">Aktif çalışanlar</div>
        </div>

        <!-- Bugün Gelen -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-xl p-4 shadow-lg relative overflow-hidden">
            <div class="absolute -right-3 -top-3 w-16 h-16 bg-white/10 rounded-full"></div>
            <div class="text-xs font-medium uppercase tracking-wider opacity-80">Bugün Gelen</div>
            <div class="text-3xl font-black mt-1">{{ geldiSayisi }}</div>
            <div class="flex items-center gap-1 mt-1">
                <div class="w-full bg-white/20 rounded-full h-1.5">
                    <div class="bg-white h-1.5 rounded-full transition-all" :style="{ width: devamOrani + '%' }"></div>
                </div>
                <span class="text-[10px] font-bold">%{{ devamOrani }}</span>
            </div>
        </div>

        <!-- Gelmeyen -->
        <div class="bg-gradient-to-br from-red-500 to-red-600 text-white rounded-xl p-4 shadow-lg relative overflow-hidden">
            <div class="absolute -right-3 -top-3 w-16 h-16 bg-white/10 rounded-full"></div>
            <div class="text-xs font-medium uppercase tracking-wider opacity-80">Gelmeyen</div>
            <div class="text-3xl font-black mt-1">{{ gelmediSayisi }}</div>
            <div class="text-[10px] opacity-70 mt-1">Devamsız personel</div>
        </div>

        <!-- İçeridekiler -->
        <div class="bg-gradient-to-br from-amber-500 to-orange-500 text-white rounded-xl p-4 shadow-lg relative overflow-hidden">
            <div class="absolute -right-3 -top-3 w-16 h-16 bg-white/10 rounded-full"></div>
            <span class="absolute top-3 right-3 flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
            </span>
            <div class="text-xs font-medium uppercase tracking-wider opacity-80">Şu An İçeride</div>
            <div class="text-3xl font-black mt-1">{{ iceridekiSayisi }}</div>
            <div class="text-[10px] opacity-70 mt-1">Çıkış yapmadı</div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. ORTA BÖLÜM: Trend + Geç Kalanlar -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-4">

        <!-- Son 7 Gün Devam Trendi (2/3) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Son 7 Gün Devam Trendi
                </h3>
                <div class="flex items-center gap-3 text-[10px]">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span>Gelen</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-red-400"></span>Gelmeyen</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-amber-400"></span>Geç</span>
                </div>
            </div>
            <!-- Bar Grafik -->
            <div class="flex items-end gap-2 h-40">
                <div v-for="t in trend" :key="t.tarihRaw" class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full flex flex-col items-center justify-end h-32 relative">
                        <!-- Gelmeyen bar -->
                        <div class="w-full rounded-t bg-red-400 transition-all"
                            :style="{ height: (t.gelmedi / trendMax * 100) + '%', minHeight: t.gelmedi ? '4px' : '0' }"></div>
                        <!-- Geç kaldı bar -->
                        <div class="w-full bg-amber-400 transition-all"
                            :style="{ height: (t.gecKaldi / trendMax * 100) + '%', minHeight: t.gecKaldi ? '4px' : '0' }"></div>
                        <!-- Gelen bar -->
                        <div class="w-full rounded-b bg-emerald-500 transition-all"
                            :style="{ height: (t.geldi / trendMax * 100) + '%', minHeight: t.geldi ? '4px' : '0' }"></div>
                    </div>
                    <!-- Sayılar -->
                    <div class="text-[9px] font-bold text-gray-700">{{ t.geldi }}</div>
                    <div class="text-[9px] text-gray-400 leading-tight" :class="t.haftaSonu ? 'text-orange-400 font-bold' : ''">{{ t.tarih }}</div>
                </div>
            </div>
        </div>

        <!-- Geç Kalanlar (1/3) -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-1.5 mb-3">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Bugün Geç Kalanlar
                <span class="ml-auto text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-bold">{{ gecKalanSayisi }}</span>
            </h3>
            <div class="space-y-1.5 max-h-36 overflow-y-auto">
                <div v-for="(g, i) in gecKalanlar" :key="i" class="flex items-center justify-between bg-red-50 rounded-lg px-3 py-1.5">
                    <div>
                        <div class="text-xs font-semibold text-gray-800">{{ g.ad_soyad }}</div>
                        <div class="text-[10px] text-gray-400">{{ g.kart_no }}</div>
                    </div>
                    <span class="text-xs font-mono font-bold text-red-600">{{ g.ilk_giris }}</span>
                </div>
                <div v-if="!gecKalanlar.length" class="text-center text-xs text-gray-400 py-4">Bugün geç kalan yok 👍</div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 3. ALT BÖLÜM: İçeridekiler + İzinler + Doğum Günleri + Son Hareketler -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">

        <!-- İçeridekiler -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-1.5 mb-3">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Şu An İçeride
                <span class="ml-auto flex h-2 w-2"><span class="animate-ping absolute h-2 w-2 rounded-full bg-green-400 opacity-75"></span><span class="relative rounded-full h-2 w-2 bg-green-500"></span></span>
            </h3>
            <div class="space-y-1 max-h-40 overflow-y-auto">
                <div v-for="(p, i) in iceridekiler" :key="i" class="flex items-center justify-between bg-amber-50 rounded px-2.5 py-1.5">
                    <div class="text-xs font-medium text-gray-800">{{ p.ad_soyad }}</div>
                    <span class="text-[10px] font-mono text-amber-700">{{ p.ilk_giris }}</span>
                </div>
                <div v-if="!iceridekiler.length" class="text-center text-xs text-gray-400 py-4">İçeride kimse yok</div>
            </div>
        </div>

        <!-- Yaklaşan İzinler -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-1.5 mb-3">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Yaklaşan İzinler
            </h3>
            <div class="space-y-1.5 max-h-40 overflow-y-auto">
                <div v-for="(iz, i) in yaklasanIzinler" :key="i" class="bg-blue-50 rounded px-2.5 py-1.5">
                    <div class="text-xs font-semibold text-gray-800">{{ iz.ad_soyad }}</div>
                    <div class="text-[10px] text-blue-600">{{ iz.tarih }} → {{ iz.bitis }} ({{ iz.gun_sayisi }} gün)</div>
                    <div class="text-[10px] text-gray-400">{{ iz.aciklama }}</div>
                </div>
                <div v-if="!yaklasanIzinler.length" class="text-center text-xs text-gray-400 py-4">Yaklaşan izin yok</div>
            </div>
        </div>

        <!-- Doğum Günleri -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-1.5 mb-3">
                🎂 Bu Ayki Doğum Günleri
            </h3>
            <div class="space-y-1.5 max-h-40 overflow-y-auto">
                <div v-for="(d, i) in dogumGunleri" :key="i"
                    class="rounded px-2.5 py-1.5 flex items-center justify-between"
                    :class="d.bugun_mu ? 'bg-yellow-100 border border-yellow-300 animate-pulse' : 'bg-purple-50'">
                    <div>
                        <div class="text-xs font-semibold text-gray-800">{{ d.ad_soyad }}</div>
                        <div class="text-[10px] text-gray-500">{{ d.dogum_tarihi }}</div>
                    </div>
                    <span v-if="d.bugun_mu" class="text-sm">🎉</span>
                    <span v-else class="text-[10px] text-purple-500 font-bold">{{ d.gun }}. gün</span>
                </div>
                <div v-if="!dogumGunleri.length" class="text-center text-xs text-gray-400 py-4">Bu ay doğum günü yok</div>
            </div>
        </div>

        <!-- Son Hareketler -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-1.5 mb-3">
                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                Son Hareketler
                <span class="ml-auto text-[10px] text-green-500 font-bold animate-pulse uppercase">Canlı</span>
            </h3>
            <div class="space-y-1.5 max-h-40 overflow-y-auto">
                <div v-for="h in sonHareketler" :key="h.id" class="flex items-center justify-between bg-gray-50 rounded px-2.5 py-1.5">
                    <div>
                        <div class="text-xs font-medium text-gray-800">{{ h.ad_soyad }}</div>
                        <div class="text-[10px] text-gray-400">{{ h.tarih }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                            :class="h.islem_tipi === 'giris' ? 'bg-green-100 text-green-700' : h.islem_tipi === 'cikis' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600'">
                            {{ h.islem_tipi === 'giris' ? 'GİRİŞ' : h.islem_tipi === 'cikis' ? 'ÇIKIŞ' : h.islem_tipi.toUpperCase() }}
                        </span>
                        <span class="text-[10px] font-mono text-gray-600">{{ h.saat }}</span>
                    </div>
                </div>
                <div v-if="!sonHareketler.length" class="text-center text-xs text-gray-400 py-4">Henüz hareket yok</div>
            </div>
        </div>
    </div>

</div>
</AuthenticatedLayout>
</template>
