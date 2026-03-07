import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, Alert, TextInput, ScrollView } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import api from '../services/api';

export default function ProfileScreen({ user, firma, onLogout }: { user: any; firma: any; onLogout: () => void }) {
    const [profil, setProfil] = useState<any>(null);
    const [showSifreDegistir, setShowSifreDegistir] = useState(false);
    const [mevcutSifre, setMevcutSifre] = useState('');
    const [yeniSifre, setYeniSifre] = useState('');

    useEffect(() => {
        loadProfil();
    }, []);

    const loadProfil = async () => {
        try {
            const data: any = await api.profil();
            setProfil(data.personel);
        } catch (err) { }
    };

    const handleSifreDegistir = async () => {
        if (!mevcutSifre || !yeniSifre || yeniSifre.length < 6) {
            Alert.alert('Uyarı', 'Yeni şifre en az 6 karakter olmalıdır.');
            return;
        }
        try {
            await api.sifreDegistir(mevcutSifre, yeniSifre);
            Alert.alert('Başarılı', 'Şifreniz değiştirildi.');
            setShowSifreDegistir(false);
            setMevcutSifre('');
            setYeniSifre('');
        } catch (err: any) {
            Alert.alert('Hata', err.mesaj || 'Şifre değiştirilemedi.');
        }
    };

    const p = profil || user;

    const bilgiler = [
        { icon: 'person', label: 'Ad Soyad', value: `${p?.ad || ''} ${p?.soyad || ''}` },
        { icon: 'card', label: 'Sicil No', value: p?.sicil_no || p?.kart_no || '—' },
        { icon: 'briefcase', label: 'Departman', value: p?.departman || '—' },
        { icon: 'medal', label: 'Görev', value: p?.gorev || p?.pozisyon || '—' },
        { icon: 'business', label: 'Firma', value: firma?.firma_adi || '—' },
    ];

    return (
        <ScrollView style={styles.container} contentContainerStyle={{ paddingBottom: 100 }}>
            <Text style={styles.header}>👤 Profilim</Text>

            {/* Avatar */}
            <View style={styles.avatarSection}>
                <View style={styles.avatar}>
                    <Text style={styles.avatarText}>
                        {(p?.ad?.[0] || '?').toUpperCase()}{(p?.soyad?.[0] || '').toUpperCase()}
                    </Text>
                </View>
                <Text style={styles.avatarName}>{p?.ad} {p?.soyad}</Text>
            </View>

            {/* Bilgiler */}
            <View style={styles.infoSection}>
                {bilgiler.map((b, i) => (
                    <View key={i} style={styles.infoRow}>
                        <Ionicons name={`${b.icon}-outline` as any} size={18} color="#A78BFA" />
                        <View style={styles.infoContent}>
                            <Text style={styles.infoLabel}>{b.label}</Text>
                            <Text style={styles.infoValue}>{b.value}</Text>
                        </View>
                    </View>
                ))}
            </View>

            {/* Şifre Değiştir */}
            <TouchableOpacity style={styles.menuItem} onPress={() => setShowSifreDegistir(!showSifreDegistir)}>
                <Ionicons name="key-outline" size={20} color="#F59E0B" />
                <Text style={styles.menuItemText}>Şifre Değiştir</Text>
                <Ionicons name={showSifreDegistir ? 'chevron-up' : 'chevron-down'} size={18} color="#6B5B8D" />
            </TouchableOpacity>

            {showSifreDegistir && (
                <View style={styles.sifreForm}>
                    <TextInput style={styles.sifreInput} placeholder="Mevcut Şifre" secureTextEntry value={mevcutSifre} onChangeText={setMevcutSifre} placeholderTextColor="#6B5B8D" />
                    <TextInput style={styles.sifreInput} placeholder="Yeni Şifre (min 6 karakter)" secureTextEntry value={yeniSifre} onChangeText={setYeniSifre} placeholderTextColor="#6B5B8D" />
                    <TouchableOpacity style={styles.sifreBtn} onPress={handleSifreDegistir}>
                        <Text style={styles.sifreBtnText}>Şifreyi Güncelle</Text>
                    </TouchableOpacity>
                </View>
            )}

            {/* Çıkış */}
            <TouchableOpacity style={styles.logoutBtn} onPress={() => {
                Alert.alert('Çıkış', 'Oturumu kapatmak istiyor musunuz?', [
                    { text: 'İptal', style: 'cancel' },
                    { text: 'Çıkış Yap', style: 'destructive', onPress: onLogout },
                ]);
            }}>
                <Ionicons name="log-out-outline" size={20} color="#EF4444" />
                <Text style={styles.logoutText}>Oturumu Kapat</Text>
            </TouchableOpacity>

            <Text style={styles.version}>PDKSPro v1.0.0</Text>
        </ScrollView>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#0F0A1E', paddingHorizontal: 20, paddingTop: 50 },
    header: { fontSize: 20, fontWeight: '800', color: '#fff', marginBottom: 20 },
    avatarSection: { alignItems: 'center', marginBottom: 28 },
    avatar: { width: 80, height: 80, borderRadius: 40, backgroundColor: '#7C3AED', justifyContent: 'center', alignItems: 'center', marginBottom: 10 },
    avatarText: { color: '#fff', fontSize: 28, fontWeight: '800' },
    avatarName: { color: '#fff', fontSize: 18, fontWeight: '700' },
    infoSection: { backgroundColor: '#1A1230', borderRadius: 16, padding: 16, gap: 14, marginBottom: 16 },
    infoRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
    infoContent: { flex: 1 },
    infoLabel: { color: '#6B5B8D', fontSize: 11 },
    infoValue: { color: '#fff', fontSize: 14, fontWeight: '600' },
    menuItem: { backgroundColor: '#1A1230', borderRadius: 14, padding: 16, flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 8 },
    menuItemText: { flex: 1, color: '#fff', fontSize: 14, fontWeight: '600' },
    sifreForm: { backgroundColor: '#1A1230', borderRadius: 14, padding: 16, gap: 10, marginBottom: 8 },
    sifreInput: { backgroundColor: '#2D2050', borderRadius: 10, padding: 12, color: '#fff', fontSize: 14 },
    sifreBtn: { backgroundColor: '#F59E0B', borderRadius: 10, padding: 14, alignItems: 'center' },
    sifreBtnText: { color: '#000', fontWeight: '700', fontSize: 14 },
    logoutBtn: { backgroundColor: '#1A1230', borderRadius: 14, padding: 16, flexDirection: 'row', alignItems: 'center', gap: 10, marginTop: 8, borderWidth: 1, borderColor: '#3B1C1C' },
    logoutText: { color: '#EF4444', fontSize: 14, fontWeight: '600' },
    version: { textAlign: 'center', color: '#3D2D6B', fontSize: 11, marginTop: 20 },
});
