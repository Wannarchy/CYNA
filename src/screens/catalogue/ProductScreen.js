import React, { useEffect, useState } from 'react';
import { View, Text, ScrollView, TouchableOpacity, StyleSheet, ActivityIndicator, Switch } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import apiClient from '../../utils/apiClient';
import useCartStore from '../../store/cartStore';
import ProductCard from '../../components/ProductCard';

const SPECS = ['Supervision et alertes en temps réel', 'Tableaux de bord & reporting', 'Conformité & journalisation', 'Support SLA inclus', 'Déploiement SaaS rapide'];

export default function ProductScreen({ route, navigation }) {
  const { productId } = route.params;
  const [product, setProduct] = useState(null);
  const [similar, setSimilar] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState(null);
  const [cycle, setCycle]     = useState('monthly');
  const { addItem, items }    = useCartStore();
  const inCart = items.some((i) => i.id === product?.id);

  useEffect(() => { fetchProduct(); }, [productId]);

  const fetchProduct = async () => {
    setLoading(true); setError(null);
    try {
      const res = await apiClient.get(`/products/show.php?id=${productId}`);
      setProduct(res.data.product); setSimilar(res.data.similar ?? []);
    } catch { setError('Impossible de charger ce produit.'); }
    finally { setLoading(false); }
  };

  const handleAdd = () => {
    if (!product?.is_available || inCart) return;
    addItem({ ...product, cycle });
    navigation.navigate('Panier');
  };

  if (loading) return <View style={styles.centered}><ActivityIndicator size="large" color="#4F46E5" /></View>;
  if (error || !product) return (
    <View style={styles.centered}>
      <Text style={styles.errorText}>{error ?? 'Produit introuvable.'}</Text>
      <TouchableOpacity style={styles.retryBtn} onPress={fetchProduct}><Text style={styles.retryText}>Réessayer</Text></TouchableOpacity>
    </View>
  );

  const unitPrice = cycle === 'yearly' ? parseFloat(product.price_yearly) : parseFloat(product.price_monthly);
  const saving    = parseFloat((parseFloat(product.price_monthly) * 12 - parseFloat(product.price_yearly)).toFixed(2));

  return (
    <View style={styles.container}>
      <ScrollView showsVerticalScrollIndicator={false}>
        <View style={styles.hero}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Ionicons name="arrow-back" size={20} color="#FFF" />
          </TouchableOpacity>
          <View style={styles.heroIcon}><Ionicons name="shield-checkmark" size={48} color="#FFF" /></View>
          {product.category_name ? <View style={styles.heroBadge}><Text style={styles.heroBadgeText}>{product.category_name}</Text></View> : null}
          <Text style={styles.heroName}>{product.name}</Text>
          <View style={[styles.availBadge, { backgroundColor: product.is_available ? '#059669' : '#6B7280' }]}>
            <Ionicons name={product.is_available ? 'checkmark-circle' : 'close-circle'} size={12} color="#FFF" />
            <Text style={styles.availText}>{product.is_available ? 'Disponible immédiatement' : 'Momentanément indisponible'}</Text>
          </View>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Cycle de facturation</Text>
          <View style={styles.cycleCard}>
            <View style={styles.cycleRow}>
              <Text style={[styles.cycleLabel, cycle === 'monthly' && styles.cycleLabelOn]}>Mensuel</Text>
              <Switch value={cycle === 'yearly'} onValueChange={(v) => setCycle(v ? 'yearly' : 'monthly')}
                trackColor={{ false: '#E5E7EB', true: '#4F46E5' }} thumbColor="#FFF" />
              <Text style={[styles.cycleLabel, cycle === 'yearly' && styles.cycleLabelOn]}>Annuel</Text>
            </View>
            <View style={styles.priceRow}>
              <View>
                <Text style={styles.priceMain}>{unitPrice.toFixed(2)} €</Text>
                <Text style={styles.priceSub}>{cycle === 'yearly' ? 'par an' : 'par mois'}</Text>
              </View>
              {cycle === 'yearly' && saving > 0 && (
                <View style={styles.savingBadge}>
                  <Ionicons name="leaf-outline" size={13} color="#059669" />
                  <Text style={styles.savingText}>Économisez {saving.toFixed(2)} €/an</Text>
                </View>
              )}
            </View>
            {cycle === 'monthly' && <Text style={styles.yearlyHint}>Passez en annuel et économisez {saving.toFixed(2)} €</Text>}
          </View>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Caractéristiques techniques</Text>
          <View style={styles.specsCard}>
            {SPECS.map((s, i) => (
              <View key={i} style={styles.specItem}>
                <Ionicons name="checkmark-circle" size={16} color="#4F46E5" />
                <Text style={styles.specText}>{s}</Text>
              </View>
            ))}
          </View>
        </View>

        {similar.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Services similaires</Text>
            {similar.map((item) => (
              <ProductCard key={item.id} product={item} compact onPress={() => navigation.push('ProductDetail', { productId: item.id })} />
            ))}
          </View>
        )}
        <View style={{ height: 110 }} />
      </ScrollView>

      <View style={styles.cta}>
        <View>
          <Text style={styles.ctaPrice}>{unitPrice.toFixed(2)} €</Text>
          <Text style={styles.ctaPriceSub}>{cycle === 'yearly' ? '/an' : '/mois'}</Text>
        </View>
        <TouchableOpacity style={[styles.ctaBtn, inCart && styles.ctaBtnActive, !product.is_available && styles.ctaBtnDisabled]}
          onPress={handleAdd} disabled={!product.is_available || inCart}>
          <Ionicons name={inCart ? 'checkmark-circle' : 'cart'} size={18} color="#FFF" />
          <Text style={styles.ctaBtnText}>{!product.is_available ? 'Indisponible' : inCart ? 'Déjà dans le panier' : "S'abonner"}</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  centered:  { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
  hero:      { backgroundColor: '#4F46E5', paddingTop: 56, paddingHorizontal: 20, paddingBottom: 28, alignItems: 'center' },
  backBtn:   { position: 'absolute', top: 52, left: 16, width: 36, height: 36, borderRadius: 10, backgroundColor: 'rgba(0,0,0,0.2)', justifyContent: 'center', alignItems: 'center' },
  heroIcon:  { width: 80, height: 80, borderRadius: 24, backgroundColor: 'rgba(255,255,255,0.15)', justifyContent: 'center', alignItems: 'center', marginBottom: 16 },
  heroBadge: { backgroundColor: 'rgba(255,255,255,0.2)', borderRadius: 6, paddingHorizontal: 10, paddingVertical: 3, marginBottom: 10 },
  heroBadgeText: { color: '#FFF', fontSize: 12, fontWeight: '600' },
  heroName:  { fontSize: 22, fontWeight: '700', color: '#FFF', textAlign: 'center', marginBottom: 14 },
  availBadge:{ flexDirection: 'row', alignItems: 'center', gap: 5, borderRadius: 8, paddingHorizontal: 10, paddingVertical: 5 },
  availText: { color: '#FFF', fontSize: 12, fontWeight: '600' },
  section:   { paddingHorizontal: 16, paddingTop: 20 },
  sectionTitle: { fontSize: 15, fontWeight: '700', color: '#111827', marginBottom: 10 },
  cycleCard: { backgroundColor: '#FFF', borderRadius: 14, padding: 16, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 6, elevation: 2 },
  cycleRow:  { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10, marginBottom: 14 },
  cycleLabel:{ fontSize: 14, color: '#9CA3AF' },
  cycleLabelOn: { color: '#111827', fontWeight: '700' },
  priceRow:  { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  priceMain: { fontSize: 28, fontWeight: '700', color: '#111827' },
  priceSub:  { fontSize: 13, color: '#9CA3AF' },
  savingBadge: { flexDirection: 'row', alignItems: 'center', gap: 5, backgroundColor: '#D1FAE5', borderRadius: 8, paddingHorizontal: 10, paddingVertical: 6 },
  savingText:{ color: '#059669', fontSize: 12, fontWeight: '600' },
  yearlyHint:{ fontSize: 12, color: '#6B7280', marginTop: 10, textAlign: 'center' },
  specsCard: { backgroundColor: '#FFF', borderRadius: 14, padding: 16, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 6, elevation: 2 },
  specItem:  { flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 10 },
  specText:  { fontSize: 14, color: '#374151', flex: 1 },
  cta:       { position: 'absolute', bottom: 0, left: 0, right: 0, backgroundColor: '#FFF', flexDirection: 'row', alignItems: 'center', padding: 16, borderTopWidth: 1, borderTopColor: '#E5E7EB', gap: 12 },
  ctaPrice:  { fontSize: 20, fontWeight: '700', color: '#111827' },
  ctaPriceSub: { fontSize: 12, color: '#9CA3AF' },
  ctaBtn:    { flex: 1, backgroundColor: '#4F46E5', borderRadius: 12, height: 50, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 8 },
  ctaBtnActive: { backgroundColor: '#059669' },
  ctaBtnDisabled: { backgroundColor: '#D1D5DB' },
  ctaBtnText:{ color: '#FFF', fontSize: 15, fontWeight: '600' },
  errorText: { color: '#EF4444', marginBottom: 12 },
  retryBtn:  { backgroundColor: '#4F46E5', borderRadius: 10, paddingHorizontal: 20, paddingVertical: 10 },
  retryText: { color: '#FFF', fontWeight: '600' },
});
