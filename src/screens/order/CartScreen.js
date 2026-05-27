import React from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import useCartStore from '../../store/cartStore';
import CartItem from '../../components/CartItem';

export default function CartScreen({ navigation }) {
  const { items, clearCart, getTotal, getCount } = useCartStore();

  if (!items.length) return (
    <View style={styles.empty}>
      <Ionicons name="cart-outline" size={64} color="#D1D5DB" />
      <Text style={styles.emptyTitle}>Votre panier est vide</Text>
      <Text style={styles.emptyText}>Ajoutez des solutions depuis le catalogue</Text>
    </View>
  );

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Panier</Text>
        <TouchableOpacity onPress={clearCart}><Text style={styles.clear}>Vider</Text></TouchableOpacity>
      </View>
      <FlatList data={items} keyExtractor={(i) => String(i.id)}
        renderItem={({ item }) => <CartItem item={item} showCycleSwitch readonly={false} />}
        contentContainerStyle={styles.list} showsVerticalScrollIndicator={false} />
      <View style={styles.footer}>
        <View style={styles.totalRow}>
          <View>
            <Text style={styles.totalLabel}>Total</Text>
            <Text style={styles.totalSub}>{getCount()} produit{getCount() > 1 ? 's' : ''}</Text>
          </View>
          <Text style={styles.totalAmount}>{getTotal()} €</Text>
        </View>
        <TouchableOpacity style={styles.btn} onPress={() => navigation.navigate('Checkout')}>
          <Text style={styles.btnText}>Passer commande</Text>
          <Ionicons name="arrow-forward" size={18} color="#FFF" />
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  empty:     { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 32 },
  emptyTitle:{ fontSize: 18, fontWeight: '600', color: '#374151', marginTop: 16 },
  emptyText: { fontSize: 14, color: '#9CA3AF', marginTop: 6, textAlign: 'center' },
  header:    { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, paddingTop: 56, paddingBottom: 16, backgroundColor: '#FFF' },
  title:     { fontSize: 26, fontWeight: '700', color: '#111827' },
  clear:     { color: '#EF4444', fontSize: 14, fontWeight: '500' },
  list:      { padding: 16, paddingBottom: 140 },
  footer:    { position: 'absolute', bottom: 0, left: 0, right: 0, backgroundColor: '#FFF', padding: 20, borderTopWidth: 1, borderTopColor: '#E5E7EB' },
  totalRow:  { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14 },
  totalLabel:{ fontSize: 15, color: '#6B7280' },
  totalSub:  { fontSize: 12, color: '#9CA3AF', marginTop: 2 },
  totalAmount: { fontSize: 22, fontWeight: '700', color: '#111827' },
  btn:       { backgroundColor: '#4F46E5', borderRadius: 12, height: 52, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 8 },
  btnText:   { color: '#FFF', fontSize: 16, fontWeight: '600' },
});
