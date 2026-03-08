import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, Image, Linking } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as Location from 'expo-location';
import api from '../services/api';

export default function HomeScreen({ user, firma, onLogout }: { user: any; firma: any; onLogout: () => void }) {
    const [loading, setLoading] = useState(false);
    const [durum, setDurum] = useState<any>(null);
    const [saat, setSaat] = useState(new Date());

    useEffect(() => {
        const timer = setInterval(() => setSaat(new Date()), 1000);
        loadDurum();
        return () => clearInterval(timer);
    }, []);

    const loadDurum = async () => {
        try {
            const data: any = await api.bugunDurum();
            setDurum(data);
        } catch (err) {
            console.log('Durum yüklenemedi');
        }
    };

    const getKonum = async (): Promise<{ enlem: number; boylam: number } | null> => {
        // GPS açık mı kontrol et
        const gpsAcik = await Location.hasServicesEnabledAsync();
        if (!gpsAcik) {
            Alert.alert(
                'Konum Kapalı',
                'Telefonunuzun konum (GPS) özelliği kapalı. Lütfen ayarlardan açın.',
                [
                    { text: 'İptal', style: 'cancel' },
                    { text: 'Ayarlara Git', onPress: () => Linking.openSettings() }
                ]
            );
            return null;
        }

        const { status } = await Location.requestForegroundPermissionsAsync();
        if (status !== 'granted') {
            Alert.alert('İzin Reddedildi', 'Giriş/çıkış için konuma erişim izni vermelisiniz.');
            return null;
        }

        try {
            const loc = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
            return { enlem: loc.coords.latitude, boylam: loc.coords.longitude };
        } catch (e) {
            Alert.alert('Hata', 'Konumunuz alınamadı. Yüksek binalar arasında veya kapalı alanda olabilirsiniz.');
            return null;
        }
    };

    const hareketKaydet = async (tip: 'giris' | 'cikis') => {
        setLoading(true);
        try {
            const konum = await getKonum();
            if (!konum && firma?.gps_zorunlu) {
                setLoading(false);
                return;
            }

            const data: any = await api.hareketKaydet(tip, konum || { enlem: 0, boylam: 0 });
            Alert.alert(
                tip === 'giris' ? '✅ Giriş Başarılı' : '🔴 Çıkış Başarılı',
                `${data.mesaj}\nSaat: ${data.zaman}${data.mesafe ? `\nMesafe: ${data.mesafe}m` : ''}`
            );
            loadDurum();
        } catch (err: any) {
            Alert.alert('Hata', err.mesaj || 'İşlem başarısız.');
        } finally {
            setLoading(false);
        }
    };

    const formatSaat = (date: Date) => date.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const formatTarih = (date: Date) => date.toLocaleDateString('tr-TR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

    const sonGiris = durum?.son_giris ? new Date(durum.son_giris).toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' }) : null;
    const sonCikis = durum?.son_cikis ? new Date(durum.son_cikis).toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' }) : null;

    return (
        <View style={styles.container}>
            {/* Üst Bilgi */}
            <View style={styles.topBar}>
                <View style={styles.userInfoBlock}>
                    {firma?.logo ? (
                        <View style={styles.logoBox}>
                            <Image source={{ uri: firma.logo }} style={styles.firmaLogo} resizeMode="contain" />
                        </View>
                    ) : (
                        <View style={styles.logoFallback}>
                            <Ionicons name="business" size={24} color="#7C3AED" />
                        </View>
                    )}
                    <View>
                        <Text style={styles.greeting}>Merhaba,</Text>
                        <Text style={styles.userName}>{user?.ad} {user?.soyad}</Text>
                        <Text style={styles.firmName}>{firma?.firma_adi}</Text>
                    </View>
                </View>
                <TouchableOpacity onPress={onLogout} style={styles.logoutBtn}>
                    <Ionicons name="log-out-outline" size={22} color="#EF4444" />
                </TouchableOpacity>
            </View>

            {/* Saat */}
            <View style={styles.clockSection}>
                <Text style={styles.clock}>{formatSaat(saat)}</Text>
                <Text style={styles.date}>{formatTarih(saat)}</Text>
            </View>

            {/* Durum Kartları */}
            <View style={styles.statusCards}>
                <View style={[styles.statusCard, { borderLeftColor: '#10B981' }]}>
                    <View style={styles.statusCardInner}>
                        <Ionicons name="log-in" size={22} color="#10B981" />
                        <View>
                            <Text style={styles.statusLabel}>Son Giriş</Text>
                            <Text style={styles.statusValue}>{sonGiris || '—:—'}</Text>
                        </View>
                    </View>
                </View>
                <View style={[styles.statusCard, { borderLeftColor: '#EF4444' }]}>
                    <View style={styles.statusCardInner}>
                        <Ionicons name="log-out" size={22} color="#EF4444" />
                        <View>
                            <Text style={styles.statusLabel}>Son Çıkış</Text>
                            <Text style={styles.statusValue}>{sonCikis || '—:—'}</Text>
                        </View>
                    </View>
                </View>
            </View>

            {/* Giriş/Çıkış Butonları */}
            <View style={styles.actionButtons}>
                <TouchableOpacity
                    style={[styles.actionBtn, styles.girisBtn]}
                    onPress={() => hareketKaydet('giris')}
                    disabled={loading}
                >
                    {loading ? <ActivityIndicator color="#fff" size="large" /> : (
                        <>
                            <View style={styles.actionBtnIcon}>
                                <Ionicons name="finger-print" size={40} color="#fff" />
                            </View>
                            <Text style={styles.actionBtnText}>GİRİŞ YAP</Text>
                            <Text style={styles.actionBtnHint}>📍 GPS ile konum doğrulama</Text>
                        </>
                    )}
                </TouchableOpacity>

                <TouchableOpacity
                    style={[styles.actionBtn, styles.cikisBtn]}
                    onPress={() => hareketKaydet('cikis')}
                    disabled={loading}
                >
                    {loading ? <ActivityIndicator color="#fff" size="large" /> : (
                        <>
                            <View style={styles.actionBtnIcon}>
                                <Ionicons name="exit-outline" size={40} color="#fff" />
                            </View>
                            <Text style={styles.actionBtnText}>ÇIKIŞ YAP</Text>
                            <Text style={styles.actionBtnHint}>📍 GPS ile konum doğrulama</Text>
                        </>
                    )}
                </TouchableOpacity>
            </View>

            {/* Alt Bilgi */}
            <Text style={styles.footerInfo}>
                {durum?.toplam_hareket ? `Bugün ${durum.toplam_hareket} hareket kaydedildi` : 'Bugün henüz hareket yok'}
            </Text>
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#0F0A1E', paddingHorizontal: 20, paddingTop: 50 },
    topBar: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 20 },
    userInfoBlock: { flexDirection: 'row', alignItems: 'center', gap: 12 },
    logoBox: { backgroundColor: '#fff', borderRadius: 10, paddingHorizontal: 8, paddingVertical: 4 },
    firmaLogo: { width: 36, height: 32 },
    logoFallback: { width: 44, height: 44, borderRadius: 8, backgroundColor: '#2D1B3E', justifyContent: 'center', alignItems: 'center' },
    greeting: { color: '#A78BFA', fontSize: 13 },
    userName: { color: '#fff', fontSize: 18, fontWeight: '800' },
    firmName: { color: '#6B5B8D', fontSize: 11, marginTop: 2 },
    logoutBtn: { backgroundColor: '#2D1B3E', padding: 10, borderRadius: 12 },
    clockSection: { alignItems: 'center', marginBottom: 24 },
    clock: { fontSize: 52, fontWeight: '200', color: '#fff', letterSpacing: 4, fontVariant: ['tabular-nums'] },
    date: { fontSize: 13, color: '#A78BFA', marginTop: 4 },
    statusCards: { flexDirection: 'row', gap: 12, marginBottom: 28 },
    statusCard: { flex: 1, backgroundColor: '#1A1230', borderRadius: 14, padding: 14, borderLeftWidth: 3 },
    statusCardInner: { flexDirection: 'row', alignItems: 'center', gap: 10 },
    statusLabel: { color: '#6B5B8D', fontSize: 11 },
    statusValue: { color: '#fff', fontSize: 20, fontWeight: '700', fontVariant: ['tabular-nums'] },
    actionButtons: { flexDirection: 'row', gap: 14, flex: 1 },
    actionBtn: { flex: 1, borderRadius: 20, justifyContent: 'center', alignItems: 'center', paddingVertical: 30, shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.4, shadowRadius: 16, elevation: 10 },
    girisBtn: { backgroundColor: '#059669', shadowColor: '#059669' },
    cikisBtn: { backgroundColor: '#DC2626', shadowColor: '#DC2626' },
    actionBtnIcon: { width: 72, height: 72, borderRadius: 36, backgroundColor: 'rgba(255,255,255,0.15)', justifyContent: 'center', alignItems: 'center', marginBottom: 12 },
    actionBtnText: { color: '#fff', fontSize: 16, fontWeight: '800', letterSpacing: 2 },
    actionBtnHint: { color: 'rgba(255,255,255,0.5)', fontSize: 10, marginTop: 6 },
    footerInfo: { textAlign: 'center', color: '#4B3F6B', fontSize: 11, paddingBottom: 30, marginTop: 16 },
});
