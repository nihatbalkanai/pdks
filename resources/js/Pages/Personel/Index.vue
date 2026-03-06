<script setup>
import { ref, watch, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    personeller: Object,
    filtreler: Object,
    tanimKodlari: Object,
    aylikPuantajParametreleri: { type: Array, default: () => [] },
    gunlukPuantajParametreleri: { type: Array, default: () => [] },
});

// Tanım kodları helper
const getTanimSecenekleri = (tip) => props.tanimKodlari?.[tip] || [];

const aramaAna = ref(props.filtreler?.arama || '');
const localPersoneller = ref([...(props.personeller?.data || [])]);
const searchText = ref('');
const activeTab = ref('ozluk');
const activeSubTab = ref('genel');
const activeIzinTab = ref('ucretli');
const isLoading = ref(false);
const isSaving = ref(false);

const emptyPersonel = {
    id: null, kart_no: '', sicil_no: '', ad: '', soyad: '', ssk_no: '',
    unvan: '', sirket: '', bolum: '', ozel_kod: '', departman: '',
    servis_kodu: '', hesap_gurubu: '', agi: '', aylik_ucret: '',
    gunluk_ucret: '', saat_1: '', saat_2: '', saat_3: '',
    giris_tarihi: '', cikis_tarihi: '', durum: true, notlar: '',
    email: '', telefon: '', gec_kalma_bildirimi: false, resim_yolu: '', puantaj_parametre_id: null,
    tc_no: '', iban_no: '', adres: '', acil_kisi_adi: '', acil_kisi_telefonu: '',
    izinler: [], avans_kesintiler: [], prim_kazanclar: [], zimmetler: [], pdks_kayitlari: [], dosyalar: [],
    izin_hakedis: null, mesailer: [], mesai_carpanlari: null,
    yemek_tipi: null, yemek_kart_no: null, yemek_ucreti: null,
    ulasim_tipi: null, servis_plaka: null, yol_parasi: null
};

const selectedPersonel = ref(JSON.parse(JSON.stringify(emptyPersonel)));

// Filtre
const filteredPersoneller = computed(() => {
    if (!searchText.value) return localPersoneller.value;
    const q = searchText.value.toLowerCase();
    return localPersoneller.value.filter(p =>
        (p.kart_no || '').toLowerCase().includes(q) ||
        (p.ad_soyad || '').toLowerCase().includes(q) ||
        (p.ad || '').toLowerCase().includes(q) ||
        (p.soyad || '').toLowerCase().includes(q)
    );
});

// Personel seç
const selectPersonel = async (personel) => {
    isLoading.value = true;
    if (personel.id) {
        // Hemen liste verisini göster
        const quick = { ...emptyPersonel, ...personel };
        if (!personel.ad && personel.ad_soyad) {
            const parts = personel.ad_soyad.split(' ');
            quick.ad = parts[0] || '';
            quick.soyad = parts.slice(1).join(' ') || '';
        }
        selectedPersonel.value = quick;

        try {
            const response = await axios.get(route('personeller.show', personel.id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const detail = response.data.personel;
            if (detail) {
                if (!detail.ad && detail.ad_soyad) {
                    const parts = (detail.ad_soyad || '').split(' ');
                    detail.ad = parts[0] || '';
                    detail.soyad = parts.slice(1).join(' ') || '';
                }
                Object.assign(selectedPersonel.value, detail);
            }
        } catch (e) {
            console.error('Personel detay yükleme hatası:', e);
        }
    } else {
        selectedPersonel.value = { ...emptyPersonel, ...personel };
    }
    isLoading.value = false;
};

// --- OTOMATİK MAAŞ HESAPLAMA ---
let isAutoCalculating = false;

watch(() => selectedPersonel.value.aylik_ucret, (newVal) => {
    if (isAutoCalculating) return;
    if (!newVal) {
        isAutoCalculating = true;
        selectedPersonel.value.gunluk_ucret = '';
        selectedPersonel.value.saat_1 = '';
        isAutoCalculating = false;
        return;
    }
    
    // Varsayılan katsayılar
    let aylikCalismaSaati = 225;
    let gunLuku = 30; // standart ay 30 gün
    
    if (selectedPersonel.value.hesap_gurubu) {
        const param = props.aylikPuantajParametreleri?.find(p => p.hesap_parametresi_adi === selectedPersonel.value.hesap_gurubu);
        if (param) {
            aylikCalismaSaati = param.aylik_calisma_saati || 225;
            // Günlük saati bulup 30'a bölmek yerine 30 güne bölündüğü varsayılır
            const gunlukSaat = param.gunluk_calisma_saati || 7.5;
            gunLuku = aylikCalismaSaati / gunlukSaat;
        }
    }
    
    isAutoCalculating = true;
    selectedPersonel.value.gunluk_ucret = (Number(newVal) / 30).toFixed(2);
    selectedPersonel.value.saat_1 = (Number(newVal) / aylikCalismaSaati).toFixed(2);
    isAutoCalculating = false;
});

watch(() => selectedPersonel.value.gunluk_ucret, (newVal) => {
    if (isAutoCalculating) return;
    if (!newVal) {
        isAutoCalculating = true;
        selectedPersonel.value.aylik_ucret = '';
        selectedPersonel.value.saat_1 = '';
        isAutoCalculating = false;
        return;
    }

    let aylikCalismaSaati = 225;
    
    if (selectedPersonel.value.hesap_gurubu) {
        const param = props.aylikPuantajParametreleri?.find(p => p.hesap_parametresi_adi === selectedPersonel.value.hesap_gurubu);
        if (param) {
            aylikCalismaSaati = param.aylik_calisma_saati || 225;
        }
    }
    
    isAutoCalculating = true;
    const aylik = Number(newVal) * 30;
    selectedPersonel.value.aylik_ucret = aylik.toFixed(2);
    selectedPersonel.value.saat_1 = (aylik / aylikCalismaSaati).toFixed(2);
    isAutoCalculating = false;
});

// Yeni personel
const newPersonel = () => {
    selectedPersonel.value = JSON.parse(JSON.stringify(emptyPersonel));
    activeTab.value = 'ozluk';
    activeSubTab.value = 'genel';
};

// Kaydet
const savePersonel = async () => {
    isSaving.value = true;
    try {
        // Sadece form alanlarını gönder (ilişki verilerini hariç tut)
        const formData = {
            kart_no: selectedPersonel.value.kart_no || '',
            ad: selectedPersonel.value.ad || '',
            soyad: selectedPersonel.value.soyad || '',
            sicil_no: selectedPersonel.value.sicil_no || '',
            ssk_no: selectedPersonel.value.ssk_no || '',
            unvan: selectedPersonel.value.unvan || '',
            sirket: selectedPersonel.value.sirket || '',
            bolum: selectedPersonel.value.bolum || '',
            ozel_kod: selectedPersonel.value.ozel_kod || '',
            departman: selectedPersonel.value.departman || '',
            servis_kodu: selectedPersonel.value.servis_kodu || '',
            hesap_gurubu: selectedPersonel.value.hesap_gurubu || '',
            agi: selectedPersonel.value.agi || '',
            aylik_ucret: selectedPersonel.value.aylik_ucret || null,
            gunluk_ucret: selectedPersonel.value.gunluk_ucret || null,
            saat_1: selectedPersonel.value.saat_1 || null,
            saat_2: selectedPersonel.value.saat_2 || null,
            saat_3: selectedPersonel.value.saat_3 || null,
            giris_tarihi: selectedPersonel.value.giris_tarihi || null,
            cikis_tarihi: selectedPersonel.value.cikis_tarihi || null,
            durum: selectedPersonel.value.durum ?? true,
            notlar: selectedPersonel.value.notlar || '',
            email: selectedPersonel.value.email || '',
            telefon: selectedPersonel.value.telefon || '',
            gec_kalma_bildirimi: selectedPersonel.value.gec_kalma_bildirimi ?? false,
            dogum_tarihi: selectedPersonel.value.dogum_tarihi || null,
            puantaj_parametre_id: selectedPersonel.value.puantaj_parametre_id || null,
            aylik_puantaj_parametre_id: selectedPersonel.value.aylik_puantaj_parametre_id || null,
            tc_no: selectedPersonel.value.tc_no || null,
            iban_no: selectedPersonel.value.iban_no || null,
            adres: selectedPersonel.value.adres || null,
            acil_kisi_adi: selectedPersonel.value.acil_kisi_adi || null,
            acil_kisi_telefonu: selectedPersonel.value.acil_kisi_telefonu || null,
            yemek_tipi: selectedPersonel.value.yemek_tipi || null,
            yemek_kart_no: selectedPersonel.value.yemek_kart_no || null,
            yemek_ucreti: selectedPersonel.value.yemek_ucreti || null,
            ulasim_tipi: selectedPersonel.value.ulasim_tipi || null,
            servis_plaka: selectedPersonel.value.servis_plaka || null,
            yol_parasi: selectedPersonel.value.yol_parasi || null,
        };

        if (selectedPersonel.value.id) {
            const res = await axios.put(route('personeller.update', selectedPersonel.value.id), formData);
            // Güncellenen veriyi local listeye yansıt
            const idx = localPersoneller.value.findIndex(p => p.id === selectedPersonel.value.id);
            if (idx !== -1 && res.data.personel) {
                localPersoneller.value[idx] = { ...localPersoneller.value[idx], ...res.data.personel };
            }
            Swal.fire({ title: 'Başarılı!', text: 'Personel güncellendi.', icon: 'success', timer: 1500 });
        } else {
            const response = await axios.post(route('personeller.store'), formData);
            if (response.data.personel) {
                localPersoneller.value.unshift(response.data.personel);
                selectedPersonel.value = { ...selectedPersonel.value, ...response.data.personel };
            }
            Swal.fire({ title: 'Başarılı!', text: 'Yeni personel kaydedildi.', icon: 'success', timer: 1500 });
        }
    } catch (error) {
        const errors = error.response?.data?.errors;
        const msg = errors ? Object.values(errors).flat().join('<br>') : (error.response?.data?.message || 'Kaydetme işlemi başarısız oldu.');
        Swal.fire('Hata!', msg, 'error');
    } finally {
        isSaving.value = false;
    }
};

// Sil
const deletePersonel = () => {
    if (!selectedPersonel.value.id) return;
    Swal.fire({
        title: 'Emin misiniz?', text: 'Bu personeli silmek istediğinize emin misiniz?',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
        confirmButtonText: 'Evet, Sil!', cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('personeller.destroy', selectedPersonel.value.id)).catch(e => Swal.fire('Hata', e.response?.data?.message || 'Silinemedi', 'error'), {
                onSuccess: () => {
                    Swal.fire('Silindi!', 'Personel başarıyla silindi.', 'success');
                    selectedPersonel.value = JSON.parse(JSON.stringify(emptyPersonel));
                }
            });
        }
    });
};

