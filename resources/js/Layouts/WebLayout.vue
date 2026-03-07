<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const page = usePage();
const isMenuOpen = ref(false);
const scrolled = ref(false);

const handleScroll = () => {
    scrolled.value = window.scrollY > 20;
};

onMounted(() => window.addEventListener('scroll', handleScroll));
onUnmounted(() => window.removeEventListener('scroll', handleScroll));

const navItems = [
    { label: 'Anasayfa', route: 'web.anasayfa' },
    { label: 'Hakkımızda', route: 'web.hakkimizda' },
    { label: 'Fiyatlar', route: 'web.fiyatlar' },
    { label: 'Referanslar', route: 'web.referanslar' },
    { label: 'Haberler', route: 'web.haberler' },
    { label: 'İletişim', route: 'web.iletisim' },
];
</script>

<template>
    <div class="web-layout">
        <!-- Navbar -->
        <nav :class="['web-nav', { scrolled }]">
            <div class="nav-container">
                <Link :href="route('web.anasayfa')" class="nav-logo">
                    <div class="logo-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    <span class="logo-text">PDKS<span class="logo-accent">Pro</span></span>
                </Link>

                <div class="nav-links desktop-only">
                    <Link v-for="item in navItems" :key="item.route" :href="route(item.route)"
                          :class="['nav-link', { active: route().current(item.route) }]">
                        {{ item.label }}
                    </Link>
                </div>

                <div class="nav-actions desktop-only">
                    <Link :href="route('login')" class="btn-login">Giriş Yap</Link>
                    <Link :href="route('login')" class="btn-demo">Ücretsiz Dene</Link>
                </div>

                <button @click="isMenuOpen = !isMenuOpen" class="mobile-menu-btn">
                    <svg v-if="!isMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div v-show="isMenuOpen" class="mobile-menu">
                <Link v-for="item in navItems" :key="item.route" :href="route(item.route)"
                      class="mobile-link" @click="isMenuOpen = false">
                    {{ item.label }}
                </Link>
                <Link :href="route('login')" class="btn-demo w-full text-center mt-3">Giriş Yap</Link>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="web-footer">
            <div class="footer-container">
                <div class="footer-grid">
                    <div class="footer-brand">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="logo-icon small"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
                            <span class="text-xl font-bold text-white">PDKS<span class="text-indigo-400">Pro</span></span>
                        </div>
                        <p class="text-gray-400 text-sm leading-relaxed">Türkiye'nin en kapsamlı Personel Devam Kontrol Sistemi. Yüzlerce firma tarafından güvenle kullanılmaktadır.</p>
                    </div>
                    <div>
                        <h4 class="footer-heading">Ürün</h4>
                        <Link :href="route('web.fiyatlar')" class="footer-link">Fiyatlar</Link>
                        <Link :href="route('web.anasayfa')" class="footer-link">Özellikler</Link>
                        <Link :href="route('web.haberler')" class="footer-link">Güncellemeler</Link>
                    </div>
                    <div>
                        <h4 class="footer-heading">Şirket</h4>
                        <Link :href="route('web.hakkimizda')" class="footer-link">Hakkımızda</Link>
                        <Link :href="route('web.referanslar')" class="footer-link">Referanslar</Link>
                        <Link :href="route('web.iletisim')" class="footer-link">İletişim</Link>
                    </div>
                    <div>
                        <h4 class="footer-heading">İletişim</h4>
                        <p class="text-gray-400 text-sm">info@pdkspro.com</p>
                        <p class="text-gray-400 text-sm mt-1">+90 (212) 000 00 00</p>
                        <p class="text-gray-400 text-sm mt-1">İstanbul, Türkiye</p>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p class="text-gray-500 text-sm">© 2026 PDKSPro. Tüm hakları saklıdır.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

.web-layout { font-family: 'Inter', sans-serif; }

/* Navbar */
.web-nav {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    transition: all 0.3s ease;
    background: rgba(255,255,255,0.8);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid transparent;
}
.web-nav.scrolled { background: rgba(255,255,255,0.95); border-color: #e5e7eb; box-shadow: 0 1px 10px rgba(0,0,0,0.05); }
.nav-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; height: 72px; }
.nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
.logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; padding: 7px; }
.logo-icon.small { width: 28px; height: 28px; padding: 5px; }
.logo-text { font-size: 22px; font-weight: 800; color: #1e1b4b; }
.logo-accent { color: #6366f1; }
.nav-links { display: flex; gap: 4px; }
.nav-link { padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; color: #4b5563; text-decoration: none; transition: all 0.2s; }
.nav-link:hover { color: #6366f1; background: #f5f3ff; }
.nav-link.active { color: #6366f1; background: #eef2ff; font-weight: 600; }
.nav-actions { display: flex; gap: 10px; align-items: center; }
.btn-login { padding: 8px 20px; font-size: 14px; font-weight: 600; color: #6366f1; text-decoration: none; border-radius: 8px; transition: all 0.2s; }
.btn-login:hover { background: #f5f3ff; }
.btn-demo { padding: 8px 24px; font-size: 14px; font-weight: 600; color: white; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 8px; text-decoration: none; transition: all 0.3s; box-shadow: 0 2px 10px rgba(99,102,241,0.3); }
.btn-demo:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(99,102,241,0.4); }
.desktop-only { display: flex; }
.mobile-menu-btn { display: none; background: none; border: none; color: #374151; cursor: pointer; padding: 8px; }
.mobile-menu { display: none; padding: 16px 24px; border-top: 1px solid #f3f4f6; }
.mobile-link { display: block; padding: 12px 0; color: #374151; text-decoration: none; font-weight: 500; border-bottom: 1px solid #f3f4f6; }

@media (max-width: 768px) {
    .desktop-only { display: none !important; }
    .mobile-menu-btn { display: block; }
    .mobile-menu { display: block; }
}

/* Footer */
.web-footer { background: #0f172a; padding: 60px 0 0; }
.footer-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; padding-bottom: 40px; }
.footer-heading { color: white; font-weight: 600; font-size: 14px; margin-bottom: 16px; }
.footer-link { display: block; color: #9ca3af; font-size: 14px; text-decoration: none; margin-bottom: 8px; transition: color 0.2s; }
.footer-link:hover { color: #a5b4fc; }
.footer-bottom { border-top: 1px solid #1e293b; padding: 20px 0; text-align: center; }

@media (max-width: 768px) {
    .footer-grid { grid-template-columns: 1fr 1fr; gap: 30px; }
}
</style>
