import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, KeyboardAvoidingView, Platform, ActivityIndicator } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import api from '../services/api';

export default function LoginScreen({ onLogin }: { onLogin: (data: any) => void }) {
    const [firmaKodu, setFirmaKodu] = useState('');
    const [tcNo, setTcNo] = useState('');
    const [sifre, setSifre] = useState('');
    const [loading, setLoading] = useState(false);
    const [showSifre, setShowSifre] = useState(false);

    const handleLogin = async () => {
        if (!firmaKodu || !tcNo || !sifre) {
            Alert.alert('Uyarı', 'Lütfen tüm alanları doldurun.');
            return;
        }

        setLoading(true);
        try {
            const data = await api.giris(firmaKodu.toUpperCase(), tcNo, sifre);
            if (!data.hata) {
                onLogin(data);
            }
        } catch (err: any) {
            Alert.alert('Hata', err.mesaj || 'Giriş yapılamadı.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
            <View style={styles.inner}>
                {/* Logo / Header */}
                <View style={styles.header}>
                    <View style={styles.logoCircle}>
                        <Ionicons name="finger-print" size={48} color="#fff" />
                    </View>
                    <Text style={styles.title}>PDKSPro</Text>
                    <Text style={styles.subtitle}>Personel Devam Kontrol Sistemi</Text>
                </View>

                {/* Form */}
                <View style={styles.form}>
                    <View style={styles.inputGroup}>
                        <Ionicons name="business-outline" size={20} color="#8B5CF6" style={styles.inputIcon} />
                        <TextInput
                            style={styles.input}
                            placeholder="Firma Kodu"
                            value={firmaKodu}
                            onChangeText={setFirmaKodu}
                            autoCapitalize="characters"
                            placeholderTextColor="#9CA3AF"
                        />
                    </View>

                    <View style={styles.inputGroup}>
                        <Ionicons name="card-outline" size={20} color="#8B5CF6" style={styles.inputIcon} />
                        <TextInput
                            style={styles.input}
                            placeholder="TC Kimlik No"
                            value={tcNo}
                            onChangeText={setTcNo}
                            keyboardType="numeric"
                            maxLength={11}
                            placeholderTextColor="#9CA3AF"
                        />
                    </View>

                    <View style={styles.inputGroup}>
                        <Ionicons name="lock-closed-outline" size={20} color="#8B5CF6" style={styles.inputIcon} />
                        <TextInput
                            style={[styles.input, { flex: 1 }]}
                            placeholder="Şifre"
                            value={sifre}
                            onChangeText={setSifre}
                            secureTextEntry={!showSifre}
                            placeholderTextColor="#9CA3AF"
                        />
                        <TouchableOpacity onPress={() => setShowSifre(!showSifre)} style={styles.eyeBtn}>
                            <Ionicons name={showSifre ? 'eye-off' : 'eye'} size={20} color="#9CA3AF" />
                        </TouchableOpacity>
                    </View>

                    <TouchableOpacity style={[styles.loginBtn, loading && styles.loginBtnDisabled]} onPress={handleLogin} disabled={loading}>
                        {loading ? (
                            <ActivityIndicator color="#fff" />
                        ) : (
                            <>
                                <Ionicons name="log-in-outline" size={20} color="#fff" />
                                <Text style={styles.loginBtnText}>Giriş Yap</Text>
                            </>
                        )}
                    </TouchableOpacity>

                    <Text style={styles.hint}>
                        İlk giriş şifreniz TC Kimlik numaranızın son 6 hanesidir.
                    </Text>
                </View>
            </View>
        </KeyboardAvoidingView>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#1E1338' },
    inner: { flex: 1, justifyContent: 'center', paddingHorizontal: 28 },
    header: { alignItems: 'center', marginBottom: 40 },
    logoCircle: { width: 90, height: 90, borderRadius: 45, backgroundColor: '#7C3AED', justifyContent: 'center', alignItems: 'center', marginBottom: 16, shadowColor: '#7C3AED', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.4, shadowRadius: 16, elevation: 12 },
    title: { fontSize: 32, fontWeight: '800', color: '#fff', letterSpacing: 2 },
    subtitle: { fontSize: 13, color: '#A78BFA', marginTop: 4 },
    form: { gap: 14 },
    inputGroup: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#2D2050', borderRadius: 14, paddingHorizontal: 14, height: 54, borderWidth: 1, borderColor: '#3D2D6B' },
    inputIcon: { marginRight: 10 },
    input: { flex: 1, color: '#fff', fontSize: 15 },
    eyeBtn: { padding: 6 },
    loginBtn: { backgroundColor: '#7C3AED', borderRadius: 14, height: 54, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 8, marginTop: 6, shadowColor: '#7C3AED', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.3, shadowRadius: 8, elevation: 6 },
    loginBtnDisabled: { opacity: 0.6 },
    loginBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
    hint: { textAlign: 'center', color: '#6B5B8D', fontSize: 11, marginTop: 12 },
});
