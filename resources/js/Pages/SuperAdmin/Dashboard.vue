<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Chart as ChartJS, registerables } from 'chart.js';
import { Line, Doughnut } from 'vue-chartjs';
import Swal from 'sweetalert2';

ChartJS.register(...registerables);

const props = defineProps({
    metrikler: Object,
    sistem: Object,
    firmalar: Object,
    paketDagilimi: Object,
    abonelikUyarisi: Array,
    abonelikGecmis: Array,
    sonGirisler: Array,
    yavasSorgular: Array,
    adminler: Array,
    can: Object
});

// Tab Yönetimi
const activeTab = ref('firmalar');

// Firma Arama
const firmaArama = ref('');
const filteredFirmalar = computed(() => {
    if (!firmaArama.value) return props.firmalar.data;
    const q = firmaArama.value.toLowerCase();
    return props.firmalar.data.filter(f =>
        f.firma_adi.toLowerCase().includes(q) ||
        (f.paket_tipi || '').toLowerCase().includes(q)
    );
});

// Sistem Yükü Grafiği
const chartData = computed(() => ({
    labels: props.sistem.grafik.labels,
    datasets: [{
        label: 'PDKS Kayıtları',
        data: props.sistem.grafik.data,
        fill: true,
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99, 102, 241, 0.15)',
        tension: 0.4,
        pointBackgroundColor: '#6366f1',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
        x: { grid: { display: false } }
    }
};

// Paket Dağılım Grafiği
const paketColors = {
    'Ücretsiz': '#94a3b8',
    'Standart': '#3b82f6',
    'Pro': '#8b5cf6',
    'Enterprise': '#f59e0b',
};
const paketChartData = computed(() => ({
    labels: Object.keys(props.paketDagilimi || {}),
    datasets: [{
        data: Object.values(props.paketDagilimi || {}),
        backgroundColor: Object.keys(props.paketDagilimi || {}).map(k => paketColors[k] || '#6b7280'),
        borderWidth: 0,
        hoverOffset: 8,
    }]
}));
const paketChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, font: { size: 12 } } }
    },
    cutout: '65%',
};

// Abonelik Yönetim Modalı
const isModalOpen = ref(false);
const editingFirma = ref(null);
const form = reactive({
    firma_adi: '',
    vergi_no: '',
    vergi_dairesi: '',
    adres: '',
    abonelik_bitis_tarihi: '',
    paket_tipi: 'Ücretsiz',
    durum: true
});

const openEditModal = (firma) => {
    editingFirma.value = firma;
    form.firma_adi = firma.firma_adi || '';
    form.vergi_no = firma.vergi_no || '';
    form.vergi_dairesi = firma.vergi_dairesi || '';
    form.adres = firma.adres || '';
    form.abonelik_bitis_tarihi = firma.abonelik_bitis_tarihi ? firma.abonelik_bitis_tarihi.split('T')[0] : '';
    form.paket_tipi = firma.paket_tipi || 'Ücretsiz';
    form.durum = firma.durum;
    isModalOpen.value = true;
};

const saveAbonelik = async () => {
    try {
        await axios.put(route('super-admin.firmalar.guncelle', editingFirma.value.id), form);
        Swal.fire({ icon: 'success', title: 'Başarılı', text: 'Firma bilgileri güncellendi', timer: 1500, showConfirmButton: false });
        isModalOpen.value = false;
        router.reload();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Hata', text: e.response?.data?.message || 'Bir hata oluştu' });
    }
};

// Firma Silme
const firmaSil = async (firma) => {
    const result = await Swal.fire({
        title: 'Firma Silinsin mi?',
        html: `<b>${firma.firma_adi}</b> firması pasife alınacak ve silinecek.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal',
    });
    if (result.isConfirmed) {
        try {
            await axios.delete(route('super-admin.firmalar.sil', firma.id));
            Swal.fire({ icon: 'success', title: 'Silindi', text: `${firma.firma_adi} pasife alındı.`, timer: 1500, showConfirmButton: false });
            router.reload();
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Hata', text: e.response?.data?.message || 'Bir hata oluştu' });
        }
    }
};

// Impersonate
const impersonate = async (firma) => {
    const result = await Swal.fire({
        title: 'Firma Olarak Giriş',
        html: `<b>${firma.firma_adi}</b> firmasının admin paneline geçiş yapılacak.<br><br><small class="text-gray-500">Üst menüdeki banner'dan kendi hesabınıza dönebilirsiniz.</small>`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        confirmButtonText: 'Giriş Yap',
        cancelButtonText: 'İptal',
    });
    if (result.isConfirmed) {
        try {
            const response = await axios.post(route('super-admin.firmalar.impersonate', firma.id));
            window.location.href = '/dashboard';
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Hata', text: e.response?.data?.message || 'Bir hata oluştu' });
        }
    }
};

// Admin Yetki Modalı
const isAdminModalOpen = ref(false);
const editingAdmin = ref(null);
const adminYetkiler = ref([]);

const openAdminModal = (adminUser) => {
    editingAdmin.value = adminUser;
    adminYetkiler.value = [...(adminUser.super_admin_yetki?.yetkiler || [])];
    isAdminModalOpen.value = true;
};

const saveAdminYetki = async () => {
    try {
        await axios.post(route('super-admin.adminler.yetki', editingAdmin.value.id), { yetkiler: adminYetkiler.value });
        Swal.fire({ icon: 'success', title: 'Başarılı', text: 'Admin yetkileri güncellendi', timer: 1500, showConfirmButton: false });
        isAdminModalOpen.value = false;
        router.reload();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Hata', text: e.response?.data?.message || 'Bir hata oluştu' });
    }
};

// Yardımcı fonksiyonlar
const formatTarih = (t) => t ? new Date(t).toLocaleDateString('tr-TR') : '-';
const gunKaldi = (t) => {
    if (!t) return null;
    const diff = Math.ceil((new Date(t) - new Date()) / (1000 * 60 * 60 * 24));
    return diff;
};

