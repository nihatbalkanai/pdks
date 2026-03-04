import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// WebSocket (Laravel Reverb) — localStorage ayarıyla kontrol edilir
// Profil sayfasından açılıp kapatılabilir.
function initEcho() {
    if (localStorage.getItem('websocket_enabled') === 'true') {
        import('pusher-js').then((PusherModule) => {
            import('laravel-echo').then((EchoModule) => {
                window.Pusher = PusherModule.default;
                window.Echo = new EchoModule.default({
                    broadcaster: 'reverb',
                    key: import.meta.env.VITE_REVERB_APP_KEY,
                    wsHost: import.meta.env.VITE_REVERB_HOST,
                    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
                    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
                    forceTLS: false,
                    enabledTransports: ['ws', 'wss'],
                });
                console.log('🟢 WebSocket bağlantısı aktif.');
            });
        });
    }
}

initEcho();

// Dışarıdan erişilebilir: toggle sonrası yeniden başlat
window.initEcho = initEcho;
window.disconnectEcho = function () {
    if (window.Echo) {
        window.Echo.disconnect();
        window.Echo = null;
        console.log('🔴 WebSocket bağlantısı kapatıldı.');
    }
};
