<script setup>
import { ref, computed, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({ kullanicilar: Array, roller: Object, yetkiMatrisi: Object, yetkiEtiketleri: Object });
const page = usePage();

const liste = ref([...(props.kullanicilar || [])]);
const activeTab = ref('kullanicilar');
const showForm = ref(false);
const editMode = ref(false);
const editId = ref(null);
const showPassword = ref(false);
const search = ref('');
const yukleniyor = ref(false);

const form = reactive({ ad_soyad: '', eposta: '', sifre: '', rol: 'kullanici' });

const rolRenkleri = {
    admin: 'bg-red-100 text-red-700 border-red-300',
    kullanici: 'bg-blue-100 text-blue-700 border-blue-300',
    muhasebe: 'bg-green-100 text-green-700 border-green-300',
    ik: 'bg-purple-100 text-purple-700 border-purple-300',
    izleyici: 'bg-gray-100 text-gray-600 border-gray-300',
};

const rolBaslikRenkleri = {
    admin: 'bg-red-500 text-white',
    kullanici: 'bg-blue-500 text-white',
    muhasebe: 'bg-green-500 text-white',
    ik: 'bg-purple-500 text-white',
    izleyici: 'bg-gray-500 text-white',
};

const filtered = computed(() => {
    if (!search.value) return liste.value;
    const q = search.value.toLowerCase();
    return liste.value.filter(k =>
        k.ad_soyad.toLowerCase().includes(q) ||
        k.eposta.toLowerCase().includes(q) ||
        (props.roller[k.rol] || k.rol).toLowerCase().includes(q)
    );
});

const yetkiGruplari = computed(() => {
    const keys = Object.keys(props.yetkiEtiketleri || {});
    const gruplar = [
        { baslik: 'Genel', keys: keys.filter(k => ['dashboard', 'personel_islemleri', 'personel_kartlari', 'raporlar'].includes(k)) },
        { baslik: 'Cihaz & Altyapı', keys: keys.filter(k => ['cihaz_islemleri', 'cihaz_transfer', 'subeler', 'servisler'].includes(k)) },
        { baslik: 'Mali İşlemler', keys: keys.filter(k => ['ek_kazanclar', 'avans_kesintiler'].includes(k)) },
        { baslik: 'Toplu İşlemler', keys: keys.filter(k => k.startsWith('toplu_')) },
        { baslik: 'Bildirimler', keys: keys.filter(k => ['zamanlanmis_bildirimler', 'toplu_mesaj', 'toplu_mail'].includes(k)) },
        { baslik: 'Yönetim & Ayarlar', keys: keys.filter(k => ['tanim_islemleri', 'kullanici_yonetimi', 'mail_ayarlari', 'mesaj_ayarlari'].includes(k)) },
    ];
    return gruplar.filter(g => g.keys.length > 0);
});

const rolKeys = computed(() => Object.keys(props.roller || {}));

const resetForm = () => {
    Object.assign(form, { ad_soyad: '', eposta: '', sifre: '', rol: 'kullanici' });
    editMode.value = false;
    editId.value = null;
    showPassword.value = false;
};

const openNew = () => { resetForm(); showForm.value = true; activeTab.value = 'kullanicilar'; };

const editKullanici = (k) => {
    Object.assign(form, { ad_soyad: k.ad_soyad, eposta: k.eposta, sifre: '', rol: k.rol });
    editMode.value = true;
    editId.value = k.id;
    showForm.value = true;
    activeTab.value = 'kullanicilar';
};

const save = async () => {
    if (!form.ad_soyad || !form.eposta) { Swal.fire('Uyarı', 'Ad soyad ve e-posta zorunludur.', 'warning'); return; }
    if (!editMode.value && !form.sifre) { Swal.fire('Uyarı', 'Yeni kullanıcı için şifre zorunludur.', 'warning'); return; }
    yukleniyor.value = true;
    try {
        if (editMode.value) {
            const res = await axios.put(route('tanim.kullanicilar.update', editId.value), { ...form });
            const idx = liste.value.findIndex(x => x.id === editId.value);
            if (idx >= 0) liste.value[idx] = { ...liste.value[idx], ...res.data.item };
            Swal.fire({toast:true, position:'top-end', icon:'success', title:'Güncellendi', showConfirmButton:false, timer:1200});
        } else {
            const res = await axios.post(route('tanim.kullanicilar.store'), { ...form });
            liste.value.push(res.data.item);
            Swal.fire({toast:true, position:'top-end', icon:'success', title:'Oluşturuldu', showConfirmButton:false, timer:1200});
        }
        showForm.value = false;
        resetForm();
    } catch(e) {
        const msg = e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join('<br>') : (e.response?.data?.message || 'Hata oluştu');
        Swal.fire('Hata', msg, 'error');
    }
    yukleniyor.value = false;
};

const sil = (k) => {
    if (k.id === page.props.auth?.user?.id) { Swal.fire('Uyarı', 'Kendi hesabınızı silemezsiniz.', 'warning'); return; }
    Swal.fire({
        title: 'Kullanıcıyı Sil',
        html: `<b>${k.ad_soyad}</b> kullanıcısı silinecek. Emin misiniz?`,
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sil', cancelButtonText: 'İptal',
    }).then(async r => {
        if (r.isConfirmed) {
            try {
                await axios.delete(route('tanim.kullanicilar.destroy', k.id));
                liste.value = liste.value.filter(x => x.id !== k.id);
                Swal.fire({toast:true, position:'top-end', icon:'success', title:'Silindi', showConfirmButton:false, timer:1200});
            } catch(e) { Swal.fire('Hata', e.response?.data?.message || 'Silinemedi', 'error'); }
        }
    });
};
</script>

<template>
<Head title="Kullanıcı Yönetimi" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <!-- Başlık -->
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <h2 class="font-bold text-sm text-gray-800">Kullanıcı Yönetimi</h2>
                    <span class="ml-2 bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded text-[10px] font-bold">{{ kullanicilar.length }} kullanıcı</span>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Tab Butonları -->
                    <div class="flex bg-white rounded border border-gray-300 overflow-hidden mr-2">
                        <button @click="activeTab = 'kullanicilar'" :class="activeTab === 'kullanicilar' ? 'bg-indigo-500 text-white' : 'text-gray-600 hover:bg-gray-100'" class="px-3 py-1 text-[10px] font-bold transition">
                            👥 Kullanıcılar
                        </button>
                        <button @click="activeTab = 'matris'" :class="activeTab === 'matris' ? 'bg-indigo-500 text-white' : 'text-gray-600 hover:bg-gray-100'" class="px-3 py-1 text-[10px] font-bold transition border-l border-gray-300">
                            🔐 Yetki Matrisi
                        </button>
                    </div>
                    <div v-if="activeTab === 'kullanicilar'" class="relative">
                        <input v-model="search" type="text" placeholder="🔍 Ara..." class="border-gray-300 rounded-sm py-1 px-3 text-xs w-48" />
                    </div>
                    <button v-if="activeTab === 'kullanicilar'" @click="openNew" class="bg-green-600 text-white px-3 py-1 rounded text-xs font-semibold hover:bg-green-700 flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        Yeni Kullanıcı
                    </button>
                </div>
            </div>

            <!-- ==================== KULLANICILAR TAB ==================== -->
            <template v-if="activeTab === 'kullanicilar'">
                <!-- Form -->
                <div v-if="showForm" class="p-4 bg-yellow-50 border-b border-yellow-200">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-gray-800">{{ editMode ? '✏️ Kullanıcıyı Düzenle' : '➕ Yeni Kullanıcı Ekle' }}</h3>
                        <button @click="showForm = false; resetForm()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-4 gap-3 text-xs">
                        <div>
                            <label class="font-semibold block mb-1">Ad Soyad:</label>
                            <input v-model="form.ad_soyad" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs" placeholder="Ad Soyad" />
                        </div>
                        <div>
                            <label class="font-semibold block mb-1">E-Posta:</label>
                            <input v-model="form.eposta" type="email" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs" placeholder="ornek@mail.com" />
                        </div>
                        <div>
                            <label class="font-semibold block mb-1">{{ editMode ? 'Yeni Şifre (Opsiyonel):' : 'Şifre:' }}</label>
                            <div class="relative">
                                <input v-model="form.sifre" :type="showPassword ? 'text' : 'password'" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs pr-8" :placeholder="editMode ? 'Boş bırakılırsa değişmez' : 'En az 6 karakter'" />
                                <button @click="showPassword = !showPassword" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="showPassword ? 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21' : 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'"></path></svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="font-semibold block mb-1">Rol:</label>
                            <select v-model="form.rol" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs">
                                <option v-for="(label, key) in roller" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-3">
                        <button @click="save" :disabled="yukleniyor" class="bg-green-600 text-white px-4 py-1.5 rounded text-xs font-semibold hover:bg-green-700 disabled:opacity-50">
                            {{ editMode ? 'Güncelle' : 'Kaydet' }}
                        </button>
                        <button @click="showForm = false; resetForm()" class="bg-gray-300 text-gray-700 px-4 py-1.5 rounded text-xs hover:bg-gray-400">İptal</button>
                    </div>
                </div>

                <!-- Kullanıcılar Tablosu -->
                <div class="flex-1 overflow-y-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead class="bg-[#d0dcea] sticky top-0">
                            <tr>
                                <th class="py-1.5 px-3 text-left border border-gray-300 font-bold w-8">#</th>
                                <th class="py-1.5 px-3 text-left border border-gray-300 font-bold">Ad Soyad</th>
                                <th class="py-1.5 px-3 text-left border border-gray-300 font-bold">E-Posta</th>
                                <th class="py-1.5 px-3 text-center border border-gray-300 font-bold">Rol</th>
                                <th class="py-1.5 px-3 text-center border border-gray-300 font-bold">Kayıt Tarihi</th>
                                <th class="py-1.5 px-3 text-center border border-gray-300 font-bold w-24">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(k, i) in filtered" :key="k.id" class="border-b border-gray-200 hover:bg-blue-50 transition"
                                :class="{'bg-blue-50/50': k.id === $page.props.auth?.user?.id}">
                                <td class="py-1.5 px-3 border-r border-gray-200 text-gray-400">{{ i + 1 }}</td>
                                <td class="py-1.5 px-3 border-r border-gray-200 font-medium">
                                    <div class="flex items-center">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-bold mr-2"
                                            :class="k.rol === 'admin' ? 'bg-red-500' : k.rol === 'muhasebe' ? 'bg-green-500' : k.rol === 'ik' ? 'bg-purple-500' : k.rol === 'izleyici' ? 'bg-gray-400' : 'bg-blue-500'">
                                            {{ k.ad_soyad.split(' ').map(w => w[0]).join('').substring(0,2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div>{{ k.ad_soyad }}</div>
                                            <div v-if="k.id === $page.props.auth?.user?.id" class="text-[9px] text-green-600 font-semibold">● Aktif Oturum (Siz)</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-1.5 px-3 border-r border-gray-200 text-gray-600">{{ k.eposta }}</td>
                                <td class="py-1.5 px-3 border-r border-gray-200 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border" :class="rolRenkleri[k.rol] || 'bg-gray-100 text-gray-600'">
                                        {{ roller[k.rol] || k.rol }}
                                    </span>
                                </td>
                                <td class="py-1.5 px-3 border-r border-gray-200 text-center text-gray-500">
                                    {{ k.created_at ? new Date(k.created_at).toLocaleDateString('tr-TR') : '-' }}
                                </td>
                                <td class="py-1.5 px-3 text-center">
                                    <button @click="editKullanici(k)" class="text-blue-600 hover:text-blue-800 mr-2" title="Düzenle">
                                        <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button @click="sil(k)" class="text-red-500 hover:text-red-700" title="Sil"
                                        :class="{'opacity-30 cursor-not-allowed': k.id === $page.props.auth?.user?.id}"
                                        :disabled="k.id === $page.props.auth?.user?.id">
                                        <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filtered.length === 0">
                                <td colspan="6" class="py-10 text-center text-gray-400">
                                    {{ search ? 'Aramayla eşleşen kullanıcı bulunamadı.' : 'Henüz kullanıcı yok.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- ==================== YETKİ MATRİSİ TAB ==================== -->
            <template v-if="activeTab === 'matris'">
                <div class="flex-1 overflow-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead class="sticky top-0 z-10">
                            <tr>
                                <th class="py-2 px-3 text-left bg-[#d0dcea] border border-gray-300 font-bold min-w-[200px] sticky left-0 z-20">Yetki / Modül</th>
                                <th v-for="(rolAd, rolKey) in roller" :key="rolKey" class="py-2 px-2 text-center border border-gray-300 font-bold min-w-[100px]" :class="rolBaslikRenkleri[rolKey]">
                                    {{ rolAd }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(grup, gi) in yetkiGruplari" :key="gi">
                                <!-- Grup Başlık -->
                                <tr>
                                    <td :colspan="1 + rolKeys.length" class="py-1.5 px-3 bg-gray-200 font-bold text-gray-700 border border-gray-300">
                                        <div class="flex items-center">
                                            <span class="mr-1.5">{{ gi === 0 ? '🏠' : gi === 1 ? '📱' : gi === 2 ? '💰' : gi === 3 ? '📦' : gi === 4 ? '🔔' : '⚙️' }}</span>
                                            {{ grup.baslik }}
                                        </div>
                                    </td>
                                </tr>
                                <!-- Yetki Satırları -->
                                <tr v-for="yetki in grup.keys" :key="yetki" class="hover:bg-blue-50/50 transition">
                                    <td class="py-1.5 px-3 pl-6 border border-gray-200 bg-white sticky left-0 font-medium text-gray-700">
                                        {{ yetkiEtiketleri[yetki] || yetki }}
                                    </td>
                                    <td v-for="rolKey in rolKeys" :key="rolKey" class="py-1.5 px-2 text-center border border-gray-200">
                                        <span v-if="yetkiMatrisi[rolKey]?.[yetki]" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 text-green-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        </span>
                                        <span v-else class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-50 text-red-300">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Özet Sayaçlar -->
                <div class="px-4 py-2 bg-gray-50 border-t border-gray-300 flex items-center gap-4 flex-wrap">
                    <template v-for="(rolAd, rolKey) in roller" :key="rolKey">
                        <div class="flex items-center gap-1.5">
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold border" :class="rolRenkleri[rolKey]">{{ rolAd }}</span>
                            <span class="text-[10px] text-gray-500">
                                {{ Object.values(yetkiMatrisi[rolKey] || {}).filter(v => v).length }}
                                / {{ Object.keys(yetkiEtiketleri || {}).length }} yetki
                            </span>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Alt Bilgi -->
            <div class="px-4 py-2 bg-gray-100 border-t border-gray-400 flex items-center text-[10px] text-gray-500">
                <svg class="w-3.5 h-3.5 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span v-if="activeTab === 'kullanicilar'"><b>Yönetici:</b> Tüm erişim &nbsp;|&nbsp; <b>Kullanıcı:</b> Temel işlemler &nbsp;|&nbsp; <b>Muhasebe:</b> Mali raporlar &nbsp;|&nbsp; <b>İK:</b> Personel/izin &nbsp;|&nbsp; <b>İzleyici:</b> Salt okunur</span>
                <span v-else>Yetki matrisi <b>config/yetkiler.php</b> dosyasından yönetilir. Yeni modül eklendiğinde buraya ekleyebilirsiniz.</span>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
