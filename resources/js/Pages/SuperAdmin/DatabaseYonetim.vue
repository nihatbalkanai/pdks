<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({ localDb: Object });
const activeTab = ref('tablolar');
const loading = ref(false);

// Bağlantı
const db = reactive({
    db_host: props.localDb?.host || '127.0.0.1',
    db_port: 3306,
    database: props.localDb?.database || 'pdks',
    db_user: props.localDb?.username || 'root',
    db_pass: '',
    baglanti_tipi: 'local', // local veya remote
});

// Tablolar
const tablolar = ref([]);
const seciliTablo = ref(null);
const tabloYapisi = ref(null);
const tabloVerileri = ref(null);
const tabloSayfa = ref(1);

// SQL Sorgu
const sqlSorgu = ref('SELECT * FROM personeller LIMIT 10;');
const sorguSonuc = ref(null);
const sorguGecmisi = ref([]);

// Yeni Tablo
const yeniTablo = reactive({ tablo_adi: '', kolonlar: [{ ad: 'id', tip: 'BIGINT UNSIGNED AUTO_INCREMENT', nullable: false, auto_increment: true, varsayilan: '' }] });
// Yeni kolon
const yeniKolon = reactive({ kolon_adi: '', kolon_tipi: 'VARCHAR(255)', nullable: true, varsayilan: '', sonra: '' });

const dbParams = computed(() => {
    if (db.baglanti_tipi === 'local') return { database: db.database };
    return { db_host: db.db_host, db_port: db.db_port, database: db.database, db_user: db.db_user, db_pass: db.db_pass };
});

const baglantiTest = async () => {
    try {
        const res = await axios.post(route('super-admin.database.baglanti-test'), dbParams.value);
        Swal.fire(res.data.hata ? 'Hata' : 'Başarılı', res.data.mesaj, res.data.hata ? 'error' : 'success');
    } catch (e) { Swal.fire('Hata', e.response?.data?.mesaj || 'Bağlantı hatası', 'error'); }
};

const tablolariYukle = async () => {
    loading.value = true;
    try {
        const res = await axios.post(route('super-admin.database.tablolar'), dbParams.value);
        tablolar.value = res.data.tablolar;
    } catch (e) { Swal.fire('Hata', e.response?.data?.mesaj || 'Tablolar yüklenemedi', 'error'); }
    finally { loading.value = false; }
};

const tabloSec = async (tablo) => {
    seciliTablo.value = tablo;
    activeTab.value = 'yapi';
    loading.value = true;
    try {
        const [yapi, veri] = await Promise.all([
            axios.post(route('super-admin.database.tablo-yapisi'), { ...dbParams.value, tablo }),
            axios.post(route('super-admin.database.tablo-verileri'), { ...dbParams.value, tablo, sayfa: 1, limit: 50 }),
        ]);
        tabloYapisi.value = yapi.data;
        tabloVerileri.value = veri.data;
    } catch (e) { Swal.fire('Hata', e.response?.data?.mesaj || 'Veriler yüklenemedi', 'error'); }
    finally { loading.value = false; }
};

const sayfaDegistir = async (sayfa) => {
    tabloSayfa.value = sayfa;
    try {
        const res = await axios.post(route('super-admin.database.tablo-verileri'), { ...dbParams.value, tablo: seciliTablo.value, sayfa, limit: 50 });
        tabloVerileri.value = res.data;
    } catch(e) {}
};

const sorguCalistir = async () => {
    if (!sqlSorgu.value.trim()) return;

    // SELECT/SHOW/DESCRIBE/EXPLAIN dışındaki sorgular için şifre iste
    const isSelect = /^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i.test(sqlSorgu.value.trim());
    let adminSifre = null;

    if (!isSelect) {
        adminSifre = await sifreSor('Bu SQL sorgusu veritabanını değiştirecek.');
        if (adminSifre === null) return;
    }

    loading.value = true;
    try {
        const payload = { ...dbParams.value, sql: sqlSorgu.value };
        if (adminSifre) payload.admin_sifre = adminSifre;

        const res = await axios.post(route('super-admin.database.sorgu'), payload);
        sorguSonuc.value = res.data;
        sorguGecmisi.value.unshift({ sql: sqlSorgu.value, zaman: new Date().toLocaleTimeString('tr-TR'), basarili: !res.data.hata });
        if (sorguGecmisi.value.length > 20) sorguGecmisi.value.pop();
    } catch (e) {
        sorguSonuc.value = { hata: true, mesaj: e.response?.data?.mesaj || 'Sorgu hatası' };
    }
    finally { loading.value = false; }
};

