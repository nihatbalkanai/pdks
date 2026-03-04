<script setup>
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({ personeller: Array, vardiyalar: Array });

const seciliPersonelId = ref(null);
const yil = ref(new Date().getFullYear());
const ay = ref(new Date().getMonth() + 1);
const planlar = ref([]);
const yukleniyor = ref(false);

const aylar = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
const gunAdlari = ['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'];
const turler = [
    { value: 'is_gunu', label: 'İş Günü', color: 'bg-blue-100 text-blue-800' },
    { value: 'tatil', label: 'Tatil', color: 'bg-gray-200 text-gray-600' },
    { value: 'resmi_tatil', label: 'Resmi Tatil', color: 'bg-red-100 text-red-700' },
    { value: 'izin', label: 'İzin', color: 'bg-green-100 text-green-700' },
];

const seciliPersonel = computed(() => props.personeller.find(p => p.id === seciliPersonelId.value));

// Takvim günlerini oluştur
const takvimGunleri = computed(() => {
    const firstDay = new Date(yil.value, ay.value - 1, 1);
    const lastDay = new Date(yil.value, ay.value, 0);
    const startDow = firstDay.getDay() === 0 ? 7 : firstDay.getDay(); // ISO: 1=Pzt
    const days = [];

    // Önceki ayın boş günleri
    for (let i = 1; i < startDow; i++) days.push(null);
    
    for (let d = 1; d <= lastDay.getDate(); d++) {
        const tarih = `${yil.value}-${String(ay.value).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const dt = new Date(yil.value, ay.value - 1, d);
        const dow = dt.getDay();
        const plan = planlar.value.find(p => p.tarih && p.tarih.substring(0, 10) === tarih);
        days.push({
            gun: d,
            tarih,
            haftaSonu: dow === 0 || dow === 6,
            plan: plan || null,
            vardiyaAd: plan?.vardiya?.ad || null,
            tur: plan?.tur || (dow === 0 || dow === 6 ? 'tatil' : 'is_gunu'),
        });
    }
    return days;
});

const planGetir = async () => {
    if (!seciliPersonelId.value) return;
    yukleniyor.value = true;
    try {
        const res = await axios.get(route('tanim.personel-calisma-plan.plan-getir', seciliPersonelId.value), { params: { yil: yil.value, ay: ay.value } });
        planlar.value = res.data;
    } catch(e) { console.error(e); }
    yukleniyor.value = false;
};

watch([seciliPersonelId, yil, ay], () => planGetir());

// Gün modal state
const gunModal = ref(null); // { gun, tur, vardiya_id, aciklama }
const gunModalYukleniyor = ref(false);

// Gün tıkla — modal aç (SweetAlert yok)
const gunDuzenle = (gun) => {
    if (!gun || !seciliPersonelId.value) return;
    gunModal.value = {
        gun,
        tur: gun.tur || 'is_gunu',
        vardiya_id: gun.plan?.vardiya_id || '',
        aciklama: gun.plan?.aciklama || '',
    };
};

const gunModalKaydet = async () => {
    if (!gunModal.value) return;
    gunModalYukleniyor.value = true;
    try {
        await axios.post(
            route('tanim.personel-calisma-plan.gun-guncelle', seciliPersonelId.value),
            {
                tarih: gunModal.value.gun.tarih,
                tur: gunModal.value.tur,
                vardiya_id: gunModal.value.vardiya_id || null,
                aciklama: gunModal.value.aciklama || null,
            }
        );
        gunModal.value = null;
        planGetir();
        Swal.fire({toast:true, position:'top-end', icon:'success', title:'Kaydedildi', showConfirmButton:false, timer:1200});
    } catch(e) {
        Swal.fire('Hata', e.response?.data?.message || 'Bir hata oluştu', 'error');
    }
    gunModalYukleniyor.value = false;
};

// Toplu atama
const topluAtaAc = async () => {
    if (!seciliPersonelId.value) return;
    const firstOfMonth = `${yil.value}-${String(ay.value).padStart(2,'0')}-01`;
    const lastOfMonth = new Date(yil.value, ay.value, 0);
    const lastStr = `${yil.value}-${String(ay.value).padStart(2,'0')}-${String(lastOfMonth.getDate()).padStart(2,'0')}`;
    const { value: formValues } = await Swal.fire({
        title: 'Toplu Atama',
        html: `
            <div style="text-align:left;font-size:13px">
                <label style="font-weight:bold">Başlangıç:</label>
                <input id="swal-bas" type="date" class="swal2-input" style="width:100%;margin:4px 0 8px" value="${firstOfMonth}">
                <label style="font-weight:bold">Bitiş:</label>
                <input id="swal-bit" type="date" class="swal2-input" style="width:100%;margin:4px 0 8px" value="${lastStr}">
                <label style="font-weight:bold">Günler:</label>
                <div style="display:flex;gap:6px;margin:4px 0 8px;flex-wrap:wrap">
                    ${[1,2,3,4,5,6,7].map(g => `<label style="display:flex;align-items:center;gap:2px"><input type="checkbox" class="swal-gun" value="${g}" ${g<=5?'checked':''}>${gunAdlari[g-1]}</label>`).join('')}
                </div>
                <label style="font-weight:bold">Vardiya:</label>
                <select id="swal-vardiya2" class="swal2-select" style="width:100%;border:1px solid #ccc;border-radius:4px;padding:6px;margin:4px 0 8px">
                    <option value="">— Yok —</option>
                    ${props.vardiyalar.map(v => `<option value="${v.id}">${v.ad}</option>`).join('')}
                </select>
                <label style="font-weight:bold">Tür:</label>
                <select id="swal-tur2" class="swal2-select" style="width:100%;border:1px solid #ccc;border-radius:4px;padding:6px">
                    ${turler.map(t => `<option value="${t.value}">${t.label}</option>`).join('')}
                </select>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Uygula',
        cancelButtonText: 'İptal',
        preConfirm: () => ({
            baslangic: document.getElementById('swal-bas').value,
            bitis: document.getElementById('swal-bit').value,
            gunler: [...document.querySelectorAll('.swal-gun:checked')].map(c => parseInt(c.value)),
            vardiya_id: document.getElementById('swal-vardiya2').value || null,
            tur: document.getElementById('swal-tur2').value,
        }),
    });
    if (formValues) {
        try {
            const res = await axios.post(route('tanim.personel-calisma-plan.toplu-ata', seciliPersonelId.value), formValues);
            planGetir();
            Swal.fire({toast:true, position:'top-end', icon:'success', title:`${res.data.count} gün atandı`, showConfirmButton:false, timer:1500});
        } catch(e) { Swal.fire('Hata', e.response?.data?.message || 'Hata', 'error'); }
    }
};

