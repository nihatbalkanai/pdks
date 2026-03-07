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
    return props.personeller.filter(p => (p.ad + ' ' + p.soyad).toLocaleLowerCase('tr-TR').includes(q) || (p.sicil_no || '').includes(q));
});

const toggleAll = () => { selectedPersonelIds.value = selectAll.value ? filtrelenmis.value.map(p => p.id) : []; };

const parseNum = (v) => { if (!v) return 0; if (typeof v === 'number') return v; return parseFloat(String(v).replace(/\./g, '').replace(',', '.')) || 0; };

const fmtPara = (v) => parseNum(v).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const toplamUcret = (items, path) => {
    let t = 0; items.forEach(s => { t += parseNum(path(s)); }); return fmtPara(t);
};

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
        if (res.data.success) { sonuclar.value = res.data; Swal.fire({ title: 'Başarılı!', text: `${res.data.sonuclar.length} personel hesaplandı.`, icon: 'success', timer: 2000 }); }
    } catch (e) { Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu.', 'error'); }
    finally { isProcessing.value = false; }
};

const yazdir = () => window.print();

const excelExport = () => {
    if (!sonuclar.value) return;
    const headers = ['#', 'Adı Soyadı', 'Bölüm', 'Net Maaş', 'Normal Çalışma', 'FM %50', 'FM %100', 'Ek Ödeme', 'Yol Parası', 'Yemek', 'Gün Fark', 'Devamsızlık', 'Avans', 'Kesinti', 'Elden', 'TOPLAM'];
    const rows = sonuclar.value.sonuclar.map((s, i) => [
        i + 1, s.ad + ' ' + s.soyad, s.bolum,
        parseNum(s.net_maas), parseNum(s.bordro.normal_calisma.ucret),
        parseNum(s.bordro.fazla_mesai_50.ucret), parseNum(s.bordro.fazla_mesai_100.ucret),
        parseNum(s.bordro.ek_odeme), parseNum(s.bordro.yol_parasi), parseNum(s.bordro.yemek),
        parseNum(s.bordro.gun_fark), parseNum(s.bordro.devamsizlik.ucret),
        parseNum(s.bordro.avans), parseNum(s.bordro.kesinti),
        parseNum(s.bordro.elden_odeme), parseNum(s.bordro.toplam)
    ]);
    // Toplam satırı
    const totals = ['', 'TOPLAM', '', ...Array(13).fill(0)];
    rows.forEach(r => { for (let i = 3; i < 16; i++) totals[i] += r[i]; });
    const ws = XLSX.utils.aoa_to_sheet([['GENEL BAZDA MAAŞ EKSTRESİ'], [`${sonuclar.value.tarih_araligi.baslangic} - ${sonuclar.value.tarih_araligi.bitis}`], [], headers, ...rows, totals]);
    ws['!cols'] = [{wch:4},{wch:25},{wch:18},{wch:14},{wch:14},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:14}];
    ws['!merges'] = [{s:{r:0,c:0},e:{r:0,c:15}},{s:{r:1,c:0},e:{r:1,c:15}}];
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Genel Maaş');
    XLSX.writeFile(wb, `genel_maas_ekstresi_${tarihBaslangic.value}_${tarihBitis.value}.xlsx`);
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="flex flex-col h-full bg-white">
            <div class="bg-gradient-to-r from-[#e8eef8] to-[#dce4f2] border-b border-gray-300 px-4 py-2 flex items-center justify-between">
                <span class="font-bold text-sm text-gray-800">📊 Genel Bazda Maaş Ekstresi</span>
                <div v-if="sonuclar" class="flex gap-2">
                    <button @click="excelExport" class="px-3 py-1 bg-white border border-green-300 rounded text-[10px] font-bold text-green-700 hover:bg-green-50">📄 Excel</button>
                    <button @click="yazdir" class="px-3 py-1 bg-white border border-gray-300 rounded text-[10px] font-bold hover:bg-gray-50">🖨️ Yazdır</button>
                </div>
            </div>
            <div class="flex flex-1 overflow-hidden">
                <!-- SOL: Personel -->
                <div class="w-56 border-r border-gray-300 flex flex-col bg-gray-50 print:hidden">
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
                    <div v-if="sonuclar" class="flex-1 overflow-auto p-3">
                        <div class="text-center mb-3">
                            <div class="font-bold text-base text-gray-800">GENEL BAZDA MAAŞ EKSTRESİ</div>
                            <div class="text-[11px] text-gray-500">{{ sonuclar.tarih_araligi.baslangic }} - {{ sonuclar.tarih_araligi.bitis }}</div>
                        </div>
                        <table class="w-full text-[10px] border-collapse">
                            <thead>
                                <tr class="bg-gradient-to-r from-indigo-100 to-indigo-200">
                                    <th class="bth text-left">#</th>
                                    <th class="bth text-left">Adı Soyadı</th>
                                    <th class="bth text-left">Bölüm</th>
                                    <th class="bth text-right">Net Maaş</th>
                                    <th class="bth text-right">Normal Çalışma</th>
                                    <th class="bth text-right">FM %50</th>
                                    <th class="bth text-right">FM %100</th>
                                    <th class="bth text-right">Ek Ödeme</th>
                                    <th class="bth text-right">Yol Parası</th>
                                    <th class="bth text-right">Yemek</th>
                                    <th class="bth text-right">Gün Fark</th>
                                    <th class="bth text-right text-red-600">Devamsızlık</th>
                                    <th class="bth text-right text-red-600">Avans</th>
                                    <th class="bth text-right text-red-600">Kesinti</th>
                                    <th class="bth text-right">Elden</th>
                                    <th class="bth text-right font-bold bg-indigo-200">TOPLAM</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(s, i) in sonuclar.sonuclar" :key="s.personel_id" class="border-b hover:bg-blue-50/40">
                                    <td class="btd">{{ i + 1 }}</td>
                                    <td class="btd text-left font-medium">{{ s.ad }} {{ s.soyad }}</td>
                                    <td class="btd text-left">{{ s.bolum }}</td>
                                    <td class="btd text-right">{{ s.net_maas }}</td>
                                    <td class="btd text-right">{{ s.bordro.normal_calisma.ucret }}</td>
                                    <td class="btd text-right">{{ s.bordro.fazla_mesai_50.ucret }}</td>
                                    <td class="btd text-right">{{ s.bordro.fazla_mesai_100.ucret }}</td>
                                    <td class="btd text-right">{{ s.bordro.ek_odeme }}</td>
                                    <td class="btd text-right">{{ s.bordro.yol_parasi }}</td>
                                    <td class="btd text-right">{{ s.bordro.yemek }}</td>
                                    <td class="btd text-right">{{ s.bordro.gun_fark }}</td>
                                    <td class="btd text-right text-red-600">({{ s.bordro.devamsizlik.ucret }})</td>
                                    <td class="btd text-right text-red-600">({{ s.bordro.avans }})</td>
                                    <td class="btd text-right text-red-600">({{ s.bordro.kesinti }})</td>
                                    <td class="btd text-right">{{ s.bordro.elden_odeme }}</td>
                                    <td class="btd text-right font-bold bg-indigo-50">{{ s.bordro.toplam }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-indigo-100 font-bold">
                                    <td colspan="3" class="btd text-left">TOPLAM ({{ sonuclar.sonuclar.length }} kişi)</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.net_maas) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.normal_calisma.ucret) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.fazla_mesai_50.ucret) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.fazla_mesai_100.ucret) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.ek_odeme) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.yol_parasi) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.yemek) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.gun_fark) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.devamsizlik.ucret) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.avans) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.kesinti) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.elden_odeme) }}</td>
                                    <td class="btd text-right font-bold bg-indigo-200">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.toplam) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div v-else class="flex-1 flex items-center justify-center text-gray-400">
                        <div class="text-center"><div class="text-xs font-medium">Genel Bazda Maaş Ekstresi</div><div class="text-[10px] mt-1">Personel seçip tarih belirleyin</div></div>
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
