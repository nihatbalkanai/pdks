<script setup>
import { ref, watch, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    personeller: Object,
    filtreler: Object,
    tanimKodlari: Object,
    aylikPuantajParametreleri: { type: Array, default: () => [] },
});

// Tanım kodları helper
const getTanimSecenekleri = (tip) => props.tanimKodlari?.[tip] || [];

const aramaAna = ref(props.filtreler?.arama || '');
const localPersoneller = ref([...(props.personeller?.data || [])]);
const searchText = ref('');
const activeTab = ref('ozluk');
const activeSubTab = ref('genel');
const isLoading = ref(false);
const isSaving = ref(false);

const emptyPersonel = {
    id: null, kart_no: '', sicil_no: '', ad: '', soyad: '', ssk_no: '',
    unvan: '', sirket: '', bolum: '', ozel_kod: '', departman: '',
    servis_kodu: '', hesap_gurubu: '', agi: '', aylik_ucret: '',
    gunluk_ucret: '', saat_1: '', saat_2: '', saat_3: '',
    giris_tarihi: '', cikis_tarihi: '', durum: true, notlar: '',
    email: '', telefon: '', gec_kalma_bildirimi: false, resim_yolu: '',
    izinler: [], avans_kesintiler: [], prim_kazanclar: [], zimmetler: [], pdks_kayitlari: []
};

const selectedPersonel = ref(JSON.parse(JSON.stringify(emptyPersonel)));

// Filtre
const filteredPersoneller = computed(() => {
    if (!searchText.value) return localPersoneller.value;
    const q = searchText.value.toLowerCase();
    return localPersoneller.value.filter(p =>
        (p.kart_no || '').toLowerCase().includes(q) ||
        (p.ad_soyad || '').toLowerCase().includes(q) ||
        (p.ad || '').toLowerCase().includes(q) ||
        (p.soyad || '').toLowerCase().includes(q)
    );
});

