<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    personeller: Object,
    filtreler: Object,
});

// Normal Sayfa Durumu
const aramaAna = ref(props.filtreler.arama || '');
watch(aramaAna, (value) => {
    router.get(route('personeller.index'), { arama: value }, { preserveState: true, replace: true });
});

// Dev Modal Durumu
const isCardModalOpen = ref(false);
const modalSearch = ref('');
const localPersoneller = ref([...(props.personeller?.data || [])]);

// Tab State
const activeTab = ref('ozluk'); // ozluk, giris_cikis, izin, avans, prim
const activeSubTab = ref('genel'); // genel, ozluk_sub, not, zimmet

// Seçili Personel
const emptyPersonel = {
    id: null, kart_no: '', sicil_no: '', ad: '', soyad: '', ssk_no: '',
    unvan: '', sirket: '', bolum: '', ozel_kod: '', departman: '',
    servis_kodu: '', hesap_gurubu: '', agi: '', aylik_ucret: '',
    gunluk_ucret: '', saat_1: '', saat_2: '', saat_3: '',
    giris_tarihi: '', cikis_tarihi: '', durum: true
};

const selectedPersonel = ref(JSON.parse(JSON.stringify(emptyPersonel)));
const isLoading = ref(false);

const selectPersonel = async (personel) => {
    isLoading.value = true;
    
    if (personel.id) {
        // Önce liste verisini anında göster
        const quick = { ...emptyPersonel, ...personel };
        if (!personel.ad && personel.ad_soyad) {
            const parts = personel.ad_soyad.split(' ');
            quick.ad = parts[0] || '';
            quick.soyad = parts.slice(1).join(' ') || '';
        }
        selectedPersonel.value = quick;

        // Sonra API'den detaylı veriyi çek
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
                // Mevcut quick data'yı kaybetme, API verisini üzerine yaz
                Object.assign(selectedPersonel.value, detail);
            }
        } catch (e) {
            console.error('Personel detay yükleme hatası:', e);
            // Quick data zaten yüklü, ek bir şey yapmaya gerek yok
        }
    } else {
        const newP = { ...emptyPersonel, ...personel };
        if (!personel.ad && personel.ad_soyad) {
            const parts = personel.ad_soyad.split(' ');
            newP.ad = parts[0] || '';
            newP.soyad = parts.slice(1).join(' ') || '';
        }
        selectedPersonel.value = newP;
    }
    
    isLoading.value = false;
};

const isSaving = ref(false);

const openModal = (personel = null) => {
    isCardModalOpen.value = true;
    if (personel) {
        selectPersonel(personel);
    } else {
        selectedPersonel.value = JSON.parse(JSON.stringify(emptyPersonel));
    }
};

const closeModal = () => {
    isCardModalOpen.value = false;
};

const formatZimmetTarihi = (tarih) => {
    if (!tarih) return '';
    return new Date(tarih).toLocaleDateString('tr-TR');
};

const savePersonel = async () => {
    isSaving.value = true;
    try {
        let response;
        if (selectedPersonel.value.id) {
            response = await axios.put(route('personeller.update', selectedPersonel.value.id), selectedPersonel.value);
            Swal.fire({ title: 'Başarılı!', text: 'Personel bilgileri güncellendi.', icon: 'success', confirmButtonText: 'Tamam' });
        } else {
            response = await axios.post(route('personeller.store'), selectedPersonel.value);
            Swal.fire({ title: 'Başarılı!', text: 'Yeni personel kaydedildi.', icon: 'success', confirmButtonText: 'Tamam' });
            selectedPersonel.value = response.data.personel;
        }
        router.reload({ only: ['personeller'] });
        setTimeout(() => { localPersoneller.value = [...props.personeller.data] }, 1000);
    } catch (error) {
        console.error(error);
        const msg = error.response?.data?.message || 'Kaydetme işlemi başarısız oldu.';
        Swal.fire('Hata!', msg, 'error');
    } finally {
        isSaving.value = false;
    }
};

