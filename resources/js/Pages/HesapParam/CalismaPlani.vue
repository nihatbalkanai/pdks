<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({ gruplar: Array, vardiyalar: Array });

const localGruplar  = ref([...(props.gruplar || [])]);
const planlar       = ref([]);
const selectedGrup  = ref(null);
const isLoading     = ref(false);
const isAiLoading   = ref(false);
const seciliYil     = ref(new Date().getFullYear());

// Grup formu
const grupEditMode    = ref(false);
const grupForm        = ref({ aciklama: '', hesap_parametresi: 'GENEL AYLIK PAR.' });
const selectedGrupIdx = ref(-1);

// Toplu Atama modal
const showTopluAta = ref(false);
const topluForm    = ref({ baslangic: '', bitis: '', vardiya_id: '', tur: 'is_gunu', gun_filtre: 'hepsi' });

// Plan şablonları
const showAiModal = ref(false);
const aiForm      = ref({ yil: new Date().getFullYear(), vardiya_id: '', sablon: 'standart' });
const sablonlar = [
    { key: 'standart',          icon: '📋', label: 'Standart (İş Kanunu)',    desc: 'Pzt-Cuma iş, Cmt+Paz tatil',    color: 'bg-blue-50 border-blue-300 hover:bg-blue-100' },
    { key: 'cumartesi_calisma', icon: '🏭', label: 'Cumartesi Çalışma',       desc: 'Pzt-Cmt iş, Paz tatil',          color: 'bg-amber-50 border-amber-300 hover:bg-amber-100' },
    { key: 'market',            icon: '🛒', label: 'Market / Perakende',       desc: 'Sal-Paz iş, Pazartesi tatil',     color: 'bg-green-50 border-green-300 hover:bg-green-100' },
    { key: 'restoran',          icon: '🍽️', label: 'Restoran / Kafe',          desc: 'Çar-Pzt iş, Salı tatil',          color: 'bg-orange-50 border-orange-300 hover:bg-orange-100' },
    { key: 'surekli',           icon: '🔄', label: '7/7 Sürekli Çalışma',      desc: 'Her gün iş günü (tatil yok)',     color: 'bg-red-50 border-red-300 hover:bg-red-100' },
];

// --- Grup İşlemleri ---
const selectGrup = async (g, i) => {
    selectedGrup.value    = g;
    selectedGrupIdx.value = i;
    grupEditMode.value    = false;
    grupForm.value        = { aciklama: g.aciklama, hesap_parametresi: g.hesap_parametresi || '' };
    await planYukle(g.id);
};

const planYukle = async (grupId) => {
    isLoading.value = true;
    try {
        const res   = await axios.get(route('tanim.calisma-plani.plan-getir', grupId), { params: { yil: seciliYil.value } });
        planlar.value = res.data.planlar;
    } catch (e) { planlar.value = []; }
    finally { isLoading.value = false; }
};

const yilDegistir = async () => {
    if (selectedGrup.value) await planYukle(selectedGrup.value.id);
};

const yeniGrup = () => {
    selectedGrupIdx.value = -1;
    selectedGrup.value    = null;
    grupEditMode.value    = true;
    grupForm.value        = { aciklama: '', hesap_parametresi: 'GENEL AYLIK PAR.' };
    planlar.value         = [];
};

const grupDuzenle = () => {
    if (!selectedGrup.value) { Swal.fire('Uyarı', 'Düzenlenecek grubu seçin.', 'warning'); return; }
    grupEditMode.value = true;
};

const grupKaydet = async () => {
    if (!grupForm.value.aciklama) { Swal.fire('Uyarı', 'Açıklama zorunludur.', 'warning'); return; }
    try {
        if (selectedGrup.value) {
            const res = await axios.put(route('tanim.calisma-gruplari.update', selectedGrup.value.id), grupForm.value);
            localGruplar.value[selectedGrupIdx.value] = res.data.grup;
            selectedGrup.value = res.data.grup;
        } else {
            const res = await axios.post(route('tanim.calisma-gruplari.store'), grupForm.value);
            localGruplar.value.push(res.data.grup);
            const newIdx = localGruplar.value.length - 1;
            selectedGrupIdx.value = newIdx;
            selectedGrup.value    = res.data.grup;
        }
        grupEditMode.value = false;
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Kaydedildi', showConfirmButton: false, timer: 1200 });
    } catch (err) {
        const errMsg = err.response?.data?.message || 'Kayıt başarısız.';
        Swal.fire('Hata', errMsg, 'error');
    }
};