// Personel seç
const selectPersonel = async (personel) => {
    isLoading.value = true;
    if (personel.id) {
        // Hemen liste verisini göster
        const quick = { ...emptyPersonel, ...personel };
        if (!personel.ad && personel.ad_soyad) {
            const parts = personel.ad_soyad.split(' ');
            quick.ad = parts[0] || '';
            quick.soyad = parts.slice(1).join(' ') || '';
        }
        selectedPersonel.value = quick;

        try {
            const response = await axios.get(route('personeller.show', personel.id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const detail = response.data.personel;
            if (detail) {
                if (!detail.ad && detail.ad_soyad) {
                    const parts = (detail.ad_soyad || '').split(' ');
                    detail.ad = parts[0] || '';
                    detail.soyad = parts.slice(1).join(' ') || '';
                }
                Object.assign(selectedPersonel.value, detail);
            }
        } catch (e) {
            console.error('Personel detay yükleme hatası:', e);
        }
    } else {
        selectedPersonel.value = { ...emptyPersonel, ...personel };
    }
    isLoading.value = false;
};

// --- OTOMATİK MAAŞ HESAPLAMA ---
let isAutoCalculating = false;

watch(() => selectedPersonel.value.aylik_ucret, (newVal) => {
    if (isAutoCalculating) return;
    if (!newVal) {
        isAutoCalculating = true;
        selectedPersonel.value.gunluk_ucret = '';
        selectedPersonel.value.saat_1 = '';
        isAutoCalculating = false;
        return;
    }
    
    // Varsayılan katsayılar
    let aylikCalismaSaati = 225;
    let gunLuku = 30; // standart ay 30 gün
    
    if (selectedPersonel.value.hesap_gurubu) {
        const param = props.aylikPuantajParametreleri?.find(p => p.hesap_parametresi_adi === selectedPersonel.value.hesap_gurubu);
        if (param) {
            aylikCalismaSaati = param.aylik_calisma_saati || 225;
            // Günlük saati bulup 30'a bölmek yerine 30 güne bölündüğü varsayılır
            const gunlukSaat = param.gunluk_calisma_saati || 7.5;
            gunLuku = aylikCalismaSaati / gunlukSaat;
        }
    }
    
    isAutoCalculating = true;
    selectedPersonel.value.gunluk_ucret = (Number(newVal) / 30).toFixed(2);
    selectedPersonel.value.saat_1 = (Number(newVal) / aylikCalismaSaati).toFixed(2);
    isAutoCalculating = false;
});

watch(() => selectedPersonel.value.gunluk_ucret, (newVal) => {
    if (isAutoCalculating) return;
    if (!newVal) {
        isAutoCalculating = true;
        selectedPersonel.value.aylik_ucret = '';
        selectedPersonel.value.saat_1 = '';
        isAutoCalculating = false;
        return;
    }

    let aylikCalismaSaati = 225;
    
    if (selectedPersonel.value.hesap_gurubu) {
        const param = props.aylikPuantajParametreleri?.find(p => p.hesap_parametresi_adi === selectedPersonel.value.hesap_gurubu);
        if (param) {
            aylikCalismaSaati = param.aylik_calisma_saati || 225;
        }
    }
    
    isAutoCalculating = true;
    const aylik = Number(newVal) * 30;
    selectedPersonel.value.aylik_ucret = aylik.toFixed(2);
    selectedPersonel.value.saat_1 = (aylik / aylikCalismaSaati).toFixed(2);
    isAutoCalculating = false;
});

// Yeni personel
const newPersonel = () => {
    selectedPersonel.value = JSON.parse(JSON.stringify(emptyPersonel));
    activeTab.value = 'ozluk';
    activeSubTab.value = 'genel';
};

// Kaydet
const savePersonel = async () => {
    isSaving.value = true;
    try {
        // Sadece form alanlarını gönder (ilişki verilerini hariç tut)
        const formData = {
            kart_no: selectedPersonel.value.kart_no || '',
            ad: selectedPersonel.value.ad || '',
            soyad: selectedPersonel.value.soyad || '',
            sicil_no: selectedPersonel.value.sicil_no || '',
            ssk_no: selectedPersonel.value.ssk_no || '',
            unvan: selectedPersonel.value.unvan || '',
            sirket: selectedPersonel.value.sirket || '',
            bolum: selectedPersonel.value.bolum || '',
            ozel_kod: selectedPersonel.value.ozel_kod || '',
            departman: selectedPersonel.value.departman || '',
            servis_kodu: selectedPersonel.value.servis_kodu || '',
            hesap_gurubu: selectedPersonel.value.hesap_gurubu || '',
            agi: selectedPersonel.value.agi || '',
            aylik_ucret: selectedPersonel.value.aylik_ucret || null,
            gunluk_ucret: selectedPersonel.value.gunluk_ucret || null,
            saat_1: selectedPersonel.value.saat_1 || null,
            saat_2: selectedPersonel.value.saat_2 || null,
            saat_3: selectedPersonel.value.saat_3 || null,
            giris_tarihi: selectedPersonel.value.giris_tarihi || null,
            cikis_tarihi: selectedPersonel.value.cikis_tarihi || null,
            durum: selectedPersonel.value.durum ?? true,
            notlar: selectedPersonel.value.notlar || '',
            email: selectedPersonel.value.email || '',
            telefon: selectedPersonel.value.telefon || '',
            gec_kalma_bildirimi: selectedPersonel.value.gec_kalma_bildirimi ?? false,
            dogum_tarihi: selectedPersonel.value.dogum_tarihi || null,
        };

        if (selectedPersonel.value.id) {
            const res = await axios.put(route('personeller.update', selectedPersonel.value.id), formData);
            // Güncellenen veriyi local listeye yansıt
            const idx = localPersoneller.value.findIndex(p => p.id === selectedPersonel.value.id);
            if (idx !== -1 && res.data.personel) {
                localPersoneller.value[idx] = { ...localPersoneller.value[idx], ...res.data.personel };
            }
            Swal.fire({ title: 'Başarılı!', text: 'Personel güncellendi.', icon: 'success', timer: 1500 });
        } else {
            const response = await axios.post(route('personeller.store'), formData);
            if (response.data.personel) {
                localPersoneller.value.unshift(response.data.personel);
                selectedPersonel.value = { ...selectedPersonel.value, ...response.data.personel };
            }
            Swal.fire({ title: 'Başarılı!', text: 'Yeni personel kaydedildi.', icon: 'success', timer: 1500 });
        }
    } catch (error) {
        const errors = error.response?.data?.errors;
        const msg = errors ? Object.values(errors).flat().join('<br>') : (error.response?.data?.message || 'Kaydetme işlemi başarısız oldu.');
        Swal.fire('Hata!', msg, 'error');
    } finally {
        isSaving.value = false;
    }
};

// Sil
const deletePersonel = () => {
    if (!selectedPersonel.value.id) return;
    Swal.fire({
        title: 'Emin misiniz?', text: 'Bu personeli silmek istediğinize emin misiniz?',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
        confirmButtonText: 'Evet, Sil!', cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('personeller.destroy', selectedPersonel.value.id), {
                onSuccess: () => {
                    Swal.fire('Silindi!', 'Personel başarıyla silindi.', 'success');
                    selectedPersonel.value = JSON.parse(JSON.stringify(emptyPersonel));
                }
            });
        }
    });
};

