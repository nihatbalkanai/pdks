import React, { useState, useCallback } from 'react';
import { View, Text, StyleSheet, ScrollView, Alert, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import api from '../services/api';

export default function MoreScreen({ user, firma, onLogout }: { user: any; firma: any; onLogout: () => void }) {
    const [tab, setTab] = useState<'bordro' | 'belgeler'>('bordro');
    const [bordro, setBordro] = useState<any>(null);
    const [belgeler, setBelgeler] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    useFocusEffect(useCallback(() => { loadTab(); }, [tab]));

    const loadTab = async () => {
        setLoading(true);
        try {
            if (tab === 'bordro') {
                const res: any = await api.bordroOzeti();
                setBordro(res.bordro);
            } else {
                const res: any = await api.belgelerim();
                setBelgeler(res.belgeler || []);
            }
        } catch (err: any) {
            if (err.status === 401) Alert.alert('Hata', 'Oturum süresi dolmuş.');
        }
        setLoading(false);
    };

    const formatPara = (n: number) => n.toLocaleString('tr-TR', { minimumFractionDigits: 2 });

    const BordroView = () => {
        if (!bordro) return (
            <View style={s.emptyBox}>
                <Ionicons name="wallet-outline" size={48} color="#3D2D6B" />
                <Text style={s.empty}>Bu dönem bordro bulunamadı</Text>
            </View>
        );
        const items = [
            { label: 'Brüt Maaş', val: formatPara(bordro.brut_maas), icon: 'cash', color: '#3B82F6' },
            { label: 'Net Maaş', val: formatPara(bordro.net_maas), icon: 'wallet', color: '#10B981' },
            { label: 'FM %50 Ücreti', val: formatPara(bordro.fazla_mesai_50), icon: 'time', color: '#F59E0B' },
            { label: 'FM %100 Ücreti', val: formatPara(bordro.fazla_mesai_100), icon: 'flash', color: '#EF4444' },
            { label: 'SGK Kesintisi', val: `-${formatPara(bordro.sgk_kesintisi)}`, icon: 'shield', color: '#EC4899' },
            { label: 'Gelir Vergisi', val: `-${formatPara(bordro.gelir_vergisi)}`, icon: 'receipt', color: '#8B5CF6' },
            { label: 'Avans', val: `-${formatPara(bordro.avans)}`, icon: 'arrow-down-circle', color: '#F97316' },
            { label: 'Çalışma Günü', val: `${bordro.calisma_gun} gün`, icon: 'checkmark-circle', color: '#10B981' },
        ];
        return (
            <View style={s.bordroGrid}>
                <View style={s.bordroHeader}>
                    <Text style={s.bordroAy}>{bordro.ay}</Text>
                    <View style={s.netBox}>
                        <Text style={s.netLabel}>NET MAAŞ</Text>
                        <Text style={s.netVal}>₺{formatPara(bordro.net_maas)}</Text>
                    </View>
                </View>
                {items.map((it, i) => (
                    <View key={i} style={s.bordroRow}>
                        <Ionicons name={`${it.icon}-outline` as any} size={16} color={it.color} />
                        <Text style={s.bordroLabel}>{it.label}</Text>
                        <Text style={[s.bordroVal, { color: it.color }]}>₺{it.val}</Text>
                    </View>
                ))}
            </View>
        );
    };

    const BelgelerView = () => {
        if (belgeler.length === 0) return (
            <View style={s.emptyBox}>
                <Ionicons name="document-outline" size={48} color="#3D2D6B" />
                <Text style={s.empty}>Henüz belge yok</Text>
            </View>
        );
        return (
            <View style={s.belgeList}>
                {belgeler.map((b, i) => (
                    <View key={i} style={s.belgeRow}>
                        <Ionicons name="document-text" size={20} color="#A78BFA" />
                        <View style={s.belgeInfo}>
                            <Text style={s.belgeAd}>{b.ad}</Text>
                            <Text style={s.belgeTur}>{b.tur} • {b.tarih}</Text>
                        </View>
                    </View>
                ))}
            </View>
        );
    };

    return (
        <ScrollView style={s.container} contentContainerStyle={{ paddingBottom: 100 }}>
            <Text style={s.title}>💼 Finans & Belgeler</Text>

            <View style={s.tabs}>
                <TouchableOpacity style={[s.tab, tab === 'bordro' && s.tabActive]} onPress={() => setTab('bordro')}>
                    <Ionicons name="wallet" size={16} color={tab === 'bordro' ? '#fff' : '#6B5B8D'} />
                    <Text style={[s.tabText, tab === 'bordro' && s.tabTextActive]}>Bordro</Text>
                </TouchableOpacity>
                <TouchableOpacity style={[s.tab, tab === 'belgeler' && s.tabActive]} onPress={() => setTab('belgeler')}>
                    <Ionicons name="document-text" size={16} color={tab === 'belgeler' ? '#fff' : '#6B5B8D'} />
                    <Text style={[s.tabText, tab === 'belgeler' && s.tabTextActive]}>Belgeler</Text>
                </TouchableOpacity>
            </View>

            {loading ? <Text style={s.empty}>Yükleniyor...</Text> : tab === 'bordro' ? <BordroView /> : <BelgelerView />}
        </ScrollView>
    );
}

const s = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#0F0A1E', paddingHorizontal: 20, paddingTop: 50 },
    title: { fontSize: 20, fontWeight: '800', color: '#fff', marginBottom: 16 },
    empty: { color: '#6B5B8D', fontSize: 14, textAlign: 'center', marginTop: 40 },
    emptyBox: { alignItems: 'center', gap: 12, marginTop: 40 },
    tabs: { flexDirection: 'row', gap: 8, marginBottom: 16 },
    tab: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, paddingVertical: 10, borderRadius: 12, backgroundColor: '#1A1230', borderWidth: 1, borderColor: '#2D2050' },
    tabActive: { backgroundColor: '#7C3AED', borderColor: '#A78BFA' },
    tabText: { color: '#6B5B8D', fontWeight: '700', fontSize: 13 },
    tabTextActive: { color: '#fff' },
    bordroGrid: { backgroundColor: '#1A1230', borderRadius: 16, padding: 16, borderWidth: 1, borderColor: '#2D2050' },
    bordroHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16, paddingBottom: 14, borderBottomWidth: 1, borderBottomColor: '#2D2050' },
    bordroAy: { color: '#A78BFA', fontSize: 14, fontWeight: '700' },
    netBox: { alignItems: 'flex-end' },
    netLabel: { color: '#6B5B8D', fontSize: 10, fontWeight: '600' },
    netVal: { color: '#10B981', fontSize: 22, fontWeight: '800' },
    bordroRow: { flexDirection: 'row', alignItems: 'center', gap: 10, paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: '#1F1640' },
    bordroLabel: { flex: 1, color: '#8B7BAD', fontSize: 13 },
    bordroVal: { fontSize: 14, fontWeight: '700' },
    belgeList: { gap: 8 },
    belgeRow: { flexDirection: 'row', alignItems: 'center', gap: 12, backgroundColor: '#1A1230', padding: 14, borderRadius: 12, borderWidth: 1, borderColor: '#2D2050' },
    belgeInfo: { flex: 1 },
    belgeAd: { color: '#fff', fontSize: 13, fontWeight: '600' },
    belgeTur: { color: '#6B5B8D', fontSize: 11, marginTop: 2 },
});
