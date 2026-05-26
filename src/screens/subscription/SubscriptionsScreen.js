import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import apiClient from '../../utils/apiClient';

const STATUS = {
  actif:    { bg: '#D1FAE5', text: '#059669', icon: 'checkmark-circle', label: 'Actif' },
  résilié:  { bg: '#FEE2E2', text: '#DC2626', icon: 'close-circle',     label: 'Résilié' },
  suspendu: { bg: '#FEF3C7', text: '#D97706', icon: 'pause-circle',     label: 'Suspendu' },
};

function SubscriptionCard({ sub, onRefresh }) {
  const [open, setOpen]         = useState(false);
  const [cancelling, setCancelling] = useState(false);
  const config  = STATUS[sub.actif ? 'actif' : 'résilié'];
  const isYearly = sub.cycle === 'yearly';

  const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

  const daysLeft = () => {
    if (!sub.date_fin) return null;
    const diff = Math.ceil((new Date(sub.date_fin) - new Date()) / 86400000);
    return diff > 0 ? diff : 0;
  };
  const remaining = daysLeft();

  const handleCancel = () => {
    Alert.alert('Résilier', `Résilier "${sub.product_name}" ?`, [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Confirmer', style: 'destructive', onPress: async () => {
        setCancelling(true);
        try { await apiClient.delete(`/subscriptions/cancel.php?id=${sub.id}`); onRefresh(); }
        catch { Alert.alert('Erreur', 'Impossible de résilier. Réessayez.'); }
        finally { setCancelling(false); }
      }},
    ]);
  };

  return (
    <View style={styles.card}>
      <TouchableOpacity style={styles.cardHeader} onPress={() => setOpen(!open)} activeOpacity={0.8}>
        <View style={styles.iconBox}><Ionicons name="shield-checkmark-outline" size={22} color="#4F46E5" /></View>
        <View style={styles.cardInfo}>
          <Text style={styles.cardName} numberOfLines={1}>{sub.product_name ?? 'Abonnement'}</Text>
          <View style={styles.badges}>
            <View style={[styles.statusBadge, { backgroundColor: config.bg }]}>
              <Ionicons name={config.icon} size={11} color={config.text} />
              <Text style={[styles.statusText, { color: config.text }]}>{config.label}</Text>
            </View>
            <View style={styles.cycleBadge}><Text style={styles.cycleBadgeText}>{isYearly ? 'Annuel' : 'Mensuel'}</Text></View>
          </View>
        </View>
        <Ionicons name={open ? 'chevron-up' : 'chevron-down'} size={18} color="#9CA3AF" />
      </TouchableOpacity>

      {open && (
        <View style={styles.body}>
          <View style={styles.grid}>
            {[
              ['Début', formatDate(sub.date_debut)],
              ['Fin / Renouvellement', formatDate(sub.date_fin)],
              ['Cycle', isYearly ? 'Annuel' : 'Mensuel'],
              ['Prix', isYearly ? `${parseFloat(sub.price_yearly).toFixed(2)} €/an` : `${parseFloat(sub.price_monthly).toFixed(2)} €/mois`],
            ].map(([label, value]) => (
              <View key={label} style={styles.gridItem}>
                <Text style={styles.gridLabel}>{label}</Text>
                <Text style={styles.gridValue}>{value}</Text>
              </View>
            ))}
          </View>

          {remaining !== null && sub.actif && (
            <View style={[styles.remainingBar, remaining <= 7 && styles.remainingBarWarn]}>
              <Ionicons name="time-outline" size={14} color={remaining <= 7 ? '#D97706' : '#4F46E5'} />
              <Text style={[styles.remainingText, remaining <= 7 && { color: '#D97706' }]}>
                {remaining === 0 ? "Expire aujourd'hui" : `${remaining} jour${remaining > 1 ? 's' : ''} restant${remaining > 1 ? 's' : ''}`}
              </Text>
            </View>
          )}

          {sub.actif && (
            <TouchableOpacity style={[styles.cancelBtn, cancelling && { opacity: 0.5 }]} onPress={handleCancel} disabled={cancelling}>
              {cancelling
                ? <ActivityIndicator size="small" color="#EF4444" />
                : <><Ionicons name="close-circle-outline" size={15} color="#EF4444" /><Text style={styles.cancelText}>Résilier cet abonnement</Text></>
              }
            </TouchableOpacity>
          )}
        </View>
      )}
    </View>
  );
}

