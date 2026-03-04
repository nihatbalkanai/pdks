<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({ bildirimler: Array });

const showForm = ref(false);
const editMode = ref(false);
const editId = ref(null);

const form = useForm({
    ad: '',
    tip: 'maas_gunu',
    kanal: 'her_ikisi',
    konu: '',
    mesaj_sablonu: '',
    gun: 1,
    saat: '09:00',
    ozel_tarih: '',
    aktif: true,
});

const tipLabels = { maas_gunu: '💰 Maaş Günü', dogum_gunu: '🎂 Doğum Günü', bayram: '🎉 Bayram', ozel_tarih: '📅 Özel Tarih', genel: '📋 Genel' };
const kanalLabels = { sms: '📱 SMS', email: '📧 E-Posta', her_ikisi: '📱+📧 Her İkisi' };

const hazirSablonlar = [
    { ad: 'Maaş Günü Bildirimi', tip: 'maas_gunu', kanal: 'her_ikisi', gun: 1, konu: 'Maaş Bordronuz Hazır', mesaj_sablonu: 'Sayın {ad} {soyad},\n\nMaaş bordronuz hazırlanmıştır. Detaylar için muhasebe departmanı ile iletişime geçebilirsiniz.\n\nSaygılarımızla' },
    { ad: 'Doğum Günü Tebriği', tip: 'dogum_gunu', kanal: 'her_ikisi', konu: 'Doğum Gününüz Kutlu Olsun! 🎂', mesaj_sablonu: 'Sayın {ad} {soyad},\n\nDoğum gününüzü en içten dileklerimizle kutlarız. Nice mutlu yıllara!\n\nSaygılarımızla' },
    { ad: 'Ramazan Bayramı', tip: 'bayram', kanal: 'her_ikisi', ozel_tarih: '', konu: 'Ramazan Bayramınız Mübarek Olsun 🌙', mesaj_sablonu: 'Sayın {ad} {soyad},\n\nRamazan Bayramınızı en içten dileklerimizle kutlar, sağlık ve huzur dolu nice bayramlar dileriz.\n\nSaygılarımızla' },
    { ad: 'Kurban Bayramı', tip: 'bayram', kanal: 'her_ikisi', ozel_tarih: '', konu: 'Kurban Bayramınız Mübarek Olsun 🐑', mesaj_sablonu: 'Sayın {ad} {soyad},\n\nKurban Bayramınızı en içten dileklerimizle kutlar, sağlık ve mutluluk dolu nice bayramlar dileriz.\n\nSaygılarımızla' },
    { ad: 'Yılbaşı Tebriği', tip: 'ozel_tarih', kanal: 'email', ozel_tarih: '', konu: 'Mutlu Yıllar! 🎄', mesaj_sablonu: 'Sayın {ad} {soyad},\n\nYeni yılınız kutlu olsun! Sağlık, başarı ve mutluluk dolu bir yıl geçirmenizi diliyoruz.\n\nSaygılarımızla' },
];

const sablonKullan = (s) => {
    form.ad = s.ad;
    form.tip = s.tip;
    form.kanal = s.kanal;
    form.konu = s.konu || '';
    form.mesaj_sablonu = s.mesaj_sablonu;
    form.gun = s.gun || 1;
    form.ozel_tarih = s.ozel_tarih || '';
    showForm.value = true;
    editMode.value = false;
};

const resetForm = () => {
    form.reset();
    editMode.value = false;
    editId.value = null;
};

const openNew = () => { resetForm(); showForm.value = true; };

const editBildirim = (b) => {
    form.ad = b.ad;
    form.tip = b.tip;
    form.kanal = b.kanal;
    form.konu = b.konu || '';
    form.mesaj_sablonu = b.mesaj_sablonu;
    form.gun = b.gun;
    form.saat = b.saat ? b.saat.substring(0,5) : '09:00';
    form.ozel_tarih = b.ozel_tarih || '';
    form.aktif = b.aktif;
    editMode.value = true;
    editId.value = b.id;
    showForm.value = true;
};

const save = () => {
    if (!form.ad || !form.mesaj_sablonu) { Swal.fire('Uyarı', 'Ad ve mesaj şablonu zorunludur.', 'warning'); return; }
    if (editMode.value) {
        form.put(route('toplu-islemler.zamanlanmis-bildirimler.update', editId.value), {
            onSuccess: () => { showForm.value = false; resetForm(); Swal.fire('Başarılı!', 'Bildirim güncellendi.', 'success'); }
        });
    } else {
        form.post(route('toplu-islemler.zamanlanmis-bildirimler.store'), {
            onSuccess: () => { showForm.value = false; resetForm(); Swal.fire('Başarılı!', 'Bildirim oluşturuldu.', 'success'); }
        });
    }
};

