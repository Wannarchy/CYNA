import React from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, Alert } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { mockAddresses } from '../data/mockData';

export default function AddressBookScreen() {
  const navigation = useNavigation<any>();

  const renderAddress = ({ item }: { item: typeof mockAddresses[0] }) => (
    <View style={[styles.card, item.is_default && styles.defaultCard]}>
      <View style={styles.headerRow}>
        <Text style={styles.label}>{item.label}</Text>
        {item.is_default && <View style={styles.defaultBadge}><Text style={styles.defaultText}>Par défaut</Text></View>}
      </View>
      
      <Text style={styles.name}>{item.prenom} {item.nom}</Text>
      <Text style={styles.address}>{item.adresse1}</Text>
      <Text style={styles.address}>{item.code_postal} {item.ville}</Text>

      <View style={styles.actions}>
        <TouchableOpacity style={styles.actionBtn} onPress={() => Alert.alert("Édition", "Formulaire d'édition d'adresse (à brancher API)")}>
          <Text style={styles.actionText}>Modifier</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.actionBtn, { borderLeftWidth: 1, borderLeftColor: '#eee' }]} onPress={() => Alert.alert("Suppression", "Voulez-vous supprimer cette adresse ?")}>
          <Text style={[styles.actionText, { color: '#e74c3c' }]}>Supprimer</Text>
        </TouchableOpacity>
      </View>
    </View>
  );

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}>
          <Text style={styles.backText}>← Retour</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Mes adresses</Text>
        <View style={{ width: 50 }} />
      </View>

      <FlatList
        data={mockAddresses}
        keyExtractor={item => item.id.toString()}
        contentContainerStyle={{ padding: 15 }}
        renderItem={renderAddress}
      />

      <TouchableOpacity style={styles.addButton} onPress={() => Alert.alert("Ajout", "Formulaire d'ajout d'adresse (à brancher API)")}>
        <Ionicons name="add-circle-outline" size={24} color="#fff" />
        <Text style={styles.addButtonText}>Ajouter une nouvelle adresse</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F5F7FA' },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 20, backgroundColor: '#fff' },
  backText: { color: '#0056b3', fontSize: 16, fontWeight: '600' },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#2C3E50' },
  card: { backgroundColor: '#fff', borderRadius: 10, padding: 15, marginBottom: 15, shadowColor: '#000', shadowOpacity: 0.03, shadowRadius: 4, elevation: 2, borderLeftWidth: 4, borderLeftColor: 'transparent' },
  defaultCard: { borderLeftColor: '#27ae60' },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  label: { fontSize: 16, fontWeight: 'bold', color: '#2C3E50' },
  defaultBadge: { backgroundColor: '#e8f5e9', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 4 },
  defaultText: { fontSize: 12, fontWeight: 'bold', color: '#27ae60' },
  name: { fontSize: 15, color: '#555', marginBottom: 4 },
  address: { fontSize: 14, color: '#7f8c8d', marginBottom: 2 },
  actions: { flexDirection: 'row', borderTopWidth: 1, borderColor: '#eee', marginTop: 15, paddingTop: 15 },
  actionBtn: { flex: 1, alignItems: 'center', paddingVertical: 5 },
  actionText: { fontSize: 14, fontWeight: '600', color: '#0056b3' },
  addButton: { backgroundColor: '#0056b3', margin: 20, padding: 16, borderRadius: 10, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', shadowColor: '#0056b3', shadowOpacity: 0.2, shadowOffset: {
      height: 4,
      width: 0
  }, shadowRadius: 8, elevation: 4 },
  addButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold', marginLeft: 10 }
});