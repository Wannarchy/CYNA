import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, Linking } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import apiClient, { API_BASE_URL } from '../../utils/apiClient';

const STATUS_MAP = {
  payé:       { bg: '#D1FAE5', text: '#059669', icon: 'checkmark-circle-outline' },
  en_attente: { bg: '#FEF3C7', text: '#D97706', icon: 'time-outline' },
  annulé:     { bg: '#FEE2E2', text: '#DC2626', icon: 'close-circle-outline' },
};

export default function OrdersScreen() {
  const [orders, setOrders]   = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState(null);

  useEffect(() => { fetchOrders(); }, []);

  const fetchOrders = async () => {
    setLoading(true); setError(null);
    try {
      const res = await apiClient.get('/orders/index.php');
      setOrders(res.data.orders ?? res.data);
    } catch { setError('Impossible de charger vos commandes.'); }
    finally { setLoading(false); }
  };

  const handleInvoice = async (orderId) => {
    await Linking.openURL(`${API_BASE_URL}/orders/invoice.php?order_id=${orderId}`);
  };

  const renderOrder = ({ item }) => {
    const statusKey = (item.statut ?? 'payé').replace(' ', '_');
    const status    = STATUS_MAP[statusKey] ?? STATUS_MAP.payé;
    return (
      <View style={styles.card}>
        <View style={styles.cardTop}>
          <View style={styles.orderIcon}><Ionicons name="receipt-outline" size={22} color="#4F46E5" /></View>
          <View style={styles.cardInfo}>
            <Text style={styles.orderRef}>Commande #{item.id}</Text>
            <Text style={styles.orderDate}>
              {new Date(item.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })}
            </Text>
          </View>
          <View style={[styles.statusBadge, { backgroundColor: status.bg }]}>
            <Ionicons name={status.icon} size={12} color={status.text} />
            <Text style={[styles.statusText, { color: status.text }]}>{item.statut ?? 'payé'}</Text>
          </View>
        </View>

        {(item.items ?? []).map((ligne, idx) => (
          <View key={idx} style={styles.ligne}>
            <View style={{ flex: 1, marginRight: 8 }}>
              <Text style={styles.ligneName} numberOfLines={1}>{ligne.name ?? 'Produit'}</Text>
              <Text style={styles.ligneCycle}>{ligne.cycle === 'yearly' ? 'Annuel' : 'Mensuel'}</Text>
            </View>
            <Text style={styles.lignePrice}>{parseFloat(ligne.price).toFixed(2)} €</Text>
          </View>
        ))}

        <View style={styles.divider} />
        <View style={styles.cardBottom}>
          <Text style={styles.totalLabel}>Total TTC</Text>
          <Text style={styles.totalAmount}>{parseFloat(item.total).toFixed(2)} €</Text>
        </View>

        <TouchableOpacity style={styles.invoiceBtn} onPress={() => handleInvoice(item.id)}>
          <Ionicons name="document-text-outline" size={16} color="#4F46E5" />
          <Text style={styles.invoiceBtnText}>Télécharger la facture PDF</Text>
        </TouchableOpacity>
      </View>
    );
  };

  if (loading) return <View style={styles.centered}><ActivityIndicator size="large" color="#4F46E5" /></View>;
  if (error)   return (
    <View style={styles.centered}>
      <Text style={styles.errorText}>{error}</Text>
      <TouchableOpacity style={styles.retryBtn} onPress={fetchOrders}><Text style={styles.retryText}>Réessayer</Text></TouchableOpacity>
    </View>
  );

  return (
    <View style={styles.container}>
      <View style={styles.header}><Text style={styles.title}>Mes commandes</Text></View>
      <FlatList data={orders} keyExtractor={(i) => String(i.id)} renderItem={renderOrder}
        contentContainerStyle={styles.list} showsVerticalScrollIndicator={false}
        ListEmptyComponent={
          <View style={styles.centered}>
            <Ionicons name="receipt-outline" size={48} color="#D1D5DB" />
            <Text style={styles.emptyText}>Aucune commande pour le moment</Text>
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
  list:      { padding: 16, paddingBottom: 24 },
  card:      { backgroundColor: '#FFF', borderRadius: 14, padding: 16, marginBottom: 12, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 6, shadowOffset: { width: 0, height: 2 }, elevation: 2 },
  cardTop:   { flexDirection: 'row', alignItems: 'center', marginBottom: 14 },
  orderIcon: { width: 44, height: 44, borderRadius: 12, backgroundColor: '#EEF2FF', justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  cardInfo:  { flex: 1 },
  orderRef:  { fontSize: 14, fontWeight: '600', color: '#111827' },
  orderDate: { fontSize: 12, color: '#9CA3AF', marginTop: 2 },
  statusBadge: { flexDirection: 'row', alignItems: 'center', gap: 4, borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3 },
  statusText:{ fontSize: 11, fontWeight: '600' },
  ligne:     { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 4 },
  ligneName: { fontSize: 13, color: '#374151' },
  ligneCycle:{ fontSize: 11, color: '#9CA3AF', marginTop: 1 },
  lignePrice:{ fontSize: 13, fontWeight: '500', color: '#374151' },
  divider:   { height: 1, backgroundColor: '#F3F4F6', marginVertical: 10 },
  cardBottom:{ flexDirection: 'row', justifyContent: 'space-between', marginBottom: 12 },
  totalLabel:{ fontSize: 14, color: '#6B7280' },
  totalAmount: { fontSize: 16, fontWeight: '700', color: '#111827' },
  invoiceBtn:{ flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, borderWidth: 1, borderColor: '#C7D2FE', borderRadius: 8, paddingVertical: 9, backgroundColor: '#EEF2FF' },
  invoiceBtnText: { color: '#4F46E5', fontSize: 13, fontWeight: '600' },
  errorText: { color: '#EF4444', marginBottom: 12 },
  retryBtn:  { backgroundColor: '#4F46E5', borderRadius: 10, paddingHorizontal: 20, paddingVertical: 10 },
  retryText: { color: '#FFF', fontWeight: '600' },
  emptyText: { color: '#9CA3AF', marginTop: 12, fontSize: 14 },
});
