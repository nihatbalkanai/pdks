<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const iseGiris = ref('');
const istenCikis = ref('');
const brut = ref('');
const kidemTavani = ref(35058.58); // 2026 Kıdem tavanı (örnek)

const sonuc = computed(() => {
    if (!iseGiris.value || !istenCikis.value || !brut.value) return null;

    const giris = new Date(iseGiris.value);
    const cikis = new Date(istenCikis.value);
    const farkMs = cikis - giris;
    if (farkMs < 0) return null;

    const toplamGun = Math.floor(farkMs / 86400000);
    const yil = Math.floor(toplamGun / 365);
    const kalanGun = toplamGun % 365;
    const ay = Math.floor(kalanGun / 30);
    const gun = kalanGun % 30;
    const brutMaas = parseFloat(brut.value);

    // Kıdem Tazminatı
    const kidemBrut = Math.min(brutMaas, kidemTavani.value);
    const kidemTutar = (kidemBrut / 365) * toplamGun;

    // İhbar Tazminatı
    let ihbarHafta = 0;
    if (toplamGun < 182) ihbarHafta = 2;
    else if (toplamGun < 547) ihbarHafta = 4;
    else if (toplamGun < 1095) ihbarHafta = 6;
    else ihbarHafta = 8;
    const ihbarGun = ihbarHafta * 7;
    const ihbarTutar = (brutMaas / 30) * ihbarGun;

    // Yıllık İzin Alacağı
    let izinGun = 0;
    if (yil >= 1 && yil < 5) izinGun = 14;
    else if (yil >= 5 && yil < 15) izinGun = 20;
    else if (yil >= 15) izinGun = 26;
    const izinTutar = (brutMaas / 30) * izinGun;

    const toplamTutar = kidemTutar + ihbarTutar + izinTutar;

    return {
        yil, ay, gun, toplamGun,
        kidemBrut, kidemTutar,
        ihbarHafta, ihbarGun, ihbarTutar,
        izinGun, izinTutar,
        toplamTutar,
    };
});

