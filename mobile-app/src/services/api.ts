import * as SecureStore from 'expo-secure-store';

// ⚠️ Development: artisan serve (0.0.0.0:8000)
// ⚠️ Production'da gerçek sunucu adresini yazın (örn: https://pdks.yourdomain.com/api/mobil)
const API_BASE = __DEV__
    ? 'http://192.168.1.108:8000/api/mobil'
    : 'https://pdks.yourdomain.com/api/mobil';

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'DELETE';

interface ApiResponse<T = any> {
    hata: boolean;
    mesaj?: string;
    [key: string]: T | boolean | string | undefined;
}

class ApiService {
    private token: string | null = null;

    async init(): Promise<void> {
        this.token = await SecureStore.getItemAsync('auth_token');
    }

    async setToken(token: string): Promise<void> {
        this.token = token;
        await SecureStore.setItemAsync('auth_token', token);
    }

    async clearToken(): Promise<void> {
        this.token = null;
        await SecureStore.deleteItemAsync('auth_token');
    }

    async getToken(): Promise<string | null> {
        if (!this.token) {
            this.token = await SecureStore.getItemAsync('auth_token');
        }
        return this.token;
    }

    private async request<T>(method: HttpMethod, endpoint: string, body?: object): Promise<T> {
        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        const config: RequestInit = { method, headers };
        if (body && method !== 'GET') {
            config.body = JSON.stringify(body);
        }

        const response = await fetch(`${API_BASE}${endpoint}`, config);
        const data = await response.json();

        if (!response.ok) {
            throw { status: response.status, ...data };
        }

        return data as T;
    }

    // ═══ AUTH ═══
    async firmaLogo(firmaKodu: string) {
        return this.request<any>('GET', `/firma-logo/${firmaKodu.toUpperCase()}`);
    }

    async giris(firmaKodu: string, tcNo: string, sifre: string, cihazBilgi?: object) {
        const data = await this.request<any>('POST', '/giris', {
            firma_kodu: firmaKodu,
            tc_no: tcNo,
            sifre,
            ...cihazBilgi,
        });

        if (!data.hata && data.token) {
            await this.setToken(data.token);
        }
        return data;
    }

    async cikisYap() {
        await this.clearToken();
    }

    // ═══ GİRİŞ/ÇIKIŞ ═══
    async hareketKaydet(tip: 'giris' | 'cikis', konum: { enlem: number; boylam: number }, yontem: string = 'gps', ekstra?: object) {
        return this.request('POST', '/hareket', {
            tip,
            enlem: konum.enlem,
            boylam: konum.boylam,
            dogrulama_yontemi: yontem,
            ...ekstra,
        });
    }

    // ═══ VERİ ═══
    async bugunDurum() {
        return this.request('GET', '/bugun');
    }

    async gecmis() {
        return this.request('GET', '/gecmis');
    }

    async profil() {
        return this.request('GET', '/profil');
    }

    async sifreDegistir(mevcutSifre: string, yeniSifre: string) {
        return this.request('POST', '/sifre-degistir', {
            mevcut_sifre: mevcutSifre,
            yeni_sifre: yeniSifre,
            yeni_sifre_confirmation: yeniSifre,
        });
    }

    // ═══ YENİ MODÜLLER ═══
    async izinlerim() {
        return this.request('GET', '/izinlerim');
    }

    async izinTalebi(data: { izin_turu_id: number; tarih: string; bitis_tarihi?: string; aciklama?: string }) {
        return this.request('POST', '/izin-talebi', data);
    }

    async izinTurleri() {
        return this.request('GET', '/izin-turleri');
    }

    async puantajOzeti(ay?: number, yil?: number) {
        const params = new URLSearchParams();
        if (ay) params.append('ay', ay.toString());
        if (yil) params.append('yil', yil.toString());
        const qs = params.toString() ? `?${params.toString()}` : '';
        return this.request('GET', `/puantaj-ozeti${qs}`);
    }

    async vardiyaTakvimi(ay?: number, yil?: number) {
        const params = new URLSearchParams();
        if (ay) params.append('ay', ay.toString());
        if (yil) params.append('yil', yil.toString());
        const qs = params.toString() ? `?${params.toString()}` : '';
        return this.request('GET', `/vardiya-takvimi${qs}`);
    }

    async belgelerim() {
        return this.request('GET', '/belgelerim');
    }

    async bordroOzeti(ay?: number, yil?: number) {
        const params = new URLSearchParams();
        if (ay) params.append('ay', ay.toString());
        if (yil) params.append('yil', yil.toString());
        const qs = params.toString() ? `?${params.toString()}` : '';
        return this.request('GET', `/bordro-ozeti${qs}`);
    }
}

export const api = new ApiService();
export default api;