// ===== ZİMMET YÖNETİMİ =====
const yeniZimmet = ref({ kategori: '', bolum_adi: '', aciklama: '', verilis_tarihi: new Date().toISOString().slice(0,10) });

const reloadPersonelDetail = async () => {
    if (!selectedPersonel.value.id) return;
    try {
        const res = await axios.get(route('personeller.show', selectedPersonel.value.id), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (res.data.personel) {
            Object.assign(selectedPersonel.value, res.data.personel);
        }
    } catch (e) { console.error(e); }
};

const zimmetEkle = async () => {
    if (!yeniZimmet.value.aciklama) { Swal.fire('Uyarı', 'Açıklama zorunludur.', 'warning'); return; }
    if (!selectedPersonel.value.id) { Swal.fire('Uyarı', 'Önce personel seçiniz.', 'warning'); return; }
    try {
        await axios.post(route('tanim.personel-zimmet.store'), {
            personel_id: selectedPersonel.value.id,
            ...yeniZimmet.value
        });
        yeniZimmet.value = { kategori: '', bolum_adi: '', aciklama: '', verilis_tarihi: new Date().toISOString().slice(0,10) };
        await reloadPersonelDetail();
        Swal.fire({ title: 'Başarılı!', text: 'Zimmet eklendi.', icon: 'success', timer: 1500 });
    } catch (e) {
        Swal.fire('Hata', e.response?.data?.message || 'Zimmet eklenemedi.', 'error');
    }
};

const zimmetSil = (z) => {
    Swal.fire({
        title: 'Zimmet Sil', text: `"${z.aciklama}" zimmeti silinecek. Emin misiniz?`,
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
        confirmButtonText: 'Sil', cancelButtonText: 'İptal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(route('tanim.personel-zimmet.destroy', z.id));
                await reloadPersonelDetail();
                Swal.fire({ title: 'Silindi!', text: 'Zimmet silindi.', icon: 'success', timer: 1500 });
            } catch (e) {
                Swal.fire('Hata', e.response?.data?.message || 'Silinemedi.', 'error');
            }
        }
    });
};

const zimmetIade = (z) => {
    Swal.fire({
        title: 'Zimmet İade', text: `"${z.aciklama}" iade edilecek. Emin misiniz?`,
        icon: 'question', showCancelButton: true, confirmButtonColor: '#f59e0b',
        confirmButtonText: 'İade Et', cancelButtonText: 'İptal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.post(route('tanim.personel-zimmet.iade', z.id));
                await reloadPersonelDetail();
                Swal.fire({ title: 'İade Edildi!', text: 'Zimmet iade edildi.', icon: 'success', timer: 1500 });
            } catch (e) {
                Swal.fire('Hata', e.response?.data?.message || 'İade edilemedi.', 'error');
            }
        }
    });
};

// ===== MESAİ YÖNETİMİ =====
const yeniMesai = ref({ tarih: new Date().toISOString().slice(0,10), ilk_giris: '', son_cikis: '', toplam_calisma_suresi: 0, fazla_mesai_dakika: 0 });

const mesaiEkle = async () => {
    if (!yeniMesai.value.fazla_mesai_dakika || yeniMesai.value.fazla_mesai_dakika <= 0) { Swal.fire('Uyarı', 'Fazla mesai dakikası giriniz.', 'warning'); return; }
    if (!selectedPersonel.value.id) { Swal.fire('Uyarı', 'Önce personel seçiniz.', 'warning'); return; }
    try {
        await axios.post(route('tanim.mesai.store'), {
            personel_id: selectedPersonel.value.id,
            ...yeniMesai.value
        });
        yeniMesai.value = { tarih: new Date().toISOString().slice(0,10), ilk_giris: '', son_cikis: '', toplam_calisma_suresi: 0, fazla_mesai_dakika: 0 };
        await reloadPersonelDetail();
        Swal.fire({ title: 'Başarılı!', text: 'Mesai eklendi.', icon: 'success', timer: 1500 });
    } catch (e) {
        Swal.fire('Hata', e.response?.data?.message || 'Mesai eklenemedi.', 'error');
    }
};

const mesaiGuncelle = async (m, yeniDakika) => {
    const dk = parseInt(yeniDakika);
    if (isNaN(dk) || dk < 0) return;
    try {
        await axios.put(route('tanim.mesai.update', m.id), { fazla_mesai_dakika: dk });
        m.fazla_mesai_dakika = dk;
    } catch (e) {
        Swal.fire('Hata', e.response?.data?.message || 'Güncellenemedi.', 'error');
    }
};

