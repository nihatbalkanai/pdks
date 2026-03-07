<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({ ayarlar: Object });
const activeTab = ref('dosyalar');
const loading = ref(false);
const dosyalar = ref([]);
const seciliDosyalar = ref([]);
const yuklemeSonuclari = ref([]);
const yuklemeLog = ref([]);
const filterText = ref('');
const filterDurum = ref('tumu');

const ftp = reactive({
    host: props.ayarlar?.ftp_host || '',
    user: props.ayarlar?.ftp_user || '',
    pass: '',
    port: props.ayarlar?.ftp_port || 21,
    root: props.ayarlar?.ftp_root || '/public_html',
    protokol: props.ayarlar?.protokol || 'ftp',
});

const baglantiDurumu = ref(null); // null, 'basarili', 'hata'

const filtrelenmis = computed(() => {
    let list = dosyalar.value;
    if (filterText.value) {
        list = list.filter(d => d.dosya.toLowerCase().includes(filterText.value.toLowerCase()));
    }
    if (filterDurum.value !== 'tumu') {
        list = list.filter(d => d.durum === filterDurum.value);
    }
    return list;
});

const tumunuSec = computed({
    get: () => filtrelenmis.value.length > 0 && filtrelenmis.value.every(d => seciliDosyalar.value.includes(d.dosya)),
    set: (val) => {
        if (val) {
            filtrelenmis.value.forEach(d => { if (!seciliDosyalar.value.includes(d.dosya)) seciliDosyalar.value.push(d.dosya); });
        } else {
            seciliDosyalar.value = seciliDosyalar.value.filter(s => !filtrelenmis.value.some(d => d.dosya === s));
        }
    }
});

const dosyaTipi = (dosya) => {
    const ext = dosya.split('.').pop()?.toLowerCase();
    const map = { php: '🐘', vue: '💚', js: '🟡', ts: '🔵', css: '🎨', json: '📋', sql: '🗄️', md: '📝', env: '🔐' };
    return map[ext] || '📄';
};

const durumRenk = { degistirildi: 'bg-amber-100 text-amber-700', eklendi: 'bg-green-100 text-green-700', silindi: 'bg-red-100 text-red-700', yeni: 'bg-blue-100 text-blue-700', diger: 'bg-gray-100 text-gray-700' };
const durumLabel = { degistirildi: '✏️ Değiştirildi', eklendi: '➕ Eklendi', silindi: '🗑️ Silindi', yeni: '🆕 Yeni', diger: '❓ Diğer' };

const dosyalariYukle = async () => {
    loading.value = true;
    try {
        const res = await axios.get(route('super-admin.deployment.dosyalar'));
        dosyalar.value = res.data.dosyalar;
    } catch (err) {
        Swal.fire('Hata', 'Dosya listesi alınamadı', 'error');
    } finally {
        loading.value = false;
    }
};

const baglantiTest = async () => {
    if (!ftp.host || !ftp.user || !ftp.pass) { Swal.fire('Uyarı', 'Bağlantı bilgilerini doldurun', 'warning'); return; }
    baglantiDurumu.value = null;
    try {
        const res = await axios.post(route('super-admin.deployment.test'), { host: ftp.host, user: ftp.user, pass: ftp.pass, port: ftp.port });
        baglantiDurumu.value = res.data.hata ? 'hata' : 'basarili';
        Swal.fire(res.data.hata ? 'Bağlantı Hatası' : 'Bağlantı Başarılı', res.data.mesaj, res.data.hata ? 'error' : 'success');
    } catch (err) {
        baglantiDurumu.value = 'hata';
        Swal.fire('Hata', err.response?.data?.mesaj || 'Bağlantı kurulamadı', 'error');
    }
};