// Yeni Firma Oluşturma Modalı
const isFirmaModalOpen = ref(false);
const firmaForm = reactive({
    firma_adi: '',
    vergi_no: '',
    vergi_dairesi: '',
    adres: '',
    paket_tipi: 'Standart',
    abonelik_bitis_tarihi: '',
    admin_ad_soyad: '',
    admin_eposta: '',
    admin_sifre: '',
});
const firmaFormLoading = ref(false);

const resetFirmaForm = () => {
    firmaForm.firma_adi = '';
    firmaForm.vergi_no = '';
    firmaForm.vergi_dairesi = '';
    firmaForm.adres = '';
    firmaForm.paket_tipi = 'Standart';
    firmaForm.abonelik_bitis_tarihi = '';
    firmaForm.admin_ad_soyad = '';
    firmaForm.admin_eposta = '';
    firmaForm.admin_sifre = '';
};

const generatePassword = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    let result = '';
    for (let i = 0; i < 10; i++) result += chars.charAt(Math.floor(Math.random() * chars.length));
    firmaForm.admin_sifre = result;
};

const saveFirma = async () => {
    firmaFormLoading.value = true;
    try {
        await axios.post(route('super-admin.firmalar.olustur'), firmaForm);
        Swal.fire({ icon: 'success', title: 'Firma Oluşturuldu!', html: `<b>${firmaForm.firma_adi}</b> firması ve admin kullanıcısı başarıyla oluşturuldu.<br><br><small>Giriş: ${firmaForm.admin_eposta} / ${firmaForm.admin_sifre}</small>`, confirmButtonColor: '#6366f1' });
        isFirmaModalOpen.value = false;
        resetFirmaForm();
        router.reload();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Hata', text: e.response?.data?.message || 'Bir hata oluştu' });
    } finally {
        firmaFormLoading.value = false;
    }
};

// Duyuru Sistemi
const duyuruListesi = ref([]);
const duyuruLoading = ref(false);
const isDuyuruModalOpen = ref(false);
const duyuruForm = reactive({ baslik: '', icerik: '', tip: 'bilgi' });
const tipEtiketleri = {
    bilgi: { label: 'ℹ️ Bilgi', color: 'bg-blue-100 text-blue-700' },
    uyari: { label: '⚠️ Uyarı', color: 'bg-amber-100 text-amber-700' },
    bakim: { label: '🔧 Bakım', color: 'bg-red-100 text-red-700' },
    guncelleme: { label: '🚀 Güncelleme', color: 'bg-emerald-100 text-emerald-700' },
};

const loadDuyurular = async () => {
    duyuruLoading.value = true;
    try {
        const res = await axios.get(route('super-admin.duyurular.index'));
        duyuruListesi.value = res.data;
    } catch (e) { console.error(e); }
    finally { duyuruLoading.value = false; }
};

const saveDuyuru = async () => {
    try {
        await axios.post(route('super-admin.duyurular.gonder'), duyuruForm);
        Swal.fire({ icon: 'success', title: 'Yayınlandı', text: 'Duyuru başarıyla yayınlandı.', timer: 1500, showConfirmButton: false });
        isDuyuruModalOpen.value = false;
        duyuruForm.baslik = '';
        duyuruForm.icerik = '';
        duyuruForm.tip = 'bilgi';
        loadDuyurular();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Hata', text: e.response?.data?.message || 'Bir hata oluştu' });
    }
};

const deleteDuyuru = async (id) => {
    const r = await Swal.fire({ title: 'Sil?', text: 'Bu duyuru silinecek.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Sil', cancelButtonText: 'İptal' });
    if (r.isConfirmed) {
        await axios.delete(route('super-admin.duyurular.sil', id));
        loadDuyurular();
    }
};

// Paket Yönetimi
const paketListesi = ref([]);
const paketLoading = ref(false);
const isPaketModalOpen = ref(false);
const editingPaket = ref(null);
const paketForm = reactive({
    max_personel: 0, max_kullanici: 0, max_cihaz: 0,
    aylik_fiyat: 0, yillik_fiyat: 0,
    ozellikler: [], aciklama: '', aktif: true,
});
const yeniOzellik = ref('');

const loadPaketler = async () => {
    paketLoading.value = true;
    try {
        const res = await axios.get(route('super-admin.paketler.index'));
        paketListesi.value = res.data;
    } catch (e) { console.error(e); }
    finally { paketLoading.value = false; }
};

const openPaketModal = (paket) => {
    editingPaket.value = paket;
    paketForm.max_personel = paket.max_personel;
    paketForm.max_kullanici = paket.max_kullanici;
    paketForm.max_cihaz = paket.max_cihaz;
    paketForm.aylik_fiyat = paket.aylik_fiyat;
    paketForm.yillik_fiyat = paket.yillik_fiyat;
    paketForm.ozellikler = [...(paket.ozellikler || [])];
    paketForm.aciklama = paket.aciklama || '';
    paketForm.aktif = paket.aktif;
    yeniOzellik.value = '';
    isPaketModalOpen.value = true;
};

const addOzellik = () => {
    if (yeniOzellik.value.trim()) {
        paketForm.ozellikler.push(yeniOzellik.value.trim());
        yeniOzellik.value = '';
    }
};
const removeOzellik = (i) => { paketForm.ozellikler.splice(i, 1); };

const savePaket = async () => {
    try {
        await axios.put(route('super-admin.paketler.guncelle', editingPaket.value.id), paketForm);
        Swal.fire({ icon: 'success', title: 'Başarılı', text: 'Paket güncellendi', timer: 1500, showConfirmButton: false });
        isPaketModalOpen.value = false;
        loadPaketler();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Hata', text: e.response?.data?.message || 'Bir hata oluştu' });
    }
};

const limitLabel = (v) => v == 0 ? 'Sınırsız' : v;

// Aktivite Logları
const aktiviteLogListesi = ref([]);
const aktiviteLoading = ref(false);

