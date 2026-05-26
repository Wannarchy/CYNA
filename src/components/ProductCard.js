import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import useCartStore from '../store/cartStore';

export default function ProductCard({ product, onPress, compact = false }) {
  const { addItem, items } = useCartStore();
  const inCart = items.some((i) => i.id === product.id);

  const handleAdd = () => addItem({ ...product, cycle: 'monthly' });

  if (compact) return (
    <TouchableOpacity style={styles.compact} onPress={onPress} activeOpacity={0.8}>
      <View style={styles.compactIcon}><Ionicons name="shield-checkmark-outline" size={20} color="#4F46E5" /></View>
      <View style={styles.compactBody}>
        <Text style={styles.compactName} numberOfLines={1}>{product.name}</Text>
        <Text style={styles.compactPrice}>{parseFloat(product.price_monthly).toFixed(2)} €<Text style={styles.sub}>/mois</Text></Text>
      </View>
      <TouchableOpacity style={[styles.addBtn, inCart && styles.addBtnActive]} onPress={handleAdd} disabled={!product.is_available}>
        <Ionicons name={inCart ? 'checkmark' : 'add'} size={16} color="#FFF" />
      </TouchableOpacity>
    </TouchableOpacity>
  );

  return (
    <TouchableOpacity style={styles.card} onPress={onPress} activeOpacity={0.85}>
      {product.category_name ? <View style={styles.typeBadge}><Text style={styles.typeBadgeText}>{product.category_name}</Text></View> : null}
      <View style={styles.iconBox}><Ionicons name="shield-checkmark-outline" size={32} color="#4F46E5" /></View>
      <Text style={styles.name} numberOfLines={1}>{product.name}</Text>
      {!product.is_available && <View style={styles.unavailBadge}><Text style={styles.unavailText}>Momentanément indisponible</Text></View>}
      <View style={styles.footer}>
        <View>
          <Text style={styles.price}>{parseFloat(product.price_monthly).toFixed(2)} €</Text>
          <Text style={styles.priceSub}>par mois</Text>
        </View>
        <TouchableOpacity style={[styles.addBtn, { paddingHorizontal: 14, paddingVertical: 9, flexDirection: 'row', gap: 6 }, inCart && styles.addBtnActive, !product.is_available && styles.addBtnDisabled]}
          onPress={handleAdd} disabled={!product.is_available}>
          <Ionicons name={inCart ? 'checkmark' : 'add'} size={18} color="#FFF" />
          <Text style={styles.addBtnText}>{inCart ? 'Ajouté' : 'Ajouter'}</Text>
        </TouchableOpacity>
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card:      { backgroundColor: '#FFF', borderRadius: 16, padding: 18, marginBottom: 12, shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, shadowOffset: { width: 0, height: 2 }, elevation: 3 },
  typeBadge: { alignSelf: 'flex-start', backgroundColor: '#EEF2FF', borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3, marginBottom: 12 },
  typeBadgeText: { fontSize: 11, color: '#4F46E5', fontWeight: '600' },
  iconBox:   { width: 56, height: 56, borderRadius: 16, backgroundColor: '#EEF2FF', justifyContent: 'center', alignItems: 'center', marginBottom: 12 },
  name:      { fontSize: 16, fontWeight: '700', color: '#111827', marginBottom: 6 },
  unavailBadge: { backgroundColor: '#FEE2E2', borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3, alignSelf: 'flex-start', marginBottom: 10 },
  unavailText: { fontSize: 11, color: '#DC2626', fontWeight: '600' },
  footer:    { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  price:     { fontSize: 20, fontWeight: '700', color: '#111827' },
  priceSub:  { fontSize: 12, color: '#9CA3AF' },
  addBtn:    { backgroundColor: '#4F46E5', borderRadius: 10, width: 36, height: 36, justifyContent: 'center', alignItems: 'center' },
  addBtnActive: { backgroundColor: '#059669' },
  addBtnDisabled: { backgroundColor: '#D1D5DB' },
  addBtnText:{ color: '#FFF', fontSize: 13, fontWeight: '600' },
  compact:   { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFF', borderRadius: 12, padding: 12, marginBottom: 8, shadowColor: '#000', shadowOpacity: 0.04, shadowRadius: 4, elevation: 1 },
  compactIcon: { width: 40, height: 40, borderRadius: 10, backgroundColor: '#EEF2FF', justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  compactBody: { flex: 1 },
  compactName: { fontSize: 14, fontWeight: '600', color: '#111827', marginBottom: 3 },
  compactPrice: { fontSize: 13, fontWeight: '700', color: '#4F46E5' },
  sub:       { fontSize: 11, fontWeight: '400', color: '#9CA3AF' },
});
