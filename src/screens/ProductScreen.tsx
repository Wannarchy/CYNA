import React, { useState, useContext } from 'react';
import { View, Text, ScrollView, Image, ImageBackground, StyleSheet, TouchableOpacity, Switch, Alert, ActivityIndicator } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useRoute, useNavigation } from '@react-navigation/native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery } from '@tanstack/react-query';
import api, { getFullImageUrl } from '../services/api';
import { CartContext } from '../context/CartContext';
import { RootStackParamList } from '../navigation/AppNavigator';
import { Product } from '../types'; // <-- IMPORT DU VRAI TYPE

type ProductScreenRouteProp = NativeStackScreenProps<RootStackParamList, 'Product'>['route'];
type ProductScreenNavigationProp = NativeStackScreenProps<RootStackParamList, 'Product'>['navigation'];

const FEATURES = [
  { icon: 'shield-checkmark-outline', text: 'Protection multi-terminaux', color: '#10b981' },
  { icon: 'time-outline', text: 'Support 24/7 par des experts', color: '#3b82f6' },
  { icon: 'analytics-outline', text: 'Tableau de bord temps réel', color: '#f59e0b' },
  { icon: 'lock-closed-outline', text: 'Conformité RGPD totale', color: '#8b5cf6' },
];

export default function ProductScreen() {
  const route = useRoute<ProductScreenRouteProp>();
  const navigation = useNavigation<ProductScreenNavigationProp>();
  const { productId } = route.params;
  const cart = useContext(CartContext);
  const addItem = cart?.addItem;

  const [isYearly, setIsYearly] = useState(false);

  // 1. Récupérer les infos du produit spécifique
  const { data: productData, isLoading } = useQuery({
    queryKey: ['product', productId],
    queryFn: async () => (await api.get(`/products/${productId}`)).data,
  });

  // 2. Récupérer la liste complète des produits
  const { data: allProductsData } = useQuery({
    queryKey: ['products'],
    queryFn: async () => (await api.get('/products')).data,
  });
  
  // --- DÉBALLAGE DES DONNÉES LARAVEL ---
  const product: Product | undefined = productData?.data ?? productData;
  const rawProducts = allProductsData?.data ?? allProductsData;
  const allProducts: Product[] = Array.isArray(rawProducts) ? rawProducts : [];
  
  const similarProducts = allProducts
    .filter((p) => p.category_id === product?.category_id && p.id !== product?.id)
    .slice(0, 3);

  const handleAddToCart = () => {
    if (!product) return;
    if (!addItem) {
      Alert.alert('Erreur', "Impossible d'ajouter le service au panier.");
      return;
    }
    const selectedCycle = isYearly ? 'yearly' : 'monthly';
    addItem(product, selectedCycle);

    Alert.alert(
      "Ajouté au panier",
      `${product.name} (Formule ${isYearly ? 'Annuelle' : 'Mensuelle'}) a été ajouté.`,
      [
        { text: "Continuer mes achats", style: "cancel" },
        { text: "Voir mon panier", onPress: () => navigation.navigate('MainTabs' as any, { screen: 'Cart' } as any) }
      ]
    );
  };

  if (isLoading || !product) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color="#0056b3" />
        <Text style={styles.loadingText}>Chargement du service...</Text>
      </View>
    );
  }

  const currentPrice = isYearly ? Number(product.price_yearly) : Number(product.price_monthly);
  const descriptionText = "Solution de cybersécurité de pointe conçue pour les entreprises. Assurez la protection de vos endpoints et la surveillance continue de votre infrastructure avec notre équipe d'experts dédiée.";

  return (
    <View style={styles.container}>
      <ScrollView showsVerticalScrollIndicator={false} style={{ flex: 1 }}>
        {/* HERO SECTION */}
        <ImageBackground source={{ uri: getFullImageUrl(product.image_path) }} style={styles.heroImage}>
          <View style={styles.overlayHero}>
            <Text style={styles.heroTitle}>{product.name}</Text>
            <View style={styles.trustBadges}>
              <View style={styles.trustBadge}>
                <Ionicons name="flash-outline" size={12} color="#fff" />
                <Text style={styles.trustBadgeText}>Setup en 24h</Text>
              </View>
              <View style={styles.trustBadge}>
                <Ionicons name="infinite-outline" size={12} color="#fff" />
                <Text style={styles.trustBadgeText}>Sans engagement</Text>
              </View>
            </View>
          </View>
        </ImageBackground>
        
        <View style={styles.content}>
          {/* Disponibilité */}
          <View style={[styles.badgeContainer, product.is_available ? styles.badgeSuccess : styles.badgeDanger]}>
            <Text style={[styles.badgeText, !product.is_available && {color: '#c039b2'}]}>
              {product.is_available ? 'Disponible immédiatement' : 'Service momentanément indisponible'}
            </Text>
          </View>

          {/* DESCRIPTION */}
          <Text style={styles.sectionTitle}>Description</Text>
          <Text style={styles.description}>{descriptionText}</Text>

          {/* CARACTÉRISTIQUES AVEC ICÔNES */}
          <Text style={styles.sectionTitle}>Ce qui est inclus</Text>
          <View style={styles.featuresCard}>
            {FEATURES.map((feature, index) => (
              <View key={index} style={[styles.featureRow, index !== FEATURES.length - 1 && styles.featureBorder]}>
                <View style={[styles.featureIconBg, { backgroundColor: `${feature.color}15` }]}>
                  <Ionicons name={feature.icon as any} size={20} color={feature.color} />
                </View>
                <Text style={styles.featureText}>{feature.text}</Text>
              </View>
            ))}
          </View>

          {/* PRODUITS SIMILAIRES */}
          {similarProducts.length > 0 && (
            <>
              <Text style={styles.sectionTitle}>Services similaires</Text>
              <View style={styles.similarContainer}>
                {similarProducts.map((p) => (
                  <TouchableOpacity 
                    key={p.id} 
                    style={styles.similarCard} 
                    onPress={() => navigation.replace('Product', { productId: String(p.id) })}
                  >
                    <Text style={styles.similarName}>{p.name}</Text>
                    <Text style={styles.similarPrice}>Dès {p.price_monthly} €/mois</Text>
                  </TouchableOpacity>
                ))}
              </View>
            </>
          )}
          <View style={{ height: 140 }} />
        </View>
      </ScrollView>

      {/* FOOTER MODERNE */}
      <View style={styles.footer}>
        <View style={styles.toggleContainer}>
          <Text style={[styles.toggleText, !isYearly && styles.textActive]}>Mensuel</Text>
          <Switch
            trackColor={{ false: "#cbd5e1", true: "#0056b3" }}
            thumbColor="#fff"
            onValueChange={setIsYearly}
            value={isYearly}
          />
          <Text style={[styles.toggleText, isYearly && styles.textActive]}>Annuel</Text>
          {isYearly && (
            <View style={styles.saveBadge}>
              <Text style={styles.saveBadgeText}>-16%</Text>
            </View>
          )}
        </View>

        <View style={styles.priceRow}>
          <View>
            <Text style={styles.priceLabel}>Prix TTC</Text>
            <Text style={styles.priceAmount}>{currentPrice.toFixed(2)} € <Text style={styles.priceCycle}>{isYearly ? '/an' : '/mois'}</Text></Text>
          </View>
          
          <TouchableOpacity 
            style={[styles.ctaButton, !product.is_available && styles.ctaDisabled]}
            onPress={handleAddToCart}
            disabled={!product.is_available}
          >
            <Ionicons name="cart-outline" size={20} color="#fff" style={{ marginRight: 8 }} />
            <Text style={styles.ctaText}>{product.is_available ? "S'abonner" : 'Indisponible'}</Text>
          </TouchableOpacity>
        </View>
        
        <View style={styles.secureLine}>
          <Ionicons name="lock-closed" size={12} color="#94a3b8" />
          <Text style={styles.secureText}>Paiement sécurisé. Annulez à tout moment.</Text>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#fff' },
  loadingText: { color: '#7f8c8d', marginTop: 15, fontSize: 16 },
  heroImage: { width: '100%', height: 240, justifyContent: 'flex-end' },
  overlayHero: { backgroundColor: 'rgba(15, 23, 42, 0.75)', padding: 25 },
  heroTitle: { color: '#fff', fontSize: 28, fontWeight: 'bold', marginBottom: 10 },
  trustBadges: { flexDirection: 'row', gap: 10 },
  trustBadge: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(255,255,255,0.15)', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 20 },
  trustBadgeText: { color: '#fff', fontSize: 11, fontWeight: '600', marginLeft: 4 },
  content: { padding: 20, borderTopLeftRadius: 24, borderTopRightRadius: 24, marginTop: -24, backgroundColor: '#F8FAFC', flex: 1 },
  badgeContainer: { alignSelf: 'flex-start', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 20, marginBottom: 20 },
  badgeSuccess: { backgroundColor: '#ecfdf5' },
  badgeDanger: { backgroundColor: '#fef2f2' },
  badgeText: { color: '#059669', fontSize: 12, fontWeight: '600' },
  sectionTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B', marginTop: 25, marginBottom: 12 },
  description: { color: '#64748B', lineHeight: 24, fontSize: 15 },
  featuresCard: { backgroundColor: '#fff', borderRadius: 16, padding: 5, shadowColor: '#000', shadowOpacity: 0.02, shadowRadius: 15, elevation: 2 },
  featureRow: { flexDirection: 'row', alignItems: 'center', padding: 15 },
  featureBorder: { borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  featureIconBg: { width: 40, height: 40, borderRadius: 12, justifyContent: 'center', alignItems: 'center', marginRight: 15 },
  featureText: { color: '#334155', fontSize: 15, fontWeight: '500' },
  similarContainer: { flexDirection: 'row', gap: 10 },
  similarCard: { flex: 1, backgroundColor: '#fff', padding: 15, borderRadius: 12, borderWidth: 1, borderColor: '#E2E8F0' },
  similarName: { fontWeight: 'bold', color: '#1E293B', marginBottom: 5, fontSize: 13 },
  similarPrice: { color: '#0056b3', fontSize: 12, fontWeight: '600' },
  footer: { backgroundColor: '#fff', paddingTop: 15, paddingBottom: 25, paddingHorizontal: 20, borderTopWidth: 1, borderColor: '#eee', shadowColor: '#000', shadowOpacity: 0.05, shadowOffset: { height: -5, width: 0 }, shadowRadius: 15, elevation: 10 },
  toggleContainer: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center', marginBottom: 15 },
  toggleText: { fontSize: 14, color: '#94a3b8', marginHorizontal: 10, fontWeight: '600' },
  textActive: { color: '#0F172A' },
  saveBadge: { backgroundColor: '#10b981', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 12, marginLeft: 8 },
  saveBadgeText: { color: '#fff', fontSize: 10, fontWeight: 'bold' },
  priceRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  priceLabel: { fontSize: 12, color: '#64748b', textTransform: 'uppercase' },
  priceAmount: { fontSize: 26, fontWeight: 'bold', color: '#0F172A' },
  priceCycle: { fontSize: 14, color: '#64748b', fontWeight: 'normal' },
  ctaButton: { backgroundColor: '#0056b3', paddingHorizontal: 30, paddingVertical: 16, borderRadius: 12, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', shadowColor: '#0056b3', shadowOpacity: 0.3, shadowOffset: { height: 4, width: 0 }, shadowRadius: 8, elevation: 5 },
  ctaDisabled: { backgroundColor: '#cbd5e1', shadowOpacity: 0 },
  ctaText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
  secureLine: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', marginTop: 5 },
  secureText: { color: '#94a3b8', fontSize: 11, marginLeft: 5 },
});