const loadAktiviteLoglar = async () => {
    aktiviteLoading.value = true;
    try {
        const res = await axios.get(route('super-admin.aktivite-loglar'));
        aktiviteLogListesi.value = res.data;
    } catch (e) { console.error(e); }
    finally { aktiviteLoading.value = false; }
};

const islemEtiketleri = {
    firma_olusturuldu: { label: 'Firma Oluşturuldu', color: 'bg-green-100 text-green-700', icon: '🏢' },
    firma_guncellendi: { label: 'Firma Güncellendi', color: 'bg-blue-100 text-blue-700', icon: '✏️' },
    firma_silindi: { label: 'Firma Silindi', color: 'bg-red-100 text-red-700', icon: '🗑️' },
    paket_guncellendi: { label: 'Paket Güncellendi', color: 'bg-purple-100 text-purple-700', icon: '📦' },
};

// Destek Biletleri
const destekBiletleri = ref([]);
const destekLoading = ref(false);
const destekFilter = ref('tumu');
const isDestekDetayOpen = ref(false);
const destekDetay = ref(null);
const destekMesajlar = ref([]);
const yeniDestekMesaj = ref('');

const durumRenk = {
    acik: 'bg-blue-100 text-blue-700',
    yanit_bekleniyor: 'bg-amber-100 text-amber-700',
    cevaplandi: 'bg-green-100 text-green-700',
    cozuldu: 'bg-gray-100 text-gray-700',
    kapatildi: 'bg-gray-200 text-gray-500',
};
const durumLabel = {
    acik: 'Açık', yanit_bekleniyor: 'Yanıt Bekleniyor', cevaplandi: 'Cevaplandı', cozuldu: 'Çözüldü', kapatildi: 'Kapatıldı',
};
const oncelikRenk = {
    dusuk: 'text-gray-500', normal: 'text-blue-500', yuksek: 'text-amber-500', acil: 'text-red-600',
};
const kategoriLabel = {
    teknik: '🔧 Teknik', fatura: '💰 Fatura', genel: '📋 Genel', ozellik_talebi: '✨ Özellik',
};

const loadDestekBiletleri = async () => {
    destekLoading.value = true;
    try {
        const res = await axios.get(route('super-admin.destek.index'), { params: { durum: destekFilter.value } });
        destekBiletleri.value = res.data;
    } catch (e) { console.error(e); }
    finally { destekLoading.value = false; }
};

const openDestekDetay = async (bilet) => {
    try {
        const res = await axios.get(route('super-admin.destek.detay', bilet.id));
        destekDetay.value = res.data.bilet;
        destekMesajlar.value = res.data.mesajlar;
        yeniDestekMesaj.value = '';
        isDestekDetayOpen.value = true;
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Hata', text: 'Bilet detayı yüklenemedi' });
    }
};

const sendDestekMesaj = async () => {
    if (!yeniDestekMesaj.value.trim()) return;
    try {
        await axios.post(route('super-admin.destek.mesaj', destekDetay.value.id), { mesaj: yeniDestekMesaj.value });
        yeniDestekMesaj.value = '';
        const res = await axios.get(route('super-admin.destek.detay', destekDetay.value.id));
        destekDetay.value = res.data.bilet;
        destekMesajlar.value = res.data.mesajlar;
        loadDestekBiletleri();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Hata', text: 'Mesaj gönderilemedi' });
    }
};

const changeDestekDurum = async (biletId, durum) => {
    try {
        await axios.put(route('super-admin.destek.durum', biletId), { durum });
        if (destekDetay.value?.id === biletId) destekDetay.value.durum = durum;
        loadDestekBiletleri();
    } catch (e) { console.error(e); }
};

const onTabChange = (tab) => {
    activeTab.value = tab;
    if (tab === 'duyurular' && duyuruListesi.value.length === 0) loadDuyurular();
    if (tab === 'paketler' && paketListesi.value.length === 0) loadPaketler();
    if (tab === 'aktivite' && aktiviteLogListesi.value.length === 0) loadAktiviteLoglar();
    if (tab === 'destek' && destekBiletleri.value.length === 0) loadDestekBiletleri();
};
</script>

