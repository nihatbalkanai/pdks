<script setup>
import { Head, Link } from '@inertiajs/vue3';
import WebLayout from '@/Layouts/WebLayout.vue';
import { ref, onMounted } from 'vue';

defineProps({ paketler: Array });

const counters = ref({ firma: 0, personel: 0, kayit: 0, saat: 0 });
const targets = { firma: 500, personel: 25000, kayit: 1000000, saat: 99.9 };

onMounted(() => {
    const duration = 2000;
    const steps = 60;
    let step = 0;
    const interval = setInterval(() => {
        step++;
        const progress = step / steps;
        const ease = 1 - Math.pow(1 - progress, 3);
        counters.value.firma = Math.round(targets.firma * ease);
        counters.value.personel = Math.round(targets.personel * ease);
        counters.value.kayit = Math.round(targets.kayit * ease);
        counters.value.saat = Math.round(targets.saat * ease * 10) / 10;
        if (step >= steps) clearInterval(interval);
    }, duration / steps);
});

const ozellikler = [
    { icon: '⏱️', title: 'Giriş/Çıkış Takibi', desc: 'PDKS cihazları ile otomatik personel giriş-çıkış kaydı. Gerçek zamanlı takip ve anlık bildirimler.' },
    { icon: '📊', title: 'Puantaj & Maaş Hesaplama', desc: 'Otomatik puantaj oluşturma, fazla mesai, AGİ, SGK ve tüm yasal kesintileri otomatik hesaplama.' },
    { icon: '🏖️', title: 'İzin Yönetimi', desc: 'Yıllık izin, rapor, ücretsiz izin ve tüm izin türlerini takip edin. Kıdem bazlı otomatik hak ediş.' },
    { icon: '👥', title: 'Personel Kartları', desc: 'Detaylı personel bilgileri, özlük dosyaları, sertifika ve belge yönetimi tek ekranda.' },
    { icon: '🔒', title: 'Güvenlik & Yetkilendirme', desc: 'Rol bazlı erişim kontrolü, çoklu kullanıcı desteği ve detaylı işlem logları.' },
    { icon: '📱', title: 'Çoklu Cihaz & Şube', desc: 'Birden fazla PDKS cihazı ve şube yönetimi. Merkezi kontrol paneli ile tüm lokasyonlarınızı yönetin.' },
];
</script>