const mesaiSil = (m) => {
    Swal.fire({
        title: 'Mesai Sil', text: `${formatTarih(m.tarih)} tarihli mesai silinecek. Emin misiniz?`,
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
        confirmButtonText: 'Sil', cancelButtonText: 'İptal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(route('tanim.mesai.destroy', m.id));
                await reloadPersonelDetail();
                Swal.fire({ title: 'Silindi!', text: 'Mesai silindi.', icon: 'success', timer: 1500 });
            } catch (e) {
                Swal.fire('Hata', e.response?.data?.message || 'Silinemedi.', 'error');
            }
        }
    });
};

// Aylık ücretten otomatik günlük ücret ve saat ücretlerini hesapla
const hesaplaUcretler = () => {
    const aylik = parseFloat(String(selectedPersonel.value.aylik_ucret).replace(',', '.')) || 0;
    if (aylik > 0) {
        const gunluk = aylik / 30;
        const saatlik = gunluk / 7.5;
        selectedPersonel.value.gunluk_ucret = gunluk.toFixed(2);
        selectedPersonel.value.saat_1 = saatlik.toFixed(2);
        selectedPersonel.value.saat_2 = (saatlik * 1.5).toFixed(2);
        selectedPersonel.value.saat_3 = (saatlik * 2.0).toFixed(2);
    } else {
        selectedPersonel.value.gunluk_ucret = '';
        selectedPersonel.value.saat_1 = '';
        selectedPersonel.value.saat_2 = '';
        selectedPersonel.value.saat_3 = '';
    }
};

const formatTarih = (t) => {
    if (!t) return '';
    return new Date(t).toLocaleDateString('tr-TR');
};
const formatTutar = (t) => {
    if (!t) return '0,00';
    return Number(t).toLocaleString('tr-TR', { minimumFractionDigits: 2 });
};

// Dakikayı saat:dakika formatına çevir
const dakikaToSaat = (dk) => {
    if (!dk || dk <= 0) return '0:00';
    const s = Math.floor(dk / 60);
    const d = dk % 60;
    return `${s}:${String(d).padStart(2, '0')}`;
};

// Toplam mesai saati
const mesaiToplamSaat = computed(() => {
    const toplam = filtreliMesailer.value.reduce((acc, m) => acc + (m.fazla_mesai_dakika || 0), 0);
    return dakikaToSaat(toplam);
});

// Resim yükleme
const resimInput = ref(null);
const dosyaInput = ref(null);
const isUploading = ref(false);

const triggerDosyaSec = () => {
    if (!selectedPersonel.value.id) {
        Swal.fire('Uyarı', 'Önce personeli kaydedin.', 'warning');
        return;
    }
    dosyaInput.value?.click();
};

// Tarih Filtresi
const now = new Date();
const filtreBaslangic = ref(new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0,10));
const filtreBitis = ref(new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0,10));
const aktifFiltre = ref('ay');

const filtreUygula = (tip) => {
    aktifFiltre.value = tip;
    const bugun = new Date();
    if (tip === 'gun') {
        const t = bugun.toISOString().slice(0,10);
        filtreBaslangic.value = t;
        filtreBitis.value = t;
    } else if (tip === 'hafta') {
        const day = bugun.getDay() || 7;
        const mon = new Date(bugun); mon.setDate(bugun.getDate() - day + 1);
        const sun = new Date(mon); sun.setDate(mon.getDate() + 6);
        filtreBaslangic.value = mon.toISOString().slice(0,10);
        filtreBitis.value = sun.toISOString().slice(0,10);
    } else if (tip === 'ay') {
        filtreBaslangic.value = new Date(bugun.getFullYear(), bugun.getMonth(), 1).toISOString().slice(0,10);
        filtreBitis.value = new Date(bugun.getFullYear(), bugun.getMonth() + 1, 0).toISOString().slice(0,10);
    } else if (tip === 'yil') {
        filtreBaslangic.value = bugun.getFullYear() + '-01-01';
        filtreBitis.value = bugun.getFullYear() + '-12-31';
    } else if (tip === 'tumu') {
        filtreBaslangic.value = '2020-01-01';
        filtreBitis.value = '2099-12-31';
    }
};

const tarihAralikIcinde = (tarihStr) => {
    if (!tarihStr) return false;
    const t = tarihStr.substring(0, 10);
    return t >= filtreBaslangic.value && t <= filtreBitis.value;
};

// Filtrelenmiş veriler
const filtreliPdksKayitlari = computed(() => {
    return (selectedPersonel.value.pdks_kayitlari || []).filter(k => tarihAralikIcinde(k.kayit_tarihi));
});
const filtreliMesailer = computed(() => {
    return (selectedPersonel.value.mesailer || []).filter(m => tarihAralikIcinde(m.tarih));
});
const filtreliIzinler = computed(() => {
    return (selectedPersonel.value.izinler || []).filter(iz => tarihAralikIcinde(iz.tarih));
});
const filtreliUcretliIzinler = computed(() => {
    return filtreliIzinler.value.filter(iz => !iz.izin_turu?.ad?.includes('Rapor') && !iz.izin_turu?.ucret_kesintisi_yapilacak_mi);
});
const filtreliUcretsizIzinler = computed(() => {
    return filtreliIzinler.value.filter(iz => !iz.izin_turu?.ad?.includes('Rapor') && iz.izin_turu?.ucret_kesintisi_yapilacak_mi);
});
const filtreliRaporlar = computed(() => {
    return filtreliIzinler.value.filter(iz => iz.izin_turu?.ad?.includes('Rapor'));
});
const filtreliAvanslar = computed(() => {
    return (selectedPersonel.value.avans_kesintiler || []).filter(a => tarihAralikIcinde(a.tarih));
});
const filtreliPrimler = computed(() => {
    return (selectedPersonel.value.prim_kazanclar || []).filter(p => tarihAralikIcinde(p.tarih));
});

// TC Kimlik No doğrulama
const tcNoDogrula = () => {
    const tc = selectedPersonel.value.tc_no;
    if (!tc) return;
    if (tc.length !== 11 || !/^\d{11}$/.test(tc) || tc[0] === '0') {
        Swal.fire('Uyarı', 'Geçersiz TC Kimlik No. 11 haneli ve 0 ile başlamamalı.', 'warning');
        return;
    }
    // TC algoritma kontrolü
    let tek = 0, cift = 0;
    for (let i = 0; i < 9; i++) { if (i % 2 === 0) tek += +tc[i]; else cift += +tc[i]; }
    const d10 = ((tek * 7) - cift) % 10;
    let toplam = 0; for (let i = 0; i < 10; i++) toplam += +tc[i];
    if (+tc[9] !== (d10 < 0 ? d10 + 10 : d10) || +tc[10] !== toplam % 10) {
        Swal.fire('Uyarı', 'TC Kimlik No algoritma kontrolünden geçemedi.', 'warning');
    }
};

// IBAN doğrulama
const ibanDogrula = () => {
    let iban = (selectedPersonel.value.iban_no || '').replace(/\s/g, '').toUpperCase();
    if (!iban) return;
    if (!iban.startsWith('TR')) { Swal.fire('Uyarı', 'IBAN "TR" ile başlamalı.', 'warning'); return; }
    if (iban.length !== 26) { Swal.fire('Uyarı', 'Türk IBAN 26 karakter olmalı (TR + 24 hane).', 'warning'); return; }
    selectedPersonel.value.iban_no = iban;
};

