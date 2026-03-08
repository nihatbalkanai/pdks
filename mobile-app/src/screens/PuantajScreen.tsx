import React, { useState, useCallback } from 'react';
import { View, Text, StyleSheet, ScrollView, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import api from '../services/api';

export default function PuantajScreen() {
    const [ozet, setOzet] = useState<any>(null);
    const [loading, setLoading] = useState(true);

    useFocusEffect(
        useCallback(() => { loadData(); }, [])
    );

    const loadData = async () => {
        setLoading(true);
        try {
            const res: any = await api.puantajOzeti();
            setOzet(res.ozet);
        } catch (err: any) {
            if (err.status === 401) Alert.alert('Hata', 'Oturum süresi dolmuş.');
        }
        setLoading(false);
    };

    const StatCard = ({ icon, label, value, color, sub }: any) => (
        <View style={[s.stat, { borderLeftColor: color }]}>
            <View style={s.statTop}>
                <Ionicons name={icon} size={18} color={color} />
                <Text style={[s.statVal, { color }]}>{value}</Text>
            </View>
            <Text style={s.statLabel}>{label}</Text>
            {sub ? <Text style={s.statSub}>{sub}</Text> : null}
        </View>
    );

    if (loading) return <View style={s.container}><Text style={s.empty}>Yükleniyor...</Text></View>;

    return (
        <ScrollView style={s.container} contentContainerStyle={{ paddingBottom: 100 }}>
            <Text style={s.title}>📊 Puantaj Özeti</Text>
            {ozet ? (
                <>
                    <View style={s.ayBadge}>
                        <Ionicons name="calendar" size={16} color="#A78BFA" />
                        <Text style={s.ayText}>{ozet.ay}</Text>
                    </View>

                    <View style={s.grid}>
                        <StatCard icon="checkmark-circle" label="Gelinen Gün" value={ozet.gelinen_gun} color="#10B981" sub={`/ ${ozet.takvim_gunu} takvim günü`} />
                        <StatCard icon="time" label="Toplam Çalışma" value={`${ozet.toplam_saat}h`} color="#3B82F6" sub={`${ozet.toplam_dakika} dakika`} />
                        <StatCard icon="sunny" label="Ücretli İzin" value={`${ozet.ucretli_izin} gün`} color="#F59E0B" />
                        <StatCard icon="moon" label="Ücretsiz İzin" value={`${ozet.ucretsiz_izin} gün`} color="#EF4444" />
                        <StatCard icon="medkit" label="Rapor" value={`${ozet.rapor_gun} gün`} color="#EC4899" />
                        <StatCard icon="flag" label="Resmi Tatil" value={`${ozet.resmi_tatil} gün`} color="#8B5CF6" />
                    </View>

                    <View style={s.progressSection}>
                        <Text style={s.progressTitle}>Devam Oranı</Text>
                        <View style={s.progressBar}>
                            <View style={[s.progressFill, { width: `${Math.min(100, (ozet.gelinen_gun / Math.max(1, ozet.takvim_gunu - ozet.resmi_tatil - 8)) * 100)}%` }]} />
                        </View>
                        <Text style={s.progressText}>
                            {ozet.gelinen_gun} / {ozet.takvim_gunu - ozet.resmi_tatil - 8} iş günü (tahmini)
                        </Text>
                    </View>
                </>
            ) : (
                <View style={s.emptyBox}>
                    <Ionicons name="analytics-outline" size={48} color="#3D2D6B" />
                    <Text style={s.empty}>Bu ay için veri bulunamadı</Text>
                </View>
            )}
        </ScrollView>
    );
}

const s = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#0F0A1E', paddingHorizontal: 20, paddingTop: 50 },
    title: { fontSize: 20, fontWeight: '800', color: '#fff', marginBottom: 16 },
    empty: { color: '#6B5B8D', fontSize: 14, textAlign: 'center', marginTop: 40 },
    emptyBox: { flex: 1, justifyContent: 'center', alignItems: 'center', gap: 12, marginTop: 60 },
    ayBadge: { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: '#1A1230', paddingHorizontal: 16, paddingVertical: 10, borderRadius: 12, marginBottom: 16, alignSelf: 'flex-start' },
    ayText: { color: '#A78BFA', fontSize: 14, fontWeight: '700' },
    grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
    stat: { backgroundColor: '#1A1230', borderRadius: 14, padding: 14, width: '48%' as any, borderLeftWidth: 3, borderWidth: 1, borderColor: '#2D2050' },
    statTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
    statVal: { fontSize: 20, fontWeight: '800' },
    statLabel: { color: '#8B7BAD', fontSize: 12, fontWeight: '600' },
    statSub: { color: '#4B3F6B', fontSize: 10, marginTop: 2 },
    progressSection: { backgroundColor: '#1A1230', borderRadius: 14, padding: 16, marginTop: 16, borderWidth: 1, borderColor: '#2D2050' },
    progressTitle: { color: '#fff', fontSize: 14, fontWeight: '700', marginBottom: 10 },
    progressBar: { height: 8, backgroundColor: '#2D2050', borderRadius: 4, overflow: 'hidden' },
    progressFill: { height: '100%', backgroundColor: '#7C3AED', borderRadius: 4 },
    progressText: { color: '#6B5B8D', fontSize: 11, marginTop: 6, textAlign: 'center' },
});
