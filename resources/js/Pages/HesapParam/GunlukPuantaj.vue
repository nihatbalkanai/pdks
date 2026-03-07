<script setup>
import { ref, computed, reactive, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({ parametreler: Array });

const liste = ref([...(props.parametreler || [])]);
const seciliId = ref(null);
const activeTab = ref('genel');

const seciliParametre = computed(() => {
    if (!seciliId.value) return null;
    return liste.value.find(p => p.id === seciliId.value) || null;
});

const form = reactive({
    id: null, ad: '', gun_donum_saati: '06:00', iceri_giris_saati: '08:30',
    disari_cikis_saati: '18:00', erken_gelme_toleransi: '08:00', gec_gelme_toleransi: '22:22',
    erken_cikma_toleransi: '22:22', hesaplama_tipi: 'normal_toplam',
    mola_suresi: 0, gec_gelme_cezasi: 0, erken_cikma_cezasi: 0, durum: true,
});

watch(seciliId, (id) => {
    const p = liste.value.find(x => x.id === id);
    if (p) {
        Object.assign(form, {
            id: p.id, ad: p.ad, gun_donum_saati: p.gun_donum_saati || '06:00',
            iceri_giris_saati: p.iceri_giris_saati || '08:30', disari_cikis_saati: p.disari_cikis_saati || '18:00',
            erken_gelme_toleransi: p.erken_gelme_toleransi || '08:00', gec_gelme_toleransi: p.gec_gelme_toleransi || '22:22',
            erken_cikma_toleransi: p.erken_cikma_toleransi || '22:22', hesaplama_tipi: p.hesaplama_tipi || 'normal_toplam',
            mola_suresi: p.mola_suresi || 0, gec_gelme_cezasi: p.gec_gelme_cezasi || 0,
            erken_cikma_cezasi: p.erken_cikma_cezasi || 0, durum: p.durum,
        });
    }
});

const yeniModalAcik = ref(false);
const yeniParametre = () => {
    seciliId.value = null;
    Object.assign(form, { id: null, ad: '', gun_donum_saati: '06:00', iceri_giris_saati: '08:30', disari_cikis_saati: '18:00', erken_gelme_toleransi: '08:00', gec_gelme_toleransi: '22:22', erken_cikma_toleransi: '22:22', hesaplama_tipi: 'normal_toplam', mola_suresi: 0, gec_gelme_cezasi: 0, erken_cikma_cezasi: 0, durum: true });
    yeniModalAcik.value = true;
};

const kaydet = async () => {
    try {
        if (form.id) {
            const res = await axios.put(route('tanim.gunluk-puantaj.update', form.id), { ...form });
            const idx = liste.value.findIndex(x => x.id === form.id);
            if (idx >= 0) liste.value[idx] = res.data.item;
            Swal.fire({toast:true, position:'top-end', icon:'success', title:'Güncellendi', showConfirmButton:false, timer:1200});
        } else {
            const res = await axios.post(route('tanim.gunluk-puantaj.store'), { ...form });
            liste.value.push(res.data.item);
            seciliId.value = res.data.item.id;
            yeniModalAcik.value = false;
            Swal.fire({toast:true, position:'top-end', icon:'success', title:'Eklendi', showConfirmButton:false, timer:1200});
        }
    } catch(e) { Swal.fire('Hata', e.response?.data?.message || 'İşlem başarısız', 'error'); }
};

const sil = (id) => {
    Swal.fire({title:'Emin misiniz?', text:'Bu parametre silinecektir.', icon:'warning', showCancelButton:true, confirmButtonText:'Evet, Sil'}).then(async (res) => {
        if (res.isConfirmed) {
            try {
                await axios.delete(route('tanim.gunluk-puantaj.destroy', id));
                liste.value = liste.value.filter(x => x.id !== id);
                seciliId.value = null;
                Swal.fire({toast:true, position:'top-end', icon:'success', title:'Silindi', showConfirmButton:false, timer:1200});
            } catch(e) { Swal.fire('Hata', 'Silinemedi', 'error'); }
        }
    });
};

// Bordro Alanları
const bordroForm = reactive({ id: null, bordro_alani: '', basla: '08:30', bitis: '18:00', min_sure: '00:30', max_sure: '22:00', ekle: '00:00', carpan: 100, ucret: 'ucret_1' });
const bordroModalAcik = ref(false);

const openBordroModal = (item = null) => {
    if (item) {
        Object.assign(bordroForm, { id: item.id, bordro_alani: item.bordro_alani, basla: item.basla || '08:30', bitis: item.bitis || '18:00', min_sure: item.min_sure || '00:30', max_sure: item.max_sure || '22:00', ekle: item.ekle || '00:00', carpan: item.carpan || 100, ucret: item.ucret || 'ucret_1' });
    } else {
        Object.assign(bordroForm, { id: null, bordro_alani: '', basla: '08:30', bitis: '18:00', min_sure: '00:30', max_sure: '22:00', ekle: '00:00', carpan: 100, ucret: 'ucret_1' });
    }
    bordroModalAcik.value = true;
};

const saveBordro = async () => {
    try {
        if (bordroForm.id) {
            const res = await axios.put(route('tanim.gunluk-puantaj.bordro-update', bordroForm.id), { ...bordroForm });
            const ba = seciliParametre.value?.bordro_alanlari;
            if (ba) { const idx = ba.findIndex(x => x.id === bordroForm.id); if (idx >= 0) ba[idx] = res.data.item; }
            Swal.fire({toast:true, position:'top-end', icon:'success', title:'Güncellendi', showConfirmButton:false, timer:1200});
        } else {
            const res = await axios.post(route('tanim.gunluk-puantaj.bordro-store', seciliId.value), { ...bordroForm });
            if (!seciliParametre.value.bordro_alanlari) seciliParametre.value.bordro_alanlari = [];
            seciliParametre.value.bordro_alanlari.push(res.data.item);
            Swal.fire({toast:true, position:'top-end', icon:'success', title:'Eklendi', showConfirmButton:false, timer:1200});
        }
        bordroModalAcik.value = false;
    } catch(e) { Swal.fire('Hata', e.response?.data?.message || 'İşlem başarısız', 'error'); }
};

const deleteBordro = (id) => {
    Swal.fire({title:'Emin misiniz?', icon:'warning', showCancelButton:true, confirmButtonText:'Evet, Sil'}).then(async (res) => {
        if (res.isConfirmed) {
            try {
                await axios.delete(route('tanim.gunluk-puantaj.bordro-destroy', id));
                const ba = seciliParametre.value?.bordro_alanlari;
                if (ba) { const idx = ba.findIndex(x => x.id === id); if (idx >= 0) ba.splice(idx, 1); }
                Swal.fire({toast:true, position:'top-end', icon:'success', title:'Silindi', showConfirmButton:false, timer:1200});
            } catch(e) { Swal.fire('Hata', 'Silinemedi', 'error'); }
        }
    });
};

const kopyaOlustur = () => {
    if (!seciliParametre.value) return;
    const p = seciliParametre.value;
    form.id = null;
    form.ad = p.ad + ' (Kopya)';
    yeniModalAcik.value = true;
};
</script>

<template>
<Head title="Günlük Puantaj Parametreleri" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full overflow-hidden">
            
            <!-- Başlık -->
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h2 class="font-bold text-xs text-gray-800">Günlük Puantaj Tablosu</h2>
            </div>

            <div class="flex flex-1 overflow-hidden">
                
                <!-- SOL PANEL: Tablo Listesi -->
                <div class="w-64 border-r border-gray-300 bg-white flex flex-col">
                    <div class="bg-gray-100 px-3 py-2 border-b border-gray-300 font-bold text-xs text-gray-700">Tablo Adı</div>
                    <div class="flex-1 overflow-y-auto">
                        <div v-for="p in liste" :key="p.id" 
                            @click="seciliId = p.id"
                            class="px-3 py-2 text-xs cursor-pointer border-b border-gray-100 flex items-center gap-2 transition"
                            :class="seciliId === p.id ? 'bg-yellow-100 font-bold text-gray-900 border-l-4 border-yellow-400' : 'hover:bg-gray-50 text-gray-700'">
                            <span v-if="seciliId === p.id" class="text-yellow-600">▶</span>
                            {{ p.ad }}
                        </div>
                        <div v-if="!liste.length" class="px-3 py-6 text-center text-gray-400 text-xs">Henüz tablo yok.</div>
                    </div>
                    <!-- Alt Butonlar -->
                    <div class="border-t border-gray-300 bg-gray-50 px-2 py-2 flex gap-1">
                        <button @click="yeniParametre" class="bg-green-500 hover:bg-green-600 text-white rounded p-1.5" title="Yeni Tablo"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg></button>
                        <button @click="seciliId && sil(seciliId)" :disabled="!seciliId" class="bg-red-500 hover:bg-red-600 text-white rounded p-1.5 disabled:opacity-40" title="Sil"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                        <button @click="kopyaOlustur" :disabled="!seciliId" class="bg-blue-500 hover:bg-blue-600 text-white rounded p-1.5 disabled:opacity-40" title="Kopya Oluştur"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg></button>
                    </div>
                </div>

                <!-- SAĞ PANEL: Detay -->
                <div class="flex-1 flex flex-col bg-[#eef2f9] overflow-hidden">
                    <div v-if="!seciliParametre" class="flex-1 flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-xs">Soldaki listeden bir tablo seçin veya yeni oluşturun.</p>
                        </div>
                    </div>

                    <template v-if="seciliParametre">
                        <!-- Sekmeler -->
                        <div class="flex bg-gray-100 border-b border-gray-300">
                            <button @click="activeTab='genel'" class="px-5 py-2 text-xs font-semibold transition" :class="activeTab==='genel' ? 'bg-white border-b-2 border-purple-500 text-purple-700' : 'text-gray-600 hover:bg-gray-200'">Genel</button>
                            <button @click="activeTab='mola'" class="px-5 py-2 text-xs font-semibold transition" :class="activeTab==='mola' ? 'bg-white border-b-2 border-purple-500 text-purple-700' : 'text-gray-600 hover:bg-gray-200'">Mola / Ceza</button>
                        </div>

                        <div class="flex-1 overflow-y-auto p-4">
                            <!-- GENEL SEKMESİ -->
                            <div v-if="activeTab==='genel'" class="max-w-2xl">
                                <div class="grid grid-cols-2 gap-x-8 gap-y-3">
                                    <div class="col-span-2">
                                        <label class="block text-xs font-bold mb-1">Adı:</label>
                                        <input v-model="form.ad" type="text" class="w-full text-xs rounded border-gray-300 focus:border-purple-500 bg-white" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1">Gün dönüm saati:</label>
                                        <input v-model="form.gun_donum_saati" type="time" class="w-full text-xs rounded border-gray-300 focus:border-purple-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1">Erken gelme toleransı:</label>
                                        <input v-model="form.erken_gelme_toleransi" type="time" class="w-full text-xs rounded border-gray-300 focus:border-purple-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1">İçeri giriş saati:</label>
                                        <input v-model="form.iceri_giris_saati" type="time" class="w-full text-xs rounded border-gray-300 focus:border-purple-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1">Geç gelme toleransı:</label>
                                        <input v-model="form.gec_gelme_toleransi" type="time" class="w-full text-xs rounded border-gray-300 focus:border-purple-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1">Dışarı çıkış saati:</label>
                                        <input v-model="form.disari_cikis_saati" type="time" class="w-full text-xs rounded border-gray-300 focus:border-purple-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1">Erken çıkma toleransı:</label>
                                        <input v-model="form.erken_cikma_toleransi" type="time" class="w-full text-xs rounded border-gray-300 focus:border-purple-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1">Hesaplama tipi:</label>
                                        <select v-model="form.hesaplama_tipi" class="w-full text-xs rounded border-gray-300 focus:border-purple-500">
                                            <option value="normal_toplam">Normal toplam</option>
                                            <option value="net_toplam">Net toplam</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end">
                                    <button @click="kaydet" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-1.5 rounded text-xs shadow transition">Kaydet</button>
                                </div>
                            </div>

                            <!-- MOLA / CEZA SEKMESİ -->
                            <div v-if="activeTab==='mola'" class="max-w-2xl">
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold mb-1">Mola süresi (dk):</label>
                                        <input v-model.number="form.mola_suresi" type="number" class="w-full text-xs rounded border-gray-300 focus:border-purple-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1">Geç gelme cezası (dk):</label>
                                        <input v-model.number="form.gec_gelme_cezasi" type="number" class="w-full text-xs rounded border-gray-300 focus:border-purple-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1">Erken çıkma cezası (dk):</label>
                                        <input v-model.number="form.erken_cikma_cezasi" type="number" class="w-full text-xs rounded border-gray-300 focus:border-purple-500" />
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end">
                                    <button @click="kaydet" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-1.5 rounded text-xs shadow transition">Kaydet</button>
                                </div>
                            </div>

                            <!-- BORDRO ALANLARI -->
                            <div class="mt-6 border-t border-gray-300 pt-4">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="font-bold text-xs text-gray-700">Bordro Alanları <span class="font-normal text-gray-400">(Mesai Kuralları)</span></h4>
                                    <button @click="openBordroModal()" class="bg-green-500 hover:bg-green-600 text-white rounded p-1.5" title="Yeni Bordro Alanı"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg></button>
                                </div>
                                <div class="bg-blue-50 border border-blue-200 rounded px-3 py-2 mb-3 text-[10px] text-blue-800 leading-relaxed">
                                    <b>ℹ️ Nasıl Çalışır?</b> Bu kurallar, personelin çıkış saatine göre hangi mesai türünün uygulanacağını belirler.
                                    <ul class="mt-1 ml-3 list-disc space-y-0.5">
                                        <li><b>Başla / Bitiş:</b> Mesainin geçerli olduğu saat aralığı (Örn: 18:00-22:00)</li>
                                        <li><b>Min:</b> Bu aralıkta en az bu kadar çalışılırsa mesai sayılır (Örn: 30dk)</li>
                                        <li><b>Max:</b> Bu aralıkta en fazla bu kadar mesai yazılır (Örn: 4 saat)</li>
                                        <li><b>Çarpan:</b> 150 = %50 FM (x1.5), 200 = %100 FM (x2.0)</li>
                                    </ul>
                                    <div class="mt-1 text-blue-600">
                                        <b>Örnek:</b> Çıkış 20:00 → 18:00-20:00 arası 2 saat → Min 30dk ✓ → Max 4 saat ✓ → %50 FM olarak <b>2 saat</b> yazılır.
                                    </div>
                                </div>
                                <table class="w-full text-xs text-left bg-white border border-gray-200">
                                    <thead class="bg-gray-100 text-gray-600 uppercase">
                                        <tr>
                                            <th class="px-2 py-1.5 border-b">Bordro Alanı</th>
                                            <th class="px-2 py-1.5 border-b text-center">Başla</th>
                                            <th class="px-2 py-1.5 border-b text-center">Bitiş</th>
                                            <th class="px-2 py-1.5 border-b text-center">Min</th>
                                            <th class="px-2 py-1.5 border-b text-center">Max</th>
                                            <th class="px-2 py-1.5 border-b text-center">Ekle</th>
                                            <th class="px-2 py-1.5 border-b text-center">Çarpan</th>
                                            <th class="px-2 py-1.5 border-b text-center">Ücret</th>
                                            <th class="px-2 py-1.5 border-b text-right">İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="b in seciliParametre.bordro_alanlari" :key="b.id" class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="px-2 py-1.5 font-medium">{{ b.bordro_alani }}</td>
                                            <td class="px-2 py-1.5 text-center">{{ b.basla }}</td>
                                            <td class="px-2 py-1.5 text-center">{{ b.bitis }}</td>
                                            <td class="px-2 py-1.5 text-center">{{ b.min_sure }}</td>
                                            <td class="px-2 py-1.5 text-center">{{ b.max_sure }}</td>
                                            <td class="px-2 py-1.5 text-center">{{ b.ekle }}</td>
                                            <td class="px-2 py-1.5 text-center font-bold">{{ b.carpan }}</td>
                                            <td class="px-2 py-1.5 text-center">{{ b.ucret }}</td>
                                            <td class="px-2 py-1.5 text-right">
                                                <button @click="openBordroModal(b)" class="text-blue-500 hover:text-blue-700 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572z"></path></svg></button>
                                                <button @click="deleteBordro(b.id)" class="text-red-500 hover:text-red-700 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                            </td>
                                        </tr>
                                        <tr v-if="!seciliParametre.bordro_alanlari || !seciliParametre.bordro_alanlari.length">
                                            <td colspan="9" class="px-2 py-4 text-center text-gray-400">Bordro alanı bulunmamaktadır.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Yeni Parametre Modal -->
    <div v-if="yeniModalAcik" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded shadow-xl w-[420px] border border-gray-300 overflow-hidden">
            <div class="bg-gray-100 px-4 py-3 border-b border-gray-300 flex justify-between items-center"><h3 class="font-bold">Yeni Günlük Puantaj Tablosu</h3><button @click="yeniModalAcik=false" class="text-gray-500 text-xl">&times;</button></div>
            <div class="p-4 space-y-3">
                <div><label class="block text-xs font-bold mb-1">Tablo Adı *</label><input v-model="form.ad" type="text" class="w-full text-xs rounded border-gray-300" placeholder="Örn: HAFTA İÇİ MAVİ YAKA"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-bold mb-1">Gün dönüm saati</label><input v-model="form.gun_donum_saati" type="time" class="w-full text-xs rounded border-gray-300"></div>
                    <div><label class="block text-xs font-bold mb-1">İçeri giriş saati</label><input v-model="form.iceri_giris_saati" type="time" class="w-full text-xs rounded border-gray-300"></div>
                    <div><label class="block text-xs font-bold mb-1">Dışarı çıkış saati</label><input v-model="form.disari_cikis_saati" type="time" class="w-full text-xs rounded border-gray-300"></div>
                    <div><label class="block text-xs font-bold mb-1">Hesaplama tipi</label>
                        <select v-model="form.hesaplama_tipi" class="w-full text-xs rounded border-gray-300"><option value="normal_toplam">Normal toplam</option><option value="net_toplam">Net toplam</option></select>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 p-3 border-t border-gray-200 flex justify-end gap-2">
                <button @click="yeniModalAcik=false" class="px-3 py-1.5 border bg-white rounded text-xs hover:bg-gray-100">İptal</button>
                <button @click="kaydet" class="px-3 py-1.5 bg-purple-600 text-white rounded text-xs hover:bg-purple-700">Kaydet</button>
            </div>
        </div>
    </div>

    <!-- Bordro Modal -->
    <div v-if="bordroModalAcik" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded shadow-xl w-[500px] border border-gray-300 overflow-hidden">
            <div class="bg-gray-100 px-4 py-3 border-b border-gray-300 flex justify-between items-center"><h3 class="font-bold">{{ bordroForm.id ? 'Bordro Alanı Düzenle' : 'Yeni Bordro Alanı' }}</h3><button @click="bordroModalAcik=false" class="text-gray-500 text-xl">&times;</button></div>
            <div class="p-4 space-y-3">
                <div><label class="block text-xs font-bold mb-1">Bordro Alanı Adı *</label><input v-model="bordroForm.bordro_alani" type="text" class="w-full text-xs rounded border-gray-300" placeholder="Örn: FAZLA MESAİ %50"></div>
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="block text-xs font-bold mb-1">Başla</label><input v-model="bordroForm.basla" type="time" class="w-full text-xs rounded border-gray-300"></div>
                    <div><label class="block text-xs font-bold mb-1">Bitiş</label><input v-model="bordroForm.bitis" type="time" class="w-full text-xs rounded border-gray-300"></div>
                    <div><label class="block text-xs font-bold mb-1">Min Süre</label><input v-model="bordroForm.min_sure" type="time" class="w-full text-xs rounded border-gray-300"></div>
                    <div><label class="block text-xs font-bold mb-1">Max Süre</label><input v-model="bordroForm.max_sure" type="time" class="w-full text-xs rounded border-gray-300"></div>
                    <div><label class="block text-xs font-bold mb-1">Ekle</label><input v-model="bordroForm.ekle" type="time" class="w-full text-xs rounded border-gray-300"></div>
                    <div><label class="block text-xs font-bold mb-1">Çarpan (%)</label><input v-model.number="bordroForm.carpan" type="number" class="w-full text-xs rounded border-gray-300" placeholder="150"></div>
                </div>
                <div><label class="block text-xs font-bold mb-1">Ücret Alanı</label>
                    <select v-model="bordroForm.ucret" class="w-full text-xs rounded border-gray-300">
                        <option value="ucret_1">Ücret 1</option>
                        <option value="ucret_2">Ücret 2</option>
                        <option value="ucret_3">Ücret 3</option>
                    </select>
                </div>
            </div>
            <div class="bg-gray-50 p-3 border-t border-gray-200 flex justify-end gap-2">
                <button @click="bordroModalAcik=false" class="px-3 py-1.5 border bg-white rounded text-xs hover:bg-gray-100">İptal</button>
                <button @click="saveBordro" class="px-3 py-1.5 bg-green-600 text-white rounded text-xs hover:bg-green-700">Kaydet</button>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
