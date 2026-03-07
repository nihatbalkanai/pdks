<script setup>
import { ref, computed, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ personeller: Array, servisler: { type: Array, default: () => [] } });

const activeTab = ref('atama');
const search = ref('');
const selectedIds = ref([]);
const ulasimTipi = ref('servis');
const progress = ref(0);
const isProcessing = ref(false);

const form = reactive({
    personel_ids: [],
    ulasim_tipi: 'servis',
    servis_plaka: '',
    yol_parasi: '',
});

// Araç Yönetimi
const isAracModalOpen = ref(false);
const editingArac = ref(null);
const aracListesi = ref([...(props.servisler || [])]);
const aracForm = reactive({ plaka: '', sofor: '', guzergah: '', durum: true });

const openAracModal = (arac = null) => {
    editingArac.value = arac;
    if (arac) {
        aracForm.plaka = arac.plaka;
        aracForm.sofor = arac.sofor;
        aracForm.guzergah = arac.guzergah;
        aracForm.durum = !!arac.durum;
    } else {
        aracForm.plaka = '';
        aracForm.sofor = '';
        aracForm.guzergah = '';
        aracForm.durum = true;
    }
    isAracModalOpen.value = true;
};

const saveArac = async () => {
    try {
        if (editingArac.value) {
            await axios.put(route('servisler.update', editingArac.value.id), { ...aracForm });
        } else {
            await axios.post(route('servisler.store'), { ...aracForm });
        }
        isAracModalOpen.value = false;
        Swal.fire('Başarılı', editingArac.value ? 'Araç güncellendi' : 'Yeni araç eklendi', 'success');
        // Reload
        const res = await axios.get(route('servisler.index') + '?json=1');
        if (res.data?.data) aracListesi.value = res.data.data;
        else window.location.reload();
    } catch (e) {
        Swal.fire('Hata', e.response?.data?.message || 'İşlem başarısız', 'error');
    }
};

