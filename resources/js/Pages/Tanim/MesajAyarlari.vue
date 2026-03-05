<script setup>
import { ref, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({ ayar: Object });

const form = reactive({
    sms_api_url: props.ayar?.sms_api_url || '',
    api_anahtari: '',
    sms_kullanici: props.ayar?.sms_kullanici || '',
    sms_sifre: '',
    sms_baslik: props.ayar?.sms_baslik || '',
    durum: props.ayar?.durum ?? true,
});

const testForm = reactive({ test_telefon: '' });
const showKeys = ref(false);

const smsServisleri = [
    { ad: 'NetGSM', url: 'https://api.netgsm.com.tr/sms/send/get', aciklama: 'Türkiye\'nin en yaygın SMS servislerinden' },
    { ad: 'İleti Merkezi', url: 'https://api.iletimerkezi.com/v1/send-sms/get/', aciklama: 'Toplu SMS gönderimi' },
    { ad: 'Mutlucell', url: 'https://smsgw.mutlucell.com/smsgw-ws/sndblkex', aciklama: 'Kurumsal SMS çözümü' },
    { ad: 'JetSMS', url: 'https://ws.jetsms.com.tr/api', aciklama: 'JetSMS API' },
];

const secServis = (s) => { form.sms_api_url = s.url; };

const save = () => {
    axios.post(route('tanim.mesaj-ayarlari.kaydet'), { ...form }).catch(e => Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu', 'error'));
};

const sendTest = () => {
    if (!testForm.test_telefon) { Swal.fire('Uyarı', 'Test telefon numarası girin.', 'warning'); return; }
    testForm.post(route('tanim.mesaj-test'), {
        onSuccess: () => Swal.fire('Başarılı!', `Test mesajı ${testForm.test_telefon} numarasına gönderildi.`, 'success'),
        onError: () => Swal.fire('Hata', 'Test gönderimi başarısız.', 'error'),
    });
};
</script>

<template>
<Head title="Mesaj (SMS) Ayarları" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                <h2 class="font-bold text-sm text-gray-800">Mesaj (SMS) Ayarları</h2>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                <div class="max-w-2xl">
                    <!-- Durum Toggle -->
                    <div class="flex items-center justify-between mb-5 bg-gray-50 rounded border border-gray-200 px-4 py-3">
                        <div>
                            <div class="text-sm font-bold text-gray-800">SMS Gönderimi</div>
                            <div class="text-[10px] text-gray-500">Sistem üzerinden SMS gönderilmesini etkinleştirin</div>
                        </div>
                        <button @click="form.durum = !form.durum" type="button"
                            :class="form.durum ? 'bg-green-500' : 'bg-gray-300'"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200">
                            <span :class="form.durum ? 'translate-x-5' : 'translate-x-0'"
                                class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow border border-gray-200 transition duration-200"></span>
                        </button>
                    </div>

                    <!-- SMS Servisi Seçimi -->
                    <div class="border border-green-200 rounded mb-4 bg-green-50">
                        <div class="px-4 py-2 border-b border-green-200 text-xs font-bold text-green-700 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Hızlı SMS Servisi Seçimi
                        </div>
                        <div class="p-3 grid grid-cols-2 gap-2">
                            <button v-for="s in smsServisleri" :key="s.ad" @click="secServis(s)" type="button" class="bg-white border border-gray-300 rounded px-3 py-2 text-left hover:bg-green-100 transition">
                                <div class="text-xs font-bold">📱 {{ s.ad }}</div>
                                <div class="text-[9px] text-gray-500">{{ s.aciklama }}</div>
                            </button>
                        </div>
                    </div>

                    <!-- API Bilgileri -->
                    <div class="border border-gray-200 rounded mb-4">
                        <div class="bg-gray-100 px-4 py-2 border-b border-gray-200 text-xs font-bold text-gray-700 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                            API Bağlantı Bilgileri
                        </div>
                        <div class="p-4 space-y-3">
                            <div>
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">API URL:</label>
                                <input v-model="form.sms_api_url" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs font-mono" placeholder="https://api.smsservisi.com/send" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-semibold text-gray-600 mb-1 block">API Key / Kullanıcı Adı:</label>
                                    <input v-model="form.sms_kullanici" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs" placeholder="Kullanıcı adı veya API key" />
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-600 mb-1 block">API Secret / Şifre:</label>
                                    <div class="relative">
                                        <input v-model="form.sms_sifre" :type="showKeys ? 'text' : 'password'" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs pr-8" placeholder="••••••••" />
                                        <button @click="showKeys = !showKeys" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="showKeys ? 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21' : 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-semibold text-gray-600 mb-1 block">API Key (Opsiyonel):</label>
                                    <input v-model="form.api_anahtari" :type="showKeys ? 'text' : 'password'" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs" placeholder="Varsa API key" />
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-600 mb-1 block">SMS Başlığı (Gönderici Adı):</label>
                                    <input v-model="form.sms_baslik" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs" placeholder="SIRKETADI" />
                                    <div class="text-[9px] text-gray-400 mt-0.5">BTK onaylı başlık kullanın</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Test Gönderimi -->
                    <div class="border border-orange-200 rounded bg-orange-50">
                        <div class="px-4 py-2 border-b border-orange-200 text-xs font-bold text-orange-700 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                            Test SMS Gönderimi
                        </div>
                        <div class="p-3 flex gap-2 items-end">
                            <div class="flex-1">
                                <label class="text-[10px] font-semibold text-gray-600 mb-1 block">Test Telefon Numarası:</label>
                                <input v-model="testForm.test_telefon" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs" placeholder="05XX XXX XX XX" />
                            </div>
                            <button @click="sendTest" class="bg-orange-500 text-white px-4 py-1.5 rounded text-xs font-semibold hover:bg-orange-600 whitespace-nowrap">Test Gönder</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 px-4 py-2 bg-gray-100 border-t border-gray-400">
                <button @click="save" :disabled="false" class="flex items-center bg-green-600 text-white border border-green-700 rounded-sm px-5 py-1.5 text-xs hover:bg-green-700 shadow-sm font-semibold disabled:opacity-50">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Kaydet
                </button>
                <div class="ml-auto text-[10px] text-gray-400">Son güncelleme: {{ ayar?.updated_at ? new Date(ayar.updated_at).toLocaleString('tr-TR') : '-' }}</div>
            </div>
        </div>
    </div>
</AuthenticatedLayout>
</template>
