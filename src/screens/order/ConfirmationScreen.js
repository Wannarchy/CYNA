import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

export default function ConfirmationScreen({ route, navigation }) {
  const { orderId, total } = route.params ?? {};

  return (
    <View style={styles.container}>
      <View style={styles.iconBox}>
        <Ionicons name="checkmark-circle" size={72} color="#059669" />
      </View>
      <Text style={styles.title}>Commande confirmée !</Text>
      <Text style={styles.subtitle}>
        Merci pour votre confiance.{'\n'}
        Votre commande <Text style={styles.ref}>#{orderId}</Text> a bien été enregistrée.
      </Text>

      {total !== undefined && (
        <View style={styles.totalCard}>
          <Text style={styles.totalLabel}>Total réglé</Text>
          <Text style={styles.totalAmount}>{parseFloat(total).toFixed(2)} €</Text>
        </View>
      )}

      <TouchableOpacity style={styles.btnPrimary} onPress={() => navigation.navigate('Commandes')}>
        <Ionicons name="receipt-outline" size={18} color="#FFF" />
        <Text style={styles.btnPrimaryText}>Voir mes commandes</Text>
      </TouchableOpacity>

      <TouchableOpacity style={styles.btnOutline} onPress={() => navigation.navigate('Catalogue')}>
        <Text style={styles.btnOutlineText}>Retour au catalogue</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB', justifyContent: 'center', alignItems: 'center', padding: 32 },
  iconBox:   { width: 120, height: 120, borderRadius: 30, backgroundColor: '#ECFDF5', justifyContent: 'center', alignItems: 'center', marginBottom: 24 },
  title:     { fontSize: 26, fontWeight: '700', color: '#111827', marginBottom: 12 },
  subtitle:  { fontSize: 15, color: '#6B7280', textAlign: 'center', lineHeight: 22, marginBottom: 24 },
  ref:       { fontWeight: '700', color: '#374151' },
  totalCard: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', width: '100%', backgroundColor: '#FFF', borderRadius: 14, padding: 16, marginBottom: 32, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 6, elevation: 2 },
  totalLabel:{ fontSize: 15, color: '#6B7280' },
  totalAmount: { fontSize: 20, fontWeight: '700', color: '#111827' },
  btnPrimary:{ width: '100%', backgroundColor: '#4F46E5', borderRadius: 12, height: 52, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 8, marginBottom: 12 },
  btnPrimaryText: { color: '#FFF', fontSize: 15, fontWeight: '600' },
  btnOutline:{ width: '100%', borderWidth: 1.5, borderColor: '#E5E7EB', borderRadius: 12, height: 52, justifyContent: 'center', alignItems: 'center' },
  btnOutlineText: { color: '#374151', fontSize: 15, fontWeight: '500' },
});
