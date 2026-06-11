import React, { useState, useContext } from 'react';
import { View, Text, ScrollView, Image, ImageBackground, StyleSheet, TouchableOpacity, Switch, Alert, ActivityIndicator } from 'react-native';
import { useRoute, useNavigation } from '@react-navigation/native';
import { useQuery } from '@tanstack/react-query';
import api, { getFullImageUrl } from '../services/api';
import { CartContext } from '../context/CartContext';

export default function ProductScreen() {
  const route = useRoute();
  const navigation = useNavigation<any>();
  const { productId } = route.params as { productId: number };
  const { addItem } = useContext(CartContext);

  const [isYearly, setIsYearly] = useState(false);

  // 1. Récupérer les infos du produit spécifique
  const { data: productData, isLoading } = useQuery({
    queryKey: ['product', productId],
    queryFn: () => api.get(`/products/${productId}`),
  });
  const product = productData?.data;

  // 2. Récupérer la liste complète des produits (déjà en cache grâce à l'accueil !)
  const { data: productsData } = useQuery({
    queryKey: ['products'],
    queryFn: () => api.get('/products'),
  });
  const allProducts = Array.isArray(productsData?.data) ? productsData.data : [];
  
  // Filtrer les produits similaires (même catégorie, ID différent)
  const similarProducts = allProducts
    .filter((p: any) => p.category_id === product?.category_id && p.id !== product.id)
    .slice(0, 3);

  const handleAddToCart = () => {
    if (!product) return;
    const selectedCycle = isYearly ? 'yearly' : 'monthly';
    addItem(product, selectedCycle);

    Alert.alert(
      "Ajouté au panier",
      `${product.name} (Formule ${isYearly ? 'Annuelle' : 'Mensuelle'}) a été ajouté.`,
      [
        { text: "Continuer mes achats", style: "cancel" },
        { text: "Voir mon panier", onPress: () => navigation.navigate('MainTabs', { screen: 'Cart' }) }
      ]
    );
  };

  // État de chargement
  if (isLoading || !product) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color="#0056b3" />
        <Text style={styles.loadingText}>Chargement du service...</Text>
      </View>
    );
  }

  const currentPrice = isYearly ? product.price_yearly : product.price_monthly;

  return (
    <View style={styles.container}>
      <ScrollView showsVerticalScrollIndicator={false} style={{ flex: 1 }}>
        <ImageBackground source={{ uri: getFullImageUrl(product.image_path) }} style={styles.heroImage}>
          <View style={styles.overlayHero}>
            <Text style={styles.heroTitle}>{product.name}</Text>
          </View>
        </ImageBackground>
        
        <View style={styles.content}>
          <View style={[styles.badgeContainer, product.is_available ? styles.badgeSuccess : styles.badgeDanger]}>
            <Text style={[styles.badgeText, !product.is_available && {color: '#c0392b'}]}>
              {product.is_available ? 'Disponible immédiatement' : 'Service momentanément indisponible'}
            </Text>
          </View>

          <Text style={styles.sectionTitle}>Description</Text>
          <Text style={styles.description}>
            Solution de cybersécurité de pointe conçue pour les entreprises. 
            Assurez la protection de vos endpoints et la surveillance continue de votre infrastructure avec notre équipe d'experts dédiée.
          </Text>

          <Text style={styles.sectionTitle}>Caractéristiques techniques</Text>
          <View style={styles.featuresList}>
            {['Protection multi-terminaux', 'Support 24/7', 'Tableau de bord en temps réel', 'Conformité RGPD'].map((feature, index) => (
              <View key={index} style={styles.featureRow}>
                <View style={styles.bulletPoint} />
                <Text style={styles.featureText}>{feature}</Text>
              </View>
            ))}
          </View>

          {similarProducts.length > 0 && (
            <>
              <Text style={styles.sectionTitle}>Services similaires</Text>
              <View style={styles.similarContainer}>
                {similarProducts.map((p: any) => (
                  <TouchableOpacity key={p.id} style={styles.similarCard} onPress={() => navigation.replace('Product', { productId: p.id })}>
                    <Text style={styles.similarName}>{p.name}</Text>
                    <Text style={styles.similarPrice}>À partir de {p.price_monthly} €/mois</Text>
                  </TouchableOpacity>
                ))}
              </View>
            </>
          )}
          <View style={{ height: 120 }} />
        </View>
      </ScrollView>

      <View style={styles.footer}>
        <View style={styles.toggleContainer}>
          <Text style={[styles.toggleText, !isYearly && styles.textActive]}>Mensuel</Text>
          <Switch
            trackColor={{ false: "#ddd", true: "#0056b3" }}
            thumbColor="#fff"
            onValueChange={setIsYearly}
            value={isYearly}
          />
          <Text style={[styles.toggleText, isYearly && styles.textActive]}>Annuel (-2 mois)</Text>
        </View>

        <View style={styles.priceRow}>
          <View>
            <Text style={styles.priceLabel}>Prix</Text>
            <Text style={styles.priceAmount}>{currentPrice.toFixed(2)} € <Text style={styles.priceCycle}>{isYearly ? '/an' : '/mois'}</Text></Text>
          </View>
          
          <TouchableOpacity 
            style={[styles.ctaButton, !product.is_available && styles.ctaDisabled]}
            onPress={handleAddToCart}
            disabled={!product.is_available}
          >
            <Text style={styles.ctaText}>{product.is_available ? "S'abonner maintenant" : 'Indisponible'}</Text>
          </TouchableOpacity>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F5F7FA' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#fff' },
  loadingText: { color: '#7f8c8d', marginTop: 15, fontSize: 16 },
  heroImage: { width: '100%', height: 220, justifyContent: 'flex-end' },
  overlayHero: { backgroundColor: 'rgba(0, 30, 60, 0.6)', padding: 25 },
  heroTitle: { color: '#fff', fontSize: 28, fontWeight: 'bold' },
  content: { padding: 20, borderTopLeftRadius: 20, borderTopRightRadius: 20, marginTop: -20, backgroundColor: '#F5F7FA', flex: 1 },
  badgeContainer: { alignSelf: 'flex-start', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 20, marginBottom: 20 },
  badgeSuccess: { backgroundColor: '#e8f5e9' },
  badgeDanger: { backgroundColor: '#ffeaea' },
  badgeText: { color: '#2e7d32', fontSize: 12, fontWeight: '600' },
  sectionTitle: { fontSize: 18, fontWeight: 'bold', color: '#2C3E50', marginTop: 25, marginBottom: 10 },
  description: { color: '#555', lineHeight: 22, fontSize: 15 },
  featuresList: { backgroundColor: '#fff', borderRadius: 12, padding: 15, shadowColor: '#000', shadowOpacity: 0.02, shadowRadius: 4, elevation: 1 },
  featureRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  bulletPoint: { width: 8, height: 8, borderRadius: 4, backgroundColor: '#0056b3', marginRight: 12 },
  featureText: { color: '#34495E', fontSize: 15 },
  similarContainer: { flexDirection: 'row', gap: 10 },
  similarCard: { flex: 1, backgroundColor: '#fff', padding: 15, borderRadius: 10, shadowColor: '#000', shadowOpacity: 0.02, shadowRadius: 4, elevation: 1 },
  similarName: { fontWeight: 'bold', color: '#2C3E50', marginBottom: 5, fontSize: 13 },
  similarPrice: { color: '#0056b3', fontSize: 12 },
  footer: { backgroundColor: '#fff', paddingTop: 15, paddingBottom: 30, paddingHorizontal: 20, borderTopWidth: 1, borderColor: '#eee', shadowColor: '#000', shadowOpacity: 0.05, shadowOffset: {
      height: -5,
      width: 0
  }, shadowRadius: 10, elevation: 10 },
  toggleContainer: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center', marginBottom: 15 },
  toggleText: { fontSize: 14, color: '#95a5a6', marginHorizontal: 10 },
  textActive: { color: '#2C3E50', fontWeight: 'bold' },
  priceRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  priceLabel: { fontSize: 12, color: '#7f8c8d', textTransform: 'uppercase' },
  priceAmount: { fontSize: 24, fontWeight: 'bold', color: '#2C3E50' },
  priceCycle: { fontSize: 14, color: '#7f8c8d', fontWeight: 'normal' },
  ctaButton: { backgroundColor: '#0056b3', paddingHorizontal: 30, paddingVertical: 15, borderRadius: 12, shadowColor: '#0056b3', shadowOpacity: 0.3, shadowOffset: {
      height: 4,
      width: 0
  }, shadowRadius: 8, elevation: 5 },
  ctaDisabled: { backgroundColor: '#bdc3c7', shadowOpacity: 0 },
  ctaText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
});