export default function SubscriptionsScreen() {
  const [subs, setSubs]       = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState(null);

  useEffect(() => { fetchSubs(); }, []);

  const fetchSubs = async () => {
    setLoading(true); setError(null);
    try {
      const res = await apiClient.get('/subscriptions/index.php');
      setSubs(res.data.subscriptions ?? res.data);
    } catch { setError('Impossible de charger vos abonnements.'); }
    finally { setLoading(false); }
  };

  if (loading) return <View style={styles.centered}><ActivityIndicator size="large" color="#4F46E5" /></View>;
  if (error)   return (
    <View style={styles.centered}>
      <Text style={styles.errorText}>{error}</Text>
      <TouchableOpacity style={styles.retryBtn} onPress={fetchSubs}><Text style={styles.retryText}>Réessayer</Text></TouchableOpacity>
    </View>
  );

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Mes abonnements</Text>
        <Text style={styles.sub}>{subs.filter((s) => s.actif).length} actif{subs.filter((s) => s.actif).length > 1 ? 's' : ''}</Text>
      </View>
      <FlatList data={subs} keyExtractor={(i) => String(i.id)}
        renderItem={({ item }) => <SubscriptionCard sub={item} onRefresh={fetchSubs} />}
        contentContainerStyle={styles.list} showsVerticalScrollIndicator={false}
        ListEmptyComponent={
          <View style={styles.centered}>
            <Ionicons name="refresh-circle-outline" size={48} color="#D1D5DB" />
            <Text style={styles.emptyText}>Aucun abonnement actif</Text>
          </View>
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  centered:  { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
  header:    { paddingHorizontal: 20, paddingTop: 56, paddingBottom: 16, backgroundColor: '#FFF' },
  title:     { fontSize: 26, fontWeight: '700', color: '#111827' },
  sub:       { fontSize: 13, color: '#6B7280', marginTop: 2 },
  list:      { padding: 16, paddingBottom: 24 },
  card:      { backgroundColor: '#FFF', borderRadius: 14, marginBottom: 12, overflow: 'hidden', shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 6, shadowOffset: { width: 0, height: 2 }, elevation: 2 },
  cardHeader:{ flexDirection: 'row', alignItems: 'center', padding: 14 },
  iconBox:   { width: 46, height: 46, borderRadius: 13, backgroundColor: '#EEF2FF', justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  cardInfo:  { flex: 1, gap: 6 },
  cardName:  { fontSize: 15, fontWeight: '600', color: '#111827' },
  badges:    { flexDirection: 'row', gap: 6 },
  statusBadge: { flexDirection: 'row', alignItems: 'center', gap: 4, borderRadius: 6, paddingHorizontal: 7, paddingVertical: 2 },
  statusText:{ fontSize: 11, fontWeight: '600' },
  cycleBadge:{ backgroundColor: '#F3F4F6', borderRadius: 6, paddingHorizontal: 7, paddingVertical: 2 },
  cycleBadgeText: { fontSize: 11, color: '#6B7280', fontWeight: '500' },
  body:      { paddingHorizontal: 14, paddingBottom: 14, borderTopWidth: 1, borderTopColor: '#F3F4F6' },
  grid:      { flexDirection: 'row', flexWrap: 'wrap', backgroundColor: '#F9FAFB', borderRadius: 10, padding: 12, marginTop: 12, marginBottom: 12 },
  gridItem:  { width: '50%', paddingVertical: 6, paddingHorizontal: 4 },
  gridLabel: { fontSize: 11, color: '#9CA3AF', marginBottom: 2 },
  gridValue: { fontSize: 13, fontWeight: '600', color: '#374151' },
  remainingBar: { flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: '#EEF2FF', borderRadius: 8, paddingHorizontal: 12, paddingVertical: 8, marginBottom: 12 },
  remainingBarWarn: { backgroundColor: '#FEF3C7' },
  remainingText: { fontSize: 13, color: '#4F46E5', fontWeight: '500' },
  cancelBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, borderWidth: 1, borderColor: '#FECACA', borderRadius: 8, paddingVertical: 9, backgroundColor: '#FEF2F2' },
  cancelText:{ color: '#EF4444', fontSize: 13, fontWeight: '600' },
  errorText: { color: '#EF4444', marginBottom: 12 },
  retryBtn:  { backgroundColor: '#4F46E5', borderRadius: 10, paddingHorizontal: 20, paddingVertical: 10 },
  retryText: { color: '#FFF', fontWeight: '600' },
  emptyText: { color: '#9CA3AF', marginTop: 12, fontSize: 14 },
});