const deletePersonel = () => {
    if(!selectedPersonel.value.id) return;
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu personeli silmek istediğinize emin misiniz?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, Sil!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('personeller.destroy', selectedPersonel.value.id), {
                onSuccess: () => {
                    Swal.fire('Silindi!', 'Personel başarıyla silindi.', 'success');
                    selectedPersonel.value = JSON.parse(JSON.stringify(emptyPersonel));
                    isCardModalOpen.value = false;
                }
            });
        }
    });
};

const filteredLocalPersoneller = computed(() => {
    if (!modalSearch.value) return localPersoneller.value;
    const lower = modalSearch.value.toLowerCase();
    return localPersoneller.value.filter(p => 
        (p.kart_no || '').toLowerCase().includes(lower) || 
        (p.ad_soyad || '').toLowerCase().includes(lower) || 
        (p.ad || '').toLowerCase().includes(lower) || 
        (p.soyad || '').toLowerCase().includes(lower)
    );
});
</script>

<template>
    <Head title="Personel Yönetimi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Personel Yönetimi</h2>
                <button @click="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md shadow transition">
                    + Yeni Personel / Kart
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Arama Kutusu -->
                <div class="bg-white p-4 shadow-sm sm:rounded-lg flex justify-between">
                    <input v-model="aramaAna" type="text" placeholder="Genel listede ad, soyad veya sicil no ile ara..." class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-1/3" />
                </div>

                <!-- Tablo -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b bg-gray-50 text-gray-600 text-sm uppercase">
                                        <th class="py-3 px-4">Ad Soyad</th>
                                        <th class="py-3 px-4">Sicil No</th>
                                        <th class="py-3 px-4">Bölüm</th>
                                        <th class="py-3 px-4 text-right">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="personel in personeller.data" :key="personel.id" class="border-b hover:bg-gray-50 transition">
                                        <td class="py-3 px-4">{{ personel.ad_soyad || (personel.ad + ' ' + personel.soyad) }}</td>
                                        <td class="py-3 px-4 font-mono text-sm">{{ personel.sicil_no }}</td>
                                        <td class="py-3 px-4">{{ personel.bolum || '-' }}</td>
                                        <td class="py-3 px-4 text-right">
                                            <button @click="openModal(personel)" class="text-indigo-600 hover:text-indigo-900 mx-2 font-bold">Kartı Aç</button>
                                        </td>
                                    </tr>
                                    <tr v-if="personeller.data.length === 0">
                                        <td colspan="4" class="py-6 text-center text-gray-500">Hiç personel bulunamadı.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- YENİ PERSONEL KARTI MODALI (WINDOWS FORM STILLİ) -->
        <Modal :show="isCardModalOpen" @close="closeModal" maxWidth="4xl">
            <div class="bg-gray-100 min-h-[600px] flex flex-col font-sans">
                <!-- Modal Header -->
                <div class="flex justify-between items-center py-2 px-4 bg-gray-200 border-b border-gray-300">
                    <h3 class="font-semibold text-gray-700 text-sm">Personel Kartları</h3>
                    <button @click="closeModal" class="text-gray-500 hover:text-red-500 font-bold">✕</button>
                </div>

                <div class="flex flex-1 overflow-hidden p-2 gap-2 h-[75vh]">
                    <!-- Sol Panel (Liste) -->
                    <div class="w-1/3 max-w-[300px] bg-white border border-gray-300 flex flex-col">
                        <div class="p-2 border-b border-gray-300 flex items-center bg-gray-50">
                            <input type="checkbox" class="mr-2 rounded text-indigo-600" />
                            <label class="text-xs mr-2 whitespace-nowrap">Hızlı bakış:</label>
                            <input v-model="modalSearch" type="text" class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs focus:ring-0 focus:border-indigo-500" />
                        </div>
                        <div class="flex-1 overflow-y-auto bg-white">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-200 sticky top-0 shadow-sm">
                                    <tr>
                                        <th class="py-1 px-2 text-left border-r border-gray-300 border-b w-12">Kart</th>
                                        <th class="py-1 px-2 text-left border-r border-gray-300 border-b">Ad</th>
                                        <th class="py-1 px-2 text-left border-b">Soyad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in filteredLocalPersoneller" :key="p.id" 
                                        @click="selectPersonel(p)"
                                        class="cursor-pointer border-b border-gray-100 hover:bg-yellow-50"
                                        :class="{'bg-yellow-100 font-medium': selectedPersonel.id === p.id}">
                                        <td class="py-1 px-2 border-r border-gray-200">{{ p.kart_no || p.id }}</td>
                                        <td class="py-1 px-2 border-r border-gray-200">{{ p.ad || p.ad_soyad?.split(' ')[0] }}</td>
                                        <td class="py-1 px-2">{{ p.soyad || p.ad_soyad?.split(' ').slice(1).join(' ') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Alt Navigasyon Barı (Görsel) -->
                        <div class="p-1 bg-gray-100 border-t border-gray-300 flex justify-between items-center text-gray-500">
                            <div class="flex space-x-1">
                                <button class="w-6 h-6 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 font-bold text-green-600">◀</button>
                                <button class="w-6 h-6 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 font-bold text-green-600">▶</button>
                                <button @click="openModal(null)" class="w-6 h-6 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 font-bold text-yellow-500">+</button>
                                <button @click="savePersonel" class="w-6 h-6 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 font-bold text-blue-500">✓</button>
                                <button @click="deletePersonel" class="w-6 h-6 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 font-bold text-red-500">✕</button>
                            </div>
                        </div>
                    </div>

                    <!-- Sağ Panel (Detaylar) -->
                    <div class="flex-1 bg-white border border-gray-300 flex flex-col">
                        <!-- Üst Sekmeler -->
                        <div class="flex bg-gray-100 border-b border-gray-300 pt-2 px-2 overflow-x-auto">
                            <button @click="activeTab='ozluk'" :class="{'bg-white border-t border-l border-r rounded-t-sm z-10 -mb-px text-black': activeTab=='ozluk', 'text-gray-600 hover:bg-gray-200 border-transparent': activeTab!='ozluk'}" class="px-4 py-2 text-xs font-semibold flex items-center border">
                                🧍 Personel Özlük
                            </button>
                            <button @click="activeTab='giris_cikis'" :class="{'bg-white border-t border-l border-r rounded-t-sm z-10 -mb-px text-black': activeTab=='giris_cikis', 'text-gray-600 hover:bg-gray-200 border-transparent': activeTab!='giris_cikis'}" class="px-4 py-2 text-xs font-semibold flex items-center border">
                                ⏱️ Giriş-Çıkış
                            </button>
                            <button @click="activeTab='izin'" :class="{'bg-white border-t border-l border-r rounded-t-sm z-10 -mb-px text-black': activeTab=='izin', 'text-gray-600 hover:bg-gray-200 border-transparent': activeTab!='izin'}" class="px-4 py-2 text-xs font-semibold flex items-center border">
                                🏖️ İzin
                            </button>
                            <button @click="activeTab='avans'" :class="{'bg-white border-t border-l border-r rounded-t-sm z-10 -mb-px text-black': activeTab=='avans', 'text-gray-600 hover:bg-gray-200 border-transparent': activeTab!='avans'}" class="px-4 py-2 text-xs font-semibold flex items-center border">
                                💸 Avans_Kesintiler
                            </button>
                            <button @click="activeTab='prim'" :class="{'bg-white border-t border-l border-r rounded-t-sm z-10 -mb-px text-black': activeTab=='prim', 'text-gray-600 hover:bg-gray-200 border-transparent': activeTab!='prim'}" class="px-4 py-2 text-xs font-semibold flex items-center border">
                                💰 Prim_Kazançlar
                            </button>
                        </div>

                        <!-- Sekme İçerikleri -->
                        <div class="flex-1 p-4 overflow-y-auto">
                            <!-- ÖZLÜK TAB -->
                            <div v-if="activeTab==='ozluk'" class="text-sm h-full flex flex-col">
                                <!-- GENEL TAB -->
                                <div v-if="activeSubTab==='genel'" class="flex-1 flex flex-col">
                                    <div class="flex gap-4">
                                    <!-- Sol Form Grubu -->
                                    <div class="flex-1 space-y-2">
                                        <div class="flex items-center"><label class="w-1/4 text-gray-700 text-xs">Kart No:</label><input v-model="selectedPersonel.kart_no" type="text" class="w-3/4 text-xs py-1 border-gray-300 rounded-sm" /></div>
                                        <div class="flex items-center"><label class="w-1/4 text-gray-700 text-xs">Sicil No:</label><input v-model="selectedPersonel.sicil_no" type="text" class="w-3/4 text-xs py-1 border-gray-300 rounded-sm bg-gray-50" /></div>
                                        <div class="flex items-center mt-2"><label class="w-1/4 text-gray-700 text-xs">Ad:</label><input v-model="selectedPersonel.ad" type="text" class="w-3/4 text-xs py-1 border-gray-300 rounded-sm" /></div>
                                        <div class="flex items-center"><label class="w-1/4 text-gray-700 text-xs">Soyad:</label><input v-model="selectedPersonel.soyad" type="text" class="w-3/4 text-xs py-1 border-gray-300 rounded-sm" /></div>
                                        <div class="flex items-center"><label class="w-1/4 text-gray-700 text-xs">SSK No:</label><input v-model="selectedPersonel.ssk_no" type="text" class="w-3/4 text-xs py-1 border-gray-300 rounded-sm" /></div>
                                        <div class="flex items-center"><label class="w-1/4 text-gray-700 text-xs">Ünvan:</label><input v-model="selectedPersonel.unvan" type="text" class="w-3/4 text-xs py-1 border-gray-300 rounded-sm" /></div>
                                        
                                        <div class="flex items-center mt-4">
                                            <label class="w-1/4 text-gray-700 text-xs">Şirket:</label>
                                            <select v-model="selectedPersonel.sirket" class="w-3/4 text-xs py-1 border-gray-300 rounded-sm">
                                                <option value="">Seçiniz</option>
                                                <option value="Merkez">Merkez</option>
                                                <option value="Şube">Şube</option>
                                            </select>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 flex items-center"><label class="w-1/3 text-gray-700 text-xs">Bölüm:</label><input v-model="selectedPersonel.bolum" class="w-2/3 text-xs py-1 border-gray-300 rounded-sm"/></div>
                                            <div class="flex-1 flex items-center"><label class="w-1/3 text-gray-700 text-xs">Özel Kod:</label><input v-model="selectedPersonel.ozel_kod" class="w-2/3 text-xs py-1 border-gray-300 rounded-sm"/></div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 flex items-center"><label class="w-1/3 text-gray-700 text-xs">Departman:</label><input v-model="selectedPersonel.departman" class="w-2/3 text-xs py-1 border-gray-300 rounded-sm"/></div>
                                            <div class="flex-1 flex items-center"><label class="w-1/3 text-gray-700 text-xs">Servis:</label><input v-model="selectedPersonel.servis_kodu" class="w-2/3 text-xs py-1 border-gray-300 rounded-sm"/></div>
                                        </div>
                                        <div class="flex items-center gap-2 mt-4">
                                            <div class="flex-1 flex items-center"><label class="w-1/3 text-gray-700 text-xs">Hesap Gurubu:</label><input v-model="selectedPersonel.hesap_gurubu" class="w-2/3 text-xs py-1 border-gray-300 rounded-sm"/></div>
                                            <div class="flex-1 flex items-center"><label class="w-1/3 text-gray-700 text-xs text-right pr-2">A.G.İ:</label><input v-model="selectedPersonel.agi" class="w-2/3 text-xs py-1 border-gray-300 rounded-sm"/></div>
                                        </div>
                                    </div>

                                    <!-- Sağ Resim Alanı -->
                                    <div class="w-32 flex flex-col pt-2">
                                        <div class="w-full h-32 border-2 border-dashed border-gray-300 flex items-center justify-center bg-gray-50 text-gray-400 text-xs mb-1 relative overflow-hidden">
                                            <span v-if="!selectedPersonel.resim_yolu">Resim yok!</span>
                                            <img v-else :src="selectedPersonel.resim_yolu" class="object-cover w-full h-full" />
                                        </div>
                                        <div class="text-[10px] text-center text-gray-500">120x130</div>
                                    </div>
                                </div>

                                <!-- Alt Çerçeve: Çalışma Bilgileri -->
                                <div class="mt-6 border border-gray-300 rounded-sm p-3 relative">
                                    <span class="absolute -top-2 left-2 bg-white px-1 text-xs text-gray-600">Çalışma bilgileri</span>
                                    
                                    <div class="flex gap-4">
                                        <div class="w-1/2 space-y-2">
                                            <div class="flex items-center"><label class="w-1/3 text-xs text-right pr-2">Aylık ücret:</label><input v-model="selectedPersonel.aylik_ucret" type="number" class="w-2/3 text-xs py-1 text-right border-gray-300 rounded-sm" /></div>
                                            <div class="flex items-center"><label class="w-1/3 text-xs text-right pr-2">Günlük ücret:</label><input v-model="selectedPersonel.gunluk_ucret" type="number" class="w-2/3 text-xs py-1 text-right border-gray-300 rounded-sm bg-gray-50" /></div>
                                            <div class="flex items-center"><label class="w-1/3 text-xs text-right pr-2">Saat 1 (A):</label><input v-model="selectedPersonel.saat_1" type="number" class="w-2/3 text-xs py-1 text-right border-gray-300 rounded-sm bg-gray-50" /></div>
                                            <div class="flex items-center"><label class="w-1/3 text-xs text-right pr-2">Saat 2 (B):</label><input v-model="selectedPersonel.saat_2" type="number" class="w-2/3 text-xs py-1 text-right border-gray-300 rounded-sm bg-gray-50" /></div>
                                            <div class="flex items-center"><label class="w-1/3 text-xs text-right pr-2">Saat 3 (C):</label><input v-model="selectedPersonel.saat_3" type="number" class="w-2/3 text-xs py-1 text-right border-gray-300 rounded-sm bg-gray-50" /></div>
                                        </div>
                                        <div class="w-1/2 flex flex-col space-y-3 pt-1">
                                            <button class="bg-gray-200 hover:bg-gray-300 text-xs py-1 px-4 rounded border border-gray-300 shadow-sm self-start">Ücreti Değiştir</button>
                                            <div class="pt-2">
                                                <div class="flex items-center mb-2"><label class="w-24 text-xs">Giriş tarihi:</label><input v-model="selectedPersonel.giris_tarihi" type="date" class="flex-1 text-xs py-1 border-gray-300 rounded-sm" /></div>
                                            </div>
                                            <div class="pt-1">
                                                <div class="flex items-center"><label class="w-24 text-xs">Çıkış tarihi:</label><input v-model="selectedPersonel.cikis_tarihi" type="date" class="flex-1 text-xs py-1 border-gray-300 rounded-sm" /></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                </div>
                                <!-- END GENEL TAB -->

                                <!-- NOT TAB -->
                                <div v-if="activeSubTab==='not'" class="flex-1 flex flex-col">
                                    <textarea 
                                        v-model="selectedPersonel.notlar" 
                                        class="w-full h-full min-h-[400px] border-gray-300 rounded-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 p-2"
                                        placeholder="Buraya notlarınızı yazabilirsiniz... (Örn: SSK BRÜT: 2.797,56 SSK NET:2.090,11)"
                                    ></textarea>
                                </div>

                                <!-- ZİMMET TAB -->
                                <div v-if="activeSubTab==='zimmet'" class="flex-1 flex flex-col space-y-4">
                                    <div class="h-64 overflow-y-auto border border-gray-300">
                                        <table class="w-full text-xs text-left">
                                            <thead class="bg-gray-100 sticky top-0">
                                                <tr>
                                                    <th class="py-1 px-2 border-r border-b">Kategori</th>
                                                    <th class="py-1 px-2 border-r border-b">Açıklama</th>
                                                    <th class="py-1 px-2 border-r border-b">Veriliş Tarihi</th>
                                                    <th class="py-1 px-2 border-b">İade Tarihi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="zimmet in selectedPersonel.zimmetler || []" :key="zimmet.id" class="border-b bg-blue-100/30">
                                                    <td class="py-1 px-2 border-r">{{ zimmet.kategori }}</td>
                                                    <td class="py-1 px-2 border-r">{{ zimmet.aciklama }}</td>
                                                    <td class="py-1 px-2 border-r">{{ formatZimmetTarihi(zimmet.verilis_tarihi) }}</td>
                                                    <td class="py-1 px-2">{{ formatZimmetTarihi(zimmet.iade_tarihi) }}</td>
                                                </tr>
                                                <tr v-if="!(selectedPersonel.zimmetler && selectedPersonel.zimmetler.length)">
                                                    <td colspan="4" class="py-10 text-center text-gray-400">&lt;Gösterilecek Bilgi yok&gt;</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4 text-xs pt-4">
                                        <div class="space-y-2">
                                            <div class="flex items-center"><label class="w-24">Bölüm Adı:</label><select class="flex-1 border-gray-300 py-1 rounded-sm text-xs"><option>Seçiniz</option></select><button class="ml-2 bg-gray-200 border border-gray-300 px-2 rounded-sm py-1 font-semibold">Bölüm Ekle</button></div>
                                            <div class="flex items-center"><label class="w-24">Kategori Adı:</label><select class="flex-1 border-gray-300 py-1 rounded-sm text-xs"><option>Seçiniz</option></select><button class="ml-2 bg-gray-200 border border-gray-300 px-2 rounded-sm py-1 font-semibold">Kategori Ekle</button></div>
                                            <div class="flex items-center"><label class="w-24">Açıklama:</label><input type="text" class="flex-1 border-gray-300 py-1 rounded-sm text-xs" /></div>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex items-center"><label class="w-24">Veriliş Tarihi:</label><input type="date" class="flex-1 border-gray-300 py-1 rounded-sm text-xs" /></div>
                                            <div class="flex items-center"><label class="w-24">İade Tarihi:</label><input type="date" class="flex-1 border-gray-300 py-1 rounded-sm text-xs" /></div>
                                        </div>
                                    </div>
                                    <div class="flex space-x-1 mt-2">
                                        <button class="w-6 h-6 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 font-bold text-yellow-500">+</button>
                                        <button class="w-6 h-6 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 font-bold text-gray-500">-</button>
                                        <button class="w-6 h-6 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 font-bold text-gray-500">✓</button>
                                    </div>
                                </div>
                                
                                <div class="mt-auto border-t border-gray-200 pt-3">
                                   <!-- Alt Tablolar Görseli (Mocked) -->
                                   <div class="flex gap-2">
                                        <button @click="activeSubTab='genel'" :class="activeSubTab==='genel' ? 'border-gray-300 bg-white shadow-sm' : 'text-blue-600 bg-transparent border-transparent'" class="border px-3 py-1 text-xs flex items-center">🧍 Genel</button>
                                        <button @click="activeSubTab='ozluk_sub'" :class="activeSubTab==='ozluk_sub' ? 'border-gray-300 bg-white shadow-sm' : 'text-blue-600 bg-transparent border-transparent'" class="border px-3 py-1 text-xs flex items-center">🔍 Özlük</button>
                                        <button @click="activeSubTab='not'" :class="activeSubTab==='not' ? 'border-gray-300 bg-white shadow-sm' : 'text-blue-600 bg-transparent border-transparent'" class="border px-3 py-1 text-xs flex items-center">📝 Not</button>
                                        <button @click="activeSubTab='zimmet'" :class="activeSubTab==='zimmet' ? 'border-gray-300 bg-white shadow-sm' : 'text-blue-600 bg-transparent border-transparent'" class="border px-3 py-1 text-xs flex items-center">🍔 Zimmet</button>
                                   </div>
                                </div>
                            </div>
                            
                            <!-- GİRİŞ ÇIKIŞ TAB -->
                            <div v-if="activeTab==='giris_cikis'" class="text-sm h-full flex flex-col space-y-4">
                                <div class="h-full overflow-y-auto border border-gray-300">
                                    <table class="w-full text-xs text-left">
                                        <thead class="bg-gray-100 sticky top-0">
                                            <tr>
                                                <th class="py-1 px-2 border-r border-b w-8">#</th>
                                                <th class="py-1 px-2 border-r border-b">Kayıt Tarihi / Saati</th>
                                                <th class="py-1 px-2 border-b">İşlem Tipi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(k, idx) in selectedPersonel.pdks_kayitlari || []" :key="k.id" class="border-b hover:bg-yellow-50">
                                                <td class="py-1 px-2 border-r">{{ idx + 1 }}</td>
                                                <td class="py-1 px-2 border-r">{{ k.kayit_tarihi }}</td>
                                                <td class="py-1 px-2 font-bold" :class="k.islem_tipi === 'Giriş' ? 'text-green-600' : 'text-red-500'">{{ k.islem_tipi }}</td>
                                            </tr>
                                            <tr v-if="!(selectedPersonel.pdks_kayitlari && selectedPersonel.pdks_kayitlari.length)">
                                                <td colspan="3" class="py-10 text-center text-gray-400">&lt;Gösterilecek Bilgi yok&gt;</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- İZİN TAB -->
                            <div v-if="activeTab==='izin'" class="text-sm h-full flex flex-col space-y-4">
                                <div class="h-full overflow-y-auto border border-gray-300">
                                    <table class="w-full text-xs text-left">
                                        <thead class="bg-gray-100 sticky top-0">
                                            <tr>
                                                <th class="py-1 px-2 border-r border-b">İzin Tipi</th>
                                                <th class="py-1 px-2 border-r border-b">Tarih</th>
                                                <th class="py-1 px-2 border-r border-b">Tatil Tipi</th>
                                                <th class="py-1 px-2 border-r border-b">Başlangıç</th>
                                                <th class="py-1 px-2 border-r border-b">Bitiş</th>
                                                <th class="py-1 px-2 border-b">Açıklama</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="izin in selectedPersonel.izinler || []" :key="izin.id" class="border-b hover:bg-yellow-50">
                                                <td class="py-1 px-2 border-r">{{ izin.izin_tipi }}</td>
                                                <td class="py-1 px-2 border-r">{{ formatZimmetTarihi(izin.tarih) }}</td>
                                                <td class="py-1 px-2 border-r">{{ izin.tatil_tipi }}</td>
                                                <td class="py-1 px-2 border-r">{{ izin.giris_saati }}</td>
                                                <td class="py-1 px-2 border-r">{{ izin.cikis_saati }}</td>
                                                <td class="py-1 px-2">{{ izin.aciklama }}</td>
                                            </tr>
                                            <tr v-if="!(selectedPersonel.izinler && selectedPersonel.izinler.length)">
                                                <td colspan="6" class="py-10 text-center text-gray-400">&lt;Gösterilecek Bilgi yok&gt;</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- AVANS TAB -->
                            <div v-if="activeTab==='avans'" class="text-sm h-full flex flex-col space-y-4">
                                <div class="h-full overflow-y-auto border border-gray-300">
                                    <table class="w-full text-xs text-left">
                                        <thead class="bg-gray-100 sticky top-0">
                                            <tr>
                                                <th class="py-1 px-2 border-r border-b">Tarih</th>
                                                <th class="py-1 px-2 border-r border-b">Bordro Alanı</th>
                                                <th class="py-1 px-2 border-r border-b">Tutar</th>
                                                <th class="py-1 px-2 border-b">Açıklama</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="avans in selectedPersonel.avans_kesintiler || []" :key="avans.id" class="border-b hover:bg-yellow-50">
                                                <td class="py-1 px-2 border-r">{{ formatZimmetTarihi(avans.tarih) }}</td>
                                                <td class="py-1 px-2 border-r">{{ avans.bordro_alani }}</td>
                                                <td class="py-1 px-2 border-r text-red-600 font-semibold">{{ avans.tutar }} ₺</td>
                                                <td class="py-1 px-2">{{ avans.aciklama }}</td>
                                            </tr>
                                            <tr v-if="!(selectedPersonel.avans_kesintiler && selectedPersonel.avans_kesintiler.length)">
                                                <td colspan="4" class="py-10 text-center text-gray-400">&lt;Gösterilecek Bilgi yok&gt;</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- PRIM TAB -->
                            <div v-if="activeTab==='prim'" class="text-sm h-full flex flex-col space-y-4">
                                <div class="h-full overflow-y-auto border border-gray-300">
                                    <table class="w-full text-xs text-left">
                                        <thead class="bg-gray-100 sticky top-0">
                                            <tr>
                                                <th class="py-1 px-2 border-r border-b">Tarih</th>
                                                <th class="py-1 px-2 border-r border-b">Bordro Alanı</th>
                                                <th class="py-1 px-2 border-r border-b">Tutar</th>
                                                <th class="py-1 px-2 border-b">Açıklama</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="prim in selectedPersonel.prim_kazanclar || []" :key="prim.id" class="border-b hover:bg-yellow-50">
                                                <td class="py-1 px-2 border-r">{{ formatZimmetTarihi(prim.tarih) }}</td>
                                                <td class="py-1 px-2 border-r">{{ prim.bordro_alani }}</td>
                                                <td class="py-1 px-2 border-r text-green-600 font-semibold">{{ prim.tutar }} ₺</td>
                                                <td class="py-1 px-2">{{ prim.aciklama }}</td>
                                            </tr>
                                            <tr v-if="!(selectedPersonel.prim_kazanclar && selectedPersonel.prim_kazanclar.length)">
                                                <td colspan="4" class="py-10 text-center text-gray-400">&lt;Gösterilecek Bilgi yok&gt;</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Panel Ayak Alanı -->
                        <div class="bg-gray-100 p-2 border-t border-gray-300 flex justify-between items-center mt-auto">
                            <div class="flex items-center">
                                <input type="checkbox" id="one_month" class="mr-2 border-gray-300 rounded text-indigo-600 text-xs" />
                                <label for="one_month" class="text-xs">Sadece bir ay öncesine kadar olan bilgileri görüntüle</label>
                            </div>
                            <div>
                                <button class="bg-gray-200 hover:bg-gray-300 border border-gray-300 text-gray-700 text-xs py-1 px-4 ml-2">Silinen Personeller</button>
                                <button v-if="activeTab==='ozluk'" @click="savePersonel" class="bg-blue-600 hover:bg-blue-700 text-white text-xs py-1 px-6 ml-2 font-bold focus:ring-2 rounded-sm outline-none shadow-sm">
                                    {{ isSaving ? 'Kaydediliyor...' : 'Kaydet' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
