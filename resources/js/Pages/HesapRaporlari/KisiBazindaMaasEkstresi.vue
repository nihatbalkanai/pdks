<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Swal from 'sweetalert2';
import axios from 'axios';
import * as XLSX from 'xlsx';

const props = defineProps({ personeller: Array });
const selectedPersonelIds = ref([]);
const selectAll = ref(false);
const tarihBaslangic = ref('');
const tarihBitis = ref('');
const isProcessing = ref(false);
const sonuclar = ref(null);
const selectedSonuc = ref(null);
const aramaText = ref('');

const toTitleCase = (str) => {
    if (!str) return '';
    return str.toLocaleLowerCase('tr-TR').replace(/(^|\s)(\S)/g, (m, s, c) => s + c.toLocaleUpperCase('tr-TR'));
};

const filtrelenmis = computed(() => {
    if (!aramaText.value) return props.personeller;
    const q = aramaText.value.toLocaleLowerCase('tr-TR');
    return props.personeller.filter(p => (p.ad + ' ' + p.soyad).toLocaleLowerCase('tr-TR').includes(q));
});

const toggleAll = () => { selectedPersonelIds.value = selectAll.value ? filtrelenmis.value.map(p => p.id) : []; };

const parseNum = (v) => { if (!v) return 0; if (typeof v === 'number') return v; return parseFloat(String(v).replace(/\./g, '').replace(',', '.')) || 0; };
const fmtPara = (v) => parseNum(v).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const hesapla = async () => {
    if (!selectedPersonelIds.value.length) { Swal.fire('Uyarı', 'En az bir personel seçin.', 'warning'); return; }
    if (!tarihBaslangic.value || !tarihBitis.value) { Swal.fire('Uyarı', 'Tarih aralığı seçin.', 'warning'); return; }
    isProcessing.value = true;
    try {
        const res = await axios.post(route('hesap-raporlari.puantaj-hesapla'), {
            personel_ids: selectedPersonelIds.value,
            tarih_baslangic: tarihBaslangic.value,
            tarih_bitis: tarihBitis.value,
            ilk_giris_son_cikis: true,
        });
        if (res.data.success) {
            sonuclar.value = res.data;
            if (res.data.sonuclar.length > 0) selectedSonuc.value = res.data.sonuclar[0];
        }
    } catch (e) { Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu.', 'error'); }
    finally { isProcessing.value = false; }
};

const yazdir = () => window.print();

const excelExport = () => {
    if (!sonuclar.value) return;
    const headers = ['#', 'Adı Soyadı', 'Bölüm', 'Net Maaş', 'Normal Çalışma', 'FM %50', 'FM %100', 'Ek Ödeme', 'Yol Parası', 'Yemek', 'Gün Fark', 'Devamsızlık', 'Avans', 'Kesinti', 'Elden Ödeme', 'TOPLAM'];
    const rows = sonuclar.value.sonuclar.map((s, i) => [
        i + 1, s.ad + ' ' + s.soyad, s.bolum,
        parseNum(s.net_maas), parseNum(s.bordro.normal_calisma.ucret),
        parseNum(s.bordro.fazla_mesai_50.ucret), parseNum(s.bordro.fazla_mesai_100.ucret),
        parseNum(s.bordro.ek_odeme), parseNum(s.bordro.yol_parasi), parseNum(s.bordro.yemek),
        parseNum(s.bordro.gun_fark), parseNum(s.bordro.devamsizlik.ucret),
        parseNum(s.bordro.avans), parseNum(s.bordro.kesinti),
        parseNum(s.bordro.elden_odeme), parseNum(s.bordro.toplam)
    ]);
    const totals = ['', 'TOPLAM', '', ...Array(13).fill(0)];
    rows.forEach(r => { for (let i = 3; i < 16; i++) totals[i] += r[i]; });
    const ws = XLSX.utils.aoa_to_sheet([['KİŞİ BAZINDA MAAŞ EKSTRESİ'], [`${sonuclar.value.tarih_araligi.baslangic} - ${sonuclar.value.tarih_araligi.bitis}`], [], headers, ...rows, totals]);
    ws['!cols'] = [{wch:4},{wch:25},{wch:18},{wch:14},{wch:14},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:14}];
    ws['!merges'] = [{s:{r:0,c:0},e:{r:0,c:15}},{s:{r:1,c:0},e:{r:1,c:15}}];
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Kişi Maaş');
    XLSX.writeFile(wb, `kisi_bazinda_maas_${tarihBaslangic.value}_${tarihBitis.value}.xlsx`);
};

