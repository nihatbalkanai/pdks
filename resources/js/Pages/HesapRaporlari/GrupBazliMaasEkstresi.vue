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
        if (res.data.success) { sonuclar.value = res.data; }
    } catch (e) { Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu.', 'error'); }
    finally { isProcessing.value = false; }
};

const yazdir = () => window.print();

const excelExport = () => {
    if (!sonuclar.value || !gruplar.value.length) return;
    const headers = ['#', 'Adı Soyadı', 'Net Maaş', 'FM %50', 'FM %100', 'Ek Ödeme', 'Yol', 'Yemek', 'Avans', 'Kesinti', 'Elden', 'TOPLAM'];
    const data = [['GRUP BAZLI MAAŞ EKSTRESİ'], [`${sonuclar.value.tarih_araligi.baslangic} - ${sonuclar.value.tarih_araligi.bitis}`], []];
    gruplar.value.forEach(g => {
        data.push([`BÖLÜM: ${g.bolum} (${g.personeller.length} kişi)`]);
        data.push(headers);
        g.personeller.forEach((s, i) => {
            data.push([
                i + 1, s.ad + ' ' + s.soyad,
                parseNum(s.net_maas), parseNum(s.bordro.fazla_mesai_50.ucret),
                parseNum(s.bordro.fazla_mesai_100.ucret), parseNum(s.bordro.ek_odeme),
                parseNum(s.bordro.yol_parasi), parseNum(s.bordro.yemek),
                parseNum(s.bordro.avans), parseNum(s.bordro.kesinti),
                parseNum(s.bordro.elden_odeme), parseNum(s.bordro.toplam)
            ]);
        });
        data.push(['', 'BÖLÜM TOPLAMI', g.toplamMaas, g.toplamFm50, g.toplamFm100, g.toplamEk, g.toplamYol, g.toplamYemek, g.toplamAvans, g.toplamKesinti, g.toplamElden, g.toplamToplam]);
        data.push([]);
    });
    const gt = genelToplam.value;
    data.push(['', 'GENEL TOPLAM', gt.maas, gt.fm50, gt.fm100, gt.ek, gt.yol, gt.yemek, gt.avans, gt.kesinti, gt.elden, gt.toplam]);
    const ws = XLSX.utils.aoa_to_sheet(data);
    ws['!cols'] = [{wch:4},{wch:25},{wch:14},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:14}];
    ws['!merges'] = [{s:{r:0,c:0},e:{r:0,c:11}},{s:{r:1,c:0},e:{r:1,c:11}}];
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Grup Maaş');
    XLSX.writeFile(wb, `grup_bazli_maas_${tarihBaslangic.value}_${tarihBitis.value}.xlsx`);
};

// Bölüm bazlı gruplama
const gruplar = computed(() => {
    if (!sonuclar.value) return [];
    const map = {};
    sonuclar.value.sonuclar.forEach(s => {
        const bolum = s.bolum || 'Tanımsız';
        if (!map[bolum]) map[bolum] = { bolum, personeller: [], toplamMaas: 0, toplamToplam: 0, toplamFm50: 0, toplamFm100: 0, toplamEk: 0, toplamYol: 0, toplamYemek: 0, toplamAvans: 0, toplamKesinti: 0, toplamElden: 0 };
        map[bolum].personeller.push(s);
        map[bolum].toplamMaas += parseNum(s.net_maas);
        map[bolum].toplamToplam += parseNum(s.bordro.toplam);
        map[bolum].toplamFm50 += parseNum(s.bordro.fazla_mesai_50.ucret);
        map[bolum].toplamFm100 += parseNum(s.bordro.fazla_mesai_100.ucret);
        map[bolum].toplamEk += parseNum(s.bordro.ek_odeme);
        map[bolum].toplamYol += parseNum(s.bordro.yol_parasi);
        map[bolum].toplamYemek += parseNum(s.bordro.yemek);
        map[bolum].toplamAvans += parseNum(s.bordro.avans);
        map[bolum].toplamKesinti += parseNum(s.bordro.kesinti);
        map[bolum].toplamElden += parseNum(s.bordro.elden_odeme);
    });
    return Object.values(map).sort((a, b) => a.bolum.localeCompare(b.bolum, 'tr'));
});