const formatPara = (val) => Number(val || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
<Head title="Kıdem & İhbar Hesaplayıcı" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#e0e7ff] to-[#c7d2fe] border-b border-gray-400 px-4 py-2">
                <h2 class="font-bold text-sm text-gray-800">🧮 Kıdem & İhbar Tazminatı Hesaplayıcı</h2>
            </div>
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-4xl mx-auto">
                    <!-- Giriş Formu -->
                    <div class="bg-gray-50 rounded-xl p-6 mb-6 border border-gray-200">
                        <h3 class="font-bold text-sm text-gray-700 mb-4">📋 Bilgi Girişi</h3>
                        <div class="grid grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">İşe Giriş Tarihi</label>
                                <input v-model="iseGiris" type="date" class="w-full border-gray-300 rounded text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">İşten Çıkış Tarihi</label>
                                <input v-model="istenCikis" type="date" class="w-full border-gray-300 rounded text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Brüt Maaş (₺)</label>
                                <input v-model="brut" type="number" step="0.01" placeholder="0.00" class="w-full border-gray-300 rounded text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Kıdem Tavanı (₺)</label>
                                <input v-model="kidemTavani" type="number" step="0.01" class="w-full border-gray-300 rounded text-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- Sonuçlar -->
                    <div v-if="sonuc" class="space-y-4">
                        <!-- Çalışma Süresi -->
                        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5">
                            <h4 class="text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-2">Çalışma Süresi</h4>
                            <div class="flex gap-8">
                                <div class="text-center"><span class="text-3xl font-black text-indigo-700">{{ sonuc.yil }}</span><span class="text-xs text-indigo-500 block">Yıl</span></div>
                                <div class="text-center"><span class="text-3xl font-black text-indigo-700">{{ sonuc.ay }}</span><span class="text-xs text-indigo-500 block">Ay</span></div>
                                <div class="text-center"><span class="text-3xl font-black text-indigo-700">{{ sonuc.gun }}</span><span class="text-xs text-indigo-500 block">Gün</span></div>
                                <div class="text-center ml-4 pl-4 border-l border-indigo-200"><span class="text-xl font-bold text-indigo-600">{{ sonuc.toplamGun.toLocaleString('tr-TR') }}</span><span class="text-xs text-indigo-500 block">Toplam Gün</span></div>
                            </div>
                        </div>

                        <!-- Detay Kartları -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-green-50 border border-green-200 rounded-xl p-5">
                                <h4 class="text-xs font-semibold text-green-600 uppercase tracking-wider mb-3">Kıdem Tazminatı</h4>
                                <div class="text-2xl font-black text-green-700 mb-2">₺{{ formatPara(sonuc.kidemTutar) }}</div>
                                <div class="text-[10px] text-green-600 space-y-0.5">
                                    <div>Baz Brüt: ₺{{ formatPara(sonuc.kidemBrut) }} (tavanlı)</div>
                                    <div>{{ sonuc.toplamGun }} gün × ₺{{ formatPara(sonuc.kidemBrut / 365) }}/gün</div>
                                </div>
                            </div>

                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                                <h4 class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-3">İhbar Tazminatı</h4>
                                <div class="text-2xl font-black text-blue-700 mb-2">₺{{ formatPara(sonuc.ihbarTutar) }}</div>
                                <div class="text-[10px] text-blue-600 space-y-0.5">
                                    <div>İhbar Süresi: {{ sonuc.ihbarHafta }} hafta ({{ sonuc.ihbarGun }} gün)</div>
                                    <div>{{ sonuc.ihbarGun }} gün × ₺{{ formatPara(parseFloat(brut) / 30) }}/gün</div>
                                </div>
                            </div>

                            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
                                <h4 class="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-3">Yıllık İzin Alacağı</h4>
                                <div class="text-2xl font-black text-amber-700 mb-2">₺{{ formatPara(sonuc.izinTutar) }}</div>
                                <div class="text-[10px] text-amber-600 space-y-0.5">
                                    <div>Hak Edilen İzin: {{ sonuc.izinGun }} gün</div>
                                    <div>{{ sonuc.izinGun }} gün × ₺{{ formatPara(parseFloat(brut) / 30) }}/gün</div>
                                </div>
                            </div>
                        </div>

                        <!-- Toplam -->
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-6 text-white text-center">
                            <h4 class="text-xs uppercase tracking-widest opacity-80 mb-1">Toplam Tazminat Tutarı</h4>
                            <div class="text-4xl font-black">₺{{ formatPara(sonuc.toplamTutar) }}</div>
                        </div>

                        <!-- İhbar Tablosu -->
                        <div class="bg-gray-50 rounded-xl p-5 border">
                            <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3">📖 İhbar Süreleri Tablosu (İş Kanunu m.17)</h4>
                            <table class="w-full text-xs">
                                <thead class="text-gray-500"><tr><th class="py-1 text-left">Çalışma Süresi</th><th class="py-1 text-center">Bildirim Süresi</th><th class="py-1 text-right">Tazminat Günü</th></tr></thead>
                                <tbody class="text-gray-700">
                                    <tr :class="sonuc.toplamGun < 182 ? 'bg-blue-100 font-bold' : ''"><td>0 - 6 ay</td><td class="text-center">2 hafta</td><td class="text-right">14 gün</td></tr>
                                    <tr :class="sonuc.toplamGun >= 182 && sonuc.toplamGun < 547 ? 'bg-blue-100 font-bold' : ''"><td>6 ay - 1.5 yıl</td><td class="text-center">4 hafta</td><td class="text-right">28 gün</td></tr>
                                    <tr :class="sonuc.toplamGun >= 547 && sonuc.toplamGun < 1095 ? 'bg-blue-100 font-bold' : ''"><td>1.5 - 3 yıl</td><td class="text-center">6 hafta</td><td class="text-right">42 gün</td></tr>
                                    <tr :class="sonuc.toplamGun >= 1095 ? 'bg-blue-100 font-bold' : ''"><td>3 yıl ve üzeri</td><td class="text-center">8 hafta</td><td class="text-right">56 gün</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center py-20 text-gray-400">
                        <span class="text-5xl mb-4">🧮</span>
                        <p class="text-sm">Hesaplama için yukarıdaki bilgileri girin</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