// Dosya yükleme
const dosyaYukle = async (event) => {
    const file = event.target.files[0];
    if (!file || !selectedPersonel.value.id) return;
    const formData = new FormData();
    formData.append('dosya', file);
    formData.append('personel_id', selectedPersonel.value.id);
    try {
        isUploading.value = true;
        await axios.post(route('tanim.personel-dosya.store'), formData, { headers: { 'Content-Type': 'multipart/form-data' } });
        await reloadPersonelDetail();
        Swal.fire({ title: 'Başarılı!', text: 'Dosya yüklendi.', icon: 'success', timer: 1500 });
    } catch (e) {
        let msg = e.response?.data?.message || 'Dosya yüklenemedi.';
        if (e.response?.data?.errors) {
            msg += '\n\n' + Object.values(e.response.data.errors).flat().join('\n');
        }
        Swal.fire('Hata', msg, 'error');
    } finally {
        isUploading.value = false;
        if (dosyaInput.value) dosyaInput.value.value = '';
    }
};

const dosyaSil = (d) => {
    Swal.fire({
        title: 'Dosya Sil', text: `"${d.dosya_adi}" silinecek. Emin misiniz?`,
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
        confirmButtonText: 'Sil', cancelButtonText: 'İptal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(route('tanim.personel-dosya.destroy', d.id));
                await reloadPersonelDetail();
                Swal.fire({ title: 'Silindi!', icon: 'success', timer: 1500 });
            } catch (e) { Swal.fire('Hata', 'Silinemedi.', 'error'); }
        }
    });
};

const formatBoyut = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const triggerResimSec = () => {
    if (!selectedPersonel.value.id) {
        Swal.fire('Uyarı', 'Önce personeli kaydedin.', 'warning');
        return;
    }
    resimInput.value.click();
};