const formatTarih = (t) => {
    if (!t) return '';
    return new Date(t).toLocaleDateString('tr-TR');
};
const formatTutar = (t) => {
    if (!t) return '0,00';
    return Number(t).toLocaleString('tr-TR', { minimumFractionDigits: 2 });
};

// Resim yükleme
const resimInput = ref(null);
const isUploading = ref(false);

const triggerResimSec = () => {
    if (!selectedPersonel.value.id) {
        Swal.fire('Uyarı', 'Önce personeli kaydedin.', 'warning');
        return;
    }
    resimInput.value.click();
};

const uploadResim = async (event) => {
    const file = event.target.files[0];
    if (!file) return;
    isUploading.value = true;
    const formData = new FormData();
    formData.append('resim', file);
    try {
        const response = await axios.post(route('personeller.resim-yukle', selectedPersonel.value.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        selectedPersonel.value.resim_yolu = response.data.resim_yolu;
        Swal.fire({ title: 'Başarılı!', text: 'Resim yüklendi.', icon: 'success', timer: 1500 });
    } catch (e) {
        Swal.fire('Hata', 'Resim yüklenirken hata oluştu.', 'error');
    } finally {
        isUploading.value = false;
    }
};
</script>

<template>
    <Head title="Personel Kartları" />
    <AuthenticatedLayout>
        <div class="p-4 h-full flex flex-col">
            <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
                <!-- Pencere Başlığı -->
                <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2">
                    <h2 class="font-bold text-sm text-gray-800">Personel Kartları</h2>
                </div>

                <div class="flex flex-1 overflow-hidden">
                    <!-- SOL: Personel Listesi -->
                    <div class="w-56 border-r border-gray-400 flex flex-col bg-gray-50">
                        <div class="p-2 border-b border-gray-300">
                            <input v-model="searchText" type="text" placeholder="🔍 Ara..."
                                class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs focus:ring-blue-400 focus:border-blue-400" />
                        </div>
                        <div class="flex-1 overflow-y-auto">
                            <div v-for="p in filteredPersoneller" :key="p.id"
                                @click="selectPersonel(p)"
                                class="px-2 py-1.5 text-xs cursor-pointer border-b border-gray-200 hover:bg-blue-50 transition flex items-center"
                                :class="{'!bg-blue-100 font-semibold border-l-2 border-l-blue-500': selectedPersonel.id === p.id}">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium truncate">{{ p.ad || '' }} {{ p.soyad || '' }}</div>
                                    <div class="text-[10px] text-gray-500">{{ p.kart_no || '-' }}</div>
                                </div>
                            </div>
                            <div v-if="filteredPersoneller.length === 0" class="p-4 text-center text-gray-400 text-xs">Personel bulunamadı</div>
                        </div>
                        <div class="p-1 border-t border-gray-300 bg-gray-100 text-center text-[10px] text-gray-500">
                            {{ filteredPersoneller.length }} personel
                        </div>
                    </div>

                    <!-- SAĞ: Detay Alanı -->
                    <div class="flex-1 flex flex-col overflow-hidden">
                        <!-- Üst Sekmeler -->
                        <div class="flex bg-gray-100 border-b border-gray-400">
                            <button @click="activeTab = 'ozluk'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'ozluk'}">Özlük Bilgileri</button>
                            <button @click="activeTab = 'giris_cikis'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'giris_cikis'}">Giriş-Çıkış</button>
                            <button @click="activeTab = 'izin'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'izin'}">İzin</button>
                            <button @click="activeTab = 'avans'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'avans'}">Avans Kesinti</button>
                            <button @click="activeTab = 'prim'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'prim'}">Prim Kazanç</button>
                            <button @click="activeTab = 'not'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'not'}">Notlar</button>
                            <button @click="activeTab = 'zimmet'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'zimmet'}">Zimmet</button>
                        </div>

                        <!-- İçerik -->
                        <div class="flex-1 overflow-y-auto p-3">
                            <!-- Loading -->
                            <div v-if="isLoading" class="flex items-center justify-center h-full">
                                <div class="text-gray-400 text-sm">Yükleniyor...</div>
                            </div>

                            <!-- ÖZLÜK -->
                            <div v-else-if="activeTab === 'ozluk'" class="space-y-3">
                                <!-- Alt Sekme -->
                                <div class="flex gap-1 mb-2">
                                    <button @click="activeSubTab = 'genel'" class="subtab-btn" :class="{'subtab-active': activeSubTab === 'genel'}">Genel</button>
                                    <button @click="activeSubTab = 'ozluk_sub'" class="subtab-btn" :class="{'subtab-active': activeSubTab === 'ozluk_sub'}">Özlük</button>
                                </div>

                                <div v-if="activeSubTab === 'genel'">
                                    <div class="flex gap-4">
                                        <!-- Resim Alanı -->
                                        <div class="flex flex-col items-center">
                                            <div class="w-28 h-32 border-2 border-dashed border-gray-300 rounded bg-gray-50 flex items-center justify-center overflow-hidden cursor-pointer hover:border-blue-400 transition" @click="triggerResimSec">
                                                <img v-if="selectedPersonel.resim_yolu" :src="'/' + selectedPersonel.resim_yolu" class="w-full h-full object-cover" />
                                                <div v-else class="text-center text-gray-400">
                                                    <svg class="w-10 h-10 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                    <span class="text-[9px]">Resim Ekle</span>
                                                </div>
                                            </div>
                                            <button @click="triggerResimSec" class="mt-1.5 text-[10px] text-blue-600 hover:text-blue-800 font-medium">{{ isUploading ? 'Yükleniyor...' : 'Fotoğraf Seç' }}</button>
                                            <input ref="resimInput" type="file" accept="image/*" class="hidden" @change="uploadResim" />
                                        </div>

                                        <!-- Form Alanları -->
                                        <div class="flex-1">
                                            <div class="grid grid-cols-4 gap-x-3 gap-y-2">
                                                <div class="form-group"><label>Kart No</label><input v-model="selectedPersonel.kart_no" class="form-input" /></div>
                                                <div class="form-group"><label>Sicil No</label><input v-model="selectedPersonel.sicil_no" class="form-input" /></div>
                                                <div class="form-group"><label>Ad</label><input v-model="selectedPersonel.ad" class="form-input" /></div>
                                                <div class="form-group"><label>Soyad</label><input v-model="selectedPersonel.soyad" class="form-input" /></div>
                                                <div class="form-group"><label>SSK No</label><input v-model="selectedPersonel.ssk_no" class="form-input" /></div>
                                                <div class="form-group"><label>Ünvan</label><input v-model="selectedPersonel.unvan" class="form-input" /></div>
                                                <div class="form-group">
                                                    <label>Şirket</label>
                                                    <select v-model="selectedPersonel.sirket" class="form-input">
                                                        <option value="">Seçiniz</option>
                                                        <option v-for="s in getTanimSecenekleri('sirket')" :key="s.kod" :value="s.aciklama">{{ s.kod }} - {{ s.aciklama }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Bölüm</label>
                                                    <select v-model="selectedPersonel.bolum" class="form-input">
                                                        <option value="">Seçiniz</option>
                                                        <option v-for="s in getTanimSecenekleri('bolum')" :key="s.kod" :value="s.aciklama">{{ s.kod }} - {{ s.aciklama }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Özel Kod</label>
                                                    <select v-model="selectedPersonel.ozel_kod" class="form-input">
                                                        <option value="">Seçiniz</option>
                                                        <option v-for="s in getTanimSecenekleri('odeme')" :key="s.kod" :value="s.aciklama">{{ s.kod }} - {{ s.aciklama }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Departman</label>
                                                    <select v-model="selectedPersonel.departman" class="form-input">
                                                        <option value="">Seçiniz</option>
                                                        <option v-for="s in getTanimSecenekleri('departman')" :key="s.kod" :value="s.aciklama">{{ s.kod }} - {{ s.aciklama }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Servis Kodu</label>
                                                    <select v-model="selectedPersonel.servis_kodu" class="form-input">
                                                        <option value="">Seçiniz</option>
                                                        <option v-for="s in getTanimSecenekleri('servis')" :key="s.kod" :value="s.aciklama">{{ s.kod }} - {{ s.aciklama }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Hesap Grubu</label>
                                                    <select v-model="selectedPersonel.hesap_gurubu" class="form-input">
                                                        <option value="">Seçiniz</option>
                                                        <option v-for="s in getTanimSecenekleri('hesap_gurubu')" :key="s.kod" :value="s.aciklama">{{ s.kod }} - {{ s.aciklama }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group"><label>E-Posta</label><input v-model="selectedPersonel.email" type="email" class="form-input" placeholder="ornek@mail.com" /></div>
                                                <div class="form-group"><label>Telefon</label><input v-model="selectedPersonel.telefon" class="form-input" placeholder="05XX XXX XX XX" /></div>
                                                <div class="form-group"><label>Doğum Tarihi</label><input v-model="selectedPersonel.dogum_tarihi" type="date" class="form-input" /></div>
                                                <div class="form-group col-span-2">
                                                    <label>Geç Kalma Bildirimi</label>
                                                    <div class="flex items-center mt-1">
                                                        <button @click="selectedPersonel.gec_kalma_bildirimi = !selectedPersonel.gec_kalma_bildirimi" type="button"
                                                            :class="selectedPersonel.gec_kalma_bildirimi ? 'bg-green-500' : 'bg-gray-300'"
                                                            class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out">
                                                            <span :class="selectedPersonel.gec_kalma_bildirimi ? 'translate-x-4' : 'translate-x-0'"
                                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out border border-gray-200"></span>
                                                        </button>
                                                        <span class="ml-2 text-[10px]" :class="selectedPersonel.gec_kalma_bildirimi ? 'text-green-600 font-semibold' : 'text-gray-500'">{{ selectedPersonel.gec_kalma_bildirimi ? 'Aktif - Mail/SMS gönderilecek' : 'Pasif' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="activeSubTab === 'ozluk_sub'">
                                    <div class="grid grid-cols-4 gap-x-3 gap-y-2">
                                        <div class="form-group"><label>AGİ</label><input v-model="selectedPersonel.agi" class="form-input" /></div>
                                        <div class="form-group"><label>Aylık Ücret</label><input v-model="selectedPersonel.aylik_ucret" type="number" class="form-input text-right" /></div>
                                        <div class="form-group"><label>Günlük Ücret</label><input v-model="selectedPersonel.gunluk_ucret" type="number" class="form-input text-right" /></div>
                                        <div class="form-group"><label>Saat 1</label><input v-model="selectedPersonel.saat_1" type="number" class="form-input text-right" /></div>
                                        <div class="form-group"><label>Saat 2</label><input v-model="selectedPersonel.saat_2" type="number" class="form-input text-right" /></div>
                                        <div class="form-group"><label>Saat 3</label><input v-model="selectedPersonel.saat_3" type="number" class="form-input text-right" /></div>
                                        <div class="form-group"><label>Giriş Tarihi</label><input v-model="selectedPersonel.giris_tarihi" type="date" class="form-input" /></div>
                                        <div class="form-group"><label>Çıkış Tarihi</label><input v-model="selectedPersonel.cikis_tarihi" type="date" class="form-input" /></div>
                                    </div>
                                </div>
                            </div>

                            <!-- GİRİŞ-ÇIKIŞ -->
                            <div v-else-if="activeTab === 'giris_cikis'">
                                <table class="data-table">
                                    <thead><tr>
                                        <th>Tarih</th><th>Saat</th><th>İşlem</th><th>Durum</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="k in (selectedPersonel.pdks_kayitlari || [])" :key="k.id">
                                            <td>{{ formatTarih(k.kayit_tarihi) }}</td>
                                            <td>{{ k.kayit_tarihi ? k.kayit_tarihi.substring(11,16) : '' }}</td>
                                            <td><span :class="k.islem_tipi === 'Giriş' ? 'text-green-600' : 'text-red-600'" class="font-semibold">{{ k.islem_tipi }}</span></td>
                                            <td>Başarılı</td>
                                        </tr>
                                        <tr v-if="!(selectedPersonel.pdks_kayitlari || []).length"><td colspan="4" class="text-center text-gray-400 py-6">Kayıt yok</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- İZİN -->
                            <div v-else-if="activeTab === 'izin'">
                                <table class="data-table">
                                    <thead><tr>
                                        <th>Tarih</th><th>Tatil Tipi</th><th>İzin Tipi</th><th>Giriş</th><th>Çıkış</th><th>Açıklama</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="iz in (selectedPersonel.izinler || [])" :key="iz.id">
                                            <td>{{ formatTarih(iz.tarih) }}</td>
                                            <td>{{ iz.tatil_tipi }}</td>
                                            <td>{{ iz.izin_tipi }}</td>
                                            <td>{{ iz.giris_saati }}</td>
                                            <td>{{ iz.cikis_saati }}</td>
                                            <td>{{ iz.aciklama }}</td>
                                        </tr>
                                        <tr v-if="!(selectedPersonel.izinler || []).length"><td colspan="6" class="text-center text-gray-400 py-6">İzin kaydı yok</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- AVANS KESİNTİ -->
                            <div v-else-if="activeTab === 'avans'">
                                <table class="data-table">
                                    <thead><tr>
                                        <th>Tarih</th><th>Tutar</th><th>Açıklama</th><th>Bordro Alanı</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="a in (selectedPersonel.avans_kesintiler || [])" :key="a.id">
                                            <td>{{ formatTarih(a.tarih) }}</td>
                                            <td class="text-right">{{ formatTutar(a.tutar) }}</td>
                                            <td>{{ a.aciklama }}</td>
                                            <td>{{ a.bordro_alani }}</td>
                                        </tr>
                                        <tr v-if="!(selectedPersonel.avans_kesintiler || []).length"><td colspan="4" class="text-center text-gray-400 py-6">Avans/kesinti kaydı yok</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- PRİM KAZANÇ -->
                            <div v-else-if="activeTab === 'prim'">
                                <table class="data-table">
                                    <thead><tr>
                                        <th>Tarih</th><th>Tutar</th><th>Açıklama</th><th>Bordro Alanı</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="p in (selectedPersonel.prim_kazanclar || [])" :key="p.id">
                                            <td>{{ formatTarih(p.tarih) }}</td>
                                            <td class="text-right">{{ formatTutar(p.tutar) }}</td>
                                            <td>{{ p.aciklama }}</td>
                                            <td>{{ p.bordro_alani }}</td>
                                        </tr>
                                        <tr v-if="!(selectedPersonel.prim_kazanclar || []).length"><td colspan="4" class="text-center text-gray-400 py-6">Prim/kazanç kaydı yok</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- NOTLAR -->
                            <div v-else-if="activeTab === 'not'">
                                <textarea v-model="selectedPersonel.notlar" rows="12"
                                    class="w-full border-gray-300 rounded-sm text-xs p-2 focus:ring-blue-400 focus:border-blue-400"
                                    placeholder="Personel notları..."></textarea>
                            </div>

                            <!-- ZİMMET -->
                            <div v-else-if="activeTab === 'zimmet'">
                                <table class="data-table">
                                    <thead><tr>
                                        <th>Kategori</th><th>Bölüm</th><th>Açıklama</th><th>Veriliş Tarihi</th><th>İade Tarihi</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="z in (selectedPersonel.zimmetler || [])" :key="z.id">
                                            <td>{{ z.kategori }}</td>
                                            <td>{{ z.bolum_adi }}</td>
                                            <td>{{ z.aciklama }}</td>
                                            <td>{{ formatTarih(z.verilis_tarihi) }}</td>
                                            <td>{{ z.iade_tarihi ? formatTarih(z.iade_tarihi) : '-' }}</td>
                                        </tr>
                                        <tr v-if="!(selectedPersonel.zimmetler || []).length"><td colspan="5" class="text-center text-gray-400 py-6">Zimmet kaydı yok</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Alt Butonlar -->
                        <div class="flex items-center justify-end gap-1 px-3 py-2 bg-gray-100 border-t border-gray-400">
                            <button @click="newPersonel" class="win-btn" title="Yeni Personel">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </button>
                            <button @click="savePersonel" :disabled="isSaving" class="win-btn" title="Kaydet">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            </button>
                            <button class="win-btn" title="Yazdır">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            </button>
                            <button @click="savePersonel" class="win-btn" title="Onayla">
                                <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </button>
                            <button @click="deletePersonel" class="win-btn" title="Sil">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                            <button class="win-btn" title="Excel">
                                <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.tab-btn {
    @apply px-3 py-1.5 text-xs font-medium border-b-2 border-transparent text-gray-600 hover:text-blue-700 hover:bg-blue-50 transition cursor-pointer;
}
.tab-active {
    @apply !text-blue-700 !border-blue-600 bg-white font-bold;
}
.subtab-btn {
    @apply px-3 py-1 text-[11px] border border-gray-300 rounded-sm bg-gray-50 text-gray-600 hover:bg-blue-50 cursor-pointer transition;
}
.subtab-active {
    @apply !bg-blue-600 !text-white !border-blue-600;
}
.form-group {
    @apply flex flex-col;
}
.form-group label {
    @apply text-[10px] font-semibold text-gray-500 mb-0.5 uppercase tracking-wide;
}
.form-input {
    @apply border-gray-300 rounded-sm py-1 px-2 text-xs focus:ring-blue-400 focus:border-blue-400;
}
.data-table {
    @apply w-full text-xs border-collapse;
}
.data-table thead {
    @apply bg-[#d0dcea] sticky top-0;
}
.data-table th {
    @apply py-1.5 px-2 text-left border border-gray-400 font-bold text-gray-700;
}
.data-table td {
    @apply py-1 px-2 border-r border-gray-200;
}
.data-table tbody tr {
    @apply border-b border-gray-200 hover:bg-blue-50 transition-colors;
}
.win-btn {
    @apply w-8 h-8 flex items-center justify-center bg-white border border-gray-400 rounded-sm hover:bg-gray-100 shadow-sm cursor-pointer transition;
}
</style>
