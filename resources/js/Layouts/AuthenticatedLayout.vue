<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { onMounted, onUnmounted } from 'vue';

const page = usePage();
const bildirimler = ref([...(page.props.auth.bildirimler || [])]);
const hasUnread = ref(bildirimler.value.length > 0);
const yetkiler = page.props.auth.yetkiler || {};
const y = (key) => yetkiler[key] ?? false;

// Platform Duyuruları
const platformDuyurulari = ref([...(page.props.platformDuyurulari || [])]);
const closedDuyurular = ref(JSON.parse(localStorage.getItem('pdks_closed_duyurular') || '[]'));
const visibleDuyurular = ref(platformDuyurulari.value.filter(d => !closedDuyurular.value.includes(d.id)));
const closeDuyuru = (id) => {
    closedDuyurular.value.push(id);
    localStorage.setItem('pdks_closed_duyurular', JSON.stringify(closedDuyurular.value));
    visibleDuyurular.value = visibleDuyurular.value.filter(d => d.id !== id);
};
const duyuruTipRenk = { bilgi: '#3b82f6', uyari: '#f59e0b', bakim: '#ef4444', guncelleme: '#10b981' };
const duyuruTipIcon = { bilgi: 'ℹ️', uyari: '⚠️', bakim: '🔧', guncelleme: '🚀' };

// Accordion durumları - localStorage'dan oku
const loadSections = () => {
    try {
        const saved = localStorage.getItem('pdks_sidebar_sections');
        if (saved) return JSON.parse(saved);
    } catch (e) {}
    return { ik: false, personel: true, toplu: false, baglanti: false, tanim: false, hesap_param: false, rapor_ozluk: true, rapor_hesap: true };
};

const sections = ref(loadSections());

const toggleSection = (key) => {
    sections.value[key] = !sections.value[key];
    try { localStorage.setItem('pdks_sidebar_sections', JSON.stringify(sections.value)); } catch (e) {}
};

const hasFeature = (ozellik) => {
    if (page.props.auth?.user?.rol === 'admin') return true;
    const firma = page.props.auth?.firma;
    if (!firma) return true;
    const paket = firma?.paket;
    if (!paket) return true; // Paket atanmamışsa tüm özellikler açık (demo/geliştirme)
    if (paket.paket_adi === 'Enterprise') return true;
    return Array.isArray(paket.ozellikler) && paket.ozellikler.includes(ozellik);
};

onMounted(() => {
    if (window.Echo && page.props.auth.user) {
        window.Echo.private(`App.Models.Kullanici.${page.props.auth.user.id}`)
            .notification((notification) => {
                bildirimler.value.unshift(notification);
                hasUnread.value = true;
            });
    }
});

onUnmounted(() => {
    if (window.Echo && page.props.auth.user) {
        window.Echo.leave(`App.Models.Kullanici.${page.props.auth.user.id}`);
    }
});
</script>