const grupSil = async () => {
    if (!selectedGrup.value) { Swal.fire('Uyarı', 'Silinecek grubu seçin.', 'warning'); return; }
    const r = await Swal.fire({
        title: 'Grubu Sil', html: `<b>${selectedGrup.value.aciklama}</b> ve tüm planı silinecek. Emin misiniz?`,
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
        confirmButtonText: 'Sil', cancelButtonText: 'İptal',
    });
    if (!r.isConfirmed) return;
    try {
        await axios.delete(route('tanim.calisma-gruplari.destroy', selectedGrup.value.id));
        localGruplar.value.splice(selectedGrupIdx.value, 1);
        selectedGrup.value = null; selectedGrupIdx.value = -1; planlar.value = [];
    } catch (e) { Swal.fire('Hata', 'Silme başarısız.', 'error'); }
};

const grupIptal = () => {
    grupEditMode.value = false;
    if (selectedGrup.value) {
        grupForm.value = { aciklama: selectedGrup.value.aciklama, hesap_parametresi: selectedGrup.value.hesap_parametresi || '' };
    } else {
        grupForm.value = { aciklama: '', hesap_parametresi: 'GENEL AYLIK PAR.' };
    }
};

// --- Plan İşlemleri ---
const satirGuncelle = async (satir) => {
    try {
        await axios.post(route('tanim.calisma-plani.satir', selectedGrup.value.id), {
            tarih: satir.tarih_raw, vardiya_id: satir.vardiya_id || null, tur: satir.tur,
        });
        const v = props.vardiyalar.find(v => v.id == satir.vardiya_id);
        satir.vardiya_ad = v?.ad || null;
    } catch (e) { Swal.fire('Hata', 'Güncelleme başarısız.', 'error'); }
};

const topluAta = async () => {
    if (!topluForm.value.baslangic || !topluForm.value.bitis) {
        Swal.fire('Uyarı', 'Tarih aralığı giriniz.', 'warning'); return;
    }
    try {
        const res = await axios.post(route('tanim.calisma-plani.toplu-ata', selectedGrup.value.id), topluForm.value);
        showTopluAta.value = false;
        await planYukle(selectedGrup.value.id);
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `${res.data.count} gün güncellendi`, showConfirmButton: false, timer: 2000 });
    } catch (e) { Swal.fire('Hata', 'İşlem başarısız.', 'error'); }
};

const tumPlanSil = async () => {
    if (!selectedGrup.value) return;
    const r = await Swal.fire({
        title: 'Tüm Planı Sil', text: 'Bu gruba ait tüm günlük planlar silinecek. Emin misiniz?',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
        confirmButtonText: 'Sil', cancelButtonText: 'İptal',
    });
    if (!r.isConfirmed) return;
    try {
        await axios.delete(route('tanim.calisma-plani.temizle', selectedGrup.value.id));
        planlar.value = [];
    } catch (e) { Swal.fire('Hata', 'Silme başarısız.', 'error'); }
};

// --- AI Plan ---
const aiPlanOlustur = async () => {
    isAiLoading.value = true;
    try {
        const res = await axios.post(route('tanim.calisma-plani.ai-plan', selectedGrup.value.id), {
            yil: aiForm.value.yil,
            vardiya_id: aiForm.value.vardiya_id || null,
            sablon: aiForm.value.sablon,
        });
        showAiModal.value = false;
        seciliYil.value   = aiForm.value.yil;
        await planYukle(selectedGrup.value.id);
        Swal.fire({
            title: '✅ Plan Oluşturuldu!',
            html: res.data.message,
            icon: 'success',
            confirmButtonText: 'Tamam',
        });
    } catch (err) {
        const msg = err.response?.data?.error || 'Plan oluşturma başarısız.';
        Swal.fire('Hata', msg, 'error');
    } finally {
        isAiLoading.value = false;
    }
};

