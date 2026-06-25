import React from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, Alert, ActivityIndicator } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../services/api';
import { RootStackParamList } from '../navigation/AppNavigator';

type AddressBookScreenNavigationProp = NativeStackNavigationProp<RootStackParamList>;

interface Address {
  id: number;
  label: string;
  prenom: string;
  nom: string;
  adresse1: string;
  code_postal: string;
  ville: string;
  pays: string;
  is_default: boolean;
}

const getAddressIcon = (label: string) => {
  const l = label.toLowerCase();
  if (l.includes('dom') || l.includes('maison')) return 'home-outline';
  if (l.includes('trav') || l.includes('bureau')) return 'briefcase-outline';
  return 'location-outline';
};

export default function AddressBookScreen() {
  const navigation = useNavigation<AddressBookScreenNavigationProp>();
  const queryClient = useQueryClient();

  const { data: addressesResponse, isLoading } = useQuery({
    queryKey: ['addresses'],
    queryFn: async () => (await api.get('/addresses')).data,
  });

  // --- DÉBALLAGE DES DONNÉES LARAVEL ---
  const rawAddresses = addressesResponse?.data ?? addressesResponse;
  const addresses: Address[] = Array.isArray(rawAddresses) ? rawAddresses : [];

  const deleteMutation = useMutation({
    mutationFn: (addressId: number) => api.delete(`/addresses/${addressId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['addresses'] });
      Alert.alert('Succès', 'L\'adresse a été supprimée.');
    },
    onError: () => Alert.alert('Erreur', 'Impossible de supprimer l\'adresse.')
  });

  const handleDelete = (id: number, label: string) => {
    Alert.alert(
      "Suppression",
      `Voulez-vous vraiment supprimer votre adresse "${label}" ?`,
      [
        { text: "Annuler", style: "cancel" },
        { text: "Supprimer", style: "destructive", onPress: () => deleteMutation.mutate(id) }
      ]
    );
  };

  const renderAddress = ({ item }: { item: Address }) => (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <View style={styles.labelContainer}>
          <View style={styles.iconBg}>
            <Ionicons name={getAddressIcon(item.label)} size={18} color="#0056b3" />
          </View>
          <Text style={styles.label}>{item.label}</Text>
        </View>
        {item.is_default && (
          <View style={styles.defaultBadge}>
            <Ionicons name="star" size={10} color="#fff" />
            <Text style={styles.defaultText}>Par défaut</Text>
          </View>
        )}
      </View>
      
      <View style={styles.cardBody}>
        <Text style={styles.name}>{item.prenom} {item.nom}</Text>
        <Text style={styles.address}>{item.adresse1}</Text>
        <Text style={styles.address}>{item.code_postal} {item.ville}, {item.pays || 'France'}</Text>
      </View>

      <View style={styles.actions}>
        <TouchableOpacity 
          style={styles.actionBtn} 
          onPress={() => navigation.navigate('EditAddress', { address: item })}
        >
          <Ionicons name="create-outline" size={16} color="#64748B" />
          <Text style={styles.actionEditText}>Modifier</Text>
        </TouchableOpacity>
        
        <TouchableOpacity 
          style={[styles.actionBtn, styles.deleteBtn]} 
          onPress={() => handleDelete(item.id, item.label)}
        >
          <Ionicons name="trash-outline" size={16} color="#EF4444" />
          <Text style={styles.actionDeleteText}>Supprimer</Text>
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
        <Text style={styles.headerTitle}>Mes adresses</Text>
        <View style={{ width: 24 }} />
      </View>

      <FlatList
        data={addresses}
        keyExtractor={item => item.id.toString()}
        contentContainerStyle={{ padding: 20, flexGrow: 1 }}
        renderItem={renderAddress}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Ionicons name="location-outline" size={80} color="#CBD5E1" />
            <Text style={styles.emptyTitle}>Aucune adresse enregistrée</Text>
            <Text style={styles.emptySubtitle}>Ajoutez une adresse pour faciliter vos prochaines commandes.</Text>
          </View>
        }
      />

      <View style={styles.footer}>
        <TouchableOpacity 
          style={styles.addButton} 
          onPress={() => navigation.navigate('EditAddress', { address: undefined })}
        >
          <Ionicons name="add-circle" size={22} color="#fff" />
          <Text style={styles.addButtonText}>Ajouter une adresse</Text>
        </TouchableOpacity>
      </View>
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
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 15 },
  labelContainer: { flexDirection: 'row', alignItems: 'center' },
  iconBg: { width: 36, height: 36, borderRadius: 10, backgroundColor: '#EFF6FF', justifyContent: 'center', alignItems: 'center', marginRight: 10 },
  label: { fontSize: 16, fontWeight: 'bold', color: '#1E293B' },
  defaultBadge: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#0056b3', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 20, gap: 4 },
  defaultText: { fontSize: 11, fontWeight: 'bold', color: '#fff' },
  cardBody: { paddingBottom: 15, borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  name: { fontSize: 15, fontWeight: '600', color: '#334155', marginBottom: 4 },
  address: { fontSize: 14, color: '#64748B', lineHeight: 20 },
  actions: { flexDirection: 'row', marginTop: 15, gap: 10 },
  actionBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: '#F8FAFC', paddingVertical: 10, borderRadius: 10, gap: 6 },
  deleteBtn: { backgroundColor: '#FEF2F2' },
  actionEditText: { fontSize: 14, fontWeight: '600', color: '#64748B' },
  actionDeleteText: { fontSize: 14, fontWeight: '600', color: '#EF4444' },
  emptyContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 40 },
  emptyTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B', marginTop: 20 },
  emptySubtitle: { fontSize: 14, color: '#64748B', textAlign: 'center', marginTop: 8 },
  footer: { backgroundColor: '#fff', padding: 20, borderTopWidth: 1, borderTopColor: '#F1F5F9' },
  addButton: { backgroundColor: '#0056b3', padding: 16, borderRadius: 12, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', shadowColor: '#0056b3', shadowOpacity: 0.3, shadowOffset: { height: 4, width: 0 }, shadowRadius: 8, elevation: 5 },
  addButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold', marginLeft: 8 }
});