import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, StyleSheet, RefreshControl } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import api from '../services/api';

export default function HistoryScreen() {
    const [hareketler, setHareketler] = useState<any[]>([]);
    const [refreshing, setRefreshing] = useState(false);

    const loadData = async () => {
        try {
            const data: any = await api.gecmis();
            setHareketler(data.hareketler || []);
        } catch (err) {
            console.log('Geçmiş yüklenemedi');
        }
    };

    useEffect(() => { loadData(); }, []);

    const onRefresh = async () => {
        setRefreshing(true);
        await loadData();
        setRefreshing(false);
    };

    const yontemIcon: Record<string, string> = { gps: 'location', qr: 'qr-code', wifi: 'wifi', beacon: 'bluetooth', manual: 'hand-left' };

    const renderItem = ({ item }: { item: any }) => {
        const tarih = new Date(item.created_at);
        const saat = tarih.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
        const gun = tarih.toLocaleDateString('tr-TR', { day: '2-digit', month: 'short' });
        const isGiris = item.tip === 'giris';

        return (
            <View style={[styles.card, item.sahte_konum_algilandi && styles.cardDanger]}>
                <View style={[styles.tipBadge, { backgroundColor: isGiris ? '#064E3B' : '#7F1D1D' }]}>
                    <Ionicons name={isGiris ? 'log-in' : 'log-out'} size={18} color={isGiris ? '#34D399' : '#F87171'} />
                </View>
                <View style={styles.cardContent}>
                    <View style={styles.cardRow}>
                        <Text style={styles.cardSaat}>{saat}</Text>
                        <Text style={styles.cardGun}>{gun}</Text>
                    </View>
                    <View style={styles.cardRow}>
                        <View style={styles.yontemBadge}>
                            <Ionicons name={(yontemIcon[item.dogrulama_yontemi] || 'help') as any} size={12} color="#A78BFA" />
                            <Text style={styles.yontemText}>{item.dogrulama_yontemi?.toUpperCase()}</Text>
                        </View>
                        {!!item.mesafe_metre && <Text style={styles.mesafe}>{item.mesafe_metre}m</Text>}
                        {!!item.sahte_konum_algilandi && <Text style={styles.sahte}>⚠️ SAHTE</Text>}
                    </View>
                </View>
                <Text style={[styles.tipText, { color: isGiris ? '#34D399' : '#F87171' }]}>
                    {isGiris ? 'GİRİŞ' : 'ÇIKIŞ'}
                </Text>
            </View>
        );
    };

    return (
        <View style={styles.container}>
            <Text style={styles.header}>📋 Geçmiş Hareketler</Text>
            <Text style={styles.subheader}>Son 30 günlük giriş/çıkış kayıtları</Text>

            <FlatList
                data={hareketler}
                renderItem={renderItem}
                keyExtractor={(item) => item.id?.toString()}
                contentContainerStyle={styles.list}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#A78BFA" />}
                ListEmptyComponent={
                    <View style={styles.empty}>
                        <Ionicons name="calendar-outline" size={48} color="#3D2D6B" />
                        <Text style={styles.emptyText}>Henüz hareket kaydı yok</Text>
                    </View>
                }
            />
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#0F0A1E', paddingTop: 50 },
    header: { fontSize: 20, fontWeight: '800', color: '#fff', paddingHorizontal: 20 },
    subheader: { fontSize: 12, color: '#6B5B8D', paddingHorizontal: 20, marginBottom: 16 },
    list: { paddingHorizontal: 16, paddingBottom: 100 },
    card: { backgroundColor: '#1A1230', borderRadius: 14, padding: 14, marginBottom: 10, flexDirection: 'row', alignItems: 'center', gap: 12 },
    cardDanger: { borderWidth: 1, borderColor: '#EF4444' },
    tipBadge: { width: 40, height: 40, borderRadius: 12, justifyContent: 'center', alignItems: 'center' },
    cardContent: { flex: 1, gap: 4 },
    cardRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
    cardSaat: { color: '#fff', fontSize: 18, fontWeight: '700', fontVariant: ['tabular-nums'] },
    cardGun: { color: '#6B5B8D', fontSize: 12 },
    yontemBadge: { flexDirection: 'row', alignItems: 'center', gap: 4, backgroundColor: '#2D2050', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 8 },
    yontemText: { color: '#A78BFA', fontSize: 9, fontWeight: '700' },
    mesafe: { color: '#6B5B8D', fontSize: 11 },
    sahte: { color: '#EF4444', fontSize: 10, fontWeight: '700' },
    tipText: { fontSize: 11, fontWeight: '800', letterSpacing: 1 },
    empty: { alignItems: 'center', paddingTop: 60, gap: 12 },
    emptyText: { color: '#4B3F6B', fontSize: 14 },
});