<template>
    <Head title="Platform Yönetim Merkezi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Platform Yönetim Merkezi</h2>
                        <p class="text-xs text-gray-500">PDKS SaaS Yönetim Paneli</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Üst Metrik Kartları -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-xs font-medium opacity-80">Aktif Firma</div>
                        <div class="text-2xl font-extrabold mt-1">{{ metrikler.aktifFirma }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-red-400 to-red-600 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-xs font-medium opacity-80">Pasif Firma</div>
                        <div class="text-2xl font-extrabold mt-1">{{ metrikler.pasifFirma }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-xs font-medium opacity-80">Toplam Cihaz</div>
                        <div class="text-2xl font-extrabold mt-1">{{ metrikler.toplamCihaz }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-xs font-medium opacity-80">Toplam Personel</div>
                        <div class="text-2xl font-extrabold mt-1">{{ metrikler.toplamPersonel }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-violet-500 to-violet-700 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-xs font-medium opacity-80">Kullanıcılar</div>
                        <div class="text-2xl font-extrabold mt-1">{{ metrikler.toplamKullanici }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-amber-700 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-xs font-medium opacity-80">Son 24s Kayıt</div>
                        <div class="text-2xl font-extrabold mt-1">{{ metrikler.sonKayitSayisi }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-teal-500 to-teal-700 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-xs font-medium opacity-80">Yeni Firma (30g)</div>
                        <div class="text-2xl font-extrabold mt-1">{{ metrikler.yeniFirma30gun }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl p-4 text-white shadow-lg">
                        <div class="text-xs font-medium opacity-80">Kuyruk Yükü</div>
                        <div class="text-2xl font-extrabold mt-1 flex items-center">
                            {{ metrikler.kuyrukBekleyen }}
                            <svg v-if="metrikler.kuyrukBekleyen > 0" class="w-4 h-4 ml-1 animate-spin opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Abonelik Uyarıları -->
                <div v-if="abonelikGecmis?.length > 0" class="bg-red-50 border border-red-200 rounded-xl p-4">
                    <h3 class="text-sm font-bold text-red-700 flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                        Aboneliği Geçmiş Firmalar ({{ abonelikGecmis.length }})
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="f in abonelikGecmis" :key="f.id" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-100 text-red-800 rounded-lg text-xs font-medium">
                            {{ f.firma_adi }} <span class="opacity-60">({{ formatTarih(f.abonelik_bitis_tarihi) }})</span>
                        </span>
                    </div>
                </div>

                <div v-if="abonelikUyarisi?.length > 0" class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <h3 class="text-sm font-bold text-amber-700 flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                        Aboneliği Yakında Bitecekler (30 gün içinde)
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="f in abonelikUyarisi" :key="f.id" class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-100 text-amber-800 rounded-lg text-xs font-medium">
                            {{ f.firma_adi }}
                            <span class="bg-amber-200 px-1.5 py-0.5 rounded text-[10px] font-bold">{{ gunKaldi(f.abonelik_bitis_tarihi) }} gün</span>
                        </span>
                    </div>
                </div>

                <!-- Grafik + Sistem + Paket -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Kayıt Grafiği -->
                    <div class="lg:col-span-5 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-sm font-bold text-gray-700 mb-3">Son 7 Gün PDKS Kayıtları</h3>
                        <div class="h-52"><Line :data="chartData" :options="chartOptions" /></div>
                    </div>

                    <!-- Paket Dağılımı -->
                    <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-sm font-bold text-gray-700 mb-3">Paket Dağılımı</h3>
                        <div class="h-52"><Doughnut :data="paketChartData" :options="paketChartOptions" /></div>
                    </div>

                    <!-- Sistem Sağlığı -->
                    <div class="lg:col-span-4 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            Sistem Sağlığı
                        </h3>
                        <div class="space-y-4 mt-4">
                            <div>
                                <div class="flex justify-between text-xs font-semibold text-gray-600 mb-1">
                                    <span>CPU Yükü</span><span>%{{ sistem.cpu }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2.5">
                                    <div class="h-2.5 rounded-full transition-all duration-1000" :class="sistem.cpu > 80 ? 'bg-red-500' : sistem.cpu > 50 ? 'bg-amber-500' : 'bg-emerald-500'" :style="{ width: Math.min(sistem.cpu, 100) + '%' }"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs font-semibold text-gray-600 mb-1">
                                    <span>RAM (PHP)</span><span>{{ sistem.ram }} MB</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2.5">
                                    <div class="bg-blue-500 h-2.5 rounded-full transition-all duration-1000" :style="{ width: Math.min((sistem.ram / 128) * 100, 100) + '%' }"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Son Girişler -->
                        <h4 class="text-xs font-bold text-gray-600 mt-6 mb-2">Son Girişler</h4>
                        <div class="space-y-1 max-h-28 overflow-y-auto">
                            <div v-for="g in sonGirisler" :key="g.id" class="flex items-center justify-between text-[11px] py-1 border-b border-gray-50">
                                <span class="font-medium text-gray-700 truncate max-w-[120px]">{{ g.ad_soyad }}</span>
                                <span class="text-gray-400">{{ g.updated_at ? new Date(g.updated_at).toLocaleString('tr-TR', { day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' }) : '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Menü -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="border-b border-gray-100 px-5">
                        <nav class="flex gap-6 -mb-px">
                            <button @click="onTabChange('firmalar')" :class="activeTab === 'firmalar' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-3 px-1 border-b-2 text-sm font-semibold transition">
                                🏢 Firmalar ({{ metrikler.aktifFirma + metrikler.pasifFirma }})
                            </button>
                            <button @click="onTabChange('duyurular')" :class="activeTab === 'duyurular' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-3 px-1 border-b-2 text-sm font-semibold transition">
                                📢 Duyurular
                            </button>
                            <button v-if="can?.odemeleri_yonet" @click="onTabChange('paketler')" :class="activeTab === 'paketler' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-3 px-1 border-b-2 text-sm font-semibold transition">
                                📦 Paketler
                            </button>
                            <button v-if="can?.admin_yonetimi" @click="onTabChange('adminler')" :class="activeTab === 'adminler' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-3 px-1 border-b-2 text-sm font-semibold transition">
                                👑 Sistem Yöneticileri ({{ adminler?.length || 0 }})
                            </button>
                            <button @click="onTabChange('aktivite')" :class="activeTab === 'aktivite' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-3 px-1 border-b-2 text-sm font-semibold transition">
                                📋 Aktivite
                            </button>
                            <button @click="onTabChange('destek')" :class="activeTab === 'destek' ? 'border-rose-500 text-rose-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-3 px-1 border-b-2 text-sm font-semibold transition">
                                🎫 Destek
                            </button>
                            <button v-if="can?.teknik_loglar_gorme" @click="onTabChange('teknik')" :class="activeTab === 'teknik' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-3 px-1 border-b-2 text-sm font-semibold transition">
                                ⚡ Teknik Loglar
                            </button>
                        </nav>
                    </div>

                    <!-- Firma Tablosu -->
                    <div v-show="activeTab === 'firmalar'" class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <input v-model="firmaArama" type="text" placeholder="Firma ara..." class="px-3 py-2 border border-gray-200 rounded-lg text-sm w-64 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                            <button v-if="can?.odemeleri_yonet" @click="isFirmaModalOpen = true" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Yeni Firma Ekle
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-bold text-gray-500 uppercase bg-gray-50 rounded-lg">
                                        <th class="py-3 px-4 rounded-tl-lg">Firma</th>
                                        <th class="py-3 px-4">Paket</th>
                                        <th class="py-3 px-4 text-center">Personel</th>
                                        <th class="py-3 px-4 text-center">Kullanıcı</th>
                                        <th class="py-3 px-4 text-center">Cihaz</th>
                                        <th class="py-3 px-4">Abonelik Bitiş</th>
                                        <th class="py-3 px-4 text-center">Durum</th>
                                        <th class="py-3 px-4 rounded-tr-lg text-right">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="firma in filteredFirmalar" :key="firma.id" class="border-b border-gray-50 hover:bg-indigo-50/40 transition">
                                        <td class="py-3 px-4">
                                            <Link :href="route('super-admin.firmalar.detay', firma.id)" class="font-semibold text-indigo-600 hover:text-indigo-800 hover:underline">
                                                {{ firma.firma_adi }}
                                            </Link>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span :class="{
                                                'bg-gray-100 text-gray-700': firma.paket_tipi === 'Ücretsiz',
                                                'bg-blue-100 text-blue-700': firma.paket_tipi === 'Standart',
                                                'bg-purple-100 text-purple-700': firma.paket_tipi === 'Pro',
                                                'bg-amber-100 text-amber-700': firma.paket_tipi === 'Enterprise',
                                            }" class="px-2 py-0.5 rounded-full text-xs font-bold">
                                                {{ firma.paket_tipi || 'Tanımsız' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center font-mono text-gray-600">{{ firma.personeller_count ?? 0 }}</td>
                                        <td class="py-3 px-4 text-center font-mono text-gray-600">{{ firma.kullanicilar_count ?? 0 }}</td>
                                        <td class="py-3 px-4 text-center font-mono text-gray-600">{{ firma.cihazlar_count ?? 0 }}</td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-1">
                                                <span class="font-mono text-gray-600 text-xs">{{ formatTarih(firma.abonelik_bitis_tarihi) }}</span>
                                                <span v-if="firma.abonelik_bitis_tarihi && gunKaldi(firma.abonelik_bitis_tarihi) <= 0" class="text-red-600 text-[10px] font-bold bg-red-50 px-1 rounded">GEÇMİŞ</span>
                                                <span v-else-if="firma.abonelik_bitis_tarihi && gunKaldi(firma.abonelik_bitis_tarihi) <= 30" class="text-amber-600 text-[10px] font-bold bg-amber-50 px-1 rounded">{{ gunKaldi(firma.abonelik_bitis_tarihi) }}g</span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span v-if="firma.durum" class="inline-flex items-center gap-1 text-green-700 bg-green-50 px-2 py-0.5 rounded-full text-xs font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Aktif
                                            </span>
                                            <span v-else class="inline-flex items-center gap-1 text-red-700 bg-red-50 px-2 py-0.5 rounded-full text-xs font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Pasif
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <Link :href="route('super-admin.firmalar.detay', firma.id)" class="text-gray-500 hover:text-indigo-600 p-1.5 hover:bg-indigo-50 rounded-lg transition" title="Detay">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                </Link>
                                                <button v-if="can?.odemeleri_yonet" @click="openEditModal(firma)" class="text-gray-500 hover:text-blue-600 p-1.5 hover:bg-blue-50 rounded-lg transition" title="Abonelik Yönet">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                </button>
                                                <button @click="impersonate(firma)" class="text-gray-500 hover:text-emerald-600 p-1.5 hover:bg-emerald-50 rounded-lg transition" title="Firma Olarak Giriş">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                                </button>
                                                <button v-if="can?.odemeleri_yonet" @click="firmaSil(firma)" class="text-gray-500 hover:text-red-600 p-1.5 hover:bg-red-50 rounded-lg transition" title="Sil">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Admin Yönetim Tab -->
                    <div v-show="activeTab === 'adminler'" class="p-5">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-bold text-gray-500 uppercase bg-gray-50">
                                    <th class="py-3 px-4 rounded-tl-lg">Admin</th>
                                    <th class="py-3 px-4">E-posta</th>
                                    <th class="py-3 px-4">Yetkiler</th>
                                    <th class="py-3 px-4 rounded-tr-lg text-right">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="admin in adminler" :key="admin.id" class="border-b border-gray-50 hover:bg-purple-50/40 transition">
                                    <td class="py-3 px-4 font-semibold text-gray-900">{{ admin.ad_soyad }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ admin.eposta }}</td>
                                    <td class="py-3 px-4">
                                        <span v-if="admin.super_admin_yetki?.yetkiler?.includes('*')" class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-bold">⭐ Tam Yetkili</span>
                                        <span v-else-if="!admin.super_admin_yetki?.yetkiler?.length" class="text-gray-400 text-xs italic">Yetkisi Yok</span>
                                        <div v-else class="flex gap-1 flex-wrap">
                                            <span v-for="y in admin.super_admin_yetki.yetkiler" :key="y" class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded text-[11px] font-medium">
                                                {{ y.replace(/_/g, ' ') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <button @click="openAdminModal(admin)" class="text-purple-600 hover:text-purple-800 font-medium bg-purple-50 hover:bg-purple-100 px-3 py-1.5 rounded-lg text-xs transition">Yetkilendir</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Teknik Log Tab -->
                    <div v-show="activeTab === 'teknik'" class="p-5">
                        <div v-if="!yavasSorgular?.length" class="text-center py-8 text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm font-medium text-green-600">Yavaş sorgu algılanmadı. Sistem sağlıklı çalışıyor.</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="(sorgu, i) in yavasSorgular" :key="i" class="bg-red-50 border border-red-100 p-3 rounded-lg text-xs font-mono text-gray-700 break-words">
                                <span class="text-red-700 font-bold">[{{ sorgu.tarih ? new Date(sorgu.tarih).toLocaleString('tr-TR') : '-' }}]</span>
                                {{ sorgu.detay }}
                            </div>
                        </div>
                    </div>

                    <!-- Duyurular Tab -->
                    <div v-show="activeTab === 'duyurular'" class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-gray-700">Platform Duyuruları</h3>
                            <button @click="isDuyuruModalOpen = true" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Yeni Duyuru
                            </button>
                        </div>
                        <div v-if="duyuruLoading" class="text-center py-8 text-gray-400 text-sm">Yükleniyor...</div>
                        <div v-else-if="!duyuruListesi.length" class="text-center py-8 text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                            <p class="text-sm">Henüz duyuru yayınlanmamış</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="d in duyuruListesi" :key="d.id" class="border border-gray-100 rounded-xl p-4 hover:shadow-sm transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span :class="tipEtiketleri[d.tip]?.color || 'bg-gray-100 text-gray-700'" class="px-2 py-0.5 rounded-full text-[11px] font-bold">
                                                {{ tipEtiketleri[d.tip]?.label || d.tip }}
                                            </span>
                                            <span class="text-xs text-gray-400">{{ formatTarih(d.created_at) }}</span>
                                        </div>
                                        <h4 class="font-semibold text-gray-900 text-sm">{{ d.baslik }}</h4>
                                        <p class="text-xs text-gray-600 mt-1">{{ d.icerik }}</p>
                                    </div>
                                    <button @click="deleteDuyuru(d.id)" class="text-gray-400 hover:text-red-500 p-1 transition" title="Sil">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paketler Tab -->
                    <div v-show="activeTab === 'paketler'" class="p-5">
                        <div v-if="paketLoading" class="text-center py-8 text-gray-400 text-sm">Yükleniyor...</div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div v-for="p in paketListesi" :key="p.id" class="border rounded-xl p-5 hover:shadow-md transition relative" :class="p.aktif ? 'border-gray-200' : 'border-red-200 opacity-60'">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-lg font-bold" :style="{ color: p.renk }">{{ p.ad }}</h4>
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium">{{ p.firma_sayisi || 0 }} firma</span>
                                </div>
                                <p class="text-xs text-gray-500 mb-3">{{ p.aciklama }}</p>
                                <div class="space-y-1.5 text-xs mb-3">
                                    <div class="flex justify-between"><span class="text-gray-500">Personel</span><span class="font-bold">{{ limitLabel(p.max_personel) }}</span></div>
                                    <div class="flex justify-between"><span class="text-gray-500">Kullanıcı</span><span class="font-bold">{{ limitLabel(p.max_kullanici) }}</span></div>
                                    <div class="flex justify-between"><span class="text-gray-500">Cihaz</span><span class="font-bold">{{ limitLabel(p.max_cihaz) }}</span></div>
                                </div>
                                <div class="border-t border-gray-100 pt-3 mb-3">
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-2xl font-extrabold" :style="{ color: p.renk }">₺{{ Number(p.aylik_fiyat).toLocaleString('tr-TR') }}</span>
                                        <span class="text-xs text-gray-400">/ay</span>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-0.5">₺{{ Number(p.yillik_fiyat).toLocaleString('tr-TR') }} /yıl</div>
                                </div>
                                <div v-if="p.ozellikler?.length" class="mb-3">
                                    <div v-for="(oz, i) in p.ozellikler" :key="i" class="text-[11px] text-gray-600 flex items-center gap-1">
                                        <span class="text-green-500">✓</span> {{ oz }}
                                    </div>
                                </div>
                                <button @click="openPaketModal(p)" class="w-full text-center py-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-xs font-semibold text-gray-700 transition">Düzenle</button>
                            </div>
                        </div>
                    </div>

                    <!-- Aktivite Log Tab -->
                    <div v-show="activeTab === 'aktivite'" class="p-5">
                        <div v-if="aktiviteLoading" class="text-center py-8 text-gray-400 text-sm">Yükleniyor...</div>
                        <div v-else-if="!aktiviteLogListesi.length" class="text-center py-8 text-gray-400">
                            <p class="text-sm">Henüz aktivite kaydı yok</p>
                        </div>
                        <div v-else class="space-y-2 max-h-96 overflow-y-auto">
                            <div v-for="log in aktiviteLogListesi" :key="log.id" class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 transition border-b border-gray-50">
                                <span class="text-lg">{{ islemEtiketleri[log.islem]?.icon || '📝' }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span :class="islemEtiketleri[log.islem]?.color || 'bg-gray-100 text-gray-700'" class="px-2 py-0.5 rounded-full text-[10px] font-bold">
                                            {{ islemEtiketleri[log.islem]?.label || log.islem }}
                                        </span>
                                        <span class="text-xs text-gray-400">{{ log.kullanici_adi || '-' }}</span>
                                    </div>
                                    <p class="text-xs text-gray-600">{{ log.detay }}</p>
                                    <div class="flex items-center gap-3 mt-1 text-[10px] text-gray-400">
                                        <span>{{ formatTarih(log.created_at) }}</span>
                                        <span v-if="log.ip_adresi">IP: {{ log.ip_adresi }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Destek Biletleri Tab -->
                    <div v-show="activeTab === 'destek'" class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex gap-2">
                                <button v-for="f in [['tumu','Tümü'],['acik','Açık'],['yanit_bekleniyor','Yanıt Bekleniyor'],['cozuldu','Çözüldü'],['kapatildi','Kapatıldı']]" :key="f[0]" @click="destekFilter = f[0]; loadDestekBiletleri()" :class="destekFilter === f[0] ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                    {{ f[1] }}
                                </button>
                            </div>
                        </div>
                        <div v-if="destekLoading" class="text-center py-8 text-gray-400 text-sm">Yükleniyor...</div>
                        <div v-else-if="!destekBiletleri.length" class="text-center py-8 text-gray-400">
                            <span class="text-4xl mb-2 block">🎫</span>
                            <p class="text-sm">Henüz destek bileti yok</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="b in destekBiletleri" :key="b.id" @click="openDestekDetay(b)" class="flex items-center gap-4 p-3 border border-gray-100 rounded-xl hover:shadow-sm hover:border-rose-200 transition cursor-pointer">
                                <span class="text-lg" :class="oncelikRenk[b.oncelik]">{{ b.oncelik === 'acil' ? '🔴' : b.oncelik === 'yuksek' ? '🟡' : '🔵' }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm text-gray-900 truncate">{{ b.konu }}</div>
                                    <div class="flex items-center gap-2 mt-0.5 text-[11px]">
                                        <span class="text-gray-400">{{ b.firma_adi }}</span>
                                        <span class="text-gray-300">•</span>
                                        <span class="text-gray-400">{{ kategoriLabel[b.kategori] }}</span>
                                        <span class="text-gray-300">•</span>
                                        <span class="text-gray-400">{{ b.olusturan_adi }}</span>
                                    </div>
                                </div>
                                <span :class="durumRenk[b.durum]" class="px-2 py-0.5 rounded-full text-[10px] font-bold whitespace-nowrap">{{ durumLabel[b.durum] }}</span>
                                <span class="text-xs text-gray-400 whitespace-nowrap">{{ formatTarih(b.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Destek Bilet Detay Modal -->
        <Modal :show="isDestekDetayOpen" @close="isDestekDetayOpen = false" maxWidth="2xl">
            <div class="p-6" v-if="destekDetay">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">{{ destekDetay.konu }}</h2>
                        <div class="flex items-center gap-2 mt-1 text-xs">
                            <span :class="durumRenk[destekDetay.durum]" class="px-2 py-0.5 rounded-full font-bold">{{ durumLabel[destekDetay.durum] }}</span>
                            <span class="text-gray-400">{{ kategoriLabel[destekDetay.kategori] }}</span>
                            <span class="text-gray-400">{{ destekDetay.firma_adi }}</span>
                        </div>
                    </div>
                    <select @change="changeDestekDurum(destekDetay.id, $event.target.value)" :value="destekDetay.durum" class="text-xs border-gray-300 rounded-lg px-2 py-1">
                        <option value="acik">Açık</option>
                        <option value="yanit_bekleniyor">Yanıt Bekleniyor</option>
                        <option value="cevaplandi">Cevaplandı</option>
                        <option value="cozuldu">Çözüldü</option>
                        <option value="kapatildi">Kapatıldı</option>
                    </select>
                </div>

                <!-- Mesajlar -->
                <div class="bg-gray-50 rounded-xl p-4 space-y-3 max-h-80 overflow-y-auto mb-4">
                    <div v-for="m in destekMesajlar" :key="m.id" :class="m.gonderen_tipi === 'admin' ? 'ml-8' : 'mr-8'">
                        <div :class="m.gonderen_tipi === 'admin' ? 'bg-indigo-50 border-indigo-200' : 'bg-white border-gray-200'" class="border rounded-xl p-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold" :class="m.gonderen_tipi === 'admin' ? 'text-indigo-600' : 'text-gray-700'">
                                    {{ m.gonderen_tipi === 'admin' ? '🛡️ Platform Destek' : '👤 ' + (m.gonderen_adi || 'Müşteri') }}
                                </span>
                                <span class="text-[10px] text-gray-400">{{ formatTarih(m.created_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ m.mesaj }}</p>
                        </div>
                    </div>
                </div>

                <!-- Yanıt Formu -->
                <div v-if="destekDetay.durum !== 'kapatildi'" class="flex gap-2">
                    <textarea v-model="yeniDestekMesaj" rows="2" class="flex-1 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Yanıtınızı yazın..."></textarea>
                    <button @click="sendDestekMesaj" :disabled="!yeniDestekMesaj.trim()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition disabled:opacity-50 self-end">Gönder</button>
                </div>
                <div v-else class="text-center text-xs text-gray-400 py-2">Bu bilet kapatılmış.</div>
            </div>
        </Modal>

        <!-- Paket Düzenleme Modal -->
        <Modal :show="isPaketModalOpen" @close="isPaketModalOpen = false" maxWidth="lg">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span class="text-xl">📦</span>
                    {{ editingPaket?.ad }} Paketini Düzenle
                </h2>
                <form @submit.prevent="savePaket" class="mt-5 space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Max Personel <span class="text-gray-400">(0=∞)</span></label>
                            <input v-model.number="paketForm.max_personel" type="number" min="0" class="w-full border-gray-300 rounded-lg text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Max Kullanıcı <span class="text-gray-400">(0=∞)</span></label>
                            <input v-model.number="paketForm.max_kullanici" type="number" min="0" class="w-full border-gray-300 rounded-lg text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Max Cihaz <span class="text-gray-400">(0=∞)</span></label>
                            <input v-model.number="paketForm.max_cihaz" type="number" min="0" class="w-full border-gray-300 rounded-lg text-sm" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Aylık Fiyat (₺)</label>
                            <input v-model.number="paketForm.aylik_fiyat" type="number" min="0" step="0.01" class="w-full border-gray-300 rounded-lg text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Yıllık Fiyat (₺)</label>
                            <input v-model.number="paketForm.yillik_fiyat" type="number" min="0" step="0.01" class="w-full border-gray-300 rounded-lg text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Açıklama</label>
                        <input v-model="paketForm.aciklama" type="text" class="w-full border-gray-300 rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">Özellikler</label>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <span v-for="(oz, i) in paketForm.ozellikler" :key="i" class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-50 text-indigo-700 rounded-lg text-xs">
                                {{ oz }}
                                <button type="button" @click="removeOzellik(i)" class="text-indigo-400 hover:text-red-500">×</button>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <input v-model="yeniOzellik" @keyup.enter.prevent="addOzellik" type="text" placeholder="Yeni özellik..." class="flex-1 border-gray-300 rounded-lg text-sm" />
                            <button type="button" @click="addOzellik" class="px-3 py-2 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-lg text-xs font-bold">Ekle</button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="paketForm.aktif" id="paket_aktif" class="rounded text-indigo-600" />
                        <label for="paket_aktif" class="text-sm text-gray-700 font-medium">Paket Aktif</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="isPaketModalOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">İptal</button>
                        <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition">Paketi Güncelle</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Duyuru Oluşturma Modal -->
        <Modal :show="isDuyuruModalOpen" @close="isDuyuruModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span class="text-xl">📢</span> Yeni Duyuru Yayınla
                </h2>
                <form @submit.prevent="saveDuyuru" class="mt-5 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tip</label>
                        <select v-model="duyuruForm.tip" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-emerald-500 text-sm">
                            <option value="bilgi">ℹ️ Bilgi</option>
                            <option value="uyari">⚠️ Uyarı</option>
                            <option value="bakim">🔧 Planlı Bakım</option>
                            <option value="guncelleme">🚀 Güncelleme</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Başlık</label>
                        <input v-model="duyuruForm.baslik" type="text" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-emerald-500 text-sm" placeholder="Duyuru başlığı..." />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">İçerik</label>
                        <textarea v-model="duyuruForm.icerik" rows="4" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-emerald-500 text-sm" placeholder="Duyuru detayları..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="isDuyuruModalOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">İptal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition">Yayınla</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Yeni Firma Oluşturma Modal -->
        <Modal :show="isFirmaModalOpen" @close="isFirmaModalOpen = false" maxWidth="xl">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 text-lg">🏢</span>
                    Yeni Firma Oluştur
                </h2>
                <form @submit.prevent="saveFirma" class="mt-5 space-y-5">
                    <!-- Firma Bilgileri -->
                    <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Firma Bilgileri</h3>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Firma Adı <span class="text-red-500">*</span></label>
                            <input v-model="firmaForm.firma_adi" type="text" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="ABC Holding A.Ş." />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Vergi No</label>
                                <input v-model="firmaForm.vergi_no" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="1234567890" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Vergi Dairesi</label>
                                <input v-model="firmaForm.vergi_dairesi" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Merkez VD" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Adres</label>
                            <input v-model="firmaForm.adres" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Firma adresi..." />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Paket <span class="text-red-500">*</span></label>
                                <select v-model="firmaForm.paket_tipi" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm">
                                    <option value="Ücretsiz">Ücretsiz</option>
                                    <option value="Standart">Standart</option>
                                    <option value="Pro">Pro</option>
                                    <option value="Enterprise">Enterprise</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Abonelik Bitiş</label>
                                <input v-model="firmaForm.abonelik_bitis_tarihi" type="date" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- Admin Kullanıcı Bilgileri -->
                    <div class="bg-indigo-50 rounded-xl p-4 space-y-3">
                        <h3 class="text-xs font-bold text-indigo-600 uppercase tracking-wider">🔑 Firma Yönetici (Admin) Hesabı</h3>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Ad Soyad <span class="text-red-500">*</span></label>
                            <input v-model="firmaForm.admin_ad_soyad" type="text" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Ahmet Yılmaz" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">E-posta <span class="text-red-500">*</span></label>
                            <input v-model="firmaForm.admin_eposta" type="email" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="ahmet@abcholding.com" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Şifre <span class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                <input v-model="firmaForm.admin_sifre" type="text" required minlength="6" class="flex-1 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm font-mono" placeholder="Minimum 6 karakter" />
                                <button type="button" @click="generatePassword" class="px-3 py-2 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-lg text-xs font-bold transition whitespace-nowrap">🎲 Otomatik</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="isFirmaModalOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">İptal</button>
                        <button type="submit" :disabled="firmaFormLoading" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition disabled:opacity-50">
                            {{ firmaFormLoading ? 'Oluşturuluyor...' : 'Firma Oluştur' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Abonelik Modal -->
        <Modal :show="isModalOpen" @close="isModalOpen = false" maxWidth="xl">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    {{ editingFirma?.firma_adi }} — Firma Düzenle
                </h2>

                <form @submit.prevent="saveAbonelik" class="mt-5 space-y-5">
                    <!-- Firma Bilgileri -->
                    <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Firma Bilgileri</h3>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Firma Adı <span class="text-red-500">*</span></label>
                            <input v-model="form.firma_adi" type="text" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Vergi No</label>
                                <input v-model="form.vergi_no" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Vergi Dairesi</label>
                                <input v-model="form.vergi_dairesi" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Adres</label>
                            <input v-model="form.adres" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm" />
                        </div>
                    </div>

                    <!-- Paket & Abonelik -->
                    <div class="bg-indigo-50 rounded-xl p-4 space-y-3">
                        <h3 class="text-xs font-bold text-indigo-600 uppercase tracking-wider">📦 Paket & Abonelik</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Paket Tipi</label>
                                <select v-model="form.paket_tipi" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm">
                                    <option value="Ücretsiz">Ücretsiz</option>
                                    <option value="Standart">Standart</option>
                                    <option value="Pro">Pro</option>
                                    <option value="Enterprise">Enterprise</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Abonelik Bitiş Tarihi</label>
                                <input type="date" v-model="form.abonelik_bitis_tarihi" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm" />
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" v-model="form.durum" id="durum_cb" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <label for="durum_cb" class="text-sm text-gray-700 font-medium">Firma Aktif</label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="isModalOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">İptal</button>
                        <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">Değişiklikleri Kaydet</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Admin Yetki Modal -->
        <Modal :show="isAdminModalOpen" @close="isAdminModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    {{ editingAdmin?.ad_soyad }} - Yetkiler
                </h2>

                <form @submit.prevent="saveAdminYetki" class="mt-6 space-y-3">
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-red-50 border border-red-100">
                        <input type="checkbox" v-model="adminYetkiler" value="*" class="rounded text-red-600 focus:ring-red-500" />
                        <span class="text-sm font-bold text-red-700">⭐ Tam Yetki (Tüm Sistem)</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50">
                        <input type="checkbox" v-model="adminYetkiler" value="firmalari_gorme" class="rounded text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm text-gray-700">Firmaları ve İstatistikleri Görme</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50">
                        <input type="checkbox" v-model="adminYetkiler" value="odemeleri_yonet" class="rounded text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm text-gray-700">Ödemeleri ve Paketleri Yönetme</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50">
                        <input type="checkbox" v-model="adminYetkiler" value="teknik_loglar" class="rounded text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm text-gray-700">Teknik Loglara Erişme</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50">
                        <input type="checkbox" v-model="adminYetkiler" value="admin_yonetimi" class="rounded text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm text-gray-700">Diğer Adminleri Yönetme</span>
                    </label>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="isAdminModalOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">İptal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition">Yetkileri Kaydet</button>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
