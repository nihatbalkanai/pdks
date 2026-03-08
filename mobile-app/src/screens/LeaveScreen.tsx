import React, { useState, useCallback } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, Alert, FlatList, Modal, TextInput, ScrollView, Keyboard, KeyboardAvoidingView, Platform } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import api from '../services/api';

export default function LeaveScreen() {
    const [izinler, setIzinler] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [turler, setTurler] = useState<any[]>([]);
    const [izinTipi, setIzinTipi] = useState<'gunluk' | 'saatlik'>('gunluk');
    const [form, setForm] = useState({
        izin_turu_id: 0,
        tarih: '',
        bitis_tarihi: '',
        baslangic_saati: '',
        bitis_saati: '',
        aciklama: '',
    });

    const loadData = useCallback(async () => {
        setLoading(true);
        try {
            const res: any = await api.izinlerim();
            setIzinler(res.izinler || []);
        } catch (err: any) {
            if (err.status === 401) Alert.alert('Hata', 'Oturum süresi dolmuş.');
            else Alert.alert('Hata', 'İzinler yüklenemedi.');
        }
        setLoading(false);
    }, []);

    // Tab'a her dönüşte veriyi yenile
    useFocusEffect(
        useCallback(() => {
            loadData();
        }, [])
    );

    const openModal = async () => {
        try {
            const res: any = await api.izinTurleri();
            setTurler(res.turler || []);
            const today = new Date().toISOString().split('T')[0];
            setForm({
                izin_turu_id: res.turler?.[0]?.id || 0,
                tarih: today,
                bitis_tarihi: today,
                baslangic_saati: '09:00',
                bitis_saati: '13:00',
                aciklama: '',
            });
            setIzinTipi('gunluk');
            setShowModal(true);
        } catch { Alert.alert('Hata', 'İzin türleri yüklenemedi.'); }
    };

    const submitTalep = async () => {
        if (!form.izin_turu_id || !form.tarih) {
            Alert.alert('Uyarı', 'İzin türü ve tarih gereklidir.');
            return;
        }
        if (izinTipi === 'saatlik' && (!form.baslangic_saati || !form.bitis_saati)) {
            Alert.alert('Uyarı', 'Saatlik izin için başlangıç ve bitiş saati gereklidir.');
            return;
        }
        try {
            const payload: any = {
                izin_turu_id: form.izin_turu_id,
                tarih: form.tarih,
                aciklama: form.aciklama,
            };
            if (izinTipi === 'gunluk') {
                payload.bitis_tarihi = form.bitis_tarihi || form.tarih;
            } else {
                payload.bitis_tarihi = form.tarih;
                payload.baslangic_saati = form.baslangic_saati;
                payload.bitis_saati = form.bitis_saati;
            }
            const res: any = await api.izinTalebi(payload);
            Alert.alert('Başarılı', res.mesaj || 'İzin talebi oluşturuldu.');
            setShowModal(false);
            loadData();
        } catch (err: any) {
            Alert.alert('Hata', err.mesaj || 'İzin talebi oluşturulamadı.');
        }
    };

    const durumRenk = (d: string) => {
        if (d === 'onaylandi') return '#10B981';
        if (d === 'reddedildi') return '#EF4444';
        return '#F59E0B';
    };
    const durumText = (d: string) => {
        if (d === 'onaylandi') return 'Onaylandı';
        if (d === 'reddedildi') return 'Reddedildi';
        return 'Beklemede';
    };

    return (
        <View style={s.container}>
            <View style={s.header}>
                <Text style={s.title}>📋 İzinlerim</Text>
                <TouchableOpacity style={s.addBtn} onPress={openModal}>
                    <Ionicons name="add-circle" size={20} color="#fff" />
                    <Text style={s.addText}>Yeni Talep</Text>
                </TouchableOpacity>
            </View>

            {loading ? (
                <Text style={s.empty}>Yükleniyor...</Text>
            ) : izinler.length === 0 ? (
                <View style={s.emptyBox}>
                    <Ionicons name="calendar-outline" size={48} color="#3D2D6B" />
                    <Text style={s.empty}>Henüz izin kaydı yok</Text>
                </View>
            ) : (
                <FlatList
                    data={izinler}
                    keyExtractor={(item) => item.id.toString()}
                    contentContainerStyle={{ paddingBottom: 100 }}
                    onRefresh={loadData}
                    refreshing={loading}
                    renderItem={({ item }) => (
                        <View style={s.card}>
                            <View style={s.cardTop}>
                                <Text style={s.cardTur}>{item.tur}</Text>
                                <View style={[s.badge, { backgroundColor: durumRenk(item.durum) + '20', borderColor: durumRenk(item.durum) }]}>
                                    <Text style={[s.badgeText, { color: durumRenk(item.durum) }]}>{durumText(item.durum)}</Text>
                                </View>
                            </View>
                            <View style={s.cardMid}>
                                <Ionicons name="calendar" size={14} color="#A78BFA" />
                                <Text style={s.cardDate}>
                                    {item.tarih}{item.bitis && item.bitis !== item.tarih ? ` → ${item.bitis}` : ''}
                                </Text>
                                <Text style={s.cardGun}>{item.gun} gün</Text>
                            </View>
                            {item.aciklama ? <Text style={s.cardAciklama}>{item.aciklama}</Text> : null}
                        </View>
                    )}
                />
            )}

            <Modal visible={showModal} transparent animationType="slide" onRequestClose={() => setShowModal(false)}>
                <KeyboardAvoidingView style={s.modalBg} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
                    <TouchableOpacity style={{ flex: 1 }} activeOpacity={1} onPress={() => { Keyboard.dismiss(); setShowModal(false); }} />
                    <View style={s.modal}>
                        <View style={s.modalHandle} />
                        <Text style={s.modalTitle}>📝 İzin Talebi</Text>

                        <ScrollView
                            showsVerticalScrollIndicator={true}
                            keyboardShouldPersistTaps="handled"
                            keyboardDismissMode="on-drag"
                            nestedScrollEnabled={true}
                            contentContainerStyle={{ paddingBottom: 20 }}
                        >
                            {/* İzin Tipi Seçimi */}
                            <Text style={s.label}>İzin Süresi</Text>
                            <View style={s.tipRow}>
                                <TouchableOpacity
                                    style={[s.tipBtn, izinTipi === 'gunluk' && s.tipBtnActive]}
                                    onPress={() => setIzinTipi('gunluk')}
                                >
                                    <Ionicons name="calendar" size={16} color={izinTipi === 'gunluk' ? '#fff' : '#6B5B8D'} />
                                    <Text style={[s.tipBtnText, izinTipi === 'gunluk' && s.tipBtnTextActive]}>Günlük</Text>
                                </TouchableOpacity>
                                <TouchableOpacity
                                    style={[s.tipBtn, izinTipi === 'saatlik' && s.tipBtnActive]}
                                    onPress={() => setIzinTipi('saatlik')}
                                >
                                    <Ionicons name="time" size={16} color={izinTipi === 'saatlik' ? '#fff' : '#6B5B8D'} />
                                    <Text style={[s.tipBtnText, izinTipi === 'saatlik' && s.tipBtnTextActive]}>Saatlik</Text>
                                </TouchableOpacity>
                            </View>

                            {/* İzin Türü */}
                            <Text style={s.label}>İzin Türü</Text>
                            <View style={s.turGrid}>
                                {turler.map(t => (
                                    <TouchableOpacity key={t.id} onPress={() => setForm({ ...form, izin_turu_id: t.id })}
                                        style={[s.turBtn, form.izin_turu_id === t.id && s.turBtnActive]}>
                                        <Text style={[s.turBtnText, form.izin_turu_id === t.id && s.turBtnTextActive]}>{t.ad}</Text>
                                    </TouchableOpacity>
                                ))}
                            </View>

                            {/* Tarih */}
                            <Text style={s.label}>{izinTipi === 'gunluk' ? 'Başlangıç Tarihi' : 'Tarih'}</Text>
                            <TextInput style={s.input} value={form.tarih} onChangeText={v => setForm({ ...form, tarih: v })} placeholder="YYYY-MM-DD" placeholderTextColor="#6B5B8D" returnKeyType="next" />

                            {izinTipi === 'gunluk' ? (
                                <>
                                    <Text style={s.label}>Bitiş Tarihi</Text>
                                    <TextInput style={s.input} value={form.bitis_tarihi} onChangeText={v => setForm({ ...form, bitis_tarihi: v })} placeholder="YYYY-MM-DD" placeholderTextColor="#6B5B8D" returnKeyType="next" />
                                </>
                            ) : (
                                <>
                                    <View style={s.saatRow}>
                                        <View style={{ flex: 1 }}>
                                            <Text style={s.label}>Başlangıç Saati</Text>
                                            <TextInput style={s.input} value={form.baslangic_saati} onChangeText={v => setForm({ ...form, baslangic_saati: v })} placeholder="HH:MM" placeholderTextColor="#6B5B8D" returnKeyType="next" />
                                        </View>
                                        <View style={{ flex: 1 }}>
                                            <Text style={s.label}>Bitiş Saati</Text>
                                            <TextInput style={s.input} value={form.bitis_saati} onChangeText={v => setForm({ ...form, bitis_saati: v })} placeholder="HH:MM" placeholderTextColor="#6B5B8D" returnKeyType="next" />
                                        </View>
                                    </View>
                                </>
                            )}

                            {/* Açıklama */}
                            <Text style={s.label}>Açıklama</Text>
                            <TextInput
                                style={[s.input, { height: 70, textAlignVertical: 'top' }]}
                                value={form.aciklama}
                                onChangeText={v => setForm({ ...form, aciklama: v })}
                                placeholder="İsteğe bağlı"
                                placeholderTextColor="#6B5B8D"
                                multiline
                                returnKeyType="done"
                                blurOnSubmit={true}
                                onSubmitEditing={Keyboard.dismiss}
                            />
                        </ScrollView>

                        <View style={s.modalBtns}>
                            <TouchableOpacity style={s.cancelBtn} onPress={() => setShowModal(false)}>
                                <Text style={s.cancelText}>İptal</Text>
                            </TouchableOpacity>
                            <TouchableOpacity style={s.submitBtn} onPress={submitTalep}>
                                <Text style={s.submitText}>Gönder</Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                </KeyboardAvoidingView>
            </Modal>
        </View>
    );
}

