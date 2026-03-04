<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const wsEnabled = ref(false);
const wsStatus = ref('kapalı');

onMounted(() => {
    wsEnabled.value = localStorage.getItem('websocket_enabled') === 'true';
    updateStatus();
});

const updateStatus = () => {
    if (wsEnabled.value && window.Echo) {
        wsStatus.value = 'bağlı';
    } else if (wsEnabled.value) {
        wsStatus.value = 'bağlanıyor...';
    } else {
        wsStatus.value = 'kapalı';
    }
};

const toggleWebSocket = () => {
    wsEnabled.value = !wsEnabled.value;
    localStorage.setItem('websocket_enabled', wsEnabled.value ? 'true' : 'false');

    if (wsEnabled.value) {
        wsStatus.value = 'bağlanıyor...';
        if (window.initEcho) window.initEcho();
        setTimeout(updateStatus, 2000);
    } else {
        if (window.disconnectEcho) window.disconnectEcho();
        wsStatus.value = 'kapalı';
    }
};
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <!-- WebSocket Ayarları -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <section class="max-w-xl">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                WebSocket Bağlantısı
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Canlı bildirim sistemi (Laravel Reverb) bağlantısını buradan açıp kapatabilirsiniz.
                                WebSocket aktifken sunucudan anlık bildirimler alırsınız.
                            </p>
                        </header>

                        <div class="mt-6 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <!-- Toggle Switch -->
                                <button
                                    @click="toggleWebSocket"
                                    :class="wsEnabled ? 'bg-green-500' : 'bg-gray-300'"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                    role="switch"
                                    :aria-checked="wsEnabled"
                                >
                                    <span
                                        :class="wsEnabled ? 'translate-x-5' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out mt-0.5 ml-0.5"
                                    ></span>
                                </button>
                                <span class="text-sm font-medium text-gray-700">
                                    {{ wsEnabled ? 'Açık' : 'Kapalı' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-green-100 text-green-800': wsStatus === 'bağlı',
                                        'bg-yellow-100 text-yellow-800': wsStatus === 'bağlanıyor...',
                                        'bg-gray-100 text-gray-600': wsStatus === 'kapalı',
                                    }"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="{
                                            'bg-green-500': wsStatus === 'bağlı',
                                            'bg-yellow-500 animate-pulse': wsStatus === 'bağlanıyor...',
                                            'bg-gray-400': wsStatus === 'kapalı',
                                        }"
                                    ></span>
                                    {{ wsStatus }}
                                </span>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-gray-400">
                            Not: WebSocket sunucusunun (<code>php artisan reverb:start</code>) çalışıyor olması gerekmektedir.
                        </p>
                    </section>
                </div>

                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