const tabloOlustur = async () => {
    if (!yeniTablo.tablo_adi) { Swal.fire('Uyarı', 'Tablo adı girin', 'warning'); return; }
    try {
        const res = await axios.post(route('super-admin.database.tablo-olustur'), { ...dbParams.value, ...yeniTablo });
        Swal.fire('Başarılı', res.data.mesaj, 'success');
        tablolariYukle();
    } catch (e) { Swal.fire('Hata', e.response?.data?.mesaj || 'Tablo oluşturulamadı', 'error'); }
};

const kolonEkle = async () => {
    if (!yeniKolon.kolon_adi) return;
    try {
        const res = await axios.post(route('super-admin.database.kolon-ekle'), { ...dbParams.value, tablo: seciliTablo.value, ...yeniKolon });
        Swal.fire('Başarılı', res.data.mesaj, 'success');
        tabloSec(seciliTablo.value);
        yeniKolon.kolon_adi = ''; yeniKolon.kolon_tipi = 'VARCHAR(255)';
    } catch (e) { Swal.fire('Hata', e.response?.data?.mesaj || 'Kolon eklenemedi', 'error'); }
};

// 🔐 Şifre soran yardımcı fonksiyon
const sifreSor = async (aciklama) => {
    const r = await Swal.fire({
        title: '🔐 Super Admin Doğrulama',
        html: `<p class="text-sm text-gray-600 mb-2">${aciklama}</p><p class="text-xs text-red-500 font-bold">Bu işlem geri alınamaz! Devam etmek için şifrenizi girin.</p>`,
        input: 'password',
        inputPlaceholder: 'Super Admin şifreniz...',
        inputAttributes: { autocomplete: 'current-password' },
        showCancelButton: true,
        confirmButtonText: '🔓 Onayla ve Uygula',
        cancelButtonText: 'İptal',
        confirmButtonColor: '#EF4444',
        inputValidator: (value) => { if (!value) return 'Şifre girilmesi zorunludur!'; },
    });
    return r.isConfirmed ? r.value : null;
};

const kolonSil = async (kolon) => {
    const adminSifre = await sifreSor(`"<b>${kolon}</b>" kolonu ve tüm verileri silinecek.`);
    if (!adminSifre) return;
    try {
        await axios.post(route('super-admin.database.kolon-sil'), { ...dbParams.value, tablo: seciliTablo.value, kolon, admin_sifre: adminSifre });
        Swal.fire('Silindi', `"${kolon}" kolonu başarıyla silindi.`, 'success');
        tabloSec(seciliTablo.value);
    } catch (e) { Swal.fire('Hata', e.response?.data?.mesaj || 'Silinemedi', 'error'); }
};

const tabloTemizle = async (tablo) => {
    const adminSifre = await sifreSor(`"<b>${tablo}</b>" tablosundaki tüm veriler silinecek.`);
    if (!adminSifre) return;
    try {
        await axios.post(route('super-admin.database.tablo-temizle'), { ...dbParams.value, tablo, admin_sifre: adminSifre });
        Swal.fire('Temizlendi', `"${tablo}" tablosu başarıyla temizlendi.`, 'success');
    } catch (e) { Swal.fire('Hata', e.response?.data?.mesaj || 'Hata', 'error'); }
};