const deleteArac = async (id) => {
    const result = await Swal.fire({ title: 'Emin misiniz?', text: 'Servis aracı silinecek', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sil', cancelButtonText: 'İptal' });
    if (!result.isConfirmed) return;
    try {
        await axios.delete(route('servisler.destroy', id));
        aracListesi.value = aracListesi.value.filter(a => a.id !== id);
        Swal.fire('Silindi', 'Servis aracı silindi', 'success');
    } catch (e) {
        Swal.fire('Hata', e.response?.data?.message || 'Silinemedi', 'error');
    }
};

// Personel İşlemleri
const toTitleCase = (str) => { if (!str) return ''; return str.toLocaleLowerCase('tr-TR').replace(/(^|\s)(\S)/g, (m, s, c) => s + c.toLocaleUpperCase('tr-TR')); };
const filtered = computed(() => {
    if (!search.value) return props.personeller;
    const q = search.value.toLocaleLowerCase('tr-TR');
    return props.personeller.filter(p => (p.kart_no || '').toLowerCase().includes(q) || (p.ad || '').toLocaleLowerCase('tr-TR').includes(q) || (p.soyad || '').toLocaleLowerCase('tr-TR').includes(q));
});

const toggleAll = () => {
    if (selectedIds.value.length === filtered.value.length) { selectedIds.value = []; }
    else { selectedIds.value = filtered.value.map(p => p.id); }
};

const ulasimDurum = (p) => {
    if (p.ulasim_tipi === 'servis') return `🚌 ${p.servis_plaka || '-'}`;
    if (p.ulasim_tipi === 'yol_parasi_gunluk') return `💵 ${Number(p.yol_parasi || 0).toLocaleString('tr-TR')} ₺/gün`;
    if (p.ulasim_tipi === 'yol_parasi_aylik') return `💵 ${Number(p.yol_parasi || 0).toLocaleString('tr-TR')} ₺/ay`;
    return '—';
};

const submit = () => {
    if (selectedIds.value.length === 0) { Swal.fire('Uyarı', 'Lütfen en az bir personel seçin.', 'warning'); return; }
    if (ulasimTipi.value === 'servis' && !form.servis_plaka) { Swal.fire('Uyarı', 'Lütfen servis plaka numarası girin.', 'warning'); return; }
    if (ulasimTipi.value === 'yol_parasi_gunluk' && !form.yol_parasi) { Swal.fire('Uyarı', 'Lütfen günlük yol parası girin.', 'warning'); return; }
    if (ulasimTipi.value === 'yol_parasi_aylik' && !form.yol_parasi) { Swal.fire('Uyarı', 'Lütfen aylık yol parası girin.', 'warning'); return; }

    form.personel_ids = selectedIds.value;
    form.ulasim_tipi = ulasimTipi.value;
    isProcessing.value = true;
    progress.value = 0;
    const iv = setInterval(() => { progress.value += 10; if (progress.value >= 90) clearInterval(iv); }, 100);

    axios.post(route('toplu-islemler.servis-yol-atama.uygula'), { ...form })
        .then(() => {
            clearInterval(iv);
            progress.value = 100;
            isProcessing.value = false;
            Swal.fire('Başarılı!', `${selectedIds.value.length} personele ulaşım tanımı atandı.`, 'success')
                .then(() => window.location.reload());
        })
        .catch(e => {
            clearInterval(iv);
            isProcessing.value = false;
            Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu', 'error');
        });
};
</script>

<template>
<Head title="Servis & Yol Parası Yönetimi" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#dbeafe] to-[#bfdbfe] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
                <h2 class="font-bold text-sm text-gray-800">🚌 Servis & Yol Parası Yönetimi</h2>
                <div class="flex gap-1">
                    <button @click="activeTab = 'atama'" :class="activeTab === 'atama' ? 'bg-white shadow-sm text-blue-700' : 'text-gray-600 hover:bg-white/50'" class="px-3 py-1 rounded text-xs font-semibold transition">
                        📋 Toplu Atama
                    </button>
                    <button @click="activeTab = 'araclar'" :class="activeTab === 'araclar' ? 'bg-white shadow-sm text-blue-700' : 'text-gray-600 hover:bg-white/50'" class="px-3 py-1 rounded text-xs font-semibold transition">
                        🚐 Araç Filosu ({{ aracListesi.length }})
                    </button>
                </div>
            </div>

            <!-- TAB: Toplu Atama -->
            <div v-show="activeTab === 'atama'" class="flex flex-1 overflow-hidden">
                <!-- SOL: Personel Listesi -->
                <div class="w-56 border-r border-gray-400 flex flex-col bg-gray-50">
                    <div class="p-2 border-b border-gray-300">
                        <input v-model="search" type="text" placeholder="🔍 Ara..." class="w-full text-xs border-gray-300 rounded px-2 py-1" />
                        <label class="flex items-center gap-1 mt-1 cursor-pointer" @click="toggleAll">
                            <input type="checkbox" :checked="selectedIds.length && selectedIds.length === filtered.length" class="rounded border-gray-300 text-blue-600" />
                            <span class="text-[10px] text-gray-500">Tümünü Seç ({{ selectedIds.length }}/{{ filtered.length }})</span>
                        </label>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <label v-for="p in filtered" :key="p.id" class="flex items-start gap-2 px-2 py-1.5 hover:bg-blue-50 cursor-pointer border-b border-gray-200">
                            <input type="checkbox" :value="p.id" v-model="selectedIds" class="mt-0.5 rounded border-gray-300 text-blue-600" />
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-gray-800 truncate">{{ toTitleCase(p.ad + ' ' + p.soyad) }}</div>
                                <div class="text-[10px] text-gray-500">{{ ulasimDurum(p) }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- SAĞ: Wizard -->
                <div class="flex-1 p-5 overflow-y-auto">
                    <!-- ADIM 1 -->
                    <div class="mb-5">
                        <span class="text-lg font-bold text-blue-700">Adım 1</span>
                        <span class="ml-3 text-sm text-gray-600">Ulaşım tanımı atamak istediğiniz personelleri seçin...</span>
                        <div class="mt-2 bg-blue-50 text-blue-700 px-3 py-2 rounded text-xs font-semibold inline-block">
                            {{ selectedIds.length }} personel seçildi
                        </div>
                    </div>

                    <!-- ADIM 2 -->
                    <div class="mb-5 border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-blue-700">Adım 2</span>
                        <span class="ml-3 text-sm text-gray-600">Ulaşım tipini ve değerini belirleyin...</span>
                        <div class="mt-3 space-y-4 max-w-lg">
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 px-4 py-3 rounded-lg border-2 cursor-pointer transition"
                                    :class="ulasimTipi === 'servis' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" v-model="ulasimTipi" value="servis" class="hidden" />
                                    <div>
                                        <div class="text-sm font-bold text-gray-800">🚌 Servis Hakkı</div>
                                        <div class="text-[10px] text-gray-500">Personele servis plakası atanır</div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 px-4 py-3 rounded-lg border-2 cursor-pointer transition"
                                    :class="ulasimTipi === 'yol_parasi_gunluk' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" v-model="ulasimTipi" value="yol_parasi_gunluk" class="hidden" />
                                    <div>
                                        <div class="text-sm font-bold text-gray-800">💵 Günlük Yol Parası</div>
                                        <div class="text-[10px] text-gray-500">Günlük yol parası atanır</div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 px-4 py-3 rounded-lg border-2 cursor-pointer transition"
                                    :class="ulasimTipi === 'yol_parasi_aylik' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" v-model="ulasimTipi" value="yol_parasi_aylik" class="hidden" />
                                    <div>
                                        <div class="text-sm font-bold text-gray-800">💵 Aylık Yol Parası</div>
                                        <div class="text-[10px] text-gray-500">Aylık toplu ödenen yol parası atanır</div>
                                    </div>
                                </label>
                            </div>

                            <div v-if="ulasimTipi === 'servis'" class="flex items-center gap-3">
                                <label class="text-xs font-semibold w-28">Servis Plakası:</label>
                                <input v-model="form.servis_plaka" type="text" placeholder="34 ABC 123" class="border-gray-300 rounded px-2 py-1 text-sm uppercase w-40" />
                            </div>
                            <div v-if="ulasimTipi === 'yol_parasi_gunluk'" class="flex items-center gap-3">
                                <label class="text-xs font-semibold w-28">Günlük Ücret:</label>
                                <input v-model="form.yol_parasi" type="number" step="0.01" min="0" placeholder="0.00" class="border-gray-300 rounded px-2 py-1 text-sm w-32" />
                                <span class="text-xs text-gray-500">₺ / gün</span>
                            </div>
                            <div v-if="ulasimTipi === 'yol_parasi_aylik'" class="flex items-center gap-3">
                                <label class="text-xs font-semibold w-28">Aylık Ücret:</label>
                                <input v-model="form.yol_parasi" type="number" step="0.01" min="0" placeholder="0.00" class="border-gray-300 rounded px-2 py-1 text-sm w-32" />
                                <span class="text-xs text-gray-500">₺ / ay</span>
                            </div>
                        </div>
                    </div>

                    <!-- ADIM 3 -->
                    <div class="border-t border-gray-300 pt-4">
                        <span class="text-lg font-bold text-blue-700">Adım 3</span>
                        <span class="ml-3 text-sm text-gray-600">Bilgilerinizi kontrol ettikten sonra işlemi başlatın.</span>
                        <div v-if="selectedIds.length > 0" class="mt-3 bg-gray-50 border rounded p-3 text-xs space-y-1">
                            <div><strong>Seçili personel:</strong> {{ selectedIds.length }} kişi</div>
                            <div><strong>Ulaşım tipi:</strong>
                                {{ ulasimTipi === 'servis' ? '🚌 Servis Hakkı' :
                                   ulasimTipi === 'yol_parasi_gunluk' ? '💵 Günlük Yol Parası' : '💵 Aylık Yol Parası' }}
                            </div>
                            <div v-if="ulasimTipi === 'servis'"><strong>Plaka:</strong> {{ form.servis_plaka || '—' }}</div>
                            <div v-if="ulasimTipi === 'yol_parasi_gunluk'"><strong>Günlük Ücret:</strong> {{ form.yol_parasi || '—' }} ₺</div>
                            <div v-if="ulasimTipi === 'yol_parasi_aylik'"><strong>Aylık Ücret:</strong> {{ form.yol_parasi || '—' }} ₺</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Araç Filosu -->
            <div v-show="activeTab === 'araclar'" class="flex-1 overflow-y-auto p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-bold text-gray-700">🚐 Araç Filosu & Güzergahlar</h3>
                    <button @click="openAracModal()" class="px-3 py-1.5 bg-blue-600 text-white rounded text-xs font-semibold hover:bg-blue-700 transition">
                        + Yeni Araç Ekle
                    </button>
                </div>

                <table class="w-full text-xs border border-gray-300 rounded">
                    <thead class="bg-gray-100 text-gray-600 uppercase font-semibold">
                        <tr>
                            <th class="py-2 px-3 text-left">Plaka</th>
                            <th class="py-2 px-3 text-left">Şoför</th>
                            <th class="py-2 px-3 text-left">Güzergah</th>
                            <th class="py-2 px-3 text-center">Personel</th>
                            <th class="py-2 px-3 text-center">Durum</th>
                            <th class="py-2 px-3 text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="a in aracListesi" :key="a.id" class="border-t border-gray-200 hover:bg-blue-50 transition">
                            <td class="py-2 px-3 font-bold text-gray-800 uppercase">{{ a.plaka }}</td>
                            <td class="py-2 px-3 text-gray-600">{{ a.sofor || '—' }}</td>
                            <td class="py-2 px-3 text-gray-600 truncate max-w-[200px]" :title="a.guzergah">{{ a.guzergah || '—' }}</td>
                            <td class="py-2 px-3 text-center text-blue-600 font-bold">{{ a.personeller_count || 0 }} Kişi</td>
                            <td class="py-2 px-3 text-center">
                                <span v-if="a.durum" class="text-green-600 font-bold bg-green-100 px-2 py-0.5 rounded text-[10px]">Aktif</span>
                                <span v-else class="text-red-500 font-bold bg-red-100 px-2 py-0.5 rounded text-[10px]">Pasif</span>
                            </td>
                            <td class="py-2 px-3 text-right">
                                <button @click="openAracModal(a)" class="text-blue-500 hover:text-blue-700 mr-2">Düzenle</button>
                                <button @click="deleteArac(a.id)" class="text-red-500 hover:text-red-700">Sil</button>
                            </td>
                        </tr>
                        <tr v-if="!aracListesi.length">
                            <td colspan="6" class="py-8 text-center text-gray-400">Kayıtlı servis aracı bulunmuyor.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Alt Butonlar -->
            <div v-show="activeTab === 'atama'" class="flex items-center gap-3 px-4 py-2 bg-gray-100 border-t border-gray-400">
                <button @click="submit" :disabled="isProcessing" class="flex items-center bg-white border border-gray-400 rounded-sm px-4 py-1.5 text-xs hover:bg-gray-50 shadow-sm font-semibold disabled:opacity-50">
                    <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    İşlemi başlat &gt;
                </button>
                <div v-if="isProcessing" class="flex-1 max-w-xs">
                    <div class="bg-gray-200 rounded-full h-3"><div class="bg-blue-500 h-3 rounded-full transition-all" :style="{width: progress+'%'}"></div></div>
                </div>
                <div class="ml-auto">
                    <button @click="$inertia.visit(route('dashboard'))" class="flex items-center bg-white border border-gray-400 rounded-sm px-4 py-1.5 text-xs hover:bg-gray-50 shadow-sm text-red-600 font-semibold">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        İptal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Araç Ekleme/Düzenleme Modalı -->
    <Modal :show="isAracModalOpen" @close="isAracModalOpen = false">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ editingArac ? '✏️ Servis Düzenle' : '🚐 Yeni Servis Aracı Ekle' }}</h3>
            <form @submit.prevent="saveArac">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Araç Plakası *</label>
                        <input v-model="aracForm.plaka" type="text" placeholder="34 ABC 123" class="mt-1 block w-full border-gray-300 uppercase rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Şoför Adı</label>
                        <input v-model="aracForm.sofor" type="text" placeholder="Şoför adı" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Güzergah Bilgisi</label>
                        <input v-model="aracForm.guzergah" type="text" placeholder="Kadıköy - Pendik vb." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" />
                    </div>
                    <div v-if="editingArac" class="flex items-center">
                        <input type="checkbox" v-model="aracForm.durum" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" />
                        <span class="ms-2 text-sm text-gray-600">Serviste (Aktif)</span>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="isAracModalOpen = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition text-sm">İptal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm font-semibold">Kaydet</button>
                </div>
            </form>
        </div>
    </Modal>
</AuthenticatedLayout>
</template>