const bordroItems = computed(() => {
    if (!selectedSonuc.value) return [];
    const b = selectedSonuc.value.bordro;
    return [
        { label: 'Normal Çalışma', gun: b.normal_calisma.gun, saat: b.normal_calisma.saat, ucret: b.normal_calisma.ucret, color: 'green' },
        { label: 'Fazla Mesai %50', gun: '-', saat: b.fazla_mesai_50.saat, ucret: b.fazla_mesai_50.ucret, color: 'yellow' },
        { label: 'Fazla Mesai %100', gun: b.fazla_mesai_100.gun, saat: b.fazla_mesai_100.saat, ucret: b.fazla_mesai_100.ucret, color: 'orange' },
        { label: 'Devamsızlık (-)' , gun: b.devamsizlik.gun, saat: b.devamsizlik.saat, ucret: b.devamsizlik.ucret, color: 'red', negative: true },
        { label: 'Hafta Tatili', gun: b.hafta_tatili.gun, saat: b.hafta_tatili.saat, ucret: b.hafta_tatili.ucret, color: 'purple' },
        { label: 'Ücretsiz İzin (-)' , gun: b.ucretsiz_izin.gun, saat: b.ucretsiz_izin.saat, ucret: b.ucretsiz_izin.ucret, color: 'indigo', negative: true },
        { label: 'Ücretli İzin', gun: b.ucretli_izin.gun, saat: b.ucretli_izin.saat, ucret: b.ucretli_izin.ucret, color: 'blue' },
    ];
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="flex flex-col h-full bg-white">
            <div class="bg-gradient-to-r from-[#e8eef8] to-[#dce4f2] border-b border-gray-300 px-4 py-2 flex items-center justify-between">
                <span class="font-bold text-sm text-gray-800">👤 Kişi Bazında Maaş Ekstresi</span>
                <div v-if="selectedSonuc" class="flex gap-2">
                    <button @click="excelExport" class="px-3 py-1 bg-white border border-green-300 rounded text-[10px] font-bold text-green-700 hover:bg-green-50">📄 Excel</button>
                    <button @click="yazdir" class="px-3 py-1 bg-white border border-gray-300 rounded text-[10px] font-bold hover:bg-gray-50">🖨️ Yazdır</button>
                </div>
            </div>
            <div class="flex flex-1 overflow-hidden">
                <!-- SOL: Personel -->
                <div class="w-56 border-r flex flex-col bg-gray-50 print:hidden">
                    <div class="px-2 py-1 border-b"><input v-model="aramaText" type="text" placeholder="🔍 Ara..." class="w-full px-2 py-0.5 text-[11px] border rounded" /></div>
                    <div class="px-2 py-1 border-b bg-white flex items-center">
                        <label class="flex items-center text-[11px] cursor-pointer"><input type="checkbox" v-model="selectAll" @change="toggleAll" class="mr-1 rounded text-blue-600" /> Tümünü Seç ({{ filtrelenmis.length }})</label>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <label v-for="p in filtrelenmis" :key="p.id" class="flex items-start gap-2 px-2 py-1 hover:bg-blue-50 cursor-pointer border-b border-gray-100 text-[11px]">
                            <input type="checkbox" :value="p.id" v-model="selectedPersonelIds" class="mt-0.5 rounded text-blue-600" />
                            <span class="truncate">{{ toTitleCase(p.ad + ' ' + p.soyad) }}</span>
                        </label>
                    </div>
                    <div class="px-2 py-1 bg-blue-50 border-t text-[10px] text-blue-600 font-medium">{{ selectedPersonelIds.length }} seçili</div>
                </div>
                <!-- SAĞ -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <div class="px-3 py-2 bg-gradient-to-r from-green-50 to-green-100 border-b flex items-center gap-3 print:hidden">
                        <input v-model="tarihBaslangic" type="date" class="px-1.5 py-0.5 text-[11px] border rounded" />
                        <input v-model="tarihBitis" type="date" class="px-1.5 py-0.5 text-[11px] border rounded" />
                        <button @click="hesapla" :disabled="isProcessing" class="px-3 py-1 bg-blue-600 text-white text-[11px] font-bold rounded hover:bg-blue-700 disabled:opacity-50">
                            {{ isProcessing ? 'Hesaplanıyor...' : '▶ Hesapla' }}
                        </button>
                    </div>
                    <div v-if="sonuclar" class="flex-1 flex overflow-hidden">
                        <!-- Sonuç Listesi -->
                        <div class="w-48 border-r bg-gray-50 overflow-y-auto print:hidden">
                            <div v-for="s in sonuclar.sonuclar" :key="s.personel_id" @click="selectedSonuc = s"
                                class="px-2 py-1.5 border-b cursor-pointer text-[11px] transition"
                                :class="selectedSonuc?.personel_id === s.personel_id ? 'bg-blue-100 font-semibold' : 'hover:bg-gray-100'">
                                {{ s.ad }} {{ s.soyad }}
                            </div>
                        </div>
                        <!-- Detay -->
                        <div v-if="selectedSonuc" class="flex-1 overflow-y-auto p-4">
                            <div class="max-w-2xl mx-auto">
                                <div class="text-center mb-4 border-b pb-3">
                                    <div class="text-lg font-bold text-gray-800">{{ selectedSonuc.ad }} {{ selectedSonuc.soyad }}</div>
                                    <div class="text-xs text-gray-500">{{ selectedSonuc.bolum }} | Sicil: {{ selectedSonuc.sicil_no || '-' }}</div>
                                    <div class="text-xs text-gray-400 mt-1">{{ sonuclar.tarih_araligi.baslangic }} - {{ sonuclar.tarih_araligi.bitis }}</div>
                                </div>
                                <!-- Özet Kartları -->
                                <div class="grid grid-cols-4 gap-2 mb-4">
                                    <div class="bg-blue-50 border border-blue-200 rounded p-2 text-center">
                                        <div class="text-lg font-bold text-blue-700">{{ selectedSonuc.net_maas }}</div>
                                        <div class="text-[9px] text-blue-600">Net Maaş</div>
                                    </div>
                                    <div class="bg-green-50 border border-green-200 rounded p-2 text-center">
                                        <div class="text-lg font-bold text-green-700">{{ selectedSonuc.bordro.toplam }}</div>
                                        <div class="text-[9px] text-green-600">Toplam Hakediş</div>
                                    </div>
                                    <div class="bg-amber-50 border border-amber-200 rounded p-2 text-center">
                                        <div class="text-lg font-bold text-amber-700">{{ selectedSonuc.bordro.elden_odeme }}</div>
                                        <div class="text-[9px] text-amber-600">Elden Ödeme</div>
                                    </div>
                                    <div class="bg-red-50 border border-red-200 rounded p-2 text-center">
                                        <div class="text-lg font-bold text-red-700">{{ selectedSonuc.ozet.devamsizlik_gun }}</div>
                                        <div class="text-[9px] text-red-600">Devamsızlık</div>
                                    </div>
                                </div>
                                <!-- Bordro Detayı -->
                                <table class="w-full text-[11px] border-collapse mb-4">
                                    <thead><tr class="bg-gray-100"><th class="bth text-left">Kalem</th><th class="bth text-right">Gün</th><th class="bth text-right">Saat</th><th class="bth text-right">Ücret</th></tr></thead>
                                    <tbody>
                                        <tr v-for="item in bordroItems" :key="item.label" class="border-b">
                                            <td class="btd font-medium" :class="`text-${item.color}-700`">{{ item.label }}</td>
                                            <td class="btd text-right">{{ item.gun }}</td>
                                            <td class="btd text-right">{{ item.saat }}</td>
                                            <td class="btd text-right font-medium" :class="item.negative ? 'text-red-600' : ''">{{ item.negative ? '(' + item.ucret + ')' : item.ucret }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <!-- Ek Kalemler -->
                                <table class="w-full text-[11px] border-collapse">
                                    <tbody>
                                        <tr class="border-b"><td class="btd">Ek Ödeme</td><td class="btd text-right">{{ selectedSonuc.bordro.ek_odeme }}</td></tr>
                                        <tr class="border-b"><td class="btd">Yol Parası</td><td class="btd text-right">{{ selectedSonuc.bordro.yol_parasi }}</td></tr>
                                        <tr class="border-b"><td class="btd">Yemek</td><td class="btd text-right">{{ selectedSonuc.bordro.yemek }}</td></tr>
                                        <tr class="border-b"><td class="btd">Gün Fark</td><td class="btd text-right">{{ selectedSonuc.bordro.gun_fark }}</td></tr>
                                        <tr class="border-b"><td class="btd text-red-600">Avans (-)</td><td class="btd text-right text-red-600">({{ selectedSonuc.bordro.avans }})</td></tr>
                                        <tr class="border-b"><td class="btd text-red-600">Kesinti (-)</td><td class="btd text-right text-red-600">({{ selectedSonuc.bordro.kesinti }})</td></tr>
                                        <tr class="border-b"><td class="btd">Elden Ödeme</td><td class="btd text-right">{{ selectedSonuc.bordro.elden_odeme }}</td></tr>
                                        <tr class="bg-indigo-100 font-bold"><td class="btd text-lg">BANKAYA YATAN</td><td class="btd text-right text-lg">{{ selectedSonuc.bordro.toplam }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex-1 flex items-center justify-center text-gray-400">
                        <div class="text-center"><div class="text-xs font-medium">Kişi Bazında Maaş Ekstresi</div><div class="text-[10px] mt-1">Personel seçip tarih belirleyin</div></div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.bth { @apply px-2 py-1 border border-gray-300 text-[11px] font-semibold; }
.btd { @apply px-2 py-1 border border-gray-200 text-[11px]; }
@media print { .print\:hidden { display: none !important; } }
</style>