const s = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#0F0A1E', paddingHorizontal: 20, paddingTop: 50 },
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
    title: { fontSize: 20, fontWeight: '800', color: '#fff' },
    addBtn: { flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: '#7C3AED', paddingHorizontal: 14, paddingVertical: 8, borderRadius: 12 },
    addText: { color: '#fff', fontWeight: '700', fontSize: 13 },
    emptyBox: { flex: 1, justifyContent: 'center', alignItems: 'center', gap: 12 },
    empty: { color: '#6B5B8D', fontSize: 14, textAlign: 'center' },
    card: { backgroundColor: '#1A1230', borderRadius: 14, padding: 14, marginBottom: 10, borderWidth: 1, borderColor: '#2D2050' },
    cardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
    cardTur: { color: '#fff', fontWeight: '700', fontSize: 14 },
    badge: { paddingHorizontal: 10, paddingVertical: 3, borderRadius: 20, borderWidth: 1 },
    badgeText: { fontSize: 11, fontWeight: '700' },
    cardMid: { flexDirection: 'row', alignItems: 'center', gap: 6 },
    cardDate: { color: '#A78BFA', fontSize: 12 },
    cardGun: { color: '#6B5B8D', fontSize: 11, marginLeft: 'auto' },
    cardAciklama: { color: '#8B7BAD', fontSize: 11, marginTop: 6 },
    modalBg: { flex: 1, backgroundColor: 'rgba(0,0,0,0.7)', justifyContent: 'flex-end' },
    modal: { backgroundColor: '#1A1230', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 20, maxHeight: '85%' },
    modalHandle: { width: 40, height: 4, backgroundColor: '#3D2D6B', borderRadius: 2, alignSelf: 'center', marginBottom: 12 },
    modalTitle: { fontSize: 18, fontWeight: '800', color: '#fff', marginBottom: 8 },
    label: { color: '#A78BFA', fontSize: 12, fontWeight: '600', marginBottom: 6, marginTop: 12 },
    tipRow: { flexDirection: 'row', gap: 10 },
    tipBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, paddingVertical: 10, borderRadius: 12, backgroundColor: '#2D2050', borderWidth: 1, borderColor: '#3D2D6B' },
    tipBtnActive: { backgroundColor: '#7C3AED', borderColor: '#A78BFA' },
    tipBtnText: { color: '#6B5B8D', fontWeight: '700', fontSize: 13 },
    tipBtnTextActive: { color: '#fff' },
    turGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
    turBtn: { backgroundColor: '#2D2050', paddingHorizontal: 12, paddingVertical: 8, borderRadius: 10, borderWidth: 1, borderColor: '#3D2D6B' },
    turBtnActive: { backgroundColor: '#7C3AED', borderColor: '#A78BFA' },
    turBtnText: { color: '#8B7BAD', fontSize: 12, fontWeight: '600' },
    turBtnTextActive: { color: '#fff' },
    input: { backgroundColor: '#2D2050', borderRadius: 10, padding: 12, color: '#fff', fontSize: 14, borderWidth: 1, borderColor: '#3D2D6B' },
    saatRow: { flexDirection: 'row', gap: 10 },
    modalBtns: { flexDirection: 'row', gap: 10, marginTop: 12, paddingTop: 12, borderTopWidth: 1, borderTopColor: '#2D2050' },
    cancelBtn: { flex: 1, padding: 14, borderRadius: 12, borderWidth: 1, borderColor: '#3D2D6B', alignItems: 'center' },
    cancelText: { color: '#8B7BAD', fontWeight: '700' },
    submitBtn: { flex: 1, padding: 14, borderRadius: 12, backgroundColor: '#7C3AED', alignItems: 'center' },
    submitText: { color: '#fff', fontWeight: '700' },
});
