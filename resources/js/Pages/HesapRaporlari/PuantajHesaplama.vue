<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Swal from 'sweetalert2';
import axios from 'axios';
import XLSX from 'xlsx-js-style';

const props = defineProps({ personeller: Array });

const selectedPersonelIds = ref([]);
const selectAll = ref(false);
const ilkGirisSonCikis = ref(true);
const tarihBaslangic = ref('');
const tarihBitis = ref('');
const isProcessing = ref(false);
const progress = ref(0);
const sonuclar = ref(null);
const selectedSonuc = ref(null);
const aramaText = ref('');
const gorunumTipi = ref('bordro'); // bordro, detay
const isInfoOpen = ref(false);

const toTitleCase = (str) => {
    if (!str) return '';
    return str
        .toLocaleLowerCase('tr-TR')
        .replace(/(^|\s)(\S)/g, (m, space, char) => space + char.toLocaleUpperCase('tr-TR'));
};

const filtrelenmisPersoneller = computed(() => {
    if (!aramaText.value) return props.personeller;
    const q = aramaText.value.toLocaleLowerCase('tr-TR');
    return props.personeller.filter(p =>
        (p.ad + ' ' + p.soyad).toLocaleLowerCase('tr-TR').includes(q) ||
        (p.sicil_no || '').toLowerCase().includes(q)
    );
});

const toggleAll = () => {
    selectedPersonelIds.value = selectAll.value ? filtrelenmisPersoneller.value.map(p => p.id) : [];
};

const hesapla = async () => {
    if (selectedPersonelIds.value.length === 0) { Swal.fire('Uyarı', 'En az bir personel seçiniz.', 'warning'); return; }
    if (!tarihBaslangic.value || !tarihBitis.value) { Swal.fire('Uyarı', 'Tarih aralığını seçiniz.', 'warning'); return; }

    isProcessing.value = true;
    progress.value = 10;
    sonuclar.value = null;

    try {
        const pi = setInterval(() => { if (progress.value < 90) progress.value += Math.random() * 15; }, 300);
        const res = await axios.post(route('hesap-raporlari.puantaj-hesapla'), {
            personel_ids: selectedPersonelIds.value,
            tarih_baslangic: tarihBaslangic.value,
            tarih_bitis: tarihBitis.value,
            ilk_giris_son_cikis: ilkGirisSonCikis.value,
        });
        clearInterval(pi);
        progress.value = 100;
        if (res.data.success) {
            sonuclar.value = res.data;
            if (res.data.sonuclar.length > 0) selectedSonuc.value = res.data.sonuclar[0];
            Swal.fire({ title: 'Başarılı!', text: `${res.data.sonuclar.length} personel hesaplandı.`, icon: 'success', timer: 2000 });
        }
    } catch (e) {
        Swal.fire('Hata', e.response?.data?.message || 'Hesaplama sırasında hata oluştu.', 'error');
    } finally {
        isProcessing.value = false;
        setTimeout(() => { progress.value = 0; }, 1500);
    }
};

const durumRenk = (d) => ({
    normal: 'text-green-700 bg-green-50', gec_gelme: 'text-orange-700 bg-orange-50',
    devamsiz: 'text-red-700 bg-red-50', ucretli_izin: 'text-blue-700 bg-blue-50',
    ucretsiz_izin: 'text-purple-700 bg-purple-50', resmi_tatil: 'text-purple-700 bg-purple-50',
    hafta_sonu: 'text-gray-500 bg-gray-100', tatil_calisma: 'text-amber-700 bg-amber-50',
    hafta_sonu_calisma: 'text-amber-700 bg-amber-50',
}[d] || 'text-gray-600 bg-gray-50');

const durumEtiket = (d) => ({
    normal: 'Normal', gec_gelme: 'Geç Gelme', devamsiz: 'Devamsız',
    ucretli_izin: 'Ücretli İzin', ucretsiz_izin: 'Ücretsiz İzin',
    resmi_tatil: 'Resmi Tatil', hafta_sonu: 'Hafta Sonu',
    tatil_calisma: 'Tatil Çalışma', hafta_sonu_calisma: 'H.S. Çalışma',
}[d] || d);

const formatSaat = (s) => { if (!s && s !== 0) return '-'; const sa = Math.floor(s); const dk = Math.round((s - sa) * 60); return `${sa}s ${dk}dk`; };

// Sayısal değer parse (Türkçe format: 1.234,56)
const parseNum = (v) => {
    if (!v) return 0;
    if (typeof v === 'number') return v;
    return parseFloat(String(v).replace(/\./g, '').replace(',', '.')) || 0;
};

// Saat topla (HH:MM formatları)
const toplamSaat = (items, path) => {
    let topDk = 0;
    items.forEach(s => {
        const val = path(s);
        if (val && val.includes(':')) {
            const [h, m] = val.split(':').map(Number);
            topDk += h * 60 + m;
        }
    });
    return `${String(Math.floor(topDk / 60)).padStart(2, '0')}:${String(topDk % 60).padStart(2, '0')}`;
};