const turClass = (tur) => ({
    is_gunu:     'bg-[#d4e2f4]',
    tatil:       'bg-orange-50',
    resmi_tatil: 'bg-red-100',
}[tur] || 'bg-[#d4e2f4]');

const turTextClass = (tur) => ({
    is_gunu:     '',
    tatil:       'text-orange-600 font-semibold',
    resmi_tatil: 'text-red-700 font-bold',
}[tur] || '');
</script>

<template>
<Head title="Genel Gruplar Çalışma Planı" />
<AuthenticatedLayout>
<div class="p-4 h-full flex flex-col">
    <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full overflow-hidden">

        <!-- Başlık -->
        <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <h2 class="font-bold text-sm text-gray-800">Genel Gruplar Çalışma Planı</h2>
            </div>
            <div class="flex items-center gap-1 text-[10px] text-gray-500">
                <span class="w-3 h-3 rounded-sm bg-[#d4e2f4] border border-gray-300 inline-block"></span>İş Günü
                <span class="w-3 h-3 rounded-sm bg-orange-100 border border-orange-300 inline-block ml-2"></span>Tatil
                <span class="w-3 h-3 rounded-sm bg-red-100 border border-red-300 inline-block ml-2"></span>Resmi Tatil
            </div>
        </div>

        <div class="flex flex-col flex-1 overflow-hidden">

            <!-- ÜST KISIM: Gruplar -->
            <div class="border-b border-gray-400 flex flex-col" style="height:210px;">
                <div class="flex-1 overflow-y-auto bg-[#c8d8ec]">
                    <table class="w-full text-xs border-collapse">
                        <thead class="sticky top-0">
                            <tr class="bg-[#f0c860]">
                                <th class="py-1.5 px-3 text-left border border-gray-400 font-bold">Açıklama</th>
                                <th class="py-1.5 px-3 text-left border border-gray-400 font-bold w-52">Hesap Parametresi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(g, i) in localGruplar" :key="g.id"
                                @click="selectGrup(g, i)"
                                class="cursor-pointer transition"
                                :class="selectedGrupIdx === i ? 'bg-[#f0c860] font-bold' : 'bg-[#d4e2f4] hover:bg-[#bfd0e8]'">
                                <td class="py-1 px-3 border border-gray-300">{{ g.aciklama }}</td>
                                <td class="py-1 px-3 border border-gray-300">{{ g.hesap_parametresi || '-' }}</td>
                            </tr>
                            <tr v-if="localGruplar.length === 0">
                                <td colspan="2" class="py-8 text-center text-gray-500 bg-[#d4e2f4]">&lt;Yeni Ekle butonuna tıklayın&gt;</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Grup Form -->
                <div class="bg-gray-100 px-4 py-2 border-t border-gray-300 flex items-end gap-2">
                    <div class="flex-1">
                        <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Açıklama</label>
                        <input v-model="grupForm.aciklama" :disabled="!grupEditMode"
                            class="w-full border border-gray-300 rounded-sm py-1 px-2 text-xs disabled:bg-gray-50 focus:ring-1 focus:ring-blue-400" />
                    </div>
                    <div class="w-52">
                        <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Hesap Parametresi</label>
                        <input v-model="grupForm.hesap_parametresi" :disabled="!grupEditMode"
                            class="w-full border border-gray-300 rounded-sm py-1 px-2 text-xs disabled:bg-gray-50 focus:ring-1 focus:ring-blue-400" />
                    </div>
                    <div class="flex gap-1 pb-0.5">
                        <button @click="yeniGrup" class="tanim-btn" title="Yeni Grup">
                            <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                        <button @click="grupSil" class="tanim-btn" title="Grubu Sil">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                        </button>
                        <button @click="grupDuzenle" class="tanim-btn" title="Düzenle">
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572z"></path></svg>
                        </button>
                        <button @click="grupKaydet" class="tanim-btn" title="Kaydet">
                            <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </button>
                        <button @click="grupIptal" class="tanim-btn" title="İptal">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ALT KISIM: Tarih-Vardiya Planı -->
            <div class="flex-1 flex flex-col overflow-hidden">

                <!-- Plan araçları -->
                <div class="bg-gray-50 border-b border-gray-300 px-4 py-1.5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-gray-600">
                            {{ selectedGrup ? selectedGrup.aciklama + ' — ' : '' }}Vardiya Planı
                        </span>
                        <select v-model="seciliYil" @change="yilDegistir" class="text-xs border border-gray-300 rounded px-2 py-0.5">
                            <option v-for="y in [2024,2025,2026,2027,2028]" :key="y" :value="y">{{ y }}</option>
                        </select>
                        <span class="text-[10px] text-gray-400">({{ planlar.length }} gün)</span>
                    </div>
                    <div class="flex gap-1.5" v-if="selectedGrup">
                        <!-- AI Buton -->
                        <button @click="showAiModal = true"
                            class="flex items-center gap-1 text-[10px] bg-purple-600 hover:bg-purple-700 text-white px-2 py-1 rounded shadow transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"></path></svg>
                            AI Plan Oluştur
                        </button>
                        <button @click="showTopluAta = true"
                            class="flex items-center gap-1 text-[10px] bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded shadow transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Toplu Ata
                        </button>
                    </div>
                </div>

                <!-- Plan Tablosu -->
                <div class="flex-1 overflow-y-auto bg-[#c8d8ec]">
                    <div v-if="!selectedGrup" class="flex items-center justify-center h-full text-gray-400 text-sm">
                        ← Üstten bir grup seçin
                    </div>
                    <div v-else-if="isLoading" class="flex items-center justify-center h-full">
                        <div class="flex items-center gap-2 text-gray-500">
                            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Yükleniyor...
                        </div>
                    </div>
                    <table v-else class="w-full text-xs border-collapse">
                        <thead class="sticky top-0">
                            <tr class="bg-[#f0c860]">
                                <th class="py-1.5 px-3 text-left border border-gray-400 font-bold w-36">Tarih</th>
                                <th class="py-1.5 px-3 text-left border border-gray-400 font-bold w-24">Gün</th>
                                <th class="py-1.5 px-3 text-left border border-gray-400 font-bold">Vardiya</th>
                                <th class="py-1.5 px-3 text-center border border-gray-400 font-bold w-32">Tür</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="satir in planlar" :key="satir.tarih_raw"
                                :class="turClass(satir.tur)">
                                <td class="py-0.5 px-3 border border-gray-200 font-medium" :class="turTextClass(satir.tur)">{{ satir.tarih }}</td>
                                <td class="py-0.5 px-3 border border-gray-200 capitalize" :class="turTextClass(satir.tur)">{{ satir.gun }}</td>
                                <td class="py-0.5 px-2 border border-gray-200">
                                    <select v-model="satir.vardiya_id" @change="satirGuncelle(satir)"
                                        class="w-full text-xs border-0 bg-transparent py-0.5 focus:ring-1 focus:ring-blue-400 rounded">
                                        <option :value="null">— Boş —</option>
                                        <option v-for="v in vardiyalar" :key="v.id" :value="v.id">{{ v.ad }}</option>
                                    </select>
                                </td>
                                <td class="py-0.5 px-2 border border-gray-200">
                                    <select v-model="satir.tur" @change="satirGuncelle(satir)"
                                        class="w-full text-xs border-0 bg-transparent py-0.5 focus:ring-1 focus:ring-blue-400 rounded"
                                        :class="turTextClass(satir.tur)">
                                        <option value="is_gunu">İş Günü</option>
                                        <option value="tatil">Tatil</option>
                                        <option value="resmi_tatil">Resmi Tatil</option>
                                    </select>
                                </td>
                            </tr>
                            <tr v-if="planlar.length === 0">
                                <td colspan="4" class="py-12 text-center text-gray-500 bg-[#d4e2f4]">
                                    Bu yıl için plan oluşturulmamış.<br>
                                    <button @click="showAiModal = true" class="mt-2 text-purple-600 underline text-xs font-semibold">
                                        🤖 AI ile otomatik plan oluştur
                                    </button>
                                    <span class="mx-2 text-gray-400">veya</span>
                                    <button @click="showTopluAta = true" class="text-blue-600 underline text-xs">Manuel toplu atama</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Alt butonlar -->
                <div class="bg-gray-100 border-t border-gray-300 px-4 py-1.5 flex items-center justify-between" v-if="selectedGrup">
                    <button @click="tumPlanSil"
                        class="text-[10px] text-red-600 hover:text-red-800 border border-red-300 hover:border-red-500 px-3 py-1 rounded transition">
                        Tüm planı sil
                    </button>
                    <span class="text-[10px] text-gray-400">Dropdown'dan seçim anında kaydedilir</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toplu Atama Modal -->
