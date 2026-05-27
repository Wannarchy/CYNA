import React, { useState, useEffect } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  ScrollView, ActivityIndicator, Alert, KeyboardAvoidingView, Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import apiClient from '../../utils/apiClient';
import useCartStore from '../../store/cartStore';
import useAuthStore from '../../store/authStore';
import CartItem from '../../components/CartItem';

export default function CheckoutScreen({ navigation }) {
  const { items, getTotal, clearCart, syncWithServer } = useCartStore();
  const { user } = useAuthStore();

  const [billingName, setBillingName]       = useState(`${user?.prenom ?? ''} ${user?.nom ?? ''}`.trim());
  const [billingAddress, setBillingAddress] = useState('');
  const [isLoading, setIsLoading]           = useState(false);
  const [syncing, setSyncing]               = useState(true);
  const [fieldErrors, setFieldErrors]       = useState({});

  useEffect(() => {
    syncWithServer().finally(() => setSyncing(false));
  }, []);

  const validate = () => {
    const e = {};
    if (!billingName.trim())    e.billingName    = 'Nom / société requis.';
    if (!billingAddress.trim()) e.billingAddress = 'Adresse de facturation requise.';
    setFieldErrors(e);
    return !Object.keys(e).length;
  };

  const handleConfirm = async () => {
    if (!validate()) return;
    setIsLoading(true);
    try {
      const res = await apiClient.post('/orders/store.php', {
        billing_name:    billingName.trim(),
        billing_address: billingAddress.trim(),
      });
      clearCart();
      navigation.replace('Confirmation', { orderId: res.data.order_id, total: res.data.total });
    } catch (err) {
      Alert.alert('Erreur', err.response?.data?.message ?? 'Erreur lors de la commande. Réessayez.');
    } finally {
      setIsLoading(false);
    }
  };

  if (syncing) return (
    <View style={styles.centered}>
      <ActivityIndicator size="large" color="#4F46E5" />
      <Text style={styles.syncText}>Préparation de la commande…</Text>
    </View>
  );

  if (!items.length) return (
    <View style={styles.centered}>
      <Ionicons name="cart-outline" size={48} color="#D1D5DB" />
      <Text style={styles.emptyText}>Votre panier est vide.</Text>
      <TouchableOpacity style={styles.retryBtn} onPress={() => navigation.navigate('Catalogue')}>
        <Text style={styles.retryText}>Voir le catalogue</Text>
      </TouchableOpacity>
    </View>
  );

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>

        <View style={styles.header}>
          <TouchableOpacity onPress={() => navigation.goBack()}><Ionicons name="arrow-back" size={22} color="#374151" /></TouchableOpacity>
          <Text style={styles.headerTitle}>Finaliser la commande</Text>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Récapitulatif</Text>
          {items.map((item) => <CartItem key={item.id} item={item} showCycleSwitch={false} readonly />)}
        </View>

        <View style={styles.totalCard}>
          <Text style={styles.totalLabel}>Total à régler</Text>
          <Text style={styles.totalAmount}>{getTotal()} €</Text>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Adresse de facturation</Text>
          <View style={styles.formCard}>

            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Nom / Société</Text>
              <View style={[styles.inputRow, fieldErrors.billingName && styles.inputError]}>
                <Ionicons name="person-outline" size={16} color="#9CA3AF" style={styles.icon} />
                <TextInput style={styles.input} placeholder="Jean Dupont ou Acme SAS" placeholderTextColor="#9CA3AF"
                  value={billingName} onChangeText={(v) => { setBillingName(v); setFieldErrors((p) => ({ ...p, billingName: null })); }}
                  autoCapitalize="words" />
              </View>
              {fieldErrors.billingName ? <Text style={styles.fieldError}>{fieldErrors.billingName}</Text> : null}
            </View>

            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Adresse complète</Text>
              <View style={[styles.inputRow, styles.inputMultiline, fieldErrors.billingAddress && styles.inputError]}>
                <Ionicons name="location-outline" size={16} color="#9CA3AF" style={[styles.icon, { marginTop: 4 }]} />
                <TextInput style={[styles.input, { textAlignVertical: 'top' }]}
                  placeholder={'12 rue de la Paix\n75001 Paris, France'} placeholderTextColor="#9CA3AF"
                  value={billingAddress} onChangeText={(v) => { setBillingAddress(v); setFieldErrors((p) => ({ ...p, billingAddress: null })); }}
                  multiline numberOfLines={3} />
              </View>
              {fieldErrors.billingAddress ? <Text style={styles.fieldError}>{fieldErrors.billingAddress}</Text> : null}
            </View>
          </View>
        </View>

        <View style={styles.infoBanner}>
          <Ionicons name="information-circle-outline" size={16} color="#4F46E5" />
          <Text style={styles.infoText}>Le paiement en production sera activé sous 48h. Cette commande est enregistrée immédiatement.</Text>
        </View>

        <View style={{ height: 100 }} />
      </ScrollView>

      <View style={styles.cta}>
        <TouchableOpacity style={[styles.confirmBtn, isLoading && { opacity: 0.6 }]} onPress={handleConfirm} disabled={isLoading}>
          {isLoading
            ? <ActivityIndicator color="#FFF" />
            : <><Ionicons name="checkmark-circle-outline" size={20} color="#FFF" /><Text style={styles.confirmBtnText}>Confirmer la commande · {getTotal()} €</Text></>
          }
        </TouchableOpacity>
      </View>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  centered:  { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 32 },
  scroll:    { paddingBottom: 24 },
  header:    { flexDirection: 'row', alignItems: 'center', gap: 12, paddingHorizontal: 16, paddingTop: 56, paddingBottom: 16, backgroundColor: '#FFF', borderBottomWidth: 1, borderBottomColor: '#F3F4F6' },
  headerTitle: { fontSize: 18, fontWeight: '700', color: '#111827' },
  section:   { paddingHorizontal: 16, paddingTop: 20 },
  sectionTitle: { fontSize: 15, fontWeight: '700', color: '#111827', marginBottom: 10 },
  totalCard: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginHorizontal: 16, marginTop: 4, backgroundColor: '#4F46E5', borderRadius: 12, padding: 16 },
  totalLabel:{ fontSize: 15, color: 'rgba(255,255,255,0.8)' },
  totalAmount: { fontSize: 22, fontWeight: '700', color: '#FFF' },
  formCard:  { backgroundColor: '#FFF', borderRadius: 14, padding: 16, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 6, elevation: 2 },
  fieldGroup:{ marginBottom: 16 },
  label:     { fontSize: 13, fontWeight: '600', color: '#374151', marginBottom: 6 },
  inputRow:  { flexDirection: 'row', alignItems: 'center', backgroundColor: '#F9FAFB', borderRadius: 10, borderWidth: 1, borderColor: '#E5E7EB', paddingHorizontal: 12, height: 48 },
  inputMultiline: { height: 88, alignItems: 'flex-start', paddingTop: 12 },
  inputError:{ borderColor: '#F87171' },
  icon:      { marginRight: 8 },
  input:     { flex: 1, fontSize: 14, color: '#111827' },
  fieldError:{ color: '#EF4444', fontSize: 12, marginTop: 4 },
  infoBanner:{ flexDirection: 'row', alignItems: 'flex-start', gap: 8, marginHorizontal: 16, marginTop: 16, backgroundColor: '#EEF2FF', borderRadius: 10, padding: 12 },
  infoText:  { flex: 1, fontSize: 12, color: '#4F46E5', lineHeight: 17 },
  cta:       { position: 'absolute', bottom: 0, left: 0, right: 0, backgroundColor: '#FFF', padding: 16, borderTopWidth: 1, borderTopColor: '#E5E7EB' },
  confirmBtn:{ backgroundColor: '#4F46E5', borderRadius: 12, height: 54, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 8 },
  confirmBtnText: { color: '#FFF', fontSize: 15, fontWeight: '600' },
  syncText:  { color: '#6B7280', marginTop: 12, fontSize: 14 },
  emptyText: { color: '#9CA3AF', marginTop: 12, fontSize: 14 },
  retryBtn:  { backgroundColor: '#4F46E5', borderRadius: 10, paddingHorizontal: 20, paddingVertical: 10, marginTop: 16 },
  retryText: { color: '#FFF', fontWeight: '600' },
});