<template>
    <div class="flex flex-col h-screen bg-gray-200 font-sans text-sm select-none">
        <!-- IMPERSONATE BANNER -->
        <div v-if="page.props.impersonate?.aktif" class="bg-gradient-to-r from-amber-500 to-orange-500 text-white px-4 py-1.5 flex items-center justify-between text-xs font-semibold z-50">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                <span>🔑 <b>{{ page.props.impersonate.firmaAdi }}</b> firması olarak giriş yaptınız</span>
            </div>
            <form method="POST" :action="route('super-admin.impersonate-leave')">
                <input type="hidden" name="_token" :value="page.props.csrf_token || document.querySelector('meta[name=csrf-token]')?.content" />
                <button type="submit" class="bg-white/20 hover:bg-white/30 px-3 py-1 rounded-lg transition font-bold">
                    ← Kendi Hesabıma Dön
                </button>
            </form>
        </div>

        <!-- PLATFORM DUYURULARI BANNER -->
        <div v-for="d in visibleDuyurular" :key="d.id" class="px-4 py-2 flex items-center justify-between text-xs font-medium z-40" :style="{ backgroundColor: duyuruTipRenk[d.tip] + '15', borderBottom: '1px solid ' + duyuruTipRenk[d.tip] + '30' }">
            <div class="flex items-center gap-2 flex-1">
                <span class="text-base">{{ duyuruTipIcon[d.tip] || '📢' }}</span>
                <span class="font-bold" :style="{ color: duyuruTipRenk[d.tip] }">{{ d.baslik }}</span>
                <span class="text-gray-600 truncate max-w-[400px]">{{ d.icerik }}</span>
            </div>
            <button @click="closeDuyuru(d.id)" class="text-gray-400 hover:text-gray-600 p-1 transition" title="Kapat">✕</button>
        </div>
        <!-- ÜST MENÜ ÇUBUĞU (Klasik Windows Stil) -->
        <div class="bg-gradient-to-b from-[#eef3fa] to-[#dce6f4] border-b border-[#a0b0cc] px-1 flex items-center h-7 text-xs">
            <Dropdown align="left" width="48">
                <template #trigger>
                    <button class="px-3 py-0.5 hover:bg-[#c8d8ee] rounded-sm transition">Dosya</button>
                </template>
                <template #content>
                    <DropdownLink v-if="page.props.auth?.isSuperAdmin" :href="route('super-admin.index')">🛡️ Platform Yönetimi</DropdownLink>
                    <DropdownLink :href="route('profile.edit')">Profil Ayarları</DropdownLink>
                    <DropdownLink :href="route('logout')" method="post" as="button">Çıkış Yap</DropdownLink>
                </template>
            </Dropdown>
            <Dropdown align="left" width="56">
                <template #trigger>
                    <button class="px-3 py-0.5 hover:bg-[#c8d8ee] rounded-sm transition">Personel İşlemleri</button>
                </template>
                <template #content>
                    <DropdownLink :href="route('personeller.index')">Personel Kartları</DropdownLink>
                    <DropdownLink v-if="y('ek_kazanclar')" :href="route('ek-kazanclar.index')">Ek Kazançlar</DropdownLink>
                    <DropdownLink v-if="y('avans_kesintiler')" :href="route('avans-kesintiler.index')">Kesintiler</DropdownLink>
                    <DropdownLink v-if="y('cihaz_transfer')" :href="route('cihaz-transfer.index')">Cihazdan Veri Transferi</DropdownLink>
                </template>
            </Dropdown>
            <Dropdown v-if="y('hesap_raporlari')" align="left" width="56">
                <template #trigger>
                    <button class="px-3 py-0.5 hover:bg-[#c8d8ee] rounded-sm transition">Hesap Raporları</button>
                </template>
                <template #content>
                    <DropdownLink v-if="y('hesap_puantaj')" :href="route('hesap-raporlari.puantaj-hesaplama')">01. Puantaj Hesaplama</DropdownLink>
                    <DropdownLink v-if="y('hesap_genel_maas')" :href="route('hesap-raporlari.genel-maas-ekstresi')">02. Genel Bazda Maaş Ekstresi</DropdownLink>
                    <DropdownLink v-if="y('hesap_kisi_maas')" :href="route('hesap-raporlari.kisi-bazinda-maas-ekstresi')">03. Kişi Bazında Maaş Ekstresi</DropdownLink>
                    <DropdownLink v-if="y('hesap_maas_pusulasi')" :href="route('hesap-raporlari.maas-pusulasi')">04. Maaş Pusulası</DropdownLink>
                    <DropdownLink v-if="y('hesap_grup_maas')" :href="route('hesap-raporlari.grup-bazli-maas-ekstresi')">05. Grup Bazlı Maaş Ekstresi</DropdownLink>
                </template>
            </Dropdown>
            <Dropdown v-if="y('toplu_islemler')" align="left" width="56">
                <template #trigger>
                    <button class="px-3 py-0.5 hover:bg-[#c8d8ee] rounded-sm transition">Toplu İşlemler</button>
                </template>
                <template #content>
                    <DropdownLink v-if="y('toplu_maas')" :href="route('toplu-islemler.maas-artirimi')">Toplu Maaş Artırımı</DropdownLink>
                    <DropdownLink v-if="y('toplu_hareket')" :href="route('toplu-islemler.hareket')">Toplu Hareket İşlemi</DropdownLink>
                    <DropdownLink v-if="y('toplu_izin')" :href="route('toplu-islemler.izin')">Toplu İzin İşlemi</DropdownLink>
                    <DropdownLink v-if="y('toplu_avans')" :href="route('toplu-islemler.avans')">Toplu Avans İşlemi</DropdownLink>
                    <DropdownLink v-if="y('toplu_prim')" :href="route('toplu-islemler.prim')">Toplu Prim İşlemi</DropdownLink>
                    <DropdownLink v-if="y('toplu_yemek')" :href="route('toplu-islemler.yemek-atama')">Toplu Yemek Atama</DropdownLink>
                    <DropdownLink v-if="y('toplu_servis_yol')" :href="route('toplu-islemler.servis-yol-atama')">Toplu Servis / Yol Parası</DropdownLink>
                    <DropdownLink v-if="y('toplu_giris_cikis')" :href="route('toplu-islemler.giris-cikis-duzenleme')">Toplu Giriş Çıkış Düzenleme</DropdownLink>
                    <DropdownLink v-if="y('toplu_mesaj')" :href="route('toplu-islemler.toplu-mesaj')">Toplu Mesaj Gönderimi</DropdownLink>
                    <DropdownLink v-if="y('toplu_mail')" :href="route('toplu-islemler.toplu-mail')">Toplu Mail Gönderimi</DropdownLink>
                    <DropdownLink v-if="y('zamanlanmis_bildirimler')" :href="route('toplu-islemler.zamanlanmis-bildirimler')">Zamanlanmış Bildirimler</DropdownLink>
                </template>
            </Dropdown>
            <Dropdown v-if="y('tanim_islemleri')" align="left" width="56">
                <template #trigger>
                    <button class="px-3 py-0.5 hover:bg-[#c8d8ee] rounded-sm transition">Tanım İşlemleri</button>
                </template>
                <template #content>
                    <DropdownLink :href="route('tanim.kodlar', 'sirket')">Şirket Tanımlama</DropdownLink>
                    <DropdownLink :href="route('tanim.kodlar', 'departman')">Departman Tanımlama</DropdownLink>
                    <DropdownLink :href="route('tanim.kodlar', 'bolum')">Bölüm Tanımlama</DropdownLink>
                    <DropdownLink :href="route('tanim.kodlar', 'odeme')">Ödeme Tanımlama</DropdownLink>
                    <DropdownLink :href="route('tanim.kodlar', 'servis')">Servis Tanımlama</DropdownLink>
                    <DropdownLink :href="route('tanim.kodlar', 'hesap_gurubu')">Hesap Grubu Tanımlama</DropdownLink>
                    <DropdownLink :href="route('tanim.kullanicilar')">Kullanıcılar</DropdownLink>
                    <DropdownLink :href="route('tanim.mail-ayarlari')">Mail Ayarları</DropdownLink>
                    <DropdownLink :href="route('tanim.mesaj-ayarlari')">Mesaj (SMS) Ayarları</DropdownLink>
                </template>
            </Dropdown>
            <Dropdown v-if="y('tanim_islemleri')" align="left" width="56">
                <template #trigger>
                    <button class="px-3 py-0.5 hover:bg-[#c8d8ee] rounded-sm transition">Araçlar</button>
                </template>
                <template #content>
                    <DropdownLink :href="route('tanim.calisma-plani.index')">Genel Gruplar Çalışma Planı</DropdownLink>
                    <DropdownLink :href="route('tanim.puantaj-parametreleri.index')">Aylık Puantaj Parametreleri</DropdownLink>
                    <DropdownLink :href="route('tanim.tatil-izin.index')">Tatil ve İzin Tanımlamaları</DropdownLink>
                    <DropdownLink :href="route('tanim.gunluk-puantaj.index')">Günlük Puantaj Parametreleri</DropdownLink>
                    <DropdownLink :href="route('tanim.bordro-alanlari.index')">Bordro Alanı Tanımlamaları</DropdownLink>
                    <DropdownLink :href="route('tanim.vardiyalar.index')">Vardiya Tanımları</DropdownLink>
                    <DropdownLink :href="route('tanim.personel-calisma-plan.index')">Personele Özel Çalışma Planları</DropdownLink>
                    <DropdownLink :href="route('tanim.personel-izin.index')">Personel İzin Yönetimi</DropdownLink>
                </template>
            </Dropdown>
            <Dropdown align="left" width="48">
                <template #trigger>
                    <button class="px-3 py-0.5 hover:bg-[#c8d8ee] rounded-sm transition">Yardım</button>
                </template>
                <template #content>
                    <DropdownLink :href="route('dashboard')">Ana Sayfa</DropdownLink>
                    <DropdownLink href="https://techsend.io" target="_blank">Hakkında</DropdownLink>
                </template>
            </Dropdown>
            
            <!-- Sağ taraf: Kullanıcı ve Bildirim -->
            <div class="ml-auto flex items-center space-x-2">
                <!-- Bildirim -->
                <Dropdown align="right" width="64">
                    <template #trigger>
                        <button class="relative p-1 text-gray-600 hover:text-gray-800 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span v-if="hasUnread" class="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                            </span>
                        </button>
                    </template>
                    <template #content>
                        <div class="px-3 py-2 border-b text-xs font-bold text-gray-700 bg-gray-50">Bildirim Merkezi</div>
                        <div class="max-h-48 overflow-y-auto">
                            <div v-for="bildirim in bildirimler" :key="bildirim.id" class="px-3 py-2 border-b hover:bg-gray-50 text-xs">
                                <div class="font-semibold" :class="bildirim.tur === 'uyari' ? 'text-red-500' : 'text-blue-500'">
                                    {{ bildirim.tur === 'uyari' ? 'Uyarı' : 'Bilgi' }}
                                </div>
                                <div class="mt-1 text-gray-700">{{ bildirim.mesaj || bildirim.data?.mesaj }}</div>
                            </div>
                            <div v-if="bildirimler.length === 0" class="px-3 py-4 text-center text-gray-400 text-xs">Bildirim yok</div>
                        </div>
                    </template>
                </Dropdown>
                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button class="flex items-center text-xs text-gray-700 hover:text-gray-900 transition px-2">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            {{ page.props.auth.user.ad_soyad || 'Kullanıcı' }}
                            <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                        </button>
                    </template>
                    <template #content>
                        <DropdownLink :href="route('profile.edit')">Profil</DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">Çıkış Yap</DropdownLink>
                    </template>
                </Dropdown>
            </div>
        </div>

        <!-- TOOLBAR (İkon Çubuğu) -->
        <div class="bg-gradient-to-b from-[#f0f4fc] to-[#e0e8f5] border-b border-[#b0bcd4] px-2 py-1 flex items-center space-x-1 h-12">
            <Link :href="route('dashboard')" class="toolbar-btn" title="Ana Sayfa">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            </Link>
            <Link :href="route('personeller.index')" class="toolbar-btn" title="Personel Kartları">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </Link>
            <Link :href="route('cihazlar.index')" class="toolbar-btn" title="Cihaz Yönetimi">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            </Link>
            <Link :href="route('raporlar.index')" class="toolbar-btn" title="Raporlar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </Link>
            <div class="w-px h-8 bg-[#b0bcd4] mx-1"></div>
            <template v-if="hasFeature('şube_yönetimi')">
                <Link :href="route('subeler.index')" class="toolbar-btn" title="Şube Yönetimi">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </Link>
            </template>
            <template v-if="hasFeature('servis_takibi')">
                <Link :href="route('servisler.index')" class="toolbar-btn" title="Servis Kontrol">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                </Link>
            </template>
            <div class="w-px h-8 bg-[#b0bcd4] mx-1"></div>
            <button class="toolbar-btn" title="Yardım">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </button>
            
            <!-- Süper Admin butonu -->
            <template v-if="page.props.auth.user.rol === 'admin'">
                <div class="w-px h-8 bg-[#b0bcd4] mx-1"></div>
                <Link :href="route('super-admin.index')" class="toolbar-btn !text-indigo-700 !border-indigo-300 !bg-indigo-50 hover:!bg-indigo-100" title="Süper Admin Paneli">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </Link>
            </template>
        </div>

        <!-- ANA İÇERİK ALANI (3 Panel) -->
        <div class="flex flex-1 overflow-hidden">
            <!-- SOL PANEL (Personel İşlemleri, Toplu İşlemler, Tanım İşlemleri) -->
            <aside class="w-52 bg-white border-r border-gray-400 flex flex-col overflow-y-auto shadow-inner">
                
                <!-- İK Yönetimi -->
                <div class="border-b border-gray-300">
                    <button @click="toggleSection('ik')" class="sidebar-section-header">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            <span class="font-semibold text-xs">İK Yönetimi</span>
                        </div>
                        <svg :class="{'rotate-180': !sections.ik}" class="w-3.5 h-3.5 transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div v-show="sections.ik" class="bg-white">
                        <Link :href="route('ik.izin-talepleri')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/ik/izin-talepleri')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-teal-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 011 1v3a1 1 0 11-2 0V8a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                            İzin Talepleri
                        </Link>
                        <Link :href="route('ik.performans')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/ik/performans')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path></svg>
                            Performans
                        </Link>
                        <Link :href="route('ik.egitimler')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/ik/egitimler')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"></path><path d="M3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0z"></path></svg>
                            Eğitim Takibi
                        </Link>
                        <Link :href="route('ik.disiplin')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/ik/disiplin')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"></path></svg>
                            Disiplin Kayıtları
                        </Link>
                        <Link :href="route('ik.kidem-hesaplayici')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/ik/kidem')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H6zm1 2a1 1 0 000 2h6a1 1 0 100-2H7zm6 7a1 1 0 011 1v3a1 1 0 11-2 0v-3a1 1 0 011-1zm-3-1a1 1 0 10-2 0v4a1 1 0 102 0v-4zm-4 3a1 1 0 012 0v1a1 1 0 11-2 0v-1z" clip-rule="evenodd"></path></svg>
                            Kıdem Hesaplayıcı
                        </Link>
                        <Link :href="route('personeller.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('personeller.*')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"></path></svg>
                            Özlük Dosyaları
                        </Link>
                    </div>
                </div>

                <!-- Personel İşlemleri -->
                <div class="border-b border-gray-300">
                    <button @click="toggleSection('personel')" class="sidebar-section-header">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span class="font-semibold text-xs">Personel İşlemleri</span>
                        </div>
                        <svg :class="{'rotate-180': !sections.personel}" class="w-3.5 h-3.5 transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div v-show="sections.personel" class="bg-white">
                        <Link :href="route('personeller.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('personeller.*')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"></path></svg>
                            Personel Kartları
                        </Link>
                        <Link v-if="y('ek_kazanclar')" :href="route('ek-kazanclar.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('ek-kazanclar.*')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path></svg>
                            Ek Kazançlar
                        </Link>
                        <Link v-if="y('avans_kesintiler')" :href="route('avans-kesintiler.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('avans-kesintiler.*')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                            Kesintiler
                        </Link>
                    </div>
                </div>

                <!-- Toplu İşlemler -->
                <div v-if="y('toplu_islemler')" class="border-b border-gray-300">
                    <button @click="toggleSection('toplu')" class="sidebar-section-header">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            <span class="font-semibold text-xs">Toplu İşlemler</span>
                        </div>
                        <svg :class="{'rotate-180': !sections.toplu}" class="w-3.5 h-3.5 transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div v-show="sections.toplu" class="bg-white">
                        <Link v-if="y('toplu_maas')" :href="route('toplu-islemler.maas-artirimi')" class="sidebar-item" :class="{'sidebar-item-active': route().current('toplu-islemler.maas-artirimi')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path></svg>
                            Toplu Maaş Artırımı
                        </Link>
                        <Link v-if="y('toplu_hareket')" :href="route('toplu-islemler.hareket')" class="sidebar-item" :class="{'sidebar-item-active': route().current('toplu-islemler.hareket')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path></svg>
                            Toplu Hareket İşlemi
                        </Link>
                        <Link v-if="y('toplu_izin')" :href="route('toplu-islemler.izin')" class="sidebar-item" :class="{'sidebar-item-active': route().current('toplu-islemler.izin')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-teal-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
                            Toplu İzin İşlemi
                        </Link>
                        <Link v-if="y('toplu_avans')" :href="route('toplu-islemler.avans')" class="sidebar-item" :class="{'sidebar-item-active': route().current('toplu-islemler.avans')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path></svg>
                            Toplu Avans İşlemi
                        </Link>
                        <Link v-if="y('toplu_prim')" :href="route('toplu-islemler.prim')" class="sidebar-item" :class="{'sidebar-item-active': route().current('toplu-islemler.prim')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm4.707 3.707a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L8.414 9H10a3 3 0 013 3v1a1 1 0 102 0v-1a5 5 0 00-5-5H8.414l1.293-1.293z" clip-rule="evenodd"></path></svg>
                            Toplu Prim İşlemi
                        </Link>
                        <Link v-if="y('toplu_yemek')" :href="route('toplu-islemler.yemek-atama')" class="sidebar-item" :class="{'sidebar-item-active': route().current('toplu-islemler.yemek-atama')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
                            Toplu Yemek Atama
                        </Link>
                        <Link v-if="y('toplu_servis_yol')" :href="route('toplu-islemler.servis-yol-atama')" class="sidebar-item" :class="{'sidebar-item-active': route().current('toplu-islemler.servis-yol-atama')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1h3.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1v-2a4 4 0 00-4-4h-2V5a1 1 0 00-1-1H3z"></path></svg>
                            Toplu Servis / Yol Parası
                        </Link>
                        <Link v-if="y('toplu_giris_cikis')" :href="route('toplu-islemler.giris-cikis-duzenleme')" class="sidebar-item" :class="{'sidebar-item-active': route().current('toplu-islemler.giris-cikis-duzenleme')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-pink-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path></svg>
                            Toplu Giriş Çıkış Düzenleme
                        </Link>
                        <Link v-if="y('toplu_mesaj')" :href="route('toplu-islemler.toplu-mesaj')" class="sidebar-item" :class="{'sidebar-item-active': route().current('toplu-islemler.toplu-mesaj')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path><path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"></path></svg>
                            Toplu Mesaj Gönderimi
                        </Link>
                        <Link v-if="y('toplu_mail')" :href="route('toplu-islemler.toplu-mail')" class="sidebar-item" :class="{'sidebar-item-active': route().current('toplu-islemler.toplu-mail')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path></svg>
                            Toplu Mail Gönderimi
                        </Link>
                        <Link v-if="y('zamanlanmis_bildirimler')" :href="route('toplu-islemler.zamanlanmis-bildirimler')" class="sidebar-item" :class="{'sidebar-item-active': route().current('toplu-islemler.zamanlanmis-bildirimler')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                            Zamanlanmış Bildirimler
                        </Link>
                    </div>
                </div>

                <!-- Bağlantılar -->
                <div class="border-b border-gray-300">
                    <button @click="toggleSection('baglanti')" class="sidebar-section-header">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            <span class="font-semibold text-xs">Bağlantılar</span>
                        </div>
                        <svg :class="{'rotate-180': !sections.baglanti}" class="w-3.5 h-3.5 transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div v-show="sections.baglanti" class="bg-white">
                        <Link v-if="y('cihaz_transfer')" :href="route('cihaz-transfer.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('cihaz-transfer.*')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"></path></svg>
                            Cihazdan Veri Transferi
                        </Link>
                        <Link :href="route('baglanti.mobil')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/baglanti/mobil')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-violet-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>
                            Mobil Bağlantı
                        </Link>
                        <span class="sidebar-item text-gray-400 cursor-default">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-violet-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            API Ayarları
                            <span class="ml-auto text-[8px] bg-violet-100 text-violet-600 px-1.5 rounded font-bold">YAKINDA</span>
                        </span>
                    </div>
                </div>

                <!-- Tanım İşlemleri -->
                <div v-if="y('tanim_islemleri')" class="border-b border-gray-300">
                    <button @click="toggleSection('tanim')" class="sidebar-section-header">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>
                            <span class="font-semibold text-xs">Tanım İşlemleri</span>
                        </div>
                        <svg :class="{'rotate-180': !sections.tanim}" class="w-3.5 h-3.5 transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div v-show="sections.tanim" class="bg-white">
                        <Link :href="route('tanim.kodlar', 'sirket')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/kodlar/sirket')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            Şirket Tanımlama
                        </Link>
                        <Link v-if="hasFeature('şube_yönetimi')" :href="route('subeler.index')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/subeler')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Şube Tanımlama
                        </Link>
                        <Link :href="route('tanim.kodlar', 'departman')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/kodlar/departman')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Departman Tanımlama
                        </Link>
                        <Link :href="route('tanim.kodlar', 'bolum')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/kodlar/bolum')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                            Bölüm Tanımlama
                        </Link>
                        <Link :href="route('tanim.kodlar', 'odeme')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/kodlar/odeme')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            Ödeme Tanımlama
                        </Link>
                        <Link :href="route('tanim.kodlar', 'servis')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/kodlar/servis')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                            Servis Tanımlama
                        </Link>
                        <Link :href="route('tanim.kodlar', 'hesap_gurubu')" class="sidebar-item" :class="{'sidebar-item-active': $page.url.includes('/kodlar/hesap_gurubu')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                            Hesap Grubu Tanımlama
                        </Link>
                        <Link :href="route('tanim.kullanicilar')" class="sidebar-item" :class="{'sidebar-item-active': route().current('tanim.kullanicilar')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"></path></svg>
                            Kullanıcılar
                        </Link>
                        <Link :href="route('tanim.mail-ayarlari')" class="sidebar-item" :class="{'sidebar-item-active': route().current('tanim.mail-ayarlari')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path></svg>
                            Mail Ayarları
                        </Link>
                        <Link :href="route('tanim.mesaj-ayarlari')" class="sidebar-item" :class="{'sidebar-item-active': route().current('tanim.mesaj-ayarlari')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path><path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"></path></svg>
                            Mesaj (SMS) Ayarları
                        </Link>
                    </div>
                </div>

                <!-- HESAP PARAMETRELERİ -->
                <div v-if="y('tanim_islemleri')" class="border-b border-gray-300">
                    <button @click="toggleSection('hesap_param')" class="sidebar-section-header">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <span class="font-semibold text-xs">Hesap Parametreleri</span>
                        </div>
                        <svg :class="{'rotate-180': !sections.hesap_param}" class="w-3.5 h-3.5 transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div v-show="sections.hesap_param" class="bg-white">
                        <Link :href="route('tanim.calisma-plani.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('tanim.calisma-plani.index')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Genel Gruplar Çalışma Planı
                        </Link>
                        <Link :href="route('tanim.puantaj-parametreleri.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('tanim.puantaj-parametreleri.index')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            Aylık Puantaj Parametreleri
                        </Link>
                        <Link :href="route('tanim.tatil-izin.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('tanim.tatil-izin.index')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Tatil ve İzin Tanımlamaları
                        </Link>
                        <Link :href="route('tanim.gunluk-puantaj.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('tanim.gunluk-puantaj.index')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Günlük Puantaj Parametreleri
                        </Link>
                        <Link :href="route('tanim.bordro-alanlari.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('tanim.bordro-alanlari.index')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Bordro Alanı Tanımlamaları
                        </Link>
                        <Link :href="route('tanim.vardiyalar.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('tanim.vardiyalar.index')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Vardiya Tanımları
                        </Link>
                        <Link :href="route('tanim.personel-calisma-plan.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('tanim.personel-calisma-plan.index')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Personele Özel Çalışma Planları
                        </Link>
                        <Link :href="route('tanim.personel-izin.index')" class="sidebar-item" :class="{'sidebar-item-active': route().current('tanim.personel-izin.index')}">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Personel İzin Yönetimi
                        </Link>
                    </div>
                </div>
            </aside>

            <!-- ANA İÇERİK ALANI -->
            <main class="flex-1 overflow-y-auto bg-gray-100">
                <slot />
            </main>

            <!-- SAĞ PANEL (Genel Raporlar) -->
            <aside class="w-64 bg-white border-l border-gray-400 flex flex-col overflow-y-auto shadow-inner">
                <div class="bg-gradient-to-r from-[#e8eef8] to-[#dce4f2] border-b border-gray-300 px-3 py-2 flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1.5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd"></path></svg>
                        <span class="font-bold text-xs text-gray-700">Genel Raporlar</span>
                    </div>
                </div>
                
                <div v-if="y('genel_raporlar')" class="border-b border-gray-300">
                    <button @click="toggleSection('rapor_ozluk')" class="sidebar-section-header">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                            <span class="font-semibold text-xs">Özlük Rapor</span>
                        </div>
                        <svg :class="{'rotate-180': !sections.rapor_ozluk}" class="w-3.5 h-3.5 transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div v-show="sections.rapor_ozluk" class="px-1 py-1">
                        <a :href="route('raporlar.r01')" class="report-item">01. Genel Bazda Giriş Çıkış Listesi</a>
                        <a :href="route('raporlar.r02')" class="report-item">02. Kişi Bazında Giriş Çıkış Listesi</a>
                        <a :href="route('raporlar.r03')" class="report-item">03. Genel Bazda Geç Kalanlar Listesi</a>
                        <a :href="route('raporlar.r04')" class="report-item">04. Kişi Bazında Geç Kalanlar Listesi</a>
                        <a :href="route('raporlar.r05')" class="report-item">05. Genel Bazda Erken Çıkanlar Listesi</a>
                        <a :href="route('raporlar.r06')" class="report-item">06. Kişi Bazında Erken Çıkanlar Listesi</a>
                        <a :href="route('raporlar.r07')" class="report-item">07. Mesaiye Kalanlar Listesi</a>
                        <a :href="route('raporlar.r08')" class="report-item">08. Devamsızlar Listesi</a>
                        <a :href="route('raporlar.r09')" class="report-item">09. Girişte Kart Kullanmayı Unutanlar</a>
                        <a :href="route('raporlar.r10')" class="report-item">10. Çıkışta Kart Kullanmayı Unutanlar</a>
                        <a :href="route('raporlar.r11')" class="report-item">11. Giriş yada Çıkışta Unutanlar</a>
                        <a :href="route('raporlar.r12')" class="report-item">12. Şuan İçerideki Personeller</a>
                        <a :href="route('raporlar.r13')" class="report-item">13. Elle Müdahale Yapılmış Hareketler</a>
                        <a :href="route('raporlar.r14')" class="report-item">14. Personellerin Not Bilgileri</a>
                        <a :href="route('raporlar.r15')" class="report-item">15. Personellerin İrtibat Bilgileri</a>
                        <a :href="route('raporlar.r16')" class="report-item">16. İşe Giren Personeller</a>
                        <a :href="route('raporlar.r17')" class="report-item">17. İşten Ayrılan Personeller</a>
                        <a :href="route('raporlar.r18')" class="report-item">18. Tatil Günü Çalışanlar</a>
                        <a :href="route('raporlar.r19')" class="report-item">19. İzin Kullananlar</a>
                        <a :href="route('raporlar.r20')" class="report-item">20. Avans Listesi</a>
                        <a :href="route('raporlar.r21')" class="report-item">21. Prim Listesi</a>
                        <a :href="route('raporlar.r22')" class="report-item">22. Aylık Devam Listesi</a>
                    </div>
                </div>

                <div v-if="y('hesap_raporlari')" class="border-b border-gray-300">
                    <button @click="toggleSection('rapor_hesap')" class="sidebar-section-header">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm1 8a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                            <span class="font-semibold text-xs">Hesap Raporları</span>
                        </div>
                        <svg :class="{'rotate-180': !sections.rapor_hesap}" class="w-3.5 h-3.5 transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div v-show="sections.rapor_hesap" class="px-1 py-1">
                        <a v-if="y('hesap_puantaj')" :href="route('hesap-raporlari.puantaj-hesaplama')" class="report-item report-item-hesap">01. Puantaj Hesaplama</a>
                        <a v-if="y('hesap_genel_maas')" :href="route('hesap-raporlari.genel-maas-ekstresi')" class="report-item report-item-hesap">02. Genel Bazda Maaş Ekstresi</a>
                        <a v-if="y('hesap_kisi_maas')" :href="route('hesap-raporlari.kisi-bazinda-maas-ekstresi')" class="report-item report-item-hesap">03. Kişi Bazında Maaş Ekstresi</a>
                        <a v-if="y('hesap_maas_pusulasi')" :href="route('hesap-raporlari.maas-pusulasi')" class="report-item report-item-hesap">04. Maaş Pusulası</a>
                        <a v-if="y('hesap_grup_maas')" :href="route('hesap-raporlari.grup-bazli-maas-ekstresi')" class="report-item report-item-hesap">05. Grup Bazlı Maaş Ekstresi</a>
                    </div>
                </div>
            </aside>
        </div>

        <!-- ALT DURUM ÇUBUĞU -->
        <div class="bg-gradient-to-b from-[#e8eef8] to-[#dce4f0] border-t border-[#a0b0cc] px-3 py-0.5 flex items-center justify-between text-[10px] text-gray-600 h-5">
            <span>PDKS Personel Devam Kontrol Sistemi v1.0 — techsend.io</span>
            <span>{{ page.props.auth.user.ad_soyad || 'Kullanıcı' }} | {{ new Date().toLocaleDateString('tr-TR') }}</span>
        </div>
    </div>
