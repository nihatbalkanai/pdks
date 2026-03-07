<script setup>
import { Head } from '@inertiajs/vue3';
import WebLayout from '@/Layouts/WebLayout.vue';
import { ref } from 'vue';
import axios from 'axios';

const form = ref({ ad: '', email: '', telefon: '', konu: '', mesaj: '' });
const gonderildi = ref(false);
const gonderiliyor = ref(false);

const gonder = async () => {
    gonderiliyor.value = true;
    // Simulate sending
    setTimeout(() => {
        gonderildi.value = true;
        gonderiliyor.value = false;
    }, 1000);
};
</script>

<template>
    <Head title="İletişim - PDKSPro" />
    <WebLayout>
        <section class="page-hero">
            <div class="hero-inner">
                <span class="page-badge">İletişim</span>
                <h1>Bize <span class="gradient-text">Ulaşın</span></h1>
                <p>Sorularınız mı var? Ekibimiz size yardımcı olmaktan mutluluk duyar.</p>
            </div>
        </section>

        <section class="contact-section">
            <div class="contact-container">
                <div class="contact-grid">
                    <div class="contact-info">
                        <div class="info-card">
                            <span class="info-icon">📍</span>
                            <h4>Adres</h4>
                            <p>Levent, Büyükdere Cad. No:123<br>Beşiktaş / İstanbul</p>
                        </div>
                        <div class="info-card">
                            <span class="info-icon">📧</span>
                            <h4>E-posta</h4>
                            <p>info@pdkspro.com<br>destek@pdkspro.com</p>
                        </div>
                        <div class="info-card">
                            <span class="info-icon">📞</span>
                            <h4>Telefon</h4>
                            <p>+90 (212) 000 00 00<br>+90 (530) 000 00 00</p>
                        </div>
                        <div class="info-card">
                            <span class="info-icon">🕐</span>
                            <h4>Çalışma Saatleri</h4>
                            <p>Pazartesi - Cuma: 09:00 - 18:00<br>Cumartesi: 10:00 - 14:00</p>
                        </div>
                    </div>

                    <div class="contact-form-wrapper">
                        <div v-if="gonderildi" class="success-msg">
                            <span class="text-4xl mb-3 block">✅</span>
                            <h3>Mesajınız Alındı!</h3>
                            <p>En kısa sürede size dönüş yapacağız. Teşekkürler!</p>
                        </div>
                        <form v-else @submit.prevent="gonder" class="contact-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Ad Soyad *</label>
                                    <input v-model="form.ad" type="text" required placeholder="Adınız Soyadınız" />
                                </div>
                                <div class="form-group">
                                    <label>E-posta *</label>
                                    <input v-model="form.email" type="email" required placeholder="ornek@sirket.com" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Telefon</label>
                                    <input v-model="form.telefon" type="tel" placeholder="+90 5XX XXX XX XX" />
                                </div>
                                <div class="form-group">
                                    <label>Konu *</label>
                                    <select v-model="form.konu" required>
                                        <option value="">Seçiniz</option>
                                        <option>Fiyat Bilgisi</option>
                                        <option>Demo Talebi</option>
                                        <option>Teknik Destek</option>
                                        <option>İş Ortaklığı</option>
                                        <option>Diğer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Mesajınız *</label>
                                <textarea v-model="form.mesaj" required rows="5" placeholder="Mesajınızı buraya yazabilirsiniz..."></textarea>
                            </div>
                            <button type="submit" class="submit-btn" :disabled="gonderiliyor">
                                {{ gonderiliyor ? 'Gönderiliyor...' : 'Mesaj Gönder' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </WebLayout>
</template>

<style scoped>
.page-hero { padding: 140px 24px 80px; text-align: center; background: linear-gradient(135deg, #eef2ff, #e0e7ff, #ddd6fe); }
.hero-inner { max-width: 700px; margin: 0 auto; }
.page-badge { display: inline-block; padding: 4px 14px; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: 100px; font-size: 12px; font-weight: 700; color: #6366f1; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; }
.page-hero h1 { font-size: 44px; font-weight: 900; color: #1e1b4b; margin-bottom: 16px; line-height: 1.1; }
.gradient-text { background: linear-gradient(135deg, #6366f1, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.page-hero p { font-size: 17px; color: #6b7280; }

.contact-section { padding: 80px 0; background: white; }
.contact-container { max-width: 1100px; margin: 0 auto; padding: 0 24px; }
.contact-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 50px; }

.contact-info { display: flex; flex-direction: column; gap: 20px; }
.info-card { padding: 24px; background: #fafbff; border-radius: 14px; border: 1px solid #eef2ff; }
.info-icon { font-size: 24px; display: block; margin-bottom: 10px; }
.info-card h4 { font-size: 15px; font-weight: 700; color: #1e1b4b; margin-bottom: 6px; }
.info-card p { font-size: 13px; color: #6b7280; line-height: 1.6; }

.contact-form-wrapper { background: #fafbff; border-radius: 20px; padding: 36px; border: 1px solid #eef2ff; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
.form-group { margin-bottom: 0; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; transition: all 0.2s; font-family: inherit; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.form-group + .form-group { margin-top: 16px; }
.submit-btn { margin-top: 20px; width: 100%; padding: 14px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-weight: 700; font-size: 15px; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 15px rgba(99,102,241,0.3); }
.submit-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); }
.submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.success-msg { text-align: center; padding: 60px 20px; }
.success-msg h3 { font-size: 22px; font-weight: 800; color: #1e1b4b; margin-bottom: 8px; }
.success-msg p { font-size: 14px; color: #6b7280; }

@media (max-width: 768px) {
    .page-hero h1 { font-size: 30px; }
    .contact-grid { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
}
</style>