// Ücret topla ve formatla
const toplamUcret = (items, path) => {
    let top = 0;
    items.forEach(s => { top += parseNum(path(s)); });
    return top.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// Gün topla
const toplamGun = (items, path) => {
    let top = 0;
    items.forEach(s => { top += (typeof path(s) === 'number' ? path(s) : parseNum(path(s))); });
    return top;
};

// ===== EXCEL EXPORT (Renkli) =====
const excelExport = () => {
    if (!sonuclar.value || !sonuclar.value.sonuclar.length) {
        Swal.fire('Uyarı', 'Önce hesaplama yapınız.', 'warning');
        return;
    }

    const data = sonuclar.value.sonuclar;
    const donem = sonuclar.value.tarih_araligi;

    // Renk paleti (UI ile aynı)
    const colors = {
        normal:   { bg: '22C55E', bgLight: 'E8F5E9', text: 'FFFFFF' },
        fm50:     { bg: 'EAB308', bgLight: 'FFF8E1', text: 'FFFFFF' },
        fm100:    { bg: 'F97316', bgLight: 'FFF3E0', text: 'FFFFFF' },
        dev:      { bg: 'EF4444', bgLight: 'FFEBEE', text: 'FFFFFF' },
        ht:       { bg: '9333EA', bgLight: 'F3E5F5', text: 'FFFFFF' },
        ui:       { bg: '6366F1', bgLight: 'E8EAF6', text: 'FFFFFF' },
        uci:      { bg: '3B82F6', bgLight: 'E3F2FD', text: 'FFFFFF' },
        header:   { bg: '374151', text: 'FFFFFF' },
        subHdr:   { bg: 'E5E7EB', text: '111827' },
        toplam:   { bg: 'D1D5DB', text: '111827' },
        title:    { bg: '1E3A5F', text: 'FFFFFF' },
    };

    const border = { style: 'thin', color: { rgb: 'BDBDBD' } };
    const borders = { top: border, bottom: border, left: border, right: border };

    const mkStyle = (bgColor, fontColor, bold = false, hAlign = 'center', numFmt) => {
        const s = {
            fill: { fgColor: { rgb: bgColor } },
            font: { bold, color: { rgb: fontColor }, name: 'Calibri', sz: 9 },
            alignment: { horizontal: hAlign, vertical: 'center', wrapText: true },
            border: borders
        };
        if (numFmt) s.numFmt = numFmt;
        return s;
    };

    const saatToSerial = (s) => {
        if (!s || !s.includes(':')) return 0;
        const [h, m] = s.split(':').map(Number);
        return (h * 60 + m) / (24 * 60);
    };

    // Sütun grupları: [startCol, endCol, colorKey]
    const colGroups = [
        [8, 10, 'normal'], [11, 12, 'fm50'], [13, 15, 'fm100'],
        [16, 18, 'dev'], [19, 21, 'ht'], [22, 24, 'ui'], [25, 27, 'uci']
    ];

    const getColColor = (ci) => {
        for (const [s, e, key] of colGroups) {
            if (ci >= s && ci <= e) return key;
        }
        return null;
    };

    // === WORKSHEET VERİLERİ ===
    const ws = {};
    const totalCols = 32;

    // Row 0: Başlık
    ws['A1'] = { v: 'GENEL PERSONEL BORDROSU (ÜCRETLER)', t: 's', s: mkStyle(colors.title.bg, colors.title.text, true) };
    // Row 1: Dönem
    ws['A2'] = { v: `Dönem: ${donem.baslangic} - ${donem.bitis}`, t: 's', s: mkStyle('F5F5F5', '374151', false) };

    // Row 3 (idx=3): Ana başlıklar
    const hdr1Labels = [
        'Kart No', 'Sicil No', 'Adı', 'Soyadı', 'Bölüm', 'Net Maaş', 'Giriş T.', 'Çıkış T.',
        'NORMAL ÇALIŞMA', '', '', 'FAZLA MESAİ %50', '', 'FAZLA MESAİ %100', '', '',
        'DEVAMSIZLIK', '', '', 'HAFTA TATİLİ', '', '', 'ÜCRETSİZ İZİN', '', '',
        'ÜCRETLİ İZİN', '', '', 'Avans', 'Ek Ödeme', 'Yol Parası', 'Yemek', 'Gün Fark', 'Elden Ödenen', 'Kesinti', 'TOPLAM'
    ];
    hdr1Labels.forEach((label, ci) => {
        const addr = XLSX.utils.encode_cell({ r: 3, c: ci });
        const ck = getColColor(ci);
        const bg = ck ? colors[ck].bg : colors.header.bg;
        const txt = ck ? colors[ck].text : colors.header.text;
        ws[addr] = { v: label || '', t: 's', s: mkStyle(bg, txt, true) };
    });

    // Row 4 (idx=4): Alt başlıklar
    const hdr2Labels = [
        '', '', '', '', '', '', '', '',
        'Gün', 'Saat', 'Ücret', 'Saat', 'Ücret', 'Gün', 'Saat', 'Ücret',
        'Gün', 'Saat', 'Ücret', 'Gün', 'Saat', 'Ücret', 'Gün', 'Saat', 'Ücret',
        'Gün', 'Saat', 'Ücret', '', '', '', '', '', '', '', ''
    ];
    hdr2Labels.forEach((label, ci) => {
        const addr = XLSX.utils.encode_cell({ r: 4, c: ci });
        const ck = getColColor(ci);
        const bg = ck ? colors[ck].bg : colors.subHdr.bg;
        const txt = ck ? colors[ck].text : colors.subHdr.text;
        ws[addr] = { v: label || '', t: 's', s: mkStyle(bg, txt, true, 'center') };
    });

    // Veri satırları (row 5+)
    data.forEach((s, ri) => {
        const row = ri + 5;
        const vals = [
            { v: s.kart_no, t: 's' }, { v: s.sicil_no || '', t: 's' },
            { v: s.ad, t: 's' }, { v: s.soyad, t: 's' }, { v: s.bolum, t: 's' },
            { v: parseNum(s.net_maas), t: 'n', z: '#,##0.00' },
            { v: s.giris_tarihi || '', t: 's' }, { v: s.cikis_tarihi || '', t: 's' },
            // Normal
            { v: parseNum(s.bordro.normal_calisma.gun), t: 'n' },
            { v: saatToSerial(s.bordro.normal_calisma.saat), t: 'n', z: '[h]:mm' },
            { v: parseNum(s.bordro.normal_calisma.ucret), t: 'n', z: '#,##0.00' },
            // FM50
            { v: saatToSerial(s.bordro.fazla_mesai_50.saat), t: 'n', z: '[h]:mm' },
            { v: parseNum(s.bordro.fazla_mesai_50.ucret), t: 'n', z: '#,##0.00' },
            // FM100
            { v: parseNum(s.bordro.fazla_mesai_100.gun), t: 'n' },
            { v: saatToSerial(s.bordro.fazla_mesai_100.saat), t: 'n', z: '[h]:mm' },
            { v: parseNum(s.bordro.fazla_mesai_100.ucret), t: 'n', z: '#,##0.00' },
            // Devamsızlık
            { v: parseNum(s.bordro.devamsizlik.gun), t: 'n' },
            { v: saatToSerial(s.bordro.devamsizlik.saat), t: 'n', z: '[h]:mm' },
            { v: parseNum(s.bordro.devamsizlik.ucret), t: 'n', z: '#,##0.00' },
            // Hafta Tatili
            { v: parseNum(s.bordro.hafta_tatili.gun), t: 'n' },
            { v: saatToSerial(s.bordro.hafta_tatili.saat), t: 'n', z: '[h]:mm' },
            { v: parseNum(s.bordro.hafta_tatili.ucret), t: 'n', z: '#,##0.00' },
            // Ücretsiz İzin
            { v: parseNum(s.bordro.ucretsiz_izin.gun), t: 'n' },
            { v: saatToSerial(s.bordro.ucretsiz_izin.saat), t: 'n', z: '[h]:mm' },
            { v: parseNum(s.bordro.ucretsiz_izin.ucret), t: 'n', z: '#,##0.00' },
            // Ücretli İzin
            { v: parseNum(s.bordro.ucretli_izin.gun), t: 'n' },
            { v: saatToSerial(s.bordro.ucretli_izin.saat), t: 'n', z: '[h]:mm' },
            { v: parseNum(s.bordro.ucretli_izin.ucret), t: 'n', z: '#,##0.00' },
            // Diğer
            { v: parseNum(s.bordro.avans), t: 'n', z: '#,##0.00' },
            { v: parseNum(s.bordro.ek_odeme), t: 'n', z: '#,##0.00' },
            { v: parseNum(s.bordro.yol_parasi), t: 'n', z: '#,##0.00' },
            { v: parseNum(s.bordro.yemek), t: 'n', z: '#,##0.00' },
            { v: parseNum(s.bordro.gun_fark), t: 'n', z: '#,##0.00' },
            { v: parseNum(s.bordro.elden_odeme), t: 'n', z: '#,##0.00' },
            { v: parseNum(s.bordro.kesinti), t: 'n', z: '#,##0.00' },
            { v: parseNum(s.bordro.toplam), t: 'n', z: '#,##0.00' },
        ];

        vals.forEach((cell, ci) => {
            const addr = XLSX.utils.encode_cell({ r: row, c: ci });
            const ck = getColColor(ci);
            const bgClr = ck ? colors[ck].bgLight : 'FFFFFF';
            const hAlign = (cell.t === 'n' && cell.z === '#,##0.00') ? 'right' : (cell.t === 's' && ci >= 2 && ci <= 4 ? 'left' : 'center');
            ws[addr] = {
                v: cell.v, t: cell.t,
                s: {
                    fill: { fgColor: { rgb: bgClr } },
                    font: { name: 'Calibri', sz: 9, color: { rgb: ci >= 31 ? '1E3A5F' : '374151' }, bold: ci >= 31 },
                    alignment: { horizontal: hAlign, vertical: 'center' },
                    border: borders,
                    numFmt: cell.z || undefined
                }
            };
            if (cell.z) ws[addr].z = cell.z;
        });
    });

    // Toplam satırı
    const topRow = data.length + 5;
    const topVals = ['TOPLAM', '', '', '', ''];
    for (let ci = 5; ci < 36; ci++) {
        // Tarih sütunlarını atla (6=Giriş T., 7=Çıkış T.)
        if (ci === 6 || ci === 7) { topVals.push(''); continue; }
        let sum = 0;
        data.forEach((s, ri) => {
            const addr = XLSX.utils.encode_cell({ r: ri + 5, c: ci });
            if (ws[addr] && ws[addr].t === 'n') sum += ws[addr].v;
        });
        topVals.push(sum);
    }
    topVals.forEach((val, ci) => {
        const addr = XLSX.utils.encode_cell({ r: topRow, c: ci });
        const ck = getColColor(ci);
        const bg = ck ? colors[ck].bg : colors.toplam.bg;
        const txt = ck ? 'FFFFFF' : colors.toplam.text;
        const isNum = typeof val === 'number';
        const saatCols = [9,11,14,17,20,23,26];
        const ucretCols = [5,10,12,15,18,21,24,27,28,29,30,31,32,33,34,35];
        let z = undefined;
        if (saatCols.includes(ci)) z = '[h]:mm';
        else if (ucretCols.includes(ci)) z = '#,##0.00';
        ws[addr] = {
            v: val, t: isNum ? 'n' : 's',
            s: mkStyle(bg, txt, true, isNum ? 'right' : 'left', z)
        };
        if (z) ws[addr].z = z;
    });

    // Aralık
    ws['!ref'] = XLSX.utils.encode_range({ s: { r: 0, c: 0 }, e: { r: topRow, c: 35 } });

    // Sütun genişlikleri
    ws['!cols'] = [
        {wch:8}, {wch:10}, {wch:14}, {wch:14}, {wch:16}, {wch:12}, {wch:12}, {wch:12},
        {wch:6}, {wch:10}, {wch:12}, {wch:10}, {wch:12}, {wch:6}, {wch:10}, {wch:12},
        {wch:6}, {wch:10}, {wch:12}, {wch:6}, {wch:10}, {wch:12}, {wch:6}, {wch:10}, {wch:12},
        {wch:6}, {wch:10}, {wch:12}, {wch:12}, {wch:12}, {wch:12}, {wch:12}, {wch:12}, {wch:12}, {wch:12}, {wch:14}
    ];

    // Satır yükseklikleri
    ws['!rows'] = [{ hpx: 28 }, { hpx: 18 }, { hpx: 6 }, { hpx: 24 }, { hpx: 20 }];

    // Birleştirme
    ws['!merges'] = [
        {s:{r:0,c:0}, e:{r:0,c:35}},
        {s:{r:1,c:0}, e:{r:1,c:35}},
        {s:{r:3,c:8}, e:{r:3,c:10}},
        {s:{r:3,c:11}, e:{r:3,c:12}},
        {s:{r:3,c:13}, e:{r:3,c:15}},
        {s:{r:3,c:16}, e:{r:3,c:18}},
        {s:{r:3,c:19}, e:{r:3,c:21}},
        {s:{r:3,c:22}, e:{r:3,c:24}},
        {s:{r:3,c:25}, e:{r:3,c:27}},
    ];
    for (let c = 0; c <= 7; c++) ws['!merges'].push({s:{r:3,c}, e:{r:4,c}});
    for (let c = 28; c <= 35; c++) ws['!merges'].push({s:{r:3,c}, e:{r:4,c}});

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Puantaj');

    const fileName = `Puantaj_${donem.baslangic.replace(/\./g,'')}_${donem.bitis.replace(/\./g,'')}.xlsx`;
    XLSX.writeFile(wb, fileName);

    Swal.fire({ title: 'Excel Oluşturuldu!', text: fileName, icon: 'success', timer: 2000 });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="flex flex-col h-full bg-white">
            <!-- Başlık -->
            <div class="bg-gradient-to-r from-[#e8eef8] to-[#dce4f2] border-b border-gray-300 px-4 py-2 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm1 8a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                    <span class="font-bold text-sm text-gray-800">Puantaj Hesaplama — Genel Personel Bordrosu</span>
                </div>
                <div v-if="sonuclar" class="flex gap-1">
                    <button @click="gorunumTipi = 'bordro'" class="px-2 py-0.5 text-[10px] rounded transition" :class="gorunumTipi === 'bordro' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border'">Bordro</button>
                    <button @click="gorunumTipi = 'detay'" class="px-2 py-0.5 text-[10px] rounded transition" :class="gorunumTipi === 'detay' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border'">Günlük Detay</button>
                </div>
            </div>

            <div class="flex flex-1 overflow-hidden">
                <!-- SOL: Personel Seçimi -->
                <div class="w-56 border-r border-gray-300 flex flex-col bg-gray-50 print:hidden">
                    <div class="px-2 py-1 border-b">
                        <input v-model="aramaText" type="text" placeholder="🔍 Ara..." class="w-full px-2 py-0.5 text-[11px] border border-gray-300 rounded focus:border-blue-400 outline-none" />
                    </div>
                    <div class="px-2 py-1 border-b bg-white flex items-center">
                        <label class="flex items-center text-[11px] cursor-pointer">
                            <input type="checkbox" v-model="selectAll" @change="toggleAll" class="mr-1 rounded border-gray-300 text-blue-600" />
                            Tümünü Seç ({{ filtrelenmisPersoneller.length }})
                        </label>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <label v-for="p in filtrelenmisPersoneller" :key="p.id"
                            class="flex items-start gap-2 px-2 py-1 hover:bg-blue-50 cursor-pointer border-b border-gray-100 text-[11px]">
                            <input type="checkbox" :value="p.id" v-model="selectedPersonelIds" class="mt-0.5 rounded border-gray-300 text-blue-600" />
                            <span class="truncate">{{ toTitleCase(p.ad + ' ' + p.soyad) }}</span>
                        </label>
                    </div>
                    <div class="px-2 py-1 bg-blue-50 border-t text-[10px] text-blue-600 font-medium">{{ selectedPersonelIds.length }} seçili</div>
                </div>

                <!-- SAĞ: Ayarlar + Sonuçlar -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <!-- Ayarlar -->
                    <div class="px-3 py-2 bg-gradient-to-r from-green-50 to-green-100 border-b flex items-center gap-3 flex-wrap">
                        <div class="flex items-center gap-2">
                            <label class="text-[10px] text-gray-600">Tarihinden:</label>
                            <input v-model="tarihBaslangic" type="date" class="px-1.5 py-0.5 text-[11px] border border-gray-300 rounded outline-none" />
                            <label class="text-[10px] text-gray-600">Tarihine:</label>
                            <input v-model="tarihBitis" type="date" class="px-1.5 py-0.5 text-[11px] border border-gray-300 rounded outline-none" />
                        </div>
                        <label class="flex items-center text-[11px] cursor-pointer">
                            <input type="checkbox" v-model="ilkGirisSonCikis" class="mr-1 rounded text-green-600" />
                            İlk giriş-son çıkış
                        </label>
                        <button @click="hesapla" :disabled="isProcessing"
                            class="px-3 py-1 bg-gradient-to-b from-blue-500 to-blue-600 text-white text-[11px] font-bold rounded shadow hover:from-blue-600 hover:to-blue-700 disabled:opacity-50 transition">
                            {{ isProcessing ? 'Hesaplanıyor...' : '▶ İşlemi Başlat' }}
                        </button>
                        <div class="ml-auto flex items-center gap-2">
                            <button v-if="sonuclar && sonuclar.sonuclar.length" @click="excelExport"
                                class="flex items-center gap-1 px-3 py-1 text-green-700 bg-white border border-green-300 rounded shadow-sm hover:bg-green-50 text-[10px] font-bold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                📊 Excel İndir
                            </button>
                            <button @click="isInfoOpen = !isInfoOpen" class="flex items-center gap-1 px-2 py-1 text-blue-600 bg-white border border-blue-200 rounded shadow-sm hover:bg-blue-50 text-[10px] font-medium transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Hesaplama Bilgisi
                            </button>
                        </div>
                    </div>

                    <!-- Info Panel (Hesaplama Bilgileri) -->
                    <div v-if="isInfoOpen" class="bg-blue-50 border-b border-blue-200 p-3 text-[11px] text-gray-700 shadow-inner">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-3 w-full">
                                <div>
                                    <h4 class="font-bold text-blue-800 mb-1 border-b border-blue-200 pb-0.5">Muhasebe Standardı (30 Gün / 225 Saat)</h4>
                                    <ul class="space-y-1 list-none ml-1">
                                        <li><strong class="text-gray-900">Normal Çalışma Ücreti</strong> = (Maaş / 30) × Çalışılan Gün</li>
                                        <li class="pl-2 border-l-2 border-blue-300 ml-1 text-gray-600">Tam ay çalışan: <strong>30 gün</strong> kabul edilir → Ücret = Maaş (tam)</li>
                                        <li class="pl-2 border-l-2 border-blue-300 ml-1 text-gray-600">Kısmi ay (ör: 16 Şubat'ta giren): 13 gün çalıştı → (Maaş / 30) × 13</li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 class="font-bold text-blue-800 mb-1 border-b border-blue-200 pb-0.5">Mesai ve Kesinti Formülleri</h4>
                                    <ul class="space-y-1 list-none ml-1">
                                        <li><strong class="text-gray-900">FM %50 Ücreti</strong> = (Maaş / 225) × 1.5 × Fazla Mesai Saat</li>
                                        <li><strong class="text-gray-900">FM %100 Ücreti</strong> = (Maaş / 225) × 2.0 × Tatil Çalışma Saat</li>
                                        <li><strong class="text-gray-900">Devamsızlık Kesinti</strong> = (Maaş / 30) × Devamsız Gün</li>
                                        <li><strong class="text-gray-900">Ücretsiz İzin Kesinti</strong> = (Maaş / 30) × Ücretsiz İzin Gün</li>
                                        <li><strong class="text-gray-900">Ücretli İzin Ücreti</strong> = (Maaş / 30) × Ücretli İzin Gün</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- İlerleme -->
                    <div v-if="progress > 0" class="px-3 py-1 bg-gray-50 border-b">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2.5 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full transition-all duration-300" :style="{ width: progress + '%' }"></div>
                            </div>
                            <span class="text-[10px] text-gray-500 w-8 text-right">{{ Math.round(progress) }}%</span>
                        </div>
                    </div>

                    <!-- BORDRO GÖRÜNÜMÜ -->
                    <div v-if="sonuclar && gorunumTipi === 'bordro'" class="flex-1 overflow-auto">
                        <div class="px-3 py-1.5 bg-white border-b text-center">
                            <div class="font-bold text-sm text-gray-800">GENEL PERSONEL BORDROSU (ÜCRETLER)</div>
                            <div class="text-[10px] text-gray-500">{{ sonuclar.tarih_araligi.baslangic }} - {{ sonuclar.tarih_araligi.bitis }}</div>
                        </div>
                        <table class="w-full text-[9px] border-collapse">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th rowspan="2" class="bth">Kart No</th>
                                    <th rowspan="2" class="bth">Sicil No</th>
                                    <th rowspan="2" class="bth">Adı</th>
                                    <th rowspan="2" class="bth">Soyadı</th>
                                    <th rowspan="2" class="bth">Bölüm</th>
                                    <th rowspan="2" class="bth">Net Maaşı</th>
                                    <th rowspan="2" class="bth">Giriş T.</th>
                                    <th rowspan="2" class="bth">Çıkış T.</th>
                                    <th colspan="3" class="bth bg-green-100">NORMAL ÇALIŞMA</th>
                                    <th colspan="2" class="bth bg-yellow-100">F.MESAİ %50</th>
                                    <th colspan="3" class="bth bg-orange-100">F.MESAİ %100</th>
                                    <th colspan="3" class="bth bg-red-100">DEVAMSIZLIK</th>
                                    <th colspan="3" class="bth bg-purple-100">HAFTA TATİLİ</th>
                                    <th colspan="3" class="bth bg-indigo-100">ÜCRETSİZ İZİN</th>
                                    <th colspan="3" class="bth bg-blue-100">ÜCRETLİ İZİN</th>
                                    <th rowspan="2" class="bth">Avans</th>
                                    <th rowspan="2" class="bth">Ek Ödeme</th>
                                    <th rowspan="2" class="bth bg-emerald-100">Yol Parası</th>
                                    <th rowspan="2" class="bth bg-teal-100">Yemek</th>
                                    <th rowspan="2" class="bth">Gün Fark</th>
                                    <th rowspan="2" class="bth bg-amber-100">Elden Ödeme</th>
                                    <th rowspan="2" class="bth bg-rose-100">Kesinti</th>
                                    <th rowspan="2" class="bth font-bold">TOPLAM</th>
                                </tr>
                                <tr class="bg-gray-100">
                                    <!-- Normal -->
                                    <th class="bth-sub">Gün</th><th class="bth-sub">Saat</th><th class="bth-sub">Ücret</th>
                                    <!-- FM 50 -->
                                    <th class="bth-sub">Saat</th><th class="bth-sub">Ücret</th>
                                    <!-- FM 100 -->
                                    <th class="bth-sub">Gün</th><th class="bth-sub">Saat</th><th class="bth-sub">Ücret</th>
                                    <!-- Devamsızlık -->
                                    <th class="bth-sub">Gün</th><th class="bth-sub">Saat</th><th class="bth-sub">Ücret</th>
                                    <!-- Hafta Tatili -->
                                    <th class="bth-sub">Gün</th><th class="bth-sub">Saat</th><th class="bth-sub">Ücret</th>
                                    <!-- Ücretsiz İzin -->
                                    <th class="bth-sub">Gün</th><th class="bth-sub">Saat</th><th class="bth-sub">Ücret</th>
                                    <!-- Ücretli İzin -->
                                    <th class="bth-sub">Gün</th><th class="bth-sub">Saat</th><th class="bth-sub">Ücret</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="s in sonuclar.sonuclar" :key="s.personel_id" class="border-b border-gray-200 hover:bg-blue-50/40 transition">
                                    <td class="btd">{{ s.kart_no }}</td>
                                    <td class="btd">{{ s.sicil_no }}</td>
                                    <td class="btd text-left">{{ s.ad }}</td>
                                    <td class="btd text-left">{{ s.soyad }}</td>
                                    <td class="btd text-left">{{ s.bolum }}</td>
                                    <td class="btd text-right">{{ s.net_maas }}</td>
                                    <td class="btd">{{ s.giris_tarihi }}</td>
                                    <td class="btd">{{ s.cikis_tarihi }}</td>
                                    <!-- Normal -->
                                    <td class="btd bg-green-50/50">{{ s.bordro.normal_calisma.gun }}</td>
                                    <td class="btd bg-green-50/50">{{ s.bordro.normal_calisma.saat }}</td>
                                    <td class="btd bg-green-50/50 text-right font-medium">{{ s.bordro.normal_calisma.ucret }}</td>
                                    <!-- FM 50 -->
                                    <td class="btd bg-yellow-50/50">{{ s.bordro.fazla_mesai_50.saat }}</td>
                                    <td class="btd bg-yellow-50/50 text-right">{{ s.bordro.fazla_mesai_50.ucret }}</td>
                                    <!-- FM 100 -->
                                    <td class="btd bg-orange-50/50">{{ s.bordro.fazla_mesai_100.gun }}</td>
                                    <td class="btd bg-orange-50/50">{{ s.bordro.fazla_mesai_100.saat }}</td>
                                    <td class="btd bg-orange-50/50 text-right">{{ s.bordro.fazla_mesai_100.ucret }}</td>
                                    <!-- Devamsızlık -->
                                    <td class="btd bg-red-50/50">{{ s.bordro.devamsizlik.gun }}</td>
                                    <td class="btd bg-red-50/50">{{ s.bordro.devamsizlik.saat }}</td>
                                    <td class="btd bg-red-50/50 text-right text-red-600">({{ s.bordro.devamsizlik.ucret }})</td>
                                    <!-- Hafta Tatili -->
                                    <td class="btd bg-purple-50/50">{{ s.bordro.hafta_tatili.gun }}</td>
                                    <td class="btd bg-purple-50/50">{{ s.bordro.hafta_tatili.saat }}</td>
                                    <td class="btd bg-purple-50/50 text-right">{{ s.bordro.hafta_tatili.ucret }}</td>
                                    <!-- Ücretsiz İzin -->
                                    <td class="btd bg-indigo-50/50">{{ s.bordro.ucretsiz_izin.gun }}</td>
                                    <td class="btd bg-indigo-50/50">{{ s.bordro.ucretsiz_izin.saat }}</td>
                                    <td class="btd bg-indigo-50/50 text-right text-red-600">({{ s.bordro.ucretsiz_izin.ucret }})</td>
                                    <!-- Ücretli İzin -->
                                    <td class="btd bg-blue-50/50">{{ s.bordro.ucretli_izin.gun }}</td>
                                    <td class="btd bg-blue-50/50">{{ s.bordro.ucretli_izin.saat }}</td>
                                    <td class="btd bg-blue-50/50 text-right">{{ s.bordro.ucretli_izin.ucret }}</td>
                                    <!-- Avans / Ek / Yol / Yemek / Fark / Elden / Kesinti / Toplam -->
                                    <td class="btd text-right">{{ s.bordro.avans }}</td>
                                    <td class="btd text-right">{{ s.bordro.ek_odeme }}</td>
                                    <td class="btd text-right bg-emerald-50/50">{{ s.bordro.yol_parasi }}</td>
                                    <td class="btd text-right bg-teal-50/50">{{ s.bordro.yemek }}</td>
                                    <td class="btd text-right">{{ s.bordro.gun_fark }}</td>
                                    <td class="btd text-right bg-amber-50/50">{{ s.bordro.elden_odeme }}</td>
                                    <td class="btd text-right text-red-600 bg-rose-50/50">({{ s.bordro.kesinti }})</td>
                                    <td class="btd text-right font-bold">{{ s.bordro.toplam }}</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="sonuclar.sonuclar.length > 0">
                                <tr class="bg-gray-200 font-bold text-[9px]">
                                    <td colspan="5" class="btd text-left font-bold">Toplam:</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.net_maas) }}</td>
                                    <td class="btd"></td>
                                    <td class="btd"></td>
                                    <!-- Normal -->
                                    <td class="btd bg-green-100">{{ toplamGun(sonuclar.sonuclar, s => s.bordro.normal_calisma.gun) }}</td>
                                    <td class="btd bg-green-100">{{ toplamSaat(sonuclar.sonuclar, s => s.bordro.normal_calisma.saat) }}</td>
                                    <td class="btd bg-green-100 text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.normal_calisma.ucret) }}</td>
                                    <!-- FM 50 -->
                                    <td class="btd bg-yellow-100">{{ toplamSaat(sonuclar.sonuclar, s => s.bordro.fazla_mesai_50.saat) }}</td>
                                    <td class="btd bg-yellow-100 text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.fazla_mesai_50.ucret) }}</td>
                                    <!-- FM 100 -->
                                    <td class="btd bg-orange-100">{{ toplamGun(sonuclar.sonuclar, s => s.bordro.fazla_mesai_100.gun) }}</td>
                                    <td class="btd bg-orange-100">{{ toplamSaat(sonuclar.sonuclar, s => s.bordro.fazla_mesai_100.saat) }}</td>
                                    <td class="btd bg-orange-100 text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.fazla_mesai_100.ucret) }}</td>
                                    <!-- Devamsızlık -->
                                    <td class="btd bg-red-100">{{ toplamGun(sonuclar.sonuclar, s => s.bordro.devamsizlik.gun) }}</td>
                                    <td class="btd bg-red-100">{{ toplamSaat(sonuclar.sonuclar, s => s.bordro.devamsizlik.saat) }}</td>
                                    <td class="btd bg-red-100 text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.devamsizlik.ucret) }}</td>
                                    <!-- Hafta Tatili -->
                                    <td class="btd bg-purple-100">{{ toplamGun(sonuclar.sonuclar, s => s.bordro.hafta_tatili.gun) }}</td>
                                    <td class="btd bg-purple-100">{{ toplamSaat(sonuclar.sonuclar, s => s.bordro.hafta_tatili.saat) }}</td>
                                    <td class="btd bg-purple-100 text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.hafta_tatili.ucret) }}</td>
                                    <!-- Ücretsiz İzin -->
                                    <td class="btd bg-indigo-100">{{ toplamGun(sonuclar.sonuclar, s => s.bordro.ucretsiz_izin.gun) }}</td>
                                    <td class="btd bg-indigo-100">{{ toplamSaat(sonuclar.sonuclar, s => s.bordro.ucretsiz_izin.saat) }}</td>
                                    <td class="btd bg-indigo-100 text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.ucretsiz_izin.ucret) }}</td>
                                    <!-- Ücretli İzin -->
                                    <td class="btd bg-blue-100">{{ toplamGun(sonuclar.sonuclar, s => s.bordro.ucretli_izin.gun) }}</td>
                                    <td class="btd bg-blue-100">{{ toplamSaat(sonuclar.sonuclar, s => s.bordro.ucretli_izin.saat) }}</td>
                                    <td class="btd bg-blue-100 text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.ucretli_izin.ucret) }}</td>
                                    <!-- Avans / Ek / Yol / Yemek / Fark / Elden / Kesinti / Toplam -->
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.avans) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.ek_odeme) }}</td>
                                    <td class="btd text-right bg-emerald-100">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.yol_parasi) }}</td>
                                    <td class="btd text-right bg-teal-100">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.yemek) }}</td>
                                    <td class="btd text-right">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.gun_fark) }}</td>
                                    <td class="btd text-right bg-amber-100">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.elden_odeme) }}</td>
                                    <td class="btd text-right bg-rose-100">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.kesinti) }}</td>
                                    <td class="btd text-right font-bold">{{ toplamUcret(sonuclar.sonuclar, s => s.bordro.toplam) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                        <div v-if="sonuclar.sonuclar.length > 0" class="px-3 py-1.5 bg-gray-50 border-t text-[10px] text-gray-600">
                            <b>{{ sonuclar.sonuclar.length }}</b> adet personel listelendi.
                        </div>
                    </div>

                    <!-- DETAY GÖRÜNÜMÜ -->
                    <div v-else-if="sonuclar && gorunumTipi === 'detay'" class="flex-1 flex overflow-hidden">
                        <div class="w-52 border-r bg-gray-50 overflow-y-auto">
                            <div class="px-2 py-1 bg-gradient-to-r from-[#e8eef8] to-[#dce4f2] text-[11px] font-bold text-gray-700 border-b sticky top-0">Sonuçlar</div>
                            <div v-for="s in sonuclar.sonuclar" :key="s.personel_id" @click="selectedSonuc = s"
                                class="px-2 py-1 border-b cursor-pointer text-[11px] transition"
                                :class="selectedSonuc?.personel_id === s.personel_id ? 'bg-blue-100 text-blue-800 font-semibold' : 'hover:bg-gray-100'">
                                {{ s.ad_soyad }}
                            </div>
                        </div>
                        <div v-if="selectedSonuc" class="flex-1 flex flex-col overflow-hidden">
                            <div class="px-3 py-2 bg-white border-b grid grid-cols-4 gap-2">
                                <div class="bg-green-50 border border-green-200 rounded p-1.5 text-center">
                                    <div class="text-base font-bold text-green-700">{{ selectedSonuc.ozet.calisma_gun }}</div>
                                    <div class="text-[9px] text-green-600">Çalışma Günü</div>
                                </div>
                                <div class="bg-blue-50 border border-blue-200 rounded p-1.5 text-center">
                                    <div class="text-base font-bold text-blue-700">{{ formatSaat(selectedSonuc.ozet.toplam_calisma_saat) }}</div>
                                    <div class="text-[9px] text-blue-600">Toplam Çalışma</div>
                                </div>
                                <div class="bg-amber-50 border border-amber-200 rounded p-1.5 text-center">
                                    <div class="text-base font-bold text-amber-700">{{ formatSaat(selectedSonuc.ozet.toplam_fazla_mesai_saat) }}</div>
                                    <div class="text-[9px] text-amber-600">Fazla Mesai</div>
                                </div>
                                <div class="bg-red-50 border border-red-200 rounded p-1.5 text-center">
                                    <div class="text-base font-bold text-red-700">{{ selectedSonuc.ozet.devamsizlik_gun }}</div>
                                    <div class="text-[9px] text-red-600">Devamsızlık</div>
                                </div>
                            </div>
                            <div class="flex-1 overflow-y-auto">
                                <table class="w-full text-[11px]">
                                    <thead class="bg-gradient-to-b from-[#e8eef8] to-[#dce4f2] sticky top-0">
                                        <tr>
                                            <th class="px-2 py-1 text-left font-semibold text-gray-700 border-b">Tarih</th>
                                            <th class="px-2 py-1 text-left font-semibold text-gray-700 border-b">Gün</th>
                                            <th class="px-2 py-1 text-center font-semibold text-gray-700 border-b">Giriş</th>
                                            <th class="px-2 py-1 text-center font-semibold text-gray-700 border-b">Çıkış</th>
                                            <th class="px-2 py-1 text-center font-semibold text-gray-700 border-b">Süre</th>
                                            <th class="px-2 py-1 text-center font-semibold text-gray-700 border-b">Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="g in selectedSonuc.gunler" :key="g.tarih"
                                            class="border-b border-gray-100 hover:bg-blue-50/50 transition"
                                            :class="g.durum === 'hafta_sonu' || g.durum === 'resmi_tatil' ? 'bg-gray-50/70' : ''">
                                            <td class="px-2 py-0.5 font-medium text-gray-800">{{ g.tarih }}</td>
                                            <td class="px-2 py-0.5 text-gray-600">{{ g.gun_adi }}</td>
                                            <td class="px-2 py-0.5 text-center font-mono">{{ g.giris || '-' }}</td>
                                            <td class="px-2 py-0.5 text-center font-mono">{{ g.cikis || '-' }}</td>
                                            <td class="px-2 py-0.5 text-center">{{ g.calisma_dakika > 0 ? formatSaat(g.calisma_saat) : '-' }}</td>
                                            <td class="px-2 py-0.5 text-center">
                                                <span class="px-1 py-0.5 rounded text-[9px] font-medium" :class="durumRenk(g.durum)">{{ durumEtiket(g.durum) }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Boş -->
                    <div v-else class="flex-1 flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <svg class="w-14 h-14 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            <div class="text-xs font-medium">Puantaj Hesaplama</div>
                            <div class="text-[10px] mt-1">Personel seçip tarih belirleyin ve "İşlemi Başlat"a tıklayın</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.bth { @apply px-1 py-1 text-center font-semibold text-gray-700 border border-gray-300; }
.bth-sub { @apply px-1 py-0.5 text-center text-gray-600 border border-gray-300; }
.btd { @apply px-1 py-0.5 text-center border border-gray-200; }
</style>
