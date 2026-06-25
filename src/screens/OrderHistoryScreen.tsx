import React, { useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, Alert, ActivityIndicator } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { useQuery } from '@tanstack/react-query';
import * as FileSystem from 'expo-file-system';
import * as Sharing from 'expo-sharing';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api, { API_BASE_URL } from '../services/api';
import { RootStackParamList } from '../navigation/AppNavigator';

type OrderHistoryScreenNavigationProp = NativeStackNavigationProp<RootStackParamList>;

interface Order {
  id: string;
  created_at: string;
  total_price: number;
  status: string;
}

export default function OrderHistoryScreen() {
  const navigation = useNavigation<OrderHistoryScreenNavigationProp>();
  const [downloadingId, setDownloadingId] = useState<string | null>(null);

  const { data: ordersResponse, isLoading } = useQuery({
    queryKey: ['orders'],
    queryFn: async () => (await api.get('/orders')).data,
  });

  // --- DÉBALLAGE DES DONNÉES LARAVEL ---
  const rawOrders = ordersResponse?.data ?? ordersResponse;
  const orders: Order[] = Array.isArray(rawOrders) ? rawOrders : [];

  const handleDownloadInvoice = async (orderId: string) => {
    setDownloadingId(orderId);
    try {
      const token = await AsyncStorage.getItem('auth_token');
      const apiUrl = `${API_BASE_URL}/orders/${orderId}/invoice`;
     const localPath = `${(FileSystem as any).documentDirectory}facture_${orderId}.pdf`;

      const { uri } = await (FileSystem as any).downloadAsync(
        apiUrl,
        localPath,
        { headers: { Authorization: `Bearer ${token}` } }
      );

      if (await Sharing.isAvailableAsync()) {
        await Sharing.shareAsync(uri);
      } else {
        Alert.alert('Succès', 'Facture téléchargée dans vos documents.');
      }
    } catch (error) {
      Alert.alert('Erreur', 'Impossible de télécharger la facture.');
    } finally {
      setDownloadingId(null);
    }
  };

  const renderOrder = ({ item }: { item: Order }) => (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <View>
          <Text style={styles.orderId}>Commande #{item.id.substring(0, 8)}</Text>
          <Text style={styles.orderDate}>{new Date(item.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })}</Text>
        </View>
        <View style={[styles.statusBadge, item.status === 'paid' ? styles.statusSuccess : styles.statusPending]}>
          <Text style={styles.statusText}>{item.status === 'paid' ? 'Payée' : 'En cours'}</Text>
        </View>
      </View>

      <View style={styles.cardFooter}>
        <Text style={styles.totalAmount}>{Number(item.total_price).toFixed(2)} €</Text>
        
        <TouchableOpacity 
          style={styles.invoiceBtn} 
          onPress={() => handleDownloadInvoice(item.id)}
          disabled={downloadingId === item.id}
        >
          {downloadingId === item.id ? (
            <ActivityIndicator size="small" color="#0056b3" />
          ) : (
            <>
              <Ionicons name="download-outline" size={16} color="#0056b3" />
              <Text style={styles.invoiceBtnText}>Facture PDF</Text>
            </>
          )}
        </TouchableOpacity>
      </View>
    </View>
  );

  if (isLoading) {
    return <View style={styles.center}><ActivityIndicator size="large" color="#0056b3" /></View>;
  }

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color="#1E293B" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Mes Commandes</Text>
        <View style={{ width: 24 }} />
      </View>

      <FlatList
        data={orders}
        keyExtractor={item => item.id}
        contentContainerStyle={{ padding: 20, flexGrow: 1 }}
        renderItem={renderOrder}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Ionicons name="receipt-outline" size={80} color="#CBD5E1" />
            <Text style={styles.emptyTitle}>Aucune commande</Text>
            <Text style={styles.emptySubtitle}>Vous n'avez pas encore passé de commande.</Text>
          </View>
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 20, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  backBtn: { padding: 5 },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B' },
  card: { backgroundColor: '#fff', borderRadius: 16, padding: 20, marginBottom: 15, shadowColor: '#000', shadowOpacity: 0.03, shadowRadius: 15, elevation: 2, borderWidth: 1, borderColor: '#F1F5F9' },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingBottom: 15, borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  orderId: { fontSize: 16, fontWeight: 'bold', color: '#1E293B' },
  orderDate: { fontSize: 13, color: '#64748B', marginTop: 4 },
  statusBadge: { paddingHorizontal: 10, paddingVertical: 5, borderRadius: 20 },
  statusSuccess: { backgroundColor: '#ecfdf5' },
  statusPending: { backgroundColor: '#fef3c7' },
  statusText: { fontSize: 12, fontWeight: 'bold', color: '#10b981' }, 
  cardFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 15 },
  totalAmount: { fontSize: 20, fontWeight: 'bold', color: '#1E293B' },
  invoiceBtn: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#EFF6FF', paddingHorizontal: 15, paddingVertical: 10, borderRadius: 10, gap: 6 },
  invoiceBtnText: { color: '#0056b3', fontSize: 14, fontWeight: '600' },
  emptyContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 40 },
  emptyTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B', marginTop: 20 },
  emptySubtitle: { fontSize: 14, color: '#64748B', textAlign: 'center', marginTop: 8 }
});