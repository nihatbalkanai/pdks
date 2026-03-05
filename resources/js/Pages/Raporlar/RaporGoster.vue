<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    raporNo: String,
    baslik: String,
    veriler: Array,
    kolonlar: Array,
    personeller: Array,
    filtreler: Object,
    tarihGizle: { type: Boolean, default: false },
});

const filters = ref({
    baslangic: props.filtreler?.baslangic || '',
    bitis: props.filtreler?.bitis || '',
    personel_id: props.filtreler?.personel_id || '',
});

const filtrele = () => {
    router.get(route('raporlar.r' + props.raporNo), filters.value, { preserveState: true, replace: true });
};

// Hücre değeri çözümleme (nested key desteği: 'personel.ad')
const getValue = (row, key) => {
    const parts = key.split('.');
    let val = row;
    for (const p of parts) {
        if (val == null) return '-';
        val = val[p];
    }
    return val ?? '-';
};

// Hücre formatla
const formatCell = (val, type) => {
    if (val === '-' || val == null) return '-';
    if (type === 'date') {
        try { return new Date(val).toLocaleDateString('tr-TR'); } catch { return val; }
    }
    if (type === 'time') {
        try { return new Date(val).toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' }); } catch { return val; }
    }
    if (type === 'dakika') {
        if (!val) return '-';
        const s = Math.floor(val / 60);
        const d = val % 60;
        return `${s}s ${d}d`;
    }
    if (type === 'tutar') {
        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(val);
    }
    return val;
};

// Badge renkleri
const badgeClass = (val) => {
    const map = {
        'geldi': 'bg-green-100 text-green-800',
        'geç kaldı': 'bg-yellow-100 text-yellow-800',
        'gelmedi': 'bg-red-100 text-red-800',
        'erken_cikis': 'bg-orange-100 text-orange-800',
        'eksik_giris': 'bg-purple-100 text-purple-800',
        'eksik_cikis': 'bg-purple-100 text-purple-800',
        'beklemede': 'bg-yellow-100 text-yellow-800',
        'onaylandi': 'bg-green-100 text-green-800',
        'reddedildi': 'bg-red-100 text-red-800',
    };
    return map[val] || 'bg-gray-100 text-gray-800';
};

const toplam = computed(() => props.veriler?.length || 0);
</script>

<template>
<Head :title="baslik" />
<AuthenticatedLayout>
<div class="p-4 h-full flex flex-col">
    <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full overflow-hidden">

        <!-- Başlık -->
        <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd"></path></svg>
                <h2 class="font-bold text-sm text-gray-800">{{ raporNo }}. {{ baslik }}</h2>
            </div>
            <span class="text-[10px] text-gray-500 bg-white/60 px-2 py-0.5 rounded border border-gray-300">{{ toplam }} kayıt</span>
        </div>

        <!-- Filtreler -->
        <div class="bg-gray-50 border-b border-gray-300 px-4 py-2 flex items-center gap-3 flex-wrap">
            <template v-if="!tarihGizle">
                <div class="flex items-center gap-1">
                    <label class="text-[10px] font-semibold text-gray-500">Başlangıç:</label>
                    <input type="date" v-model="filters.baslangic" @change="filtrele" class="text-xs border border-gray-300 rounded px-2 py-1 w-36" />
                </div>
                <div class="flex items-center gap-1">
                    <label class="text-[10px] font-semibold text-gray-500">Bitiş:</label>
                    <input type="date" v-model="filters.bitis" @change="filtrele" class="text-xs border border-gray-300 rounded px-2 py-1 w-36" />
                </div>
            </template>
            <div class="flex items-center gap-1" v-if="['02','04','06'].includes(raporNo)">
                <label class="text-[10px] font-semibold text-gray-500">Personel:</label>
                <select v-model="filters.personel_id" @change="filtrele" class="text-xs border border-gray-300 rounded px-2 py-1 w-56">
                    <option value="">Tümü</option>
                    <option v-for="p in personeller" :key="p.id" :value="p.id">{{ p.ad_soyad }}</option>
                </select>
            </div>
            <button @click="filtrele" class="text-[10px] bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded shadow flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                Filtrele
            </button>
        </div>

        <!-- Tablo -->
        <div class="flex-1 overflow-y-auto bg-[#c8d8ec]">
            <table class="w-full text-xs border-collapse">
                <thead class="sticky top-0">
                    <tr class="bg-[#f0c860]">
                        <th class="py-1.5 px-3 text-left border border-gray-400 font-bold w-10">#</th>
                        <th v-for="col in kolonlar" :key="col.key"
                            class="py-1.5 px-3 text-left border border-gray-400 font-bold">
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, idx) in veriler" :key="idx"
                        class="bg-[#d4e2f4] hover:bg-[#bfd0e8] transition">
                        <td class="py-1 px-3 border border-gray-300 text-gray-500 font-mono">{{ idx + 1 }}</td>
                        <td v-for="col in kolonlar" :key="col.key"
                            class="py-1 px-3 border border-gray-300"
                            :class="col.type === 'time' ? 'font-mono' : ''">
                            <template v-if="col.type === 'badge'">
                                <span :class="badgeClass(getValue(row, col.key))" class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase">
                                    {{ getValue(row, col.key) }}
                                </span>
                            </template>
                            <template v-else>
                                {{ formatCell(getValue(row, col.key), col.type) }}
                            </template>
                        </td>
                    </tr>
                    <tr v-if="!veriler || veriler.length === 0">
                        <td :colspan="kolonlar.length + 1" class="py-12 text-center text-gray-500 bg-[#d4e2f4]">
                            Bu filtrelere uygun veri bulunamadı.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Alt Bar -->
        <div class="bg-gray-100 border-t border-gray-300 px-4 py-1.5 flex items-center justify-between">
            <span class="text-[10px] text-gray-500">Toplam: {{ toplam }} kayıt</span>
            <button onclick="window.print()" class="text-[10px] text-blue-600 hover:text-blue-800 border border-blue-300 px-3 py-1 rounded flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Yazdır
            </button>
        </div>
    </div>
</div>
</AuthenticatedLayout>
</template>