<div v-if="showTopluAta" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-96 p-5 border border-gray-300">
        <h3 class="font-bold text-sm text-gray-800 mb-4 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            Toplu Vardiya Atama
        </h3>
        <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Başlangıç Tarihi</label>
                    <input v-model="topluForm.baslangic" type="date" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs" />
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Bitiş Tarihi</label>
                    <input v-model="topluForm.bitis" type="date" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs" />
                </div>
            </div>
            <div>
                <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Vardiya</label>
                <select v-model="topluForm.vardiya_id" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                    <option value="">— Boş (Temizle) —</option>
                    <option v-for="v in vardiyalar" :key="v.id" :value="v.id">{{ v.ad }}</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Gün Filtresi</label>
                    <select v-model="topluForm.gun_filtre" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                        <option value="hepsi">Tüm Günler</option>
                        <option value="hafta_ici">Sadece Hafta İçi</option>
                        <option value="hafta_sonu">Sadece Hafta Sonu</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Gün Türü</label>
                    <select v-model="topluForm.tur" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                        <option value="is_gunu">İş Günü</option>
                        <option value="tatil">Tatil</option>
                        <option value="resmi_tatil">Resmi Tatil</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <button @click="showTopluAta = false" class="text-xs px-4 py-1.5 border border-gray-300 rounded hover:bg-gray-50">İptal</button>
            <button @click="topluAta" class="text-xs px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded shadow">Uygula</button>
        </div>
    </div>