const genelToplam = computed(() => {
    let t = { maas: 0, toplam: 0, fm50: 0, fm100: 0, ek: 0, yol: 0, yemek: 0, avans: 0, kesinti: 0, elden: 0, kisi: 0 };
    gruplar.value.forEach(g => {
        t.kisi += g.personeller.length;
        t.maas += g.toplamMaas; t.toplam += g.toplamToplam; t.fm50 += g.toplamFm50; t.fm100 += g.toplamFm100;
        t.ek += g.toplamEk; t.yol += g.toplamYol; t.yemek += g.toplamYemek;
        t.avans += g.toplamAvans; t.kesinti += g.toplamKesinti; t.elden += g.toplamElden;
    });
    return t;
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="flex flex-col h-full bg-white">
            <div class="bg-gradient-to-r from-[#e8eef8] to-[#dce4f2] border-b border-gray-300 px-4 py-2 flex items-center justify-between">
                <span class="font-bold text-sm text-gray-800">📁 Grup Bazlı Maaş Ekstresi</span>
                <div v-if="sonuclar" class="flex gap-2">
                    <button @click="excelExport" class="px-3 py-1 bg-white border border-green-300 rounded text-[10px] font-bold text-green-700 hover:bg-green-50">📄 Excel</button>
                    <button @click="yazdir" class="px-3 py-1 bg-white border border-gray-300 rounded text-[10px] font-bold hover:bg-gray-50">🖨️ Yazdır</button>
                </div>
            </div>
            <div class="flex flex-1 overflow-hidden">
                <!-- SOL -->
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
                        <button @click="hesapla" :disabled="isProcessing" class="px-3 py-1 bg-blue-600 text-white text-[11px] font-bold rounded disabled:opacity-50">
                            {{ isProcessing ? 'Hesaplanıyor...' : '▶ Hesapla' }}
                        </button>
                    </div>
                    <div v-if="sonuclar" class="flex-1 overflow-auto p-3">
                        <div class="text-center mb-3">
                            <div class="font-bold text-base text-gray-800">GRUP BAZLI MAAŞ EKSTRESİ</div>
                            <div class="text-[11px] text-gray-500">{{ sonuclar.tarih_araligi.baslangic }} - {{ sonuclar.tarih_araligi.bitis }}</div>
                        </div>
                        <!-- Grup Tabloları -->
                        <div v-for="grup in gruplar" :key="grup.bolum" class="mb-5">
                            <div class="bg-gradient-to-r from-indigo-100 to-indigo-50 px-3 py-1.5 rounded-t border border-indigo-200 flex justify-between">
                                <span class="font-bold text-[12px] text-indigo-800">📂 {{ grup.bolum }}</span>
                                <span class="text-[10px] text-indigo-600">{{ grup.personeller.length }} kişi</span>
                            </div>
                            <table class="w-full text-[10px] border-collapse border border-gray-200">
                                <thead><tr class="bg-gray-100">
                                    <th class="bth text-left">#</th><th class="bth text-left">Adı Soyadı</th><th class="bth text-right">Net Maaş</th>
                                    <th class="bth text-right">FM %50</th><th class="bth text-right">FM %100</th><th class="bth text-right">Ek Ödeme</th>
                                    <th class="bth text-right">Yol</th><th class="bth text-right">Yemek</th>
                                    <th class="bth text-right text-red-600">Avans</th><th class="bth text-right text-red-600">Kesinti</th>
                                    <th class="bth text-right">Elden</th><th class="bth text-right font-bold">TOPLAM</th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="(s, i) in grup.personeller" :key="s.personel_id" class="border-b hover:bg-blue-50/30">
                                        <td class="btd">{{ i + 1 }}</td>
                                        <td class="btd font-medium">{{ s.ad }} {{ s.soyad }}</td>
                                        <td class="btd text-right">{{ s.net_maas }}</td>
                                        <td class="btd text-right">{{ s.bordro.fazla_mesai_50.ucret }}</td>
                                        <td class="btd text-right">{{ s.bordro.fazla_mesai_100.ucret }}</td>
                                        <td class="btd text-right">{{ s.bordro.ek_odeme }}</td>
                                        <td class="btd text-right">{{ s.bordro.yol_parasi }}</td>
                                        <td class="btd text-right">{{ s.bordro.yemek }}</td>
                                        <td class="btd text-right text-red-600">{{ s.bordro.avans }}</td>
                                        <td class="btd text-right text-red-600">{{ s.bordro.kesinti }}</td>
                                        <td class="btd text-right">{{ s.bordro.elden_odeme }}</td>
                                        <td class="btd text-right font-bold">{{ s.bordro.toplam }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-indigo-50 font-bold text-[10px]">
                                        <td colspan="2" class="btd">Bölüm Toplamı</td>
                                        <td class="btd text-right">{{ fmtPara(grup.toplamMaas) }}</td>
                                        <td class="btd text-right">{{ fmtPara(grup.toplamFm50) }}</td>
                                        <td class="btd text-right">{{ fmtPara(grup.toplamFm100) }}</td>
                                        <td class="btd text-right">{{ fmtPara(grup.toplamEk) }}</td>
                                        <td class="btd text-right">{{ fmtPara(grup.toplamYol) }}</td>
                                        <td class="btd text-right">{{ fmtPara(grup.toplamYemek) }}</td>
                                        <td class="btd text-right text-red-600">{{ fmtPara(grup.toplamAvans) }}</td>
                                        <td class="btd text-right text-red-600">{{ fmtPara(grup.toplamKesinti) }}</td>
                                        <td class="btd text-right">{{ fmtPara(grup.toplamElden) }}</td>
                                        <td class="btd text-right text-indigo-700">{{ fmtPara(grup.toplamToplam) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <!-- Genel Toplam -->
                        <div class="mt-3 bg-gradient-to-r from-green-100 to-green-200 border border-green-300 rounded p-3">
                            <div class="font-bold text-sm text-green-800 mb-2">GENEL TOPLAM ({{ genelToplam.kisi }} kişi)</div>
                            <div class="grid grid-cols-5 gap-3 text-[11px]">
                                <div><span class="text-gray-600">Net Maaş:</span><br><strong>{{ fmtPara(genelToplam.maas) }} ₺</strong></div>
                                <div><span class="text-gray-600">FM %50+%100:</span><br><strong>{{ fmtPara(genelToplam.fm50 + genelToplam.fm100) }} ₺</strong></div>
                                <div><span class="text-gray-600">Ek+Yol+Yemek:</span><br><strong>{{ fmtPara(genelToplam.ek + genelToplam.yol + genelToplam.yemek) }} ₺</strong></div>
                                <div><span class="text-red-600">Avans+Kesinti:</span><br><strong class="text-red-600">{{ fmtPara(genelToplam.avans + genelToplam.kesinti) }} ₺</strong></div>
                                <div><span class="text-green-700 font-bold">Bankaya Yatan:</span><br><strong class="text-lg text-green-700">{{ fmtPara(genelToplam.toplam) }} ₺</strong></div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex-1 flex items-center justify-center text-gray-400">
                        <div class="text-center"><div class="text-xs font-medium">Grup Bazlı Maaş Ekstresi</div><div class="text-[10px] mt-1">Personel seçip tarih belirleyin</div></div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.bth { @apply px-1.5 py-1 border border-gray-300 text-[10px] font-semibold; }
.btd { @apply px-1.5 py-0.5 border border-gray-200 text-[10px]; }
@media print { .print\:hidden { display: none !important; } }
</style>