</template>

<style scoped>
.toolbar-btn {
    @apply flex items-center justify-center w-10 h-10 rounded border border-transparent bg-transparent text-gray-600 hover:bg-white hover:border-gray-300 hover:shadow-sm transition cursor-pointer;
}

.sidebar-section-header {
    @apply w-full flex items-center justify-between px-3 py-2 bg-gradient-to-r from-[#e8eef8] to-[#dce4f2] hover:from-[#d8e4f4] hover:to-[#ccdcee] text-gray-800 transition cursor-pointer;
}

.sidebar-item {
    @apply flex items-center px-4 py-1.5 text-xs text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition cursor-pointer border-l-2 border-transparent;
}

.sidebar-item-active {
    @apply bg-blue-50 text-blue-700 border-l-2 border-blue-500 font-semibold;
}

.report-item {
    @apply flex items-center px-2 py-1 text-[11px] text-gray-700 hover:bg-yellow-50 hover:text-blue-700 transition cursor-pointer rounded-sm;
}
.report-item::before {
    content: '';
    @apply w-3 h-3 mr-1.5 bg-blue-100 rounded-sm border border-blue-300 flex-shrink-0 inline-block;
}
.report-item-hesap::before {
    @apply bg-indigo-100 border-indigo-300;
}
</style>
