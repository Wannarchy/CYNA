import React, { useState } from 'react';
import { View, Text, SectionList, TouchableOpacity, TextInput, StyleSheet } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { mockOrders, MockOrder } from '../data/mockData';

export default function OrderHistoryScreen() {
  const navigation = useNavigation<any>();
  const [expandedOrderId, setExpandedOrderId] = useState<number | null>(null);
  const [searchQuery, setSearchQuery] = useState('');

  // Formater la date en français
  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
  };

  // Filtrage par recherche
  const filteredOrders = mockOrders.filter(order => 
    order.items.some(item => item.product_name.toLowerCase().includes(searchQuery.toLowerCase())) ||
    formatDate(order.created_at).includes(searchQuery)
  );

  // Regroupement par année
  const groupedOrders = filteredOrders.reduce((acc, order) => {
    const year = new Date(order.created_at).getFullYear().toString();
    if (!acc[year]) acc[year] = [];
    acc[year].push(order);
    return acc;
  }, {} as Record<string, MockOrder[]>);

  const sections = Object.keys(groupedOrders).sort((a, b) => parseInt(b) - parseInt(a)).map(year => ({
    title: year,
    data: groupedOrders[year]
  }));

  const renderOrder = ({ item }: { item: MockOrder }) => {
    const isExpanded = expandedOrderId === item.id;

    return (
      <View style={styles.orderCard}>
        <TouchableOpacity style={styles.orderHeader} onPress={() => setExpandedOrderId(isExpanded ? null : item.id)}>
          <View style={{ flex: 1 }}>
            <Text style={styles.orderName}>{item.items[0].product_name}</Text>
            <Text style={styles.orderDate}>{formatDate(item.created_at)}</Text>
          </View>
          <View style={{ alignItems: 'flex-end' }}>
            <Text style={styles.orderTotal}>{item.total.toFixed(2)} €</Text>
            <View style={[styles.badge, item.status === 'paid' ? styles.badgePaid : styles.badgePending]}>
              <Text style={styles.badgeText}>{item.status === 'paid' ? 'Payée' : 'En attente'}</Text>
            </View>
          </View>
        </TouchableOpacity>

        {/* DÉTAILS DE LA COMMANDE (Affiché si déplié) */}
        {isExpanded && (
          <View style={styles.detailsContainer}>
            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>Abonnement :</Text>
              <Text style={styles.detailValue}>{item.items[0].cycle === 'monthly' ? 'Mensuel' : 'Annuel'}</Text>
            </View>
            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>Paiement :</Text>
              <Text style={styles.detailValue}>**** **** **** {item.card_last4}</Text>
            </View>
            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>Facturation :</Text>
              <Text style={styles.detailValue}>{item.billing_address}</Text>
            </View>
            
            <TouchableOpacity style={styles.pdfButton} onPress={() => alert("Téléchargement de la facture PDF (Fonctionnalité finale avec l'API)")}>
              <Ionicons name="document-text-outline" size={20} color="#0056b3" />
              <Text style={styles.pdfButtonText}>Télécharger la facture (PDF)</Text>
            </TouchableOpacity>
          </View>
        )}
      </View>
    );
  };

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}>
          <Text style={styles.backText}>← Retour</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Mes commandes</Text>
        <View style={{ width: 50 }} />
      </View>

      <View style={styles.searchContainer}>
        <Ionicons name="search" size={20} color="#7f8c8d" style={{ marginLeft: 15 }} />
        <TextInput 
          style={styles.searchInput}
          placeholder="Rechercher par nom ou date..."
          placeholderTextColor="#aaa"
          value={searchQuery}
          onChangeText={setSearchQuery}
        />
      </View>

      <SectionList
        sections={sections}
        keyExtractor={(item) => item.id.toString()}
        renderItem={renderOrder}
        renderSectionHeader={({ section: { title } }) => (
          <View style={styles.yearHeader}>
            <Text style={styles.yearText}>{title}</Text>
          </View>
        )}
        contentContainerStyle={{ padding: 15 }}
        ListEmptyComponent={<Text style={styles.emptyText}>Aucune commande trouvée.</Text>}
      />
    </View>
  );
}



const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F5F7FA' },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 20, backgroundColor: '#fff' },
  backText: { color: '#0056b3', fontSize: 16, fontWeight: '600' },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#2C3E50' },
  searchContainer: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', marginHorizontal: 15, marginBottom: 15, borderRadius: 10, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 2 },
  searchInput: { flex: 1, padding: 12, fontSize: 16, color: '#2C3E50' },
  yearHeader: { backgroundColor: '#e0e0e0', paddingVertical: 8, paddingHorizontal: 15, borderRadius: 8, marginBottom: 10, marginTop: 5 },
  yearText: { fontSize: 18, fontWeight: 'bold', color: '#2C3E50' },
  orderCard: { backgroundColor: '#fff', borderRadius: 10, marginBottom: 15, shadowColor: '#000', shadowOpacity: 0.03, shadowRadius: 4, elevation: 2, overflow: 'hidden' },
  orderHeader: { flexDirection: 'row', justifyContent: 'space-between', padding: 15, alignItems: 'center' },
  orderName: { fontSize: 16, fontWeight: 'bold', color: '#2C3E50' },
  orderDate: { fontSize: 13, color: '#7f8c8d', marginTop: 4 },
  orderTotal: { fontSize: 16, fontWeight: 'bold', color: '#2C3E50' },
  badge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 4, marginTop: 6 },
  badgePaid: { backgroundColor: '#e8f5e9' },
  badgePending: { backgroundColor: '#fff3cd' },
  badgeText: { fontSize: 12, fontWeight: 'bold', color: '#2e7d32' },
  detailsContainer: { borderTopWidth: 1, borderColor: '#eee', padding: 15, backgroundColor: '#fafafa' },
  detailRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 10 },
  detailLabel: { fontSize: 14, color: '#7f8c8d' },
  detailValue: { fontSize: 14, color: '#2C3E50', fontWeight: '600', flex: 1, textAlign: 'right', marginLeft: 10 },
  pdfButton: { flexDirection: 'row', backgroundColor: '#eaf2f8', padding: 12, borderRadius: 8, alignItems: 'center', justifyContent: 'center', marginTop: 10 },
  pdfButtonText: { color: '#0056b3', fontSize: 14, fontWeight: 'bold', marginLeft: 8 },
  emptyText: { textAlign: 'center', color: '#7f8c8d', marginTop: 50, fontSize: 16 }
});