<template>
    <Head title="PDKSPro - Personel Devam Kontrol Sistemi" />
    <WebLayout>
        <!-- Hero -->
        <section class="hero">
            <div class="hero-bg"></div>
            <div class="hero-content">
                <div class="hero-badge">🚀 Türkiye'nin #1 PDKS Çözümü</div>
                <h1 class="hero-title">
                    Personel Yönetiminde<br>
                    <span class="gradient-text">Yeni Nesil</span> Deneyim
                </h1>
                <p class="hero-desc">
                    Giriş-çıkış takibi, puantaj hesaplama, izin yönetimi ve maaş bordrosu — hepsi tek platformda.
                    Binlerce firma tarafından güvenle tercih edilen PDKS çözümü.
                </p>
                <div class="hero-actions">
                    <Link :href="route('login')" class="btn-primary-lg">
                        Ücretsiz Başla
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </Link>
                    <Link :href="route('web.fiyatlar')" class="btn-secondary-lg">Fiyatları Gör</Link>
                </div>
                <div class="hero-stats">
                    <div class="stat"><span class="stat-value">{{ counters.firma.toLocaleString('tr-TR') }}+</span><span class="stat-label">Aktif Firma</span></div>
                    <div class="stat-divider"></div>
                    <div class="stat"><span class="stat-value">{{ counters.personel.toLocaleString('tr-TR') }}+</span><span class="stat-label">Personel</span></div>
                    <div class="stat-divider"></div>
                    <div class="stat"><span class="stat-value">{{ counters.kayit.toLocaleString('tr-TR') }}+</span><span class="stat-label">Aylık Kayıt</span></div>
                    <div class="stat-divider"></div>
                    <div class="stat"><span class="stat-value">%{{ counters.saat }}</span><span class="stat-label">Uptime</span></div>
                </div>
            </div>
        </section>

        <!-- Özellikler -->
        <section class="features-section">
            <div class="section-container">
                <div class="section-header">
                    <span class="section-badge">Özellikler</span>
                    <h2 class="section-title">İhtiyacınız Olan <span class="gradient-text">Her Şey</span></h2>
                    <p class="section-desc">Personel yönetiminin tüm süreçlerini dijitalleştirin, zamandan ve maliyetten tasarruf edin.</p>
                </div>
                <div class="features-grid">
                    <div v-for="(oz, i) in ozellikler" :key="i" class="feature-card">
                        <div class="feature-icon">{{ oz.icon }}</div>
                        <h3 class="feature-title">{{ oz.title }}</h3>
                        <p class="feature-desc">{{ oz.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Neden Biz -->
        <section class="why-section">
            <div class="section-container">
                <div class="why-grid">
                    <div class="why-content">
                        <span class="section-badge">Neden PDKSPro?</span>
                        <h2 class="section-title" style="text-align:left">Diğerlerinden <span class="gradient-text">Farkımız</span></h2>
                        <div class="why-items">
                            <div class="why-item">
                                <div class="why-check">✓</div>
                                <div><h4>Türkiye'ye Özel Mevzuat</h4><p>SGK, AGİ, gelir vergisi dilimleri ve tüm Türk iş hukuku kuralları entegre edilmiştir.</p></div>
                            </div>
                            <div class="why-item">
                                <div class="why-check">✓</div>
                                <div><h4>7/24 Teknik Destek</h4><p>Uzman ekibimiz bilet sistemi üzerinden her zaman yanınızda.</p></div>
                            </div>
                            <div class="why-item">
                                <div class="why-check">✓</div>
                                <div><h4>Bulut Tabanlı & Güvenli</h4><p>Verileriniz SSL şifreleme ile korunur, günlük otomatik yedekleme yapılır.</p></div>
                            </div>
                            <div class="why-item">
                                <div class="why-check">✓</div>
                                <div><h4>Kolay Entegrasyon</h4><p>Tüm PDKS cihaz markaları ile uyumlu. 10 dakikada kurulum.</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="why-visual">
                        <div class="visual-card">
                            <div class="visual-header">
                                <div class="visual-dots"><span></span><span></span><span></span></div>
                                <span class="text-xs text-gray-400">PDKSPro Dashboard</span>
                            </div>
                            <div class="visual-body">
                                <div class="visual-stat green"><span class="text-2xl font-bold">247</span><span class="text-xs text-gray-500">Aktif Personel</span></div>
                                <div class="visual-stat blue"><span class="text-2xl font-bold">98.5%</span><span class="text-xs text-gray-500">Devam Oranı</span></div>
                                <div class="visual-stat purple"><span class="text-2xl font-bold">₺1.2M</span><span class="text-xs text-gray-500">Aylık Bordro</span></div>
                                <div class="visual-bar"><div class="bar-fill" style="width:94%"></div></div>
                                <div class="visual-bar"><div class="bar-fill blue" style="width:78%"></div></div>
                                <div class="visual-bar"><div class="bar-fill purple" style="width:85%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="cta-section">
            <div class="section-container">
                <div class="cta-card">
                    <h2>Hemen ücretsiz deneyin</h2>
                    <p>Kredi kartı gerekmez. 14 gün boyunca tüm özellikleri ücretsiz kullanın.</p>
                    <Link :href="route('login')" class="btn-primary-lg" style="background:white;color:#6366f1">
                        Ücretsiz Başla
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </Link>
                </div>
            </div>
        </section>
    </WebLayout>
</template>

<style scoped>
/* Hero */
.hero { position: relative; min-height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; padding-top: 72px; }
.hero-bg { position: absolute; inset: 0; background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 25%, #c7d2fe 50%, #ddd6fe 75%, #ede9fe 100%); }
.hero-bg::after { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at 30% 40%, rgba(99,102,241,0.15) 0%, transparent 50%), radial-gradient(circle at 70% 60%, rgba(139,92,246,0.1) 0%, transparent 50%); }
.hero-content { position: relative; text-align: center; max-width: 800px; padding: 60px 24px; }
.hero-badge { display: inline-block; padding: 6px 18px; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: 100px; font-size: 13px; font-weight: 600; color: #6366f1; margin-bottom: 24px; }
.hero-title { font-size: 56px; font-weight: 900; line-height: 1.1; color: #1e1b4b; margin-bottom: 20px; letter-spacing: -1px; }
.gradient-text { background: linear-gradient(135deg, #6366f1, #a855f7, #6366f1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.hero-desc { font-size: 18px; color: #6b7280; line-height: 1.7; margin-bottom: 36px; max-width: 600px; margin-left: auto; margin-right: auto; }
.hero-actions { display: flex; gap: 14px; justify-content: center; margin-bottom: 50px; }
.btn-primary-lg { display: inline-flex; align-items: center; gap: 8px; padding: 14px 32px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-weight: 700; font-size: 15px; border-radius: 12px; text-decoration: none; transition: all 0.3s; box-shadow: 0 4px 15px rgba(99,102,241,0.3); }
.btn-primary-lg:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99,102,241,0.4); }
.btn-secondary-lg { display: inline-flex; align-items: center; gap: 8px; padding: 14px 32px; background: white; color: #374151; font-weight: 700; font-size: 15px; border-radius: 12px; text-decoration: none; transition: all 0.3s; border: 1px solid #e5e7eb; }
.btn-secondary-lg:hover { border-color: #6366f1; color: #6366f1; }
.hero-stats { display: flex; align-items: center; justify-content: center; gap: 30px; }
.stat { display: flex; flex-direction: column; align-items: center; }
.stat-value { font-size: 24px; font-weight: 800; color: #1e1b4b; }
.stat-label { font-size: 12px; color: #9ca3af; margin-top: 2px; }
.stat-divider { width: 1px; height: 40px; background: #d1d5db; }

/* Features */
.features-section { padding: 100px 0; background: white; }
.section-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.section-header { text-align: center; margin-bottom: 60px; }
.section-badge { display: inline-block; padding: 4px 14px; background: #eef2ff; border-radius: 100px; font-size: 12px; font-weight: 700; color: #6366f1; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
.section-title { font-size: 40px; font-weight: 800; color: #1e1b4b; margin-bottom: 14px; }
.section-desc { font-size: 16px; color: #6b7280; max-width: 500px; margin: 0 auto; }
.features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
.feature-card { padding: 32px; border-radius: 16px; border: 1px solid #f3f4f6; transition: all 0.3s; }
.feature-card:hover { border-color: #e0e7ff; box-shadow: 0 10px 30px rgba(99,102,241,0.08); transform: translateY(-4px); }
.feature-icon { font-size: 32px; margin-bottom: 16px; }
.feature-title { font-size: 18px; font-weight: 700; color: #1e1b4b; margin-bottom: 8px; }
.feature-desc { font-size: 14px; color: #6b7280; line-height: 1.6; }

/* Why */
.why-section { padding: 100px 0; background: #fafbff; }
.why-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
.why-items { margin-top: 30px; }
.why-item { display: flex; gap: 16px; margin-bottom: 24px; }
.why-check { width: 28px; height: 28px; min-width: 28px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; margin-top: 2px; }
.why-item h4 { font-size: 16px; font-weight: 700; color: #1e1b4b; margin-bottom: 4px; }
.why-item p { font-size: 13px; color: #6b7280; line-height: 1.5; }

/* Visual Card */
.visual-card { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.08); overflow: hidden; }
.visual-header { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
.visual-dots { display: flex; gap: 6px; }
.visual-dots span { width: 10px; height: 10px; border-radius: 50%; }
.visual-dots span:nth-child(1) { background: #ef4444; }
.visual-dots span:nth-child(2) { background: #f59e0b; }
.visual-dots span:nth-child(3) { background: #22c55e; }
.visual-body { padding: 24px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.visual-stat { padding: 16px; border-radius: 12px; display: flex; flex-direction: column; gap: 4px; }
.visual-stat.green { background: #f0fdf4; }
.visual-stat.blue { background: #eff6ff; }
.visual-stat.purple { background: #faf5ff; }
.visual-bar { grid-column: span 3; height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden; }
.bar-fill { height: 100%; background: linear-gradient(90deg, #22c55e, #4ade80); border-radius: 4px; transition: width 1s ease; }
.bar-fill.blue { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.bar-fill.purple { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }

/* CTA */
.cta-section { padding: 80px 0; }
.cta-card { background: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7); border-radius: 24px; padding: 60px; text-align: center; }
.cta-card h2 { font-size: 36px; font-weight: 800; color: white; margin-bottom: 12px; }
.cta-card p { font-size: 16px; color: rgba(255,255,255,0.8); margin-bottom: 30px; }

@media (max-width: 768px) {
    .hero-title { font-size: 34px; }
    .hero-actions { flex-direction: column; align-items: center; }
    .hero-stats { flex-wrap: wrap; gap: 20px; }
    .stat-divider { display: none; }
    .features-grid { grid-template-columns: 1fr; }
    .why-grid { grid-template-columns: 1fr; }
    .section-title { font-size: 28px; }
    .cta-card { padding: 40px 24px; }
    .cta-card h2 { font-size: 24px; }
}
</style>