// Temizle
const temizle = () => {
    Swal.fire({title: `${aylar[ay.value-1]} ${yil.value} planını temizle?`, icon:'warning', showCancelButton:true, confirmButtonText:'Evet, Temizle'}).then(async (r) => {
        if (r.isConfirmed) {
            await axios.delete(route('tanim.personel-calisma-plan.temizle', seciliPersonelId.value), { params: {yil: yil.value, ay: ay.value} });
            planGetir();
        }
    });
};

// Önceki/Sonraki ay
const oncekiAy = () => { if(ay.value === 1){ ay.value=12; yil.value--; } else ay.value--; };
const sonrakiAy = () => { if(ay.value === 12){ ay.value=1; yil.value++; } else ay.value++; };

const turRenk = (tur) => {
    const t = turler.find(x => x.value === tur);
    return t ? t.color : 'bg-white';
};

// Arama
const arama = ref('');
const filtreliPersoneller = computed(() => {
    if (!arama.value) return props.personeller;
    const q = arama.value.toLowerCase();
    return props.personeller.filter(p => `${p.ad} ${p.soyad} ${p.sicil_no || ''}`.toLowerCase().includes(q));
});
</script>

<template>
<Head title="Personele Özel Çalışma Planları" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full overflow-hidden">
            <div class="bg-gradient-to-r from-[#fde8d0] to-[#f5d0a9] border-b border-gray-400 px-4 py-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <h2 class="font-bold text-sm text-gray-800">Personele Özel Çalışma Planları</h2>
            </div>

            <div class="flex flex-1 overflow-hidden">
                <!-- SOL: Personel Listesi -->
                <div class="w-64 border-r border-gray-300 bg-white flex flex-col">
                    <div class="px-2 py-2 border-b border-gray-200">
                        <input v-model="arama" type="text" placeholder="Personel ara..." class="w-full text-xs rounded border-gray-300 focus:border-orange-400 px-2 py-1">
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div v-for="p in filtreliPersoneller" :key="p.id"
                            @click="seciliPersonelId = p.id"
                            class="px-3 py-2 text-sm cursor-pointer border-b border-gray-100 transition"
                            :class="seciliPersonelId === p.id ? 'bg-orange-100 font-bold border-l-4 border-orange-400' : 'hover:bg-gray-50'">
                            <div class="text-gray-800">{{ p.ad }} {{ p.soyad }}</div>
                            <div class="text-xs text-gray-400">{{ p.sicil_no }} • {{ p.departman }}</div>
                        </div>
                        <div v-if="!filtreliPersoneller.length" class="px-3 py-6 text-center text-gray-400 text-xs">Personel bulunamadı.</div>
                    </div>
                </div>

                <!-- SAĞ: Takvim -->
                <div class="flex-1 flex flex-col bg-gray-50 overflow-hidden">
                    <div v-if="!seciliPersonelId" class="flex-1 flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <p class="text-sm">Soldaki listeden bir personel seçin.</p>
                        </div>
                    </div>

                    <template v-if="seciliPersonelId">
                        <!-- Üst: Ay Navigasyon + Butonlar -->
                        <div class="border-b border-gray-300 bg-white px-4 py-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button @click="oncekiAy" class="p-1 rounded hover:bg-gray-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button>
                                <span class="font-bold text-sm min-w-[120px] text-center">{{ aylar[ay-1] }} {{ yil }}</span>
                                <button @click="sonrakiAy" class="p-1 rounded hover:bg-gray-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button>
                            </div>
                            <div class="flex gap-2">
                                <button @click="topluAtaAc" class="bg-blue-600 hover:bg-blue-700 text-white rounded px-3 py-1 text-xs shadow">Toplu Atama</button>
                                <button @click="temizle" class="bg-red-500 hover:bg-red-600 text-white rounded px-3 py-1 text-xs shadow">Temizle</button>
                            </div>
                        </div>

                        <!-- Takvim Grid -->
                        <div class="flex-1 overflow-auto p-3">
                            <div v-if="yukleniyor" class="flex items-center justify-center h-full"><span class="text-gray-400 animate-pulse">Yükleniyor...</span></div>
                            <div v-else class="grid grid-cols-7 gap-1">
                                <!-- Gün başlıkları -->
                                <div v-for="g in gunAdlari" :key="g" class="text-center text-xs font-bold text-gray-500 py-1">{{ g }}</div>
                                <!-- Günler -->
                                <div v-for="(gun, idx) in takvimGunleri" :key="idx"
                                    @click="gun && gunDuzenle(gun)"
                                    class="min-h-[70px] rounded border text-xs p-1 transition cursor-pointer"
                                    :class="gun ? (gun.haftaSonu ? 'bg-gray-100 border-gray-200 hover:border-orange-300' : 'bg-white border-gray-200 hover:border-orange-400') : 'border-transparent'">
                                    <template v-if="gun">
                                        <div class="flex justify-between items-start">
                                            <span class="font-bold" :class="gun.haftaSonu ? 'text-red-400' : 'text-gray-700'">{{ gun.gun }}</span>
                                            <span class="rounded-full px-1.5 py-0.5 text-[10px] font-medium" :class="turRenk(gun.tur)">
                                                {{ turler.find(t => t.value === gun.tur)?.label || gun.tur }}
                                            </span>
                                        </div>
                                        <div v-if="gun.vardiyaAd" class="mt-1 text-[10px] text-blue-600 font-semibold truncate">{{ gun.vardiyaAd }}</div>
                                        <div v-if="gun.tur === 'izin' && gun.plan?.aciklama" class="mt-0.5 text-[10px] text-green-700 font-semibold truncate" :title="gun.plan.aciklama">📋 {{ gun.plan.aciklama }}</div>
                                        <div v-else-if="gun.plan?.aciklama" class="mt-0.5 text-[10px] text-gray-400 truncate">{{ gun.plan.aciklama }}</div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</AuthenticatedLayout>

    <!-- Gün Düzenleme Modalı -->
    <div v-if="gunModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="gunModal=null">
        <div class="bg-white border border-gray-400 shadow-xl w-[420px] overflow-hidden">
            <!-- Modal Başlığı -->
            <div class="bg-gradient-to-r from-[#fde8d0] to-[#f5d0a9] border-b border-gray-400 px-4 py-2.5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="font-bold text-sm text-gray-800">
                        {{ gunModal.gun.gun }} {{ aylar[ay-1] }} {{ yil }}
                    </span>
                </div>
                <button @click="gunModal=null" class="text-gray-500 hover:text-gray-800 text-lg leading-none">&times;</button>
            </div>

            <!-- Form -->
            <div class="p-4 space-y-3">
                <!-- Günlük Tür -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1">Gün Türü</label>
                    <div class="grid grid-cols-2 gap-1.5">
                        <button v-for="t in turler" :key="t.value"
                            @click="gunModal.tur = t.value"
                            class="py-1.5 px-2 text-xs font-medium rounded border transition"
                            :class="gunModal.tur === t.value
                                ? t.color + ' border-current shadow-sm'
                                : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50'">
                            {{ t.label }}
                        </button>
                    </div>
                </div>

                <!-- Seçili tür etiketi -->
                <div class="flex items-center gap-2 py-1">
                    <span class="text-[11px] text-gray-400">Seçili:</span>
                    <span class="text-xs font-semibold rounded-full px-2.5 py-0.5" :class="turler.find(t=>t.value===gunModal.tur)?.color">
                        {{ turler.find(t=>t.value===gunModal.tur)?.label }}
                    </span>
                </div>

                <!-- Vardiya -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1">Vardiya</label>
                    <select v-model="gunModal.vardiya_id" class="w-full text-sm border border-gray-300 rounded px-2 py-1.5 bg-white focus:border-orange-400 focus:ring-1 focus:ring-orange-200">
                        <option value="">— Vardiya Yok —</option>
                        <option v-for="v in vardiyalar" :key="v.id" :value="v.id">{{ v.ad }}</option>
                    </select>
                </div>

                <!-- Açıklama -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1">Açıklama</label>
                    <input v-model="gunModal.aciklama" type="text"
                        class="w-full text-sm border border-gray-300 rounded px-2 py-1.5 focus:border-orange-400 focus:ring-1 focus:ring-orange-200"
                        placeholder="Nöbet, Telafi, Özel durum vb."
                        @keyup.enter="gunModalKaydet">
                </div>
            </div>

            <!-- Butonlar -->
            <div class="bg-gray-50 border-t border-gray-200 px-4 py-2.5 flex justify-end gap-2">
                <button @click="gunModal=null"
                    class="px-3 py-1.5 text-xs border border-gray-300 bg-white rounded hover:bg-gray-100 text-gray-600 transition">
                    İptal
                </button>
                <button @click="gunModalKaydet" :disabled="gunModalYukleniyor"
                    class="px-4 py-1.5 text-xs bg-orange-500 hover:bg-orange-600 text-white rounded shadow-sm transition disabled:opacity-50 flex items-center gap-1">
                    <svg v-if="gunModalYukleniyor" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    {{ gunModalYukleniyor ? 'Kaydediliyor...' : 'Kaydet' }}
                </button>
            </div>
        </div>
    </div>
</template>
