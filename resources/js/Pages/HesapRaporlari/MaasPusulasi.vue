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
const firmaAdi = ref('');

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
    const headers = ['#', 'Adı Soyadı', 'Bölüm', 'Net Maaş', 'Normal Çalışma', 'FM %50', 'FM %100', 'Ek Ödeme', 'Yol Parası', 'Yemek', 'Gün Fark', 'Devamsızlık', 'Avans', 'Kesinti', 'Elden Ödeme', 'Bankaya Yatan'];
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
    const ws = XLSX.utils.aoa_to_sheet([['MAAŞ PUSULASI'], [`${sonuclar.value.tarih_araligi.baslangic} - ${sonuclar.value.tarih_araligi.bitis}`], [], headers, ...rows, totals]);
    ws['!cols'] = [{wch:4},{wch:25},{wch:18},{wch:14},{wch:14},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:12},{wch:14}];
    ws['!merges'] = [{s:{r:0,c:0},e:{r:0,c:15}},{s:{r:1,c:0},e:{r:1,c:15}}];
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Maaş Pusulası');
    XLSX.writeFile(wb, `maas_pusulasi_${tarihBaslangic.value}_${tarihBitis.value}.xlsx`);
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="flex flex-col h-full bg-white">
            <div class="bg-gradient-to-r from-[#e8eef8] to-[#dce4f2] border-b border-gray-300 px-4 py-2 flex items-center justify-between">
                <span class="font-bold text-sm text-gray-800">🧾 Maaş Pusulası</span>
                <div v-if="selectedSonuc" class="flex gap-2">
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
                    <div v-if="sonuclar" class="flex-1 flex overflow-hidden">
                        <!-- Kişi Listesi -->
                        <div class="w-48 border-r bg-gray-50 overflow-y-auto print:hidden">
                            <div v-for="s in sonuclar.sonuclar" :key="s.personel_id" @click="selectedSonuc = s"
                                class="px-2 py-1.5 border-b cursor-pointer text-[11px] transition"
                                :class="selectedSonuc?.personel_id === s.personel_id ? 'bg-blue-100 font-semibold' : 'hover:bg-gray-100'">
                                {{ s.ad }} {{ s.soyad }}
                            </div>
                        </div>
                        <!-- Pusula -->
                        <div v-if="selectedSonuc" class="flex-1 overflow-y-auto p-6 bg-gray-50">
                            <div class="max-w-md mx-auto bg-white border-2 border-gray-300 rounded-lg shadow-lg overflow-hidden">
                                <!-- Başlık -->
                                <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 text-white px-5 py-3 text-center">
                                    <div class="text-lg font-bold tracking-wide">MAAŞ PUSULASI</div>
                                    <div class="text-[10px] opacity-80 mt-0.5">{{ sonuclar.tarih_araligi.baslangic }} - {{ sonuclar.tarih_araligi.bitis }}</div>
                                </div>
                                <!-- Kişi Bilgileri -->
                                <div class="px-5 py-3 border-b bg-gray-50 grid grid-cols-2 gap-2 text-[11px]">
                                    <div><span class="text-gray-500">Adı Soyadı:</span><br><strong>{{ selectedSonuc.ad }} {{ selectedSonuc.soyad }}</strong></div>
                                    <div><span class="text-gray-500">Sicil No:</span><br><strong>{{ selectedSonuc.sicil_no || '-' }}</strong></div>
                                    <div><span class="text-gray-500">Bölüm:</span><br><strong>{{ selectedSonuc.bolum }}</strong></div>
                                    <div><span class="text-gray-500">Net Maaş:</span><br><strong class="text-indigo-700">{{ selectedSonuc.net_maas }} ₺</strong></div>
                                </div>
                                <!-- Hakediş -->
                                <div class="px-5 py-3">
                                    <div class="text-[10px] font-bold text-gray-500 mb-2 uppercase tracking-wider">Hakediş Kalemleri</div>
                                    <div class="space-y-1 text-[11px]">
                                        <div class="flex justify-between py-0.5"><span>Normal Çalışma ({{ selectedSonuc.bordro.normal_calisma.gun }} gün)</span><span class="font-medium">{{ selectedSonuc.bordro.normal_calisma.ucret }}</span></div>
                                        <div class="flex justify-between py-0.5"><span>Fazla Mesai %50 ({{ selectedSonuc.bordro.fazla_mesai_50.saat }})</span><span class="font-medium">{{ selectedSonuc.bordro.fazla_mesai_50.ucret }}</span></div>
                                        <div class="flex justify-between py-0.5"><span>Fazla Mesai %100 ({{ selectedSonuc.bordro.fazla_mesai_100.saat }})</span><span class="font-medium">{{ selectedSonuc.bordro.fazla_mesai_100.ucret }}</span></div>
                                        <div class="flex justify-between py-0.5"><span>Ek Ödeme</span><span class="font-medium">{{ selectedSonuc.bordro.ek_odeme }}</span></div>
                                        <div class="flex justify-between py-0.5"><span>Yol Parası</span><span class="font-medium">{{ selectedSonuc.bordro.yol_parasi }}</span></div>
                                        <div class="flex justify-between py-0.5"><span>Yemek</span><span class="font-medium">{{ selectedSonuc.bordro.yemek }}</span></div>
                                        <div class="flex justify-between py-0.5"><span>Gün Fark</span><span class="font-medium">{{ selectedSonuc.bordro.gun_fark }}</span></div>
                                    </div>
                                </div>
                                <!-- Kesintiler -->
                                <div class="px-5 py-3 border-t">
                                    <div class="text-[10px] font-bold text-red-500 mb-2 uppercase tracking-wider">Kesintiler</div>
                                    <div class="space-y-1 text-[11px]">
                                        <div class="flex justify-between py-0.5 text-red-600"><span>Devamsızlık ({{ selectedSonuc.bordro.devamsizlik.gun }} gün)</span><span>-{{ selectedSonuc.bordro.devamsizlik.ucret }}</span></div>
                                        <div class="flex justify-between py-0.5 text-red-600"><span>Avans</span><span>-{{ selectedSonuc.bordro.avans }}</span></div>
                                        <div class="flex justify-between py-0.5 text-red-600"><span>Kesinti</span><span>-{{ selectedSonuc.bordro.kesinti }}</span></div>
                                    </div>
                                </div>
                                <!-- Elden -->
                                <div v-if="parseNum(selectedSonuc.bordro.elden_odeme) > 0" class="px-5 py-2 border-t bg-amber-50">
                                    <div class="flex justify-between text-[11px]"><span class="text-amber-700 font-medium">Elden Ödeme</span><span class="font-bold text-amber-700">{{ selectedSonuc.bordro.elden_odeme }} ₺</span></div>
                                </div>
                                <!-- TOPLAM -->
                                <div class="bg-gradient-to-r from-green-600 to-green-700 text-white px-5 py-3 flex justify-between items-center">
                                    <span class="font-bold text-sm">BANKAYA YATAN</span>
                                    <span class="text-xl font-bold">{{ selectedSonuc.bordro.toplam }} ₺</span>
                                </div>
                                <!-- İmza -->
                                <div class="px-5 py-4 grid grid-cols-2 gap-4 text-[10px] text-gray-500 text-center">
                                    <div class="border-t border-dashed pt-8">İşveren İmza</div>
                                    <div class="border-t border-dashed pt-8">Personel İmza</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex-1 flex items-center justify-center text-gray-400">
                        <div class="text-center"><div class="text-xs font-medium">Maaş Pusulası</div><div class="text-[10px] mt-1">Personel seçip tarih belirleyin</div></div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@media print { .print\:hidden { display: none !important; } }
</style>