const toggleAktif = (id) => {
    router.patch(route('toplu-islemler.zamanlanmis-bildirimler.toggle', id));
};

const sil = (id) => {
    Swal.fire({ title: 'Emin misiniz?', text: 'Bu zamanlanmış bildirim silinecek.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sil', cancelButtonText: 'İptal' })
    .then(r => { if (r.isConfirmed) router.delete(route('toplu-islemler.zamanlanmis-bildirimler.destroy', id)); });
};
</script>

<template>
<Head title="Zamanlanmış Bildirimler" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h2 class="font-bold text-sm text-gray-800">Zamanlanmış Bildirimler (Cron Görevleri)</h2>
                </div>
                <button @click="openNew" class="bg-green-600 text-white px-3 py-1 rounded text-xs font-semibold hover:bg-green-700 flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Yeni Görev Ekle
                </button>
            </div>

            <div class="flex flex-1 overflow-hidden">
                <!-- SOL: Hazır Şablonlar -->
                <div class="w-52 border-r border-gray-400 flex flex-col bg-gray-50">
                    <div class="px-3 py-2 border-b border-gray-300 text-xs font-bold text-gray-600">📋 Hazır Şablonlar</div>
                    <div class="flex-1 overflow-y-auto">
                        <button v-for="(s, i) in hazirSablonlar" :key="i" @click="sablonKullan(s)" class="w-full text-left px-3 py-2 text-xs border-b border-gray-100 hover:bg-blue-50 transition">
                            <div class="font-semibold text-gray-800">{{ s.ad }}</div>
                            <div class="text-[10px] text-gray-500 mt-0.5">{{ tipLabels[s.tip] }} · {{ kanalLabels[s.kanal] }}</div>
                        </button>
                    </div>
                </div>

                <!-- SAĞ: Mevcut Görevler & Form -->
                <div class="flex-1 overflow-y-auto">
                    <!-- Form -->
                    <div v-if="showForm" class="p-4 bg-yellow-50 border-b border-yellow-200">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-bold text-gray-800">{{ editMode ? '✏️ Bildirimi Düzenle' : '➕ Yeni Zamanlanmış Bildirim' }}</h3>
                            <button @click="showForm = false; resetForm()" class="text-gray-400 hover:text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-xs">
                            <div><label class="font-semibold">Görev Adı:</label><input v-model="form.ad" class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs mt-1" /></div>
                            <div>
                                <label class="font-semibold">Tip:</label>
                                <select v-model="form.tip" class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs mt-1">
                                    <option value="maas_gunu">💰 Maaş Günü</option>
                                    <option value="dogum_gunu">🎂 Doğum Günü</option>
                                    <option value="bayram">🎉 Bayram</option>
                                    <option value="ozel_tarih">📅 Özel Tarih</option>
                                    <option value="genel">📋 Genel</option>
                                </select>
                            </div>
                            <div>
                                <label class="font-semibold">Kanal:</label>
                                <select v-model="form.kanal" class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs mt-1">
                                    <option value="sms">📱 Sadece SMS</option>
                                    <option value="email">📧 Sadece E-Posta</option>
                                    <option value="her_ikisi">📱+📧 Her İkisi</option>
                                </select>
                            </div>
                            <div v-if="form.tip === 'maas_gunu' || form.tip === 'genel'"><label class="font-semibold">Ayın Günü:</label><input type="number" v-model="form.gun" min="1" max="31" class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs mt-1" /></div>
                            <div><label class="font-semibold">Saat:</label><input type="time" v-model="form.saat" class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs mt-1" /></div>
                            <div v-if="form.tip === 'bayram' || form.tip === 'ozel_tarih'"><label class="font-semibold">Tarih:</label><input type="date" v-model="form.ozel_tarih" class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs mt-1" /></div>
                            <div class="col-span-3"><label class="font-semibold">Konu (E-Posta):</label><input v-model="form.konu" class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs mt-1" placeholder="E-posta konusu" /></div>
                            <div class="col-span-3">
                                <label class="font-semibold">Mesaj Şablonu:</label>
                                <textarea v-model="form.mesaj_sablonu" rows="4" class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs mt-1" placeholder="{ad}, {soyad}, {kart_no}, {sirket}, {departman} değişkenleri kullanılabilir"></textarea>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button @click="save" class="bg-green-600 text-white px-4 py-1.5 rounded text-xs font-semibold hover:bg-green-700">{{ editMode ? 'Güncelle' : 'Kaydet' }}</button>
                            <button @click="showForm = false; resetForm()" class="bg-gray-300 text-gray-700 px-4 py-1.5 rounded text-xs hover:bg-gray-400">İptal</button>
                        </div>
                    </div>

                    <!-- Mevcut Görevler Tablosu -->
                    <table class="w-full text-xs border-collapse">
                        <thead class="bg-[#d0dcea] sticky top-0">
                            <tr>
                                <th class="py-1.5 px-2 text-left border border-gray-300 font-bold">Durum</th>
                                <th class="py-1.5 px-2 text-left border border-gray-300 font-bold">Görev Adı</th>
                                <th class="py-1.5 px-2 text-center border border-gray-300 font-bold">Tip</th>
                                <th class="py-1.5 px-2 text-center border border-gray-300 font-bold">Kanal</th>
                                <th class="py-1.5 px-2 text-center border border-gray-300 font-bold">Zamanlama</th>
                                <th class="py-1.5 px-2 text-center border border-gray-300 font-bold">Son Çalışma</th>
                                <th class="py-1.5 px-2 text-center border border-gray-300 font-bold">Gönderim</th>
                                <th class="py-1.5 px-2 text-center border border-gray-300 font-bold w-24">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="b in bildirimler" :key="b.id" class="border-b border-gray-200 hover:bg-blue-50 transition">
                                <td class="py-1 px-2 border-r border-gray-200 text-center">
                                    <button @click="toggleAktif(b.id)" :class="b.aktif ? 'bg-green-500' : 'bg-gray-300'" class="relative inline-flex h-4 w-7 shrink-0 cursor-pointer rounded-full transition-colors">
                                        <span :class="b.aktif ? 'translate-x-3' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow border border-gray-200 transition"></span>
                                    </button>
                                </td>
                                <td class="py-1 px-2 border-r border-gray-200 font-medium">{{ b.ad }}</td>
                                <td class="py-1 px-2 border-r border-gray-200 text-center">{{ tipLabels[b.tip] || b.tip }}</td>
                                <td class="py-1 px-2 border-r border-gray-200 text-center">{{ kanalLabels[b.kanal] || b.kanal }}</td>
                                <td class="py-1 px-2 border-r border-gray-200 text-center text-[10px]">
                                    <span v-if="b.tip === 'maas_gunu'">Her ayın {{ b.gun }}. günü {{ b.saat?.substring(0,5) }}</span>
                                    <span v-else-if="b.tip === 'dogum_gunu'">Her gün {{ b.saat?.substring(0,5) }}</span>
                                    <span v-else-if="b.ozel_tarih">{{ b.ozel_tarih }} - {{ b.saat?.substring(0,5) }}</span>
                                    <span v-else>{{ b.saat?.substring(0,5) }}</span>
                                </td>
                                <td class="py-1 px-2 border-r border-gray-200 text-center text-[10px]">{{ b.son_calisma ? new Date(b.son_calisma).toLocaleDateString('tr-TR') : '-' }}</td>
                                <td class="py-1 px-2 border-r border-gray-200 text-center font-bold text-blue-600">{{ b.toplam_gonderim }}</td>
                                <td class="py-1 px-2 text-center">
                                    <button @click="editBildirim(b)" class="text-blue-600 hover:text-blue-800 mr-1" title="Düzenle"><svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                    <button @click="sil(b.id)" class="text-red-500 hover:text-red-700" title="Sil"><svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                </td>
                            </tr>
                            <tr v-if="!bildirimler || bildirimler.length === 0">
                                <td colspan="8" class="py-10 text-center text-gray-400">Henüz zamanlanmış bildirim yok. Yeni ekleyin veya soldaki şablonlardan birini kullanın.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="px-4 py-2 bg-gray-100 border-t border-gray-400 text-[10px] text-gray-500 flex items-center">
                <svg class="w-3.5 h-3.5 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Cron görevleri <code class="bg-gray-200 px-1 py-0.5 rounded mx-1">php artisan schedule:run</code> komutu ile her dakika çalışır. Sunucuda cron ayarlayın: <code class="bg-gray-200 px-1 py-0.5 rounded mx-1">* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1</code>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