</div>

<!-- Plan Şablonları Modal -->
<div v-if="showAiModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-[520px] p-5 border border-purple-200">
        <h3 class="font-bold text-sm text-gray-800 mb-1 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            Çalışma Planı Şablonu Seç
        </h3>
        <p class="text-[11px] text-gray-500 mb-3">Resmi tatiller otomatik işaretlenir. Şablon seçip yılı belirleyin.</p>

        <!-- Şablon Kartları -->
        <div class="grid grid-cols-1 gap-2 mb-4">
            <button v-for="s in sablonlar" :key="s.key" @click="aiForm.sablon = s.key"
                :class="[s.color, aiForm.sablon === s.key ? 'ring-2 ring-purple-500 shadow-md' : 'opacity-80']"
                class="flex items-center gap-3 p-2.5 rounded-lg border text-left transition cursor-pointer">
                <span class="text-xl">{{ s.icon }}</span>
                <div>
                    <div class="text-xs font-bold text-gray-800">{{ s.label }}</div>
                    <div class="text-[10px] text-gray-500">{{ s.desc }}</div>
                </div>
                <svg v-if="aiForm.sablon === s.key" class="w-5 h-5 text-purple-600 ml-auto" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
                <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">Yıl</label>
                <select v-model="aiForm.yil" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                    <option v-for="y in [2024,2025,2026,2027,2028]" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-semibold text-gray-500 block mb-0.5">İş Günü Varsayılan Vardiya</label>
                <select v-model="aiForm.vardiya_id" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                    <option value="">— Seçiniz —</option>
                    <option v-for="v in vardiyalar" :key="v.id" :value="v.id">{{ v.ad }}</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <button @click="showAiModal = false" :disabled="isAiLoading" class="text-xs px-4 py-1.5 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50">İptal</button>
            <button @click="aiPlanOlustur" :disabled="isAiLoading"
                class="text-xs px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded shadow flex items-center gap-1.5 disabled:opacity-70">
                <svg v-if="isAiLoading" class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                {{ isAiLoading ? 'Oluşturuluyor...' : '📋 Planı Oluştur' }}
            </button>
        </div>
    </div>
</div>
</AuthenticatedLayout>
</template>

<style scoped>
.tanim-btn {
    @apply w-8 h-8 flex items-center justify-center border border-gray-400 rounded bg-gradient-to-b from-[#f8f0d8] to-[#e8d8b0] hover:from-[#ffe8a8] shadow-sm transition;
}
</style>
