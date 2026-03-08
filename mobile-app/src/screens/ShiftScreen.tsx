import React, { useState, useCallback } from 'react';
import { View, Text, StyleSheet, ScrollView, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import api from '../services/api';

export default function ShiftScreen() {
    const [takvim, setTakvim] = useState<any[]>([]);
    const [ay, setAy] = useState('');
    const [loading, setLoading] = useState(true);

    useFocusEffect(useCallback(() => { loadData(); }, []));

    const loadData = async () => {
        setLoading(true);
        try {
            const res: any = await api.vardiyaTakvimi();
            setTakvim(res.takvim || []);
            setAy(res.ay || '');
        } catch (err: any) {
            if (err.status === 401) Alert.alert('Hata', 'Oturum süresi dolmuş.');
        }
        setLoading(false);
    };

    const turColor = (tur: string) => {
        if (tur === 'resmi_tatil') return '#EF4444';
        if (tur === 'tatil') return '#F59E0B';
        return '#10B981';
    };
    const turBg = (tur: string) => {
        if (tur === 'resmi_tatil') return '#2D1515';
        if (tur === 'tatil') return '#2D2515';
        return '#152D1A';
    };

    if (loading) return <View style={s.container}><Text style={s.empty}>Yükleniyor...</Text></View>;

    return (
        <ScrollView style={s.container} contentContainerStyle={{ paddingBottom: 100 }}>
            <Text style={s.title}>📅 Vardiya Takvimi</Text>
            <View style={s.ayBadge}>
                <Ionicons name="calendar" size={16} color="#A78BFA" />
                <Text style={s.ayText}>{ay}</Text>
            </View>

            <View style={s.legend}>
                <View style={s.legendItem}><View style={[s.dot, { backgroundColor: '#10B981' }]} /><Text style={s.legendText}>İş Günü</Text></View>
                <View style={s.legendItem}><View style={[s.dot, { backgroundColor: '#F59E0B' }]} /><Text style={s.legendText}>Tatil</Text></View>
                <View style={s.legendItem}><View style={[s.dot, { backgroundColor: '#EF4444' }]} /><Text style={s.legendText}>Resmi Tatil</Text></View>
            </View>

            {takvim.map((g, i) => (
                <View key={i} style={[s.row, { backgroundColor: turBg(g.tur) }]}>
                    <View style={s.tarihCol}>
                        <Text style={[s.tarih, { color: turColor(g.tur) }]}>{g.tarih}</Text>
                        <Text style={s.gun}>{g.gun}</Text>
                    </View>
                    <View style={[s.turDot, { backgroundColor: turColor(g.tur) }]} />
                    <View style={s.detailCol}>
                        <Text style={s.vardiya}>{g.vardiya || (g.tur === 'is_gunu' ? 'Normal' : '—')}</Text>
                        {g.tatil_adi ? <Text style={s.tatilAdi}>{g.tatil_adi}</Text> : null}
                    </View>
                </View>
            ))}
        </ScrollView>
    );
}

const s = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#0F0A1E', paddingHorizontal: 20, paddingTop: 50 },
    title: { fontSize: 20, fontWeight: '800', color: '#fff', marginBottom: 16 },
    empty: { color: '#6B5B8D', fontSize: 14, textAlign: 'center', marginTop: 40 },
    ayBadge: { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: '#1A1230', paddingHorizontal: 16, paddingVertical: 10, borderRadius: 12, marginBottom: 12, alignSelf: 'flex-start' },
    ayText: { color: '#A78BFA', fontSize: 14, fontWeight: '700' },
    legend: { flexDirection: 'row', gap: 16, marginBottom: 12 },
    legendItem: { flexDirection: 'row', alignItems: 'center', gap: 4 },
    dot: { width: 8, height: 8, borderRadius: 4 },
    legendText: { color: '#6B5B8D', fontSize: 10 },
    row: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10, paddingHorizontal: 12, borderRadius: 10, marginBottom: 4, gap: 10 },
    tarihCol: { width: 40, alignItems: 'center' },
    tarih: { fontSize: 18, fontWeight: '800' },
    gun: { fontSize: 10, color: '#6B5B8D', fontWeight: '600' },
    turDot: { width: 6, height: 6, borderRadius: 3 },
    detailCol: { flex: 1 },
    vardiya: { color: '#fff', fontSize: 13, fontWeight: '600' },
    tatilAdi: { color: '#EF4444', fontSize: 11, fontWeight: '500', marginTop: 2 },
});
