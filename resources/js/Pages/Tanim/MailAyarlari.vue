<script setup>
import { ref, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({ ayar: Object });

const form = reactive({
    smtp_host: props.ayar?.smtp_host || 'smtp.gmail.com',
    smtp_port: props.ayar?.smtp_port || 587,
    smtp_sifreleme: props.ayar?.smtp_sifreleme || 'tls',
    smtp_kullanici: props.ayar?.smtp_kullanici || '',
    smtp_sifre: '',
    gonderen_email: props.ayar?.gonderen_email || '',
    gonderen_ad: props.ayar?.gonderen_ad || '',
    durum: props.ayar?.durum ?? true,
});

const testForm = reactive({ test_email: '' });
const showPassword = ref(false);

const save = () => {
    axios.post(route('tanim.mail-ayarlari.kaydet'), { ...form }).catch(e => Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu', 'error'));
};

const sendTest = () => {
    if (!testForm.test_email) { Swal.fire('Uyarı', 'Test e-posta adresi girin.', 'warning'); return; }
    testForm.post(route('tanim.mail-test'), {
        onSuccess: () => Swal.fire('Başarılı!', `Test maili ${testForm.test_email} adresine gönderildi.`, 'success'),
        onError: () => Swal.fire('Hata', 'Test gönderimi başarısız.', 'error'),
    });
};
</script>

<template>
<Head title="Mail Ayarları" />
<AuthenticatedLayout>
    <div class="p-4 h-full flex flex-col">
        <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
            <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                <h2 class="font-bold text-sm text-gray-800">E-Posta (SMTP) Ayarları</h2>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                <div class="max-w-2xl">
                    <!-- Durum Toggle -->
                    <div class="flex items-center justify-between mb-5 bg-gray-50 rounded border border-gray-200 px-4 py-3">
                        <div>
                            <div class="text-sm font-bold text-gray-800">E-Posta Gönderimi</div>
                            <div class="text-[10px] text-gray-500">Sistem üzerinden e-posta gönderilmesini etkinleştirin</div>
                        </div>
                        <button @click="form.durum = !form.durum" type="button"
                            :class="form.durum ? 'bg-green-500' : 'bg-gray-300'"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200">
                            <span :class="form.durum ? 'translate-x-5' : 'translate-x-0'"
                                class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow border border-gray-200 transition duration-200"></span>
                        </button>
                    </div>

                    <!-- SMTP Sunucu Bilgileri -->
                    <div class="border border-gray-200 rounded mb-4">
                        <div class="bg-gray-100 px-4 py-2 border-b border-gray-200 text-xs font-bold text-gray-700 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path></svg>
                            SMTP Sunucu Bilgileri
                        </div>
                        <div class="p-4 grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">SMTP Sunucu:</label>
                                <input v-model="form.smtp_host" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs" placeholder="smtp.gmail.com" />
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">Port:</label>
                                <input v-model="form.smtp_port" type="number" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs" placeholder="587" />
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">Şifreleme:</label>
                                <select v-model="form.smtp_sifreleme" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs">
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                    <option value="none">Yok</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">Kullanıcı Adı:</label>
                                <input v-model="form.smtp_kullanici" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs" placeholder="user@gmail.com" />
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">Şifre:</label>
                                <div class="relative">
                                    <input v-model="form.smtp_sifre" :type="showPassword ? 'text' : 'password'" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs pr-8" placeholder="••••••••" />
                                    <button @click="showPassword = !showPassword" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="showPassword ? 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21' : 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'"></path></svg>
                                    </button>
                                </div>
                                <div class="text-[9px] text-gray-400 mt-1">Boş bırakırsanız mevcut şifre korunur</div>
                            </div>
                        </div>
                    </div>

                    <!-- Gönderici Bilgileri -->
                    <div class="border border-gray-200 rounded mb-4">
                        <div class="bg-gray-100 px-4 py-2 border-b border-gray-200 text-xs font-bold text-gray-700 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Gönderici Bilgileri
                        </div>
                        <div class="p-4 grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">Gönderen E-Posta:</label>
                                <input v-model="form.gonderen_email" type="email" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs" placeholder="bilgi@sirket.com" />
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">Gönderen Ad:</label>
                                <input v-model="form.gonderen_ad" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs" placeholder="PDKS Sistemi" />
                            </div>
                        </div>
                    </div>

                    <!-- Hızlı Ayarlar -->
                    <div class="border border-blue-200 rounded mb-4 bg-blue-50">
                        <div class="px-4 py-2 border-b border-blue-200 text-xs font-bold text-blue-700 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Hızlı Ayarlar
                        </div>
                        <div class="p-3 flex gap-2 flex-wrap">
                            <button @click="form.smtp_host='smtp.gmail.com'; form.smtp_port=587; form.smtp_sifreleme='tls'" type="button" class="bg-white border border-gray-300 rounded px-3 py-1.5 text-[10px] hover:bg-blue-100 font-semibold">📧 Gmail</button>
                            <button @click="form.smtp_host='smtp.office365.com'; form.smtp_port=587; form.smtp_sifreleme='tls'" type="button" class="bg-white border border-gray-300 rounded px-3 py-1.5 text-[10px] hover:bg-blue-100 font-semibold">📧 Outlook/Office365</button>
                            <button @click="form.smtp_host='smtp.yandex.com.tr'; form.smtp_port=465; form.smtp_sifreleme='ssl'" type="button" class="bg-white border border-gray-300 rounded px-3 py-1.5 text-[10px] hover:bg-blue-100 font-semibold">📧 Yandex</button>
                            <button @click="form.smtp_host='mail.sirket.com'; form.smtp_port=465; form.smtp_sifreleme='ssl'" type="button" class="bg-white border border-gray-300 rounded px-3 py-1.5 text-[10px] hover:bg-blue-100 font-semibold">🏢 Kurumsal SMTP</button>
                        </div>
                    </div>

                    <!-- Test Gönderimi -->
                    <div class="border border-orange-200 rounded bg-orange-50">
                        <div class="px-4 py-2 border-b border-orange-200 text-xs font-bold text-orange-700 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                            Test Gönderimi
                        </div>
                        <div class="p-3 flex gap-2 items-end">
                            <div class="flex-1">
                                <label class="text-[10px] font-semibold text-gray-600 mb-1 block">Test E-Posta Adresi:</label>
                                <input v-model="testForm.test_email" type="email" class="w-full border-gray-300 rounded-sm py-1.5 px-3 text-xs" placeholder="test@mail.com" />
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