const kopyalaKolon = (k) => { yeniKolon.sonra = k.Field; };
const formatBoyut = (b) => { if (!b) return '0'; if (b > 1048576) return (b/1048576).toFixed(1)+'MB'; if (b > 1024) return (b/1024).toFixed(0)+'KB'; return b+'B'; };
const tipRenk = (tip) => { if (/int/i.test(tip)) return 'text-blue-600'; if (/varchar|text|char/i.test(tip)) return 'text-green-600'; if (/date|time/i.test(tip)) return 'text-orange-600'; if (/decimal|float|double/i.test(tip)) return 'text-purple-600'; return 'text-gray-600'; };
const kolonTipleri = ['BIGINT UNSIGNED','BIGINT','INT','SMALLINT','TINYINT','VARCHAR(255)','VARCHAR(100)','VARCHAR(50)','TEXT','MEDIUMTEXT','LONGTEXT','DATE','DATETIME','TIMESTAMP','DECIMAL(10,2)','DECIMAL(10,7)','FLOAT','DOUBLE','BOOLEAN','ENUM','JSON','BLOB'];

onMounted(tablolariYukle);
</script>

<template>
<Head title="Veritabanı Yönetimi" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <!-- Üst Navigasyon -->
        <div class="flex items-center gap-1 mb-2">
            <Link :href="route('super-admin.index')" class="px-3 py-1.5 rounded text-xs font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200 transition">🏠 Dashboard</Link>
            <Link :href="route('super-admin.deployment')" class="px-3 py-1.5 rounded text-xs font-semibold bg-gray-100 text-gray-600 hover:bg-violet-100 hover:text-violet-700 transition">🚀 Deployment</Link>
            <span class="px-3 py-1.5 rounded text-xs font-semibold bg-emerald-600 text-white">🗄️ Database</span>
        </div>
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-600 to-teal-700 px-5 py-3 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-white text-sm flex items-center gap-2">🗄️ Veritabanı Yönetimi</h2>
                    <p class="text-emerald-200 text-[10px] mt-0.5">{{ db.database }}@{{ db.baglanti_tipi === 'local' ? 'localhost' : db.db_host }}</p>
                </div>
                <div class="flex gap-2">
                    <select v-model="db.baglanti_tipi" @change="tablolariYukle" class="text-xs bg-white/20 text-white rounded border-white/30 h-7 px-2">
                        <option value="local" class="text-gray-800">🖥️ Local DB</option>
                        <option value="remote" class="text-gray-800">🌐 Sunucu DB</option>
                    </select>
                    <button v-for="t in [['tablolar','📋 Tablolar'],['yapi','🔧 Yapı'],['veri','📊 Veriler'],['sorgu','💻 SQL'],['olustur','➕ Oluştur']]" :key="t[0]" @click="activeTab=t[0]" :class="activeTab===t[0]?'bg-white text-emerald-700':'text-emerald-100 hover:bg-white/10'" class="px-3 py-1 rounded text-xs font-semibold transition">{{ t[1] }}</button>
                </div>
            </div>

            <!-- Remote Bağlantı Bilgileri -->
            <div v-if="db.baglanti_tipi === 'remote'" class="border-b border-gray-200 px-4 py-2 bg-amber-50 flex items-center gap-3">
                <input v-model="db.db_host" type="text" class="border-gray-300 rounded text-xs h-7 w-36 px-2" placeholder="Host" />
                <input v-model="db.db_port" type="number" class="border-gray-300 rounded text-xs h-7 w-16 px-2" placeholder="Port" />
                <input v-model="db.database" type="text" class="border-gray-300 rounded text-xs h-7 w-28 px-2" placeholder="Database" />
                <input v-model="db.db_user" type="text" class="border-gray-300 rounded text-xs h-7 w-24 px-2" placeholder="User" />
                <input v-model="db.db_pass" type="password" class="border-gray-300 rounded text-xs h-7 w-24 px-2" placeholder="Şifre" />
                <button @click="baglantiTest" class="bg-amber-500 text-white px-2 py-1 rounded text-xs font-bold hover:bg-amber-600">🔍 Test</button>
                <button @click="tablolariYukle" class="bg-emerald-500 text-white px-2 py-1 rounded text-xs font-bold hover:bg-emerald-600">🔗 Bağlan</button>
            </div>

            <!-- TAB: TABLOLAR -->
            <div v-show="activeTab==='tablolar'" class="flex-1 overflow-y-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase font-semibold sticky top-0 z-10">
                        <tr><th class="py-2 px-3 text-left">Tablo</th><th class="py-2 px-3 text-right">Satır</th><th class="py-2 px-3 text-right">Boyut</th><th class="py-2 px-3 text-center">Engine</th><th class="py-2 px-3 text-center">Collation</th><th class="py-2 px-3 text-center">Oluşturma</th><th class="py-2 px-3 text-right">İşlem</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in tablolar" :key="t.TABLE_NAME" class="border-t border-gray-100 hover:bg-emerald-50 transition cursor-pointer" @click="tabloSec(t.TABLE_NAME)">
                            <td class="py-1.5 px-3 font-bold text-gray-800">🗃️ {{ t.TABLE_NAME }}</td>
                            <td class="py-1.5 px-3 text-right font-mono text-gray-600">{{ (t.TABLE_ROWS || 0).toLocaleString() }}</td>
                            <td class="py-1.5 px-3 text-right text-gray-500">{{ formatBoyut((t.DATA_LENGTH||0) + (t.INDEX_LENGTH||0)) }}</td>
                            <td class="py-1.5 px-3 text-center"><span class="text-[10px] bg-blue-100 text-blue-700 px-1.5 rounded">{{ t.ENGINE }}</span></td>
                            <td class="py-1.5 px-3 text-center text-gray-400 text-[10px]">{{ t.TABLE_COLLATION }}</td>
                            <td class="py-1.5 px-3 text-center text-gray-400">{{ t.CREATE_TIME ? new Date(t.CREATE_TIME).toLocaleDateString('tr-TR') : '—' }}</td>
                            <td class="py-1.5 px-3 text-right"><button @click.stop="tabloTemizle(t.TABLE_NAME)" class="text-red-400 hover:text-red-600 text-[10px]">Temizle</button></td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!tablolar.length" class="py-12 text-center text-gray-400">{{ loading ? '⏳ Yükleniyor...' : 'Tablo bulunamadı' }}</div>
            </div>

            <!-- TAB: YAPI -->
            <div v-show="activeTab==='yapi'" class="flex-1 overflow-y-auto p-4">
                <div v-if="!seciliTablo" class="py-12 text-center text-gray-400">Tablolar sekmesinden bir tablo seçin</div>
                <div v-else>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-gray-800">🔧 {{ seciliTablo }} — Tablo Yapısı <span class="font-normal text-gray-400">({{ tabloYapisi?.satir_sayisi }} satır)</span></h3>
                    </div>
                    <table class="w-full text-xs mb-4">
                        <thead class="bg-gray-50 text-gray-500 uppercase font-semibold"><tr><th class="py-2 px-3 text-left">Kolon</th><th class="py-2 px-3 text-left">Tip</th><th class="py-2 px-3 text-center">Null</th><th class="py-2 px-3 text-center">Key</th><th class="py-2 px-3 text-left">Default</th><th class="py-2 px-3 text-left">Extra</th><th class="py-2 px-3 text-right">İşlem</th></tr></thead>
                        <tbody>
                            <tr v-for="k in tabloYapisi?.kolonlar" :key="k.Field" class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="py-1.5 px-3 font-bold text-gray-700">{{ k.Field }}</td>
                                <td class="py-1.5 px-3 font-mono text-xs" :class="tipRenk(k.Type)">{{ k.Type }}</td>
                                <td class="py-1.5 px-3 text-center">{{ k.Null === 'YES' ? '✅' : '❌' }}</td>
                                <td class="py-1.5 px-3 text-center"><span v-if="k.Key" :class="k.Key==='PRI'?'bg-yellow-100 text-yellow-700':'bg-gray-100 text-gray-600'" class="px-1.5 rounded text-[10px] font-bold">{{ k.Key }}</span></td>
                                <td class="py-1.5 px-3 text-gray-500">{{ k.Default ?? '—' }}</td>
                                <td class="py-1.5 px-3 text-gray-400">{{ k.Extra || '—' }}</td>
                                <td class="py-1.5 px-3 text-right"><button @click="kolonSil(k.Field)" class="text-red-400 hover:text-red-600 text-[10px]">Sil</button> <button @click="kopyalaKolon(k)" class="text-blue-400 hover:text-blue-600 text-[10px] ml-1">+ Sonrasına</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <!-- Kolon Ekle -->
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                        <h4 class="text-xs font-bold text-emerald-700 mb-2">➕ Kolon Ekle</h4>
                        <div class="flex items-end gap-2">
                            <div><label class="block text-[10px] text-gray-500 mb-0.5">Kolon Adı</label><input v-model="yeniKolon.kolon_adi" type="text" class="border-gray-300 rounded text-xs h-7 w-36 px-2" /></div>
                            <div><label class="block text-[10px] text-gray-500 mb-0.5">Tip</label><select v-model="yeniKolon.kolon_tipi" class="border-gray-300 rounded text-xs h-7"><option v-for="t in kolonTipleri" :key="t" :value="t">{{ t }}</option></select></div>
                            <div><label class="block text-[10px] text-gray-500 mb-0.5">Null</label><input type="checkbox" v-model="yeniKolon.nullable" class="rounded text-emerald-600" /></div>
                            <div><label class="block text-[10px] text-gray-500 mb-0.5">Varsayılan</label><input v-model="yeniKolon.varsayilan" type="text" class="border-gray-300 rounded text-xs h-7 w-24 px-2" /></div>
                            <div><label class="block text-[10px] text-gray-500 mb-0.5">Sonra</label><input v-model="yeniKolon.sonra" type="text" class="border-gray-300 rounded text-xs h-7 w-24 px-2" placeholder="kolon adı" /></div>
                            <button @click="kolonEkle" class="bg-emerald-600 text-white px-3 h-7 rounded text-xs font-bold hover:bg-emerald-700">Ekle</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: VERİ -->
            <div v-show="activeTab==='veri'" class="flex-1 overflow-auto">
                <div v-if="!seciliTablo" class="py-12 text-center text-gray-400">Tablolar sekmesinden bir tablo seçin</div>
                <div v-else-if="tabloVerileri">
                    <div class="flex items-center justify-between px-3 py-1 bg-gray-50 border-b sticky top-0 z-10">
                        <span class="text-xs text-gray-500">{{ seciliTablo }} — {{ tabloVerileri.toplam }} satır, Sayfa {{ tabloVerileri.sayfa }}/{{ tabloVerileri.toplam_sayfa }}</span>
                        <div class="flex gap-1">
                            <button v-for="s in Math.min(tabloVerileri.toplam_sayfa, 10)" :key="s" @click="sayfaDegistir(s)" :class="tabloVerileri.sayfa === s ? 'bg-emerald-600 text-white' : 'bg-gray-200'" class="px-2 py-0.5 rounded text-[10px] font-bold">{{ s }}</button>
                        </div>
                    </div>
                    <table class="w-full text-[10px] ">
                        <thead class="bg-gray-100 text-gray-500 uppercase font-semibold sticky top-7 z-10"><tr><th v-for="k in tabloVerileri.kolonlar" :key="k" class="py-1 px-2 text-left whitespace-nowrap">{{ k }}</th></tr></thead>
                        <tbody><tr v-for="(row,i) in tabloVerileri.veriler" :key="i" class="border-t border-gray-50 hover:bg-emerald-50"><td v-for="k in tabloVerileri.kolonlar" :key="k" class="py-1 px-2 max-w-[200px] truncate" :title="row[k]">{{ row[k] ?? 'NULL' }}</td></tr></tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: SQL -->
            <div v-show="activeTab==='sorgu'" class="flex-1 flex flex-col">
                <div class="p-3 border-b">
                    <textarea v-model="sqlSorgu" class="w-full border-gray-300 rounded font-mono text-xs h-24 p-2 bg-gray-900 text-green-400" placeholder="SQL sorgunuzu yazın..." @keydown.ctrl.enter="sorguCalistir"></textarea>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-[10px] text-gray-400">Ctrl+Enter ile çalıştırın | DROP DATABASE, TRUNCATE engellenmiştir</span>
                        <button @click="sorguCalistir" :disabled="loading" class="bg-emerald-600 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-emerald-700 disabled:opacity-40">▶️ Çalıştır</button>
                    </div>
                </div>
                <div class="flex-1 overflow-auto p-3">
                    <div v-if="sorguSonuc?.hata" class="bg-red-50 border border-red-200 rounded p-3 text-xs text-red-700">❌ {{ sorguSonuc.mesaj }}</div>
                    <div v-else-if="sorguSonuc?.tip === 'exec'" class="bg-green-50 border border-green-200 rounded p-3 text-xs text-green-700">✅ {{ sorguSonuc.mesaj }}</div>
                    <div v-else-if="sorguSonuc?.tip === 'select'">
                        <div class="text-xs text-gray-500 mb-2">{{ sorguSonuc.satir_sayisi }} satır döndü ({{ sorguSonuc.sure_ms }}ms)</div>
                        <table class="w-full text-[10px]">
                            <thead class="bg-gray-100 text-gray-500 uppercase font-semibold sticky top-0"><tr><th v-for="k in sorguSonuc.kolonlar" :key="k" class="py-1 px-2 text-left">{{ k }}</th></tr></thead>
                            <tbody><tr v-for="(row,i) in sorguSonuc.sonuclar" :key="i" class="border-t border-gray-50 hover:bg-emerald-50"><td v-for="k in sorguSonuc.kolonlar" :key="k" class="py-1 px-2 max-w-[200px] truncate">{{ row[k] ?? 'NULL' }}</td></tr></tbody>
                        </table>
                    </div>
                </div>
                <!-- Sorgu Geçmişi -->
                <div v-if="sorguGecmisi.length" class="border-t px-3 py-2 bg-gray-50 max-h-24 overflow-y-auto">
                    <div v-for="(g,i) in sorguGecmisi" :key="i" @click="sqlSorgu=g.sql" class="flex items-center gap-2 text-[10px] py-0.5 cursor-pointer hover:text-emerald-700">
                        <span :class="g.basarili ? 'text-green-500' : 'text-red-500'">{{ g.basarili ? '✅' : '❌' }}</span>
                        <span class="text-gray-400">{{ g.zaman }}</span>
                        <span class="font-mono text-gray-600 truncate">{{ g.sql }}</span>
                    </div>
                </div>
            </div>

            <!-- TAB: OLUŞTUR -->
            <div v-show="activeTab==='olustur'" class="flex-1 overflow-y-auto p-5">
                <div class="max-w-2xl mx-auto space-y-4">
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5">
                        <h3 class="text-sm font-bold text-emerald-700 mb-3">➕ Yeni Tablo Oluştur</h3>
                        <div class="mb-3"><label class="block text-xs font-semibold text-gray-600 mb-1">Tablo Adı</label><input v-model="yeniTablo.tablo_adi" type="text" class="border-gray-300 rounded text-sm w-full" placeholder="tablo_adi (snake_case)" /></div>
                        <h4 class="text-xs font-bold text-gray-600 mb-2">Kolonlar</h4>
                        <div v-for="(k,i) in yeniTablo.kolonlar" :key="i" class="flex items-center gap-2 mb-2">
                            <input v-model="k.ad" type="text" class="border-gray-300 rounded text-xs h-7 w-32 px-2" placeholder="Kolon adı" />
                            <select v-model="k.tip" class="border-gray-300 rounded text-xs h-7"><option v-for="t in kolonTipleri" :key="t" :value="t">{{ t }}</option></select>
                            <button @click="yeniTablo.kolonlar.splice(i,1)" class="text-red-400 hover:text-red-600 text-xs">✕</button>
                        </div>
                        <button @click="yeniTablo.kolonlar.push({ad:'',tip:'VARCHAR(255)',nullable:true,varsayilan:''})" class="text-emerald-600 text-xs font-bold hover:text-emerald-800">+ Kolon Ekle</button>
                        <div class="mt-4"><button @click="tabloOlustur" class="w-full py-2 bg-emerald-600 text-white rounded font-bold text-sm hover:bg-emerald-700">🏗️ Tablo Oluştur</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