const sunucuyaYukle = async () => {
    if (!seciliDosyalar.value.length) { Swal.fire('Uyarı', 'Yüklenecek dosya seçin', 'warning'); return; }
    if (!ftp.host || !ftp.user || !ftp.pass) { Swal.fire('Uyarı', 'FTP bağlantı bilgilerini doldurun', 'warning'); return; }

    const r = await Swal.fire({
        title: '🚀 Sunucuya Yükle',
        html: `<b>${seciliDosyalar.value.length}</b> dosya <b>${ftp.host}</b> sunucusuna yüklenecek.<br><br><small class="text-red-500">Bu işlem sunucudaki mevcut dosyaları üzerine yazacaktır!</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yükle',
        cancelButtonText: 'İptal',
        confirmButtonColor: '#7C3AED',
    });
    if (!r.isConfirmed) return;

    loading.value = true;
    activeTab.value = 'log';
    yuklemeSonuclari.value = [];
    yuklemeLog.value = ['🔗 Sunucuya bağlanılıyor...'];

    try {
        const res = await axios.post(route('super-admin.deployment.yukle'), {
            dosyalar: seciliDosyalar.value,
            host: ftp.host, user: ftp.user, pass: ftp.pass, port: ftp.port, root: ftp.root, protokol: ftp.protokol,
        });

        yuklemeSonuclari.value = res.data.sonuclar || [];
        res.data.sonuclar?.forEach(s => {
            yuklemeLog.value.push(s.durum === 'basarili' ? `✅ ${s.dosya}` : `❌ ${s.dosya} — ${s.mesaj || 'Hata'}`);
        });
        yuklemeLog.value.push(`\n📊 Sonuç: ${res.data.basarili} başarılı, ${res.data.basarisiz} başarısız`);

        Swal.fire(res.data.basarisiz > 0 ? 'Kısmen Başarılı' : 'Başarılı', res.data.mesaj, res.data.basarisiz > 0 ? 'warning' : 'success');
        seciliDosyalar.value = [];
    } catch (err) {
        yuklemeLog.value.push(`❌ Sunucu hatası: ${err.response?.data?.mesaj || 'Bilinmeyen hata'}`);
        Swal.fire('Hata', err.response?.data?.mesaj || 'Yükleme başarısız', 'error');
    } finally {
        loading.value = false;
    }
};

const hizliSecim = (tip) => {
    seciliDosyalar.value = [];
    const patterns = {
        php: d => d.dosya.endsWith('.php'),
        vue: d => d.dosya.endsWith('.vue'),
        config: d => d.dosya.includes('config/') || d.dosya.includes('.env') || d.dosya.includes('routes/'),
        build: d => d.dosya.startsWith('public/build/'),
    };
    dosyalar.value.filter(patterns[tip] || (() => false)).forEach(d => seciliDosyalar.value.push(d.dosya));
};

onMounted(dosyalariYukle);
</script>

<template>
<Head title="Deployment — Sunucu Yönetimi" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <!-- Üst Navigasyon -->
        <div class="flex items-center gap-1 mb-2">
            <Link :href="route('super-admin.index')" class="px-3 py-1.5 rounded text-xs font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200 transition">🏠 Dashboard</Link>
            <span class="px-3 py-1.5 rounded text-xs font-semibold bg-violet-600 text-white">🚀 Deployment</span>
            <Link :href="route('super-admin.database')" class="px-3 py-1.5 rounded text-xs font-semibold bg-gray-100 text-gray-600 hover:bg-emerald-100 hover:text-emerald-700 transition">🗄️ Database</Link>
        </div>
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <!-- Header -->
            <div class="bg-gradient-to-r from-violet-600 to-purple-700 px-5 py-3 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-white text-sm flex items-center gap-2">🚀 Deployment — Sunucu Dosya Yönetimi</h2>
                    <p class="text-violet-200 text-[10px] mt-0.5">Local değişiklikleri sunucuya yükleyin</p>
                </div>
                <div class="flex gap-2">
                    <button v-for="t in [['dosyalar','📁 Dosyalar'],['baglanti','🔗 Bağlantı'],['log','📋 Log']]" :key="t[0]" @click="activeTab = t[0]" :class="activeTab === t[0] ? 'bg-white text-violet-700' : 'text-violet-100 hover:bg-white/10'" class="px-3 py-1 rounded text-xs font-semibold transition">{{ t[1] }}</button>
                </div>
            </div>

            <!-- TAB: DOSYALAR -->
            <div v-show="activeTab === 'dosyalar'" class="flex-1 overflow-hidden flex flex-col">
                <!-- Toolbar -->
                <div class="border-b border-gray-200 px-4 py-2 flex items-center justify-between gap-3 bg-gray-50">
                    <div class="flex items-center gap-2 flex-1">
                        <input v-model="filterText" type="text" class="border-gray-300 rounded text-xs w-48 h-7 px-2" placeholder="🔍 Dosya ara..." />
                        <select v-model="filterDurum" class="border-gray-300 rounded text-xs h-7">
                            <option value="tumu">Tümü</option>
                            <option value="degistirildi">Değiştirildi</option>
                            <option value="eklendi">Eklendi</option>
                            <option value="yeni">Yeni</option>
                            <option value="silindi">Silindi</option>
                        </select>
                        <button @click="dosyalariYukle" class="text-xs bg-gray-200 px-2 py-1 rounded hover:bg-gray-300">🔄 Yenile</button>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Hızlı:</span>
                        <button @click="hizliSecim('php')" class="text-[10px] bg-purple-100 text-purple-700 px-2 py-0.5 rounded font-bold hover:bg-purple-200">🐘 PHP</button>
                        <button @click="hizliSecim('vue')" class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded font-bold hover:bg-green-200">💚 Vue</button>
                        <button @click="hizliSecim('config')" class="text-[10px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded font-bold hover:bg-amber-200">⚙️ Config</button>
                        <button @click="hizliSecim('build')" class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-bold hover:bg-blue-200">📦 Build</button>
                    </div>
                </div>

                <!-- Tablo -->
                <div class="flex-1 overflow-y-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 text-gray-500 uppercase font-semibold sticky top-0 z-10">
                            <tr>
                                <th class="py-2 px-3 text-left w-8"><input type="checkbox" v-model="tumunuSec" class="rounded text-violet-600 w-4 h-4" /></th>
                                <th class="py-2 px-3 text-left">Dosya Yolu</th>
                                <th class="py-2 px-3 text-center w-28">Durum</th>
                                <th class="py-2 px-3 text-center w-20">Boyut</th>
                                <th class="py-2 px-3 text-center w-24">Kaynak</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in filtrelenmis" :key="d.dosya" class="border-t border-gray-100 hover:bg-violet-50 transition cursor-pointer" @click="seciliDosyalar.includes(d.dosya) ? seciliDosyalar.splice(seciliDosyalar.indexOf(d.dosya),1) : seciliDosyalar.push(d.dosya)">
                                <td class="py-1.5 px-3"><input type="checkbox" :checked="seciliDosyalar.includes(d.dosya)" class="rounded text-violet-600 w-4 h-4" @click.stop /></td>
                                <td class="py-1.5 px-3 font-mono text-gray-700">
                                    <span class="mr-1">{{ dosyaTipi(d.dosya) }}</span>
                                    <span :class="seciliDosyalar.includes(d.dosya) ? 'text-violet-700 font-bold' : ''">{{ d.dosya }}</span>
                                </td>
                                <td class="py-1.5 px-3 text-center"><span :class="durumRenk[d.durum]" class="px-2 py-0.5 rounded text-[10px] font-bold">{{ durumLabel[d.durum] }}</span></td>
                                <td class="py-1.5 px-3 text-center text-gray-500">{{ d.boyut_str }}</td>
                                <td class="py-1.5 px-3 text-center"><span class="text-[10px] text-gray-400">{{ d.kaynak }}</span></td>
                            </tr>
                            <tr v-if="!filtrelenmis.length"><td colspan="5" class="py-8 text-center text-gray-400">{{ loading ? '⏳ Yükleniyor...' : 'Değişen dosya yok' }}</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Alt Bar -->
                <div class="border-t border-gray-200 px-4 py-2 bg-gray-50 flex items-center justify-between">
                    <span class="text-xs text-gray-500">{{ seciliDosyalar.length }} / {{ dosyalar.length }} dosya seçili</span>
                    <button @click="sunucuyaYukle" :disabled="!seciliDosyalar.length || loading" class="px-5 py-1.5 bg-violet-600 text-white rounded text-xs font-bold hover:bg-violet-700 disabled:opacity-40 transition flex items-center gap-1">
                        <span v-if="loading">⏳ Yükleniyor...</span>
                        <span v-else>🚀 Seçilenleri Sunucuya Yükle ({{ seciliDosyalar.length }})</span>
                    </button>
                </div>
            </div>

            <!-- TAB: BAĞLANTI -->
            <div v-show="activeTab === 'baglanti'" class="flex-1 overflow-y-auto p-5">
                <div class="max-w-xl mx-auto space-y-5">
                    <div class="bg-violet-50 border border-violet-200 rounded-xl p-5">
                        <h3 class="text-sm font-bold text-violet-700 mb-3">🔗 Sunucu Bağlantı Ayarları</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Protokol</label>
                                <select v-model="ftp.protokol" class="w-full border-gray-300 rounded text-sm">
                                    <option value="ftp">FTP</option>
                                    <option value="sftp">SFTP (SSH)</option>
                                </select>
                            </div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Host / IP Adresi</label><input v-model="ftp.host" type="text" class="w-full border-gray-300 rounded text-sm" placeholder="ftp.domain.com" /></div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Port</label><input v-model="ftp.port" type="number" class="w-full border-gray-300 rounded text-sm" /></div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Kullanıcı Adı</label><input v-model="ftp.user" type="text" class="w-full border-gray-300 rounded text-sm" /></div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Şifre</label><input v-model="ftp.pass" type="password" class="w-full border-gray-300 rounded text-sm" /></div>
                            <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">Uzak Dizin (Root Path)</label><input v-model="ftp.root" type="text" class="w-full border-gray-300 rounded text-sm" placeholder="/public_html" /></div>
                        </div>
                        <div class="flex gap-2 mt-4">
                            <button @click="baglantiTest" class="px-4 py-2 bg-violet-600 text-white rounded text-xs font-bold hover:bg-violet-700">🔍 Bağlantıyı Test Et</button>
                            <span v-if="baglantiDurumu === 'basarili'" class="text-green-600 text-xs font-bold self-center">✅ Bağlantı başarılı</span>
                            <span v-if="baglantiDurumu === 'hata'" class="text-red-600 text-xs font-bold self-center">❌ Bağlantı hatası</span>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <h4 class="text-xs font-bold text-amber-700 mb-2">⚠️ Güvenlik Notu</h4>
                        <ul class="text-[10px] text-amber-600 space-y-1">
                            <li>• FTP şifresi tarayıcıda saklanmaz, her oturum girmeniz gerekir</li>
                            <li>• SFTP (SSH) bağlantısı FTP'ye göre daha güvenlidir</li>
                            <li>• Root dizini doğru ayarladığınızdan emin olun</li>
                            <li>• Yükleme sunucudaki dosyaların üzerine yazacaktır</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- TAB: LOG -->
            <div v-show="activeTab === 'log'" class="flex-1 overflow-y-auto p-4">
                <div class="bg-gray-900 rounded-xl p-4 h-full overflow-y-auto font-mono text-xs">
                    <div v-for="(log, i) in yuklemeLog" :key="i" class="py-0.5" :class="log.startsWith('✅') ? 'text-green-400' : log.startsWith('❌') ? 'text-red-400' : log.startsWith('📊') ? 'text-yellow-300 font-bold mt-2' : 'text-gray-400'">{{ log }}</div>
                    <div v-if="!yuklemeLog.length" class="text-gray-600 text-center py-12">Henüz yükleme yapılmadı</div>
                </div>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