const uploadResim = async (event) => {
    const file = event.target.files[0];
    if (!file) return;
    isUploading.value = true;
    const formData = new FormData();
    formData.append('resim', file);
    try {
        const response = await axios.post(route('personeller.resim-yukle', selectedPersonel.value.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        selectedPersonel.value.resim_yolu = response.data.resim_yolu;
        Swal.fire({ title: 'Başarılı!', text: 'Resim yüklendi.', icon: 'success', timer: 1500 });
    } catch (e) {
        Swal.fire('Hata', 'Resim yüklenirken hata oluştu.', 'error');
    } finally {
        isUploading.value = false;
    }
};
</script>

<template>
    <Head title="Personel Kartları" />
    <AuthenticatedLayout>
        <div class="p-4 h-full flex flex-col">
            <div class="bg-white border border-gray-400 shadow-md flex flex-col h-full">
                <!-- Pencere Başlığı -->
                <div class="bg-gradient-to-r from-[#d8e4f8] to-[#c0d0e8] border-b border-gray-400 px-4 py-2">
                    <h2 class="font-bold text-sm text-gray-800">Personel Kartları</h2>
                </div>

                <div class="flex flex-1 overflow-hidden">
                    <!-- SOL: Personel Listesi -->
                    <div class="w-56 border-r border-gray-400 flex flex-col bg-gray-50">
                        <div class="p-2 border-b border-gray-300">
                            <input v-model="searchText" type="text" placeholder="🔍 Ara..."
                                class="w-full border-gray-300 rounded-sm py-1 px-2 text-xs focus:ring-blue-400 focus:border-blue-400" />
                        </div>
                        <div class="flex-1 overflow-y-auto">
                            <div v-for="p in filteredPersoneller" :key="p.id"
                                @click="selectPersonel(p)"
                                class="px-2 py-1.5 text-xs cursor-pointer border-b border-gray-200 hover:bg-blue-50 transition flex items-center"
                                :class="{'!bg-blue-100 font-semibold border-l-2 border-l-blue-500': selectedPersonel.id === p.id}">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium truncate">{{ p.ad || '' }} {{ p.soyad || '' }}</div>
                                    <div class="text-[10px] text-gray-500">{{ p.kart_no || '-' }}</div>
                                </div>
                            </div>
                            <div v-if="filteredPersoneller.length === 0" class="p-4 text-center text-gray-400 text-xs">Personel bulunamadı</div>
                        </div>
                        <div class="p-1 border-t border-gray-300 bg-gray-100 text-center text-[10px] text-gray-500">
                            {{ filteredPersoneller.length }} personel
                        </div>
                    </div>

                    <!-- SAĞ: Detay Alanı -->
                    <div class="flex-1 flex flex-col overflow-hidden">
                        <!-- Üst Sekmeler -->
                        <div class="flex bg-gray-100 border-b border-gray-400">
                            <button @click="activeTab = 'ozluk'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'ozluk'}">Özlük Bilgileri</button>
                            <button @click="activeTab = 'giris_cikis'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'giris_cikis'}">Giriş-Çıkış</button>
                            <button @click="activeTab = 'izin'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'izin'}">İzin</button>
                            <button @click="activeTab = 'avans'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'avans'}">Avans Kesinti</button>
                            <button @click="activeTab = 'prim'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'prim'}">Prim Kazanç</button>
                            <button @click="activeTab = 'not'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'not'}">Notlar</button>
                            <button @click="activeTab = 'zimmet'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'zimmet'}">Zimmet</button>
                            <button @click="activeTab = 'mesai'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'mesai'}">Mesailer</button>
                            <button @click="activeTab = 'dosya'"
                                class="tab-btn" :class="{'tab-active': activeTab === 'dosya'}">Dosyalar</button>
                        </div>

                        <!-- Tarih Filtresi — tüm sekmeler için geçerli, sabit konum -->
                        <div class="flex items-center gap-1 px-3 py-1.5 bg-gray-50 border-b border-gray-300">
                            <div class="flex gap-0.5 mr-2">
                                <button @click="filtreUygula('gun')" class="px-2 py-0.5 text-[10px] rounded border transition"
                                    :class="aktifFiltre === 'gun' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">Bugün</button>
                                <button @click="filtreUygula('hafta')" class="px-2 py-0.5 text-[10px] rounded border transition"
                                    :class="aktifFiltre === 'hafta' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">Hafta</button>
                                <button @click="filtreUygula('ay')" class="px-2 py-0.5 text-[10px] rounded border transition"
                                    :class="aktifFiltre === 'ay' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">Ay</button>
                                <button @click="filtreUygula('yil')" class="px-2 py-0.5 text-[10px] rounded border transition"
                                    :class="aktifFiltre === 'yil' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">Yıl</button>
                                <button @click="filtreUygula('tumu')" class="px-2 py-0.5 text-[10px] rounded border transition"
                                    :class="aktifFiltre === 'tumu' ? 'bg-gray-700 text-white border-gray-700' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">Tümü</button>
                            </div>
                            <input v-model="filtreBaslangic" type="date" class="text-[10px] border border-gray-300 rounded px-1 py-0.5" @change="aktifFiltre = 'ozel'" />
                            <span class="text-[10px] text-gray-400">—</span>
                            <input v-model="filtreBitis" type="date" class="text-[10px] border border-gray-300 rounded px-1 py-0.5" @change="aktifFiltre = 'ozel'" />
                        </div>

                        <!-- İçerik -->
                        <div class="flex-1 overflow-y-auto p-3">
                            <!-- Loading -->
                            <div v-if="isLoading" class="flex items-center justify-center h-full">
                                <div class="text-gray-400 text-sm">Yükleniyor...</div>
                            </div>

                            <!-- ÖZLÜK -->
                            <div v-if="!isLoading && activeTab === 'ozluk'" class="space-y-3">
                                <!-- Alt Sekme -->
                                <div class="flex gap-1 mb-2">
                                    <button @click="activeSubTab = 'genel'" class="subtab-btn" :class="{'subtab-active': activeSubTab === 'genel'}">Genel</button>
                                    <button @click="activeSubTab = 'ozluk_sub'" class="subtab-btn" :class="{'subtab-active': activeSubTab === 'ozluk_sub'}">Özlük</button>
                                </div>

                                <div v-if="activeSubTab === 'genel'">
                                    <div class="flex gap-4">
                                        <!-- Resim Alanı -->
                                        <div class="flex flex-col items-center">
                                            <div class="w-28 h-32 border-2 border-dashed border-gray-300 rounded bg-gray-50 flex items-center justify-center overflow-hidden cursor-pointer hover:border-blue-400 transition" @click="triggerResimSec">
                                                <img v-if="selectedPersonel.resim_yolu" :src="'/' + selectedPersonel.resim_yolu" class="w-full h-full object-cover" />
                                                <div v-else class="text-center text-gray-400">
                                                    <svg class="w-10 h-10 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                    <span class="text-[9px]">Resim Ekle</span>
                                                </div>
                                            </div>
                                            <button @click="triggerResimSec" class="mt-1.5 text-[10px] text-blue-600 hover:text-blue-800 font-medium">{{ isUploading ? 'Yükleniyor...' : 'Fotoğraf Seç' }}</button>
                                            <input ref="resimInput" type="file" accept="image/*" class="hidden" @change="uploadResim" />
                                        </div>

                                        <!-- Form Alanları -->
                                        <div class="flex-1">
                                            <div class="grid grid-cols-4 gap-x-3 gap-y-2">
                                                <div class="form-group"><label>Kart No</label><input v-model="selectedPersonel.kart_no" class="form-input" /></div>
                                                <div class="form-group"><label>Sicil No</label><input v-model="selectedPersonel.sicil_no" class="form-input" /></div>
                                                <div class="form-group"><label>Ad</label><input v-model="selectedPersonel.ad" class="form-input" /></div>
                                                <div class="form-group"><label>Soyad</label><input v-model="selectedPersonel.soyad" class="form-input" /></div>
                                                <div class="form-group"><label>SSK No</label><input v-model="selectedPersonel.ssk_no" class="form-input" /></div>
                                                <div class="form-group"><label>Ünvan</label><input v-model="selectedPersonel.unvan" class="form-input" /></div>
                                                <div class="form-group">
                                                    <label>Şirket</label>
                                                    <select v-model="selectedPersonel.sirket" class="form-input">
                                                        <option value="">Seçiniz</option>
                                                        <option v-for="s in getTanimSecenekleri('sirket')" :key="s.kod" :value="s.aciklama">{{ s.kod }} - {{ s.aciklama }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Bölüm</label>
                                                    <select v-model="selectedPersonel.bolum" class="form-input">
                                                        <option value="">Seçiniz</option>
                                                        <option v-for="s in getTanimSecenekleri('bolum')" :key="s.kod" :value="s.aciklama">{{ s.kod }} - {{ s.aciklama }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Özel Kod</label>
                                                    <select v-model="selectedPersonel.ozel_kod" class="form-input">
                                                        <option value="">Seçiniz</option>
                                                        <option v-for="s in getTanimSecenekleri('odeme')" :key="s.kod" :value="s.aciklama">{{ s.kod }} - {{ s.aciklama }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Departman</label>
                                                    <select v-model="selectedPersonel.departman" class="form-input">
                                                        <option value="">Seçiniz</option>
                                                        <option v-for="s in getTanimSecenekleri('departman')" :key="s.kod" :value="s.aciklama">{{ s.kod }} - {{ s.aciklama }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Servis Kodu</label>
                                                    <select v-model="selectedPersonel.servis_kodu" class="form-input">
                                                        <option value="">Seçiniz</option>
                                                        <option v-for="s in getTanimSecenekleri('servis')" :key="s.kod" :value="s.aciklama">{{ s.kod }} - {{ s.aciklama }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Hesap Grubu</label>
                                                    <select v-model="selectedPersonel.hesap_gurubu" class="form-input">
                                                        <option value="">Seçiniz</option>
                                                        <option v-for="s in getTanimSecenekleri('hesap_gurubu')" :key="s.kod" :value="s.aciklama">{{ s.kod }} - {{ s.aciklama }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Puantaj Tablosu</label>
                                                    <select v-model="selectedPersonel.puantaj_parametre_id" class="form-input">
                                                        <option :value="null">Seçiniz</option>
                                                        <option v-for="p in gunlukPuantajParametreleri" :key="p.id" :value="p.id">{{ p.ad }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Aylık Hesap Parametresi</label>
                                                    <select v-model="selectedPersonel.aylik_puantaj_parametre_id" class="form-input">
                                                        <option :value="null">Seçiniz</option>
                                                        <option v-for="p in aylikPuantajParametreleri" :key="p.id" :value="p.id">{{ p.hesap_parametresi_adi }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group"><label>E-Posta</label><input v-model="selectedPersonel.email" type="email" class="form-input" placeholder="ornek@mail.com" /></div>
                                                <div class="form-group"><label>Telefon</label><input v-model="selectedPersonel.telefon" class="form-input" placeholder="05XX XXX XX XX" /></div>
                                                <div class="form-group"><label>Doğum Tarihi</label><input v-model="selectedPersonel.dogum_tarihi" type="date" class="form-input" /></div>
                                                <div class="form-group"><label>TC Kimlik No</label><input v-model="selectedPersonel.tc_no" class="form-input" maxlength="11" placeholder="11 haneli TC No" @blur="tcNoDogrula" /></div>
                                                <div class="form-group"><label>IBAN No</label><input v-model="selectedPersonel.iban_no" class="form-input" maxlength="34" placeholder="TR..." @blur="ibanDogrula" /></div>
                                                <div class="form-group"><label>Acil Kişi Adı</label><input v-model="selectedPersonel.acil_kisi_adi" class="form-input" placeholder="İsim Soyisim" /></div>
                                                <div class="form-group"><label>Acil Kişi Tel.</label><input v-model="selectedPersonel.acil_kisi_telefonu" class="form-input" placeholder="05XX XXX XX XX" /></div>
                                                <div class="form-group col-span-4"><label>Adres</label><input v-model="selectedPersonel.adres" class="form-input" placeholder="Açık adres" /></div>
                                                <div class="form-group col-span-2">
                                                    <label>Geç Kalma Bildirimi</label>
                                                    <div class="flex items-center mt-1">
                                                        <button @click="selectedPersonel.gec_kalma_bildirimi = !selectedPersonel.gec_kalma_bildirimi" type="button"
                                                            :class="selectedPersonel.gec_kalma_bildirimi ? 'bg-green-500' : 'bg-gray-300'"
                                                            class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out">
                                                            <span :class="selectedPersonel.gec_kalma_bildirimi ? 'translate-x-4' : 'translate-x-0'"
                                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out border border-gray-200"></span>
                                                        </button>
                                                        <span class="ml-2 text-[10px]" :class="selectedPersonel.gec_kalma_bildirimi ? 'text-green-600 font-semibold' : 'text-gray-500'">{{ selectedPersonel.gec_kalma_bildirimi ? 'Aktif - Mail/SMS gönderilecek' : 'Pasif' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="activeSubTab === 'ozluk_sub'">
                                    <div class="grid grid-cols-4 gap-x-3 gap-y-2">

                                        <div class="form-group">
                                            <label>Aylık Ücret</label>
                                            <input v-model.lazy="selectedPersonel.aylik_ucret" type="number" step="any" class="form-input text-right" placeholder="50000" @change="hesaplaUcretler" />
                                        </div>
                                        <div class="form-group">
                                            <label>Günlük Ücret <span class="text-[9px] text-gray-400">(aylık/30)</span></label>
                                            <input :value="selectedPersonel.gunluk_ucret" type="number" step="any" class="form-input text-right bg-gray-50" readonly />
                                        </div>
                                        <div class="form-group">
                                            <label>Saat 1 <span class="text-[9px] text-gray-400">(günlük/7.5)</span></label>
                                            <input :value="selectedPersonel.saat_1" type="number" step="any" class="form-input text-right bg-gray-50" readonly />
                                        </div>
                                        <div class="form-group">
                                            <label>Saat 2 <span class="text-[9px] text-gray-400">(x1.5)</span></label>
                                            <input :value="selectedPersonel.saat_2" type="number" step="any" class="form-input text-right bg-gray-50" readonly />
                                        </div>
                                        <div class="form-group">
                                            <label>Saat 3 <span class="text-[9px] text-gray-400">(x2.0)</span></label>
                                            <input :value="selectedPersonel.saat_3" type="number" step="any" class="form-input text-right bg-gray-50" readonly />
                                        </div>
                                        <div class="form-group"><label>Giriş Tarihi</label><input v-model="selectedPersonel.giris_tarihi" type="date" class="form-input" /></div>
                                        <div class="form-group"><label>Çıkış Tarihi</label><input v-model="selectedPersonel.cikis_tarihi" type="date" class="form-input" /></div>
                                        <div class="form-group">
                                            <label>Yemek Tipi</label>
                                            <select v-model="selectedPersonel.yemek_tipi" class="form-input">
                                                <option :value="null">— Yok —</option>
                                                <option value="kart">🎫 Yemek Kartı</option>
                                                <option value="ucret">💰 Yemek Ücreti</option>
                                            </select>
                                        </div>
                                        <div v-if="selectedPersonel.yemek_tipi === 'kart'" class="form-group"><label>Yemek Kart No</label><input v-model="selectedPersonel.yemek_kart_no" class="form-input" placeholder="1234-5678" /></div>
                                        <div v-if="selectedPersonel.yemek_tipi === 'ucret'" class="form-group"><label>Yemek Ücreti (₺/gün)</label><input v-model="selectedPersonel.yemek_ucreti" type="number" step="0.01" class="form-input text-right" placeholder="150.00" /></div>
                                        <div v-if="!selectedPersonel.yemek_tipi" class="form-group"></div>
                                        <div class="form-group">
                                            <label>Ulaşım Tipi</label>
                                            <select v-model="selectedPersonel.ulasim_tipi" class="form-input">
                                                <option :value="null">— Yok —</option>
                                                <option value="servis">🚌 Servis</option>
                                                <option value="yol_parasi">💵 Yol Parası</option>
                                            </select>
                                        </div>
                                        <div v-if="selectedPersonel.ulasim_tipi === 'servis'" class="form-group"><label>Servis Plaka</label><input v-model="selectedPersonel.servis_plaka" class="form-input" placeholder="34 ABC 123" /></div>
                                        <div v-if="selectedPersonel.ulasim_tipi === 'yol_parasi'" class="form-group"><label>Yol Parası (₺/gün)</label><input v-model="selectedPersonel.yol_parasi" type="number" step="0.01" class="form-input text-right" placeholder="75.00" /></div>
                                    </div>
                                </div>
                            </div>

                            <!-- GİRİŞ-ÇIKIŞ -->
                            <div v-else-if="activeTab === 'giris_cikis'">
                                <table class="data-table">
                                    <thead><tr>
                                        <th>Tarih</th><th>Saat</th><th>İşlem</th><th>Durum</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="k in filtreliPdksKayitlari" :key="k.id"
                                            :class="{'!bg-amber-50': k.izinli_mi}">
                                            <td>{{ formatTarih(k.kayit_tarihi) }}</td>
                                            <td>{{ k.izinli_mi ? '' : (k.kayit_tarihi ? k.kayit_tarihi.substring(11,16) : '') }}</td>
                                            <td>
                                                <span v-if="!k.izinli_mi" :class="k.islem_tipi === 'Giriş' ? 'text-green-600' : 'text-red-600'" class="font-semibold">{{ k.islem_tipi }}</span>
                                            </td>
                                            <td>
                                                <span v-if="k.izinli_mi" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-700 border border-orange-300">
                                                    ⚠ İZİNLİ <span v-if="k.izin_aciklama" class="font-normal">({{ k.izin_aciklama }})</span>
                                                </span>
                                                <span v-else class="text-green-600">Başarılı</span>
                                            </td>
                                        </tr>
                                        <tr v-if="!filtreliPdksKayitlari.length"><td colspan="4" class="text-center text-gray-400 py-6">Kayıt yok</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- İZİN -->
                            <div v-else-if="activeTab === 'izin'">
                                <!-- İzin Hakediş Özeti -->
                                <div v-if="selectedPersonel.izin_hakedis" class="mb-3 p-2 bg-green-50 rounded border border-green-200 text-xs">
                                    <div class="grid grid-cols-5 gap-2 text-center">
                                        <div><span class="text-gray-500">Kıdem</span><div class="font-bold text-green-700">{{ selectedPersonel.izin_hakedis.kidem_yil }} yıl</div></div>
                                        <div><span class="text-gray-500">Yıllık Hak</span><div class="font-bold text-blue-700">{{ selectedPersonel.izin_hakedis.yillik_hak }} gün</div></div>
                                        <div><span class="text-gray-500">Bu Yıl Kullanılan</span><div class="font-bold text-orange-600">{{ selectedPersonel.izin_hakedis.bu_yil_kullanilan }} gün</div></div>
                                        <div><span class="text-gray-500">Kalan</span><div class="font-bold text-lg" :class="selectedPersonel.izin_hakedis.kalan > 0 ? 'text-green-700' : 'text-red-600'">{{ selectedPersonel.izin_hakedis.kalan }} gün</div></div>
                                        <div><span class="text-gray-500">Toplam Kullanılan</span><div class="font-bold text-gray-600">{{ selectedPersonel.izin_hakedis.toplam_kullanilan }} gün</div></div>
                                    </div>
                                    <div class="mt-1 text-[10px] text-gray-400 text-center">İş Kanunu m.53 — 1-5 yıl: 14 gün, 5-15 yıl: 20 gün, 15+ yıl: 26 gün</div>
                                </div>

                                <div class="flex mb-2 px-3 gap-2">
                                    <button @click="activeIzinTab = 'ucretli'" class="px-3 py-1 text-xs border rounded transition shadow-sm" :class="activeIzinTab === 'ucretli' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">Ücretli İzinler</button>
                                    <button @click="activeIzinTab = 'ucretsiz'" class="px-3 py-1 text-xs border rounded transition shadow-sm" :class="activeIzinTab === 'ucretsiz' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">Ücretsiz İzinler</button>
                                    <button @click="activeIzinTab = 'rapor'" class="px-3 py-1 text-xs border rounded transition shadow-sm" :class="activeIzinTab === 'rapor' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">SSK Rapor Ödemeleri</button>
                                </div>

                                <table class="data-table mt-3">
                                    <thead><tr>
                                        <th>İzin Türü</th><th>Başlangıç</th><th>Bitiş</th><th>Gün</th><th>Tip</th><th>Durum</th><th>Açıklama</th><th v-if="activeIzinTab === 'rapor'">SSK Ödeme Tutarı</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="iz in (activeIzinTab === 'ucretli' ? filtreliUcretliIzinler : (activeIzinTab === 'ucretsiz' ? filtreliUcretsizIzinler : filtreliRaporlar))" :key="iz.id">
                                            <td class="font-medium">{{ iz.izin_turu?.ad || '-' }}</td>
                                            <td>{{ formatTarih(iz.tarih) }}</td>
                                            <td>{{ iz.bitis_tarihi ? formatTarih(iz.bitis_tarihi) : '-' }}</td>
                                            <td class="text-center">{{ iz.gun_sayisi }}</td>
                                            <td>{{ iz.izin_tipi === 'gunluk' ? 'Günlük' : 'Saatlik' }}</td>
                                            <td>
                                                <span :class="{
                                                    'bg-green-100 text-green-700 border-green-300': iz.durum === 'onaylandi',
                                                    'bg-yellow-100 text-yellow-700 border-yellow-300': iz.durum === 'beklemede',
                                                    'bg-red-100 text-red-700 border-red-300': iz.durum === 'reddedildi'
                                                }" class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold border">
                                                    {{ iz.durum === 'onaylandi' ? '✓ Onaylandı' : iz.durum === 'beklemede' ? '⏳ Beklemede' : '✗ Reddedildi' }}
                                                </span>
                                            </td>
                                            <td>{{ iz.aciklama || '-' }}</td>
                                            <td v-if="activeIzinTab === 'rapor'" class="font-bold text-green-700 text-right">{{ iz.ssk_odeme_tutari ? formatTutar(iz.ssk_odeme_tutari) : '-' }}</td>
                                        </tr>
                                        <tr v-if="(activeIzinTab === 'ucretli' && !filtreliUcretliIzinler.length) || (activeIzinTab === 'ucretsiz' && !filtreliUcretsizIzinler.length) || (activeIzinTab === 'rapor' && !filtreliRaporlar.length)">
                                            <td :colspan="activeIzinTab === 'rapor' ? 8 : 7" class="text-center text-gray-400 py-6">Kayıt bulunamadı</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- AVANS KESİNTİ -->
                            <div v-else-if="activeTab === 'avans'">
                                <table class="data-table">
                                    <thead><tr>
                                        <th>Tarih</th><th>Tutar</th><th>Taksit</th><th>Toplam Tutar</th><th>Açıklama</th><th>Bordro Alanı</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="a in filtreliAvanslar" :key="a.id">
                                            <td>{{ formatTarih(a.tarih) }}</td>
                                            <td class="text-right font-medium">{{ formatTutar(a.tutar) }}</td>
                                            <td class="text-center">{{ a.taksit_no && a.toplam_taksit ? a.taksit_no + '/' + a.toplam_taksit : '-' }}</td>
                                            <td class="text-right">{{ a.toplam_tutar ? formatTutar(a.toplam_tutar) : '-' }}</td>
                                            <td>{{ a.aciklama || '-' }}</td>
                                            <td>{{ a.bordro_alani || '-' }}</td>
                                        </tr>
                                        <tr v-if="!filtreliAvanslar.length"><td colspan="6" class="text-center text-gray-400 py-6">Avans/kesinti kaydı yok</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- PRİM KAZANÇ -->
                            <div v-else-if="activeTab === 'prim'">
                                <table class="data-table">
                                    <thead><tr>
                                        <th>Tarih</th><th>Tutar</th><th>Taksit</th><th>Toplam Tutar</th><th>Açıklama</th><th>Bordro Alanı</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="p in filtreliPrimler" :key="p.id">
                                            <td>{{ formatTarih(p.tarih) }}</td>
                                            <td class="text-right font-medium">{{ formatTutar(p.tutar) }}</td>
                                            <td class="text-center">{{ p.taksit_no && p.toplam_taksit ? p.taksit_no + '/' + p.toplam_taksit : '-' }}</td>
                                            <td class="text-right">{{ p.toplam_tutar ? formatTutar(p.toplam_tutar) : '-' }}</td>
                                            <td>{{ p.aciklama || '-' }}</td>
                                            <td>{{ p.bordro_alani || '-' }}</td>
                                        </tr>
                                        <tr v-if="!filtreliPrimler.length"><td colspan="6" class="text-center text-gray-400 py-6">Prim/kazanç kaydı yok</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- NOTLAR -->
                            <div v-else-if="activeTab === 'not'">
                                <textarea v-model="selectedPersonel.notlar" rows="12"
                                    class="w-full border-gray-300 rounded-sm text-xs p-2 focus:ring-blue-400 focus:border-blue-400"
                                    placeholder="Personel notları..."></textarea>
                            </div>

                            <!-- ZİMMET -->
                            <div v-else-if="activeTab === 'zimmet'">
                                <!-- Yeni Zimmet Ekleme Formu -->
                                <div class="mb-3 p-2 bg-blue-50 rounded border border-blue-200">
                                    <div class="grid grid-cols-5 gap-2">
                                        <div><input v-model="yeniZimmet.kategori" class="form-input text-xs" placeholder="Kategori (Bilgisayar, Telefon...)" /></div>
                                        <div><input v-model="yeniZimmet.bolum_adi" class="form-input text-xs" placeholder="Bölüm" /></div>
                                        <div><input v-model="yeniZimmet.aciklama" class="form-input text-xs" placeholder="Açıklama *" /></div>
                                        <div><input v-model="yeniZimmet.verilis_tarihi" type="date" class="form-input text-xs" /></div>
                                        <div>
                                            <button @click="zimmetEkle" class="w-full bg-blue-600 text-white text-xs px-3 py-1.5 rounded hover:bg-blue-700 transition">
                                                + Zimmet Ekle
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <table class="data-table">
                                    <thead><tr>
                                        <th>Kategori</th><th>Bölüm</th><th>Açıklama</th><th>Veriliş Tarihi</th><th>İade Tarihi</th><th class="w-24">İşlem</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="z in (selectedPersonel.zimmetler || [])" :key="z.id"
                                            :class="{'!bg-gray-100 text-gray-500': z.iade_tarihi}">
                                            <td>{{ z.kategori }}</td>
                                            <td>{{ z.bolum_adi || '-' }}</td>
                                            <td class="font-medium">{{ z.aciklama }}</td>
                                            <td>{{ formatTarih(z.verilis_tarihi) }}</td>
                                            <td>
                                                <span v-if="z.iade_tarihi" class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-200 text-gray-600 border border-gray-300">
                                                    ✓ {{ formatTarih(z.iade_tarihi) }}
                                                </span>
                                                <span v-else class="text-green-600 text-xs font-medium">Aktif</span>
                                            </td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <button v-if="!z.iade_tarihi" @click="zimmetIade(z)" class="text-orange-600 hover:text-orange-800 text-xs font-medium" title="İade Et">📤 İade</button>
                                                    <button @click="zimmetSil(z)" class="text-red-600 hover:text-red-800 text-xs font-medium" title="Sil">🗑</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!(selectedPersonel.zimmetler || []).length"><td colspan="6" class="text-center text-gray-400 py-6">Zimmet kaydı yok</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- MESAİLER -->
                            <div v-else-if="activeTab === 'mesai'">
                                <!-- Puantaj parametresi bilgisi -->
                                <div v-if="selectedPersonel.mesai_carpanlari" class="mb-3 p-2 bg-indigo-50 rounded border border-indigo-200 text-xs">
                                    <div class="font-bold text-indigo-700 mb-1">{{ selectedPersonel.mesai_carpanlari.parametre_adi }}</div>
                                    <div class="grid grid-cols-4 gap-2 text-gray-600">
                                        <div>Günlük: <strong>{{ selectedPersonel.mesai_carpanlari.gunluk_saat }} saat</strong></div>
                                        <div>Fazla Mesai: <strong class="text-orange-600">x{{ selectedPersonel.mesai_carpanlari.fazla_mesai }}</strong></div>
                                        <div>Tatil Mesai: <strong class="text-red-600">x{{ selectedPersonel.mesai_carpanlari.tatil_mesai }}</strong></div>
                                        <div>Resmi Tatil: <strong class="text-red-700">x{{ selectedPersonel.mesai_carpanlari.resmi_tatil_mesai }}</strong></div>
                                    </div>
                                </div>
                                <div v-else class="mb-3 p-2 bg-yellow-50 rounded border border-yellow-300 text-xs text-yellow-700">
                                    ⚠ Puantaj parametresi atanmamış — mesai hesaplaması yapılamaz.
                                </div>

                                <!-- Manuel Mesai Ekleme Formu -->
                                <div class="mb-3 p-2 bg-orange-50 rounded border border-orange-200">
                                    <div class="grid grid-cols-6 gap-2 items-end">
                                        <div><label class="text-[10px] text-gray-500">Tarih</label><input v-model="yeniMesai.tarih" type="date" class="form-input text-xs" /></div>
                                        <div><label class="text-[10px] text-gray-500">Giriş</label><input v-model="yeniMesai.ilk_giris" type="time" class="form-input text-xs" /></div>
                                        <div><label class="text-[10px] text-gray-500">Çıkış</label><input v-model="yeniMesai.son_cikis" type="time" class="form-input text-xs" /></div>
                                        <div><label class="text-[10px] text-gray-500">Çalışma (dk)</label><input v-model="yeniMesai.toplam_calisma_suresi" type="number" class="form-input text-xs" /></div>
                                        <div><label class="text-[10px] text-gray-500">Fazla Mesai (dk)*</label><input v-model="yeniMesai.fazla_mesai_dakika" type="number" class="form-input text-xs" /></div>
                                        <div>
                                            <button @click="mesaiEkle" class="w-full bg-orange-600 text-white text-xs px-3 py-1.5 rounded hover:bg-orange-700 transition">
                                                + Mesai Ekle
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Toplam mesai özeti -->
                                <div v-if="(selectedPersonel.mesailer || []).length" class="mb-2 text-xs text-gray-500">
                                    Toplam: <strong class="text-indigo-700">{{ mesaiToplamSaat }}</strong> saat fazla mesai
                                    ({{ (selectedPersonel.mesailer || []).length }} gün)
                                </div>

                                <table class="data-table">
                                    <thead><tr>
                                        <th>Tarih</th><th>Giriş</th><th>Çıkış</th><th>Toplam Çalışma</th><th>Fazla Mesai</th><th>Durum</th><th class="w-20">İşlem</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="m in filtreliMesailer" :key="m.id">
                                            <td>{{ formatTarih(m.tarih) }}</td>
                                            <td>{{ m.ilk_giris ? m.ilk_giris.substring(11, 16) : '-' }}</td>
                                            <td>{{ m.son_cikis ? m.son_cikis.substring(11, 16) : '-' }}</td>
                                            <td class="text-center">{{ dakikaToSaat(m.toplam_calisma_suresi) }}</td>
                                            <td class="text-center">
                                                <input :value="m.fazla_mesai_dakika" type="number" class="w-16 text-center text-xs border rounded px-1 py-0.5 font-bold text-orange-600"
                                                    @change="mesaiGuncelle(m, $event.target.value)" title="Fazla mesai dakikasını düzenle" />
                                            </td>
                                            <td>
                                                <span :class="{
                                                    'bg-green-100 text-green-700': m.durum === 'geldi',
                                                    'bg-yellow-100 text-yellow-700': m.durum === 'geç kaldı',
                                                    'bg-red-100 text-red-700': m.durum === 'erken_cikis'
                                                }" class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold border">
                                                    {{ m.durum }}
                                                </span>
                                            </td>
                                            <td>
                                                <button @click="mesaiSil(m)" class="text-red-600 hover:text-red-800 text-xs font-medium" title="Sil">🗑</button>
                                            </td>
                                        </tr>
                                        <tr v-if="!filtreliMesailer.length"><td colspan="7" class="text-center text-gray-400 py-6">Fazla mesai kaydı yok</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- DOSYALAR -->
                            <div v-else-if="activeTab === 'dosya'">
                                <div class="mb-3 flex items-center gap-3">
                                    <input type="file" ref="dosyaInput" @change="dosyaYukle" style="display:none" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" />
                                    <button @click="triggerDosyaSec" class="bg-blue-600 text-white text-xs px-4 py-1.5 rounded hover:bg-blue-700 transition" :disabled="isUploading || !selectedPersonel.id">
                                        {{ isUploading ? 'Yükleniyor...' : '📁 Dosya Yükle' }}
                                    </button>
                                    <span class="text-[10px] text-gray-400">PDF, JPG, PNG, DOC, DOCX, XLS, XLSX — Max 10MB</span>
                                </div>
                                <table class="data-table">
                                    <thead><tr>
                                        <th>Dosya Adı</th><th>Tür</th><th>Boyut</th><th>Yüklenme Tarihi</th><th class="w-24">İşlem</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="d in (selectedPersonel.dosyalar || [])" :key="d.id">
                                            <td class="font-medium">{{ d.dosya_adi }}</td>
                                            <td><span class="uppercase text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-bold">{{ d.dosya_tipi }}</span></td>
                                            <td>{{ formatBoyut(d.boyut) }}</td>
                                            <td>{{ formatTarih(d.created_at) }}</td>
                                            <td>
                                                <div class="flex gap-2">
                                                    <a :href="d.url" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs font-medium" title="Görüntüle">👁</a>
                                                    <button @click="dosyaSil(d)" class="text-red-600 hover:text-red-800 text-xs font-medium" title="Sil">🗑</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!(selectedPersonel.dosyalar || []).length"><td colspan="5" class="text-center text-gray-400 py-6">Dosya yok</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Alt Butonlar -->
                        <div class="flex items-center justify-end gap-1 px-3 py-2 bg-gray-100 border-t border-gray-400">
                            <button @click="newPersonel" class="win-btn" title="Yeni Personel">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </button>
                            <button @click="savePersonel" :disabled="isSaving" class="win-btn" title="Kaydet">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            </button>
                            <button class="win-btn" title="Yazdır">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            </button>
                            <button @click="savePersonel" class="win-btn" title="Onayla">
                                <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </button>
                            <button @click="deletePersonel" class="win-btn" title="Sil">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                            <button class="win-btn" title="Excel">
                                <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.tab-btn {
    @apply px-3 py-1.5 text-xs font-medium border-b-2 border-transparent text-gray-600 hover:text-blue-700 hover:bg-blue-50 transition cursor-pointer;
}
.tab-active {
    @apply !text-blue-700 !border-blue-600 bg-white font-bold;
}
.subtab-btn {
    @apply px-3 py-1 text-[11px] border border-gray-300 rounded-sm bg-gray-50 text-gray-600 hover:bg-blue-50 cursor-pointer transition;
}
.subtab-active {
    @apply !bg-blue-600 !text-white !border-blue-600;
}
.form-group {
    @apply flex flex-col;
}
.form-group label {
    @apply text-[10px] font-semibold text-gray-500 mb-0.5 uppercase tracking-wide;
}
.form-input {
    @apply border-gray-300 rounded-sm py-1 px-2 text-xs focus:ring-blue-400 focus:border-blue-400;
}
.data-table {
    @apply w-full text-xs border-collapse;
}
.data-table thead {
    @apply bg-[#d0dcea] sticky top-0;
}
.data-table th {
    @apply py-1.5 px-2 text-left border border-gray-400 font-bold text-gray-700;
}
.data-table td {
    @apply py-1 px-2 border-r border-gray-200;
}
.data-table tbody tr {
    @apply border-b border-gray-200 hover:bg-blue-50 transition-colors;
}
.win-btn {
    @apply w-8 h-8 flex items-center justify-center bg-white border border-gray-400 rounded-sm hover:bg-gray-100 shadow-sm cursor-pointer transition;
}
</style>
