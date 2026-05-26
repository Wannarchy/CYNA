import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet, Switch } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import useCartStore from '../store/cartStore';

export default function CartItem({ item, showCycleSwitch = true, readonly = false }) {
  const { removeItem, setCycle, getUnitPrice, getYearlySaving } = useCartStore();
  const isYearly  = item.cycle === 'yearly';
  const unitPrice = getUnitPrice(item);
  const saving    = getYearlySaving(item);

  return (
    <View style={styles.card}>
      <View style={styles.header}>
        <View style={styles.iconBox}><Ionicons name="shield-checkmark-outline" size={20} color="#4F46E5" /></View>
        <View style={styles.info}>
          <Text style={styles.name} numberOfLines={1}>{item.name}</Text>
          {item.category_name ? <Text style={styles.type}>{item.category_name}</Text> : null}
        </View>
        {!readonly && <TouchableOpacity onPress={() => removeItem(item.id)} hitSlop={8}><Ionicons name="trash-outline" size={17} color="#EF4444" /></TouchableOpacity>}
      </View>

      {showCycleSwitch && !readonly && (
        <View style={styles.cycleRow}>
          <Text style={[styles.cycleLabel, !isYearly && styles.cycleLabelOn]}>Mensuel</Text>
          <Switch value={isYearly} onValueChange={(v) => setCycle(item.id, v ? 'yearly' : 'monthly')}
            trackColor={{ false: '#E5E7EB', true: '#4F46E5' }} thumbColor="#FFF"
            style={{ transform: [{ scaleX: 0.85 }, { scaleY: 0.85 }] }} />
          <Text style={[styles.cycleLabel, isYearly && styles.cycleLabelOn]}>Annuel</Text>
          {isYearly && saving > 0 && (
            <View style={styles.saveBadge}>
              <Ionicons name="leaf-outline" size={11} color="#059669" />
              <Text style={styles.saveText}>−{saving.toFixed(2)} €</Text>
            </View>
          )}
        </View>
      )}

      {readonly && (
        <View style={styles.cyclePill}>
          <Text style={styles.cyclePillText}>{isYearly ? '🗓 Annuel' : '📅 Mensuel'}</Text>
        </View>
      )}

      <View style={styles.footer}>
        <Text style={styles.cycleDetail}>{isYearly ? `${parseFloat(item.price_yearly).toFixed(2)} €/an` : `${parseFloat(item.price_monthly).toFixed(2)} €/mois`}</Text>
        <Text style={styles.price}>{unitPrice.toFixed(2)} €{isYearly ? '/an' : '/mois'}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card:      { backgroundColor: '#FFF', borderRadius: 14, padding: 14, marginBottom: 10, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 5, shadowOffset: { width: 0, height: 2 }, elevation: 2 },
  header:    { flexDirection: 'row', alignItems: 'center', marginBottom: 10 },
  iconBox:   { width: 40, height: 40, borderRadius: 10, backgroundColor: '#EEF2FF', justifyContent: 'center', alignItems: 'center', marginRight: 10 },
  info:      { flex: 1 },
  name:      { fontSize: 14, fontWeight: '600', color: '#111827' },
  type:      { fontSize: 12, color: '#9CA3AF', marginTop: 2 },
  cycleRow:  { flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: '#F9FAFB', borderRadius: 8, padding: 8, marginBottom: 10 },
  cycleLabel:{ fontSize: 12, color: '#9CA3AF' },
  cycleLabelOn: { color: '#111827', fontWeight: '600' },
  saveBadge: { flexDirection: 'row', alignItems: 'center', gap: 3, backgroundColor: '#D1FAE5', borderRadius: 6, paddingHorizontal: 7, paddingVertical: 2, marginLeft: 'auto' },
  saveText:  { fontSize: 11, color: '#059669', fontWeight: '600' },
  cyclePill: { backgroundColor: '#F3F4F6', borderRadius: 8, paddingHorizontal: 10, paddingVertical: 5, marginBottom: 10, alignSelf: 'flex-start' },
  cyclePillText: { fontSize: 12, color: '#374151' },
  footer:    { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  cycleDetail: { fontSize: 12, color: '#9CA3AF' },
  price:     { fontSize: 16, fontWeight: '700', color: '#111827' },
});
