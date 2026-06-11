import React, { useState } from 'react';
import { View, Text, ScrollView, Image, ImageBackground, StyleSheet, TouchableOpacity, ActivityIndicator } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useQuery } from '@tanstack/react-query';
import api, { getFullImageUrl } from '../services/api';
import SkeletonBox from '../components/SkeletonBox'; // L'IMPORT EXACT ET UNIQUE

export default function HomeScreen() {
  const [activeSlide, setActiveSlide] = useState(0);
  const navigation = useNavigation<any>();

  // --- REQUÊTES API ---
  const { data: homeData, isLoading: loadingHome } = useQuery({
    queryKey: ['homepage'],
    queryFn: () => api.get('/homepage'),
  });

  const { data: categoriesData, isLoading: loadingCategories } = useQuery({
    queryKey: ['categories'],
    queryFn: () => api.get('/categories'),
  });

  const { data: productsData, isLoading: loadingProducts } = useQuery({
    queryKey: ['products'],
    queryFn: () => api.get('/products'),
  });

  // --- EXTRACTION SÉCURISÉE ---
  const slides = Array.isArray(homeData?.data?.slides) ? homeData.data.slides : [];
  const contentText = homeData?.data?.content?.content_text || '';
  const categories = Array.isArray(categoriesData?.data) ? categoriesData.data : [];
  const allProducts = Array.isArray(productsData?.data) ? productsData.data : [];
  
  const topProducts = allProducts
    .filter((p: any) => p.is_featured)
    .sort((a: any, b: any) => a.featured_order - b.featured_order);

  // --- SQUELETTE DE CHARGEMENT (PLACÉ AVANT LE RETURN) ---
  const renderSkeleton = () => (
    <View style={styles.container}>
      <SkeletonBox width="100%" height={220} borderRadius={0} />
      <View style={{ margin: 20 }}>
        <SkeletonBox width="100%" height={100} borderRadius={12} />
      </View>
      <SkeletonBox width="40%" height={20} style={{ marginLeft: 20, marginBottom: 15 }} />
      <View style={styles.gridContainer}>
        {[1, 2].map((i) => (
          <View key={i} style={styles.categoryCard}>
            <View style={styles.iconBg}>
              <SkeletonBox width={40} height={40} borderRadius={20} />
            </View>
            <SkeletonBox width={100} height={14} style={{ marginTop: 12 }} />
          </View>
        ))}
      </View>
      <SkeletonBox width="50%" height={20} style={{ marginLeft: 20, marginBottom: 15 }} />
      <View style={styles.gridContainer}>
        {[1, 2].map((i) => (
          <View key={i} style={styles.productCard}>
            <SkeletonBox width="100%" height={110} borderRadius={12} />
            <View style={{ padding: 15 }}>
              <SkeletonBox width="80%" height={16} />
              <SkeletonBox width="50%" height={14} style={{ marginTop: 10 }} />
            </View>
          </View>
        ))}
      </View>
    </View>
  );

  // --- ÉTAT DE CHARGEMENT ---
  if (loadingHome || loadingCategories || loadingProducts) {
    return renderSkeleton();
  }

  // --- VUE NORMALE ---
  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      
      {/* CARROUSEL */}
      {slides.length > 0 && (
        <View style={styles.carouselContainer}>
          <ImageBackground source={{ uri: getFullImageUrl(slides[activeSlide].image_path) }} style={styles.carouselImage} imageStyle={{ opacity: 0.4 }}>
            <View style={styles.overlay}>
              <Text style={styles.carouselTitle}>{slides[activeSlide].title}</Text>
              <Text style={styles.carouselSubtitle}>{slides[activeSlide].subtitle}</Text>
            </View>
          </ImageBackground>
          <View style={styles.dotsContainer}>
            {slides.map((_: any, index: number) => (
              <View key={index} style={[styles.dot, activeSlide === index && styles.dotActive]} />
            ))}
          </View>
        </View>
      )}

      {/* TEXTE FIXE */}
      {contentText !== '' && (
        <View style={styles.contentBox}>
          <Text style={styles.contentText}>{contentText}</Text>
        </View>
      )}

      {/* GRILLE DE CATÉGORIES */}
      {categories.length > 0 ? (
        <>
          <Text style={styles.sectionTitle}>Nos Catégories</Text>
          <View style={styles.gridContainer}>
            {categories.map((cat: any) => (
              <TouchableOpacity 
                key={cat.id} 
                style={styles.categoryCard}
                onPress={() => navigation.navigate('Category', { categoryId: cat.id })}
                activeOpacity={0.7}
              >
                <View style={styles.iconBg}>
                  <Image source={{ uri: getFullImageUrl(cat.image_path) }} style={styles.categoryImage} />
                </View>
                <Text style={styles.categoryName}>{cat.name}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </>
      ) : (
        !loadingCategories && <Text style={styles.emptyNotice}>Aucune catégorie disponible.</Text>
      )}

      {/* TOP PRODUITS */}
      {topProducts.length > 0 ? (
        <>
          <Text style={styles.sectionTitle}>Les Top Produits du moment</Text>
          <View style={styles.gridContainer}>
            {topProducts.map((prod: any) => (
              <TouchableOpacity key={prod.id} style={styles.productCard} activeOpacity={0.8}>
                <Image source={{ uri: getFullImageUrl(prod.image_path) }} style={styles.productImage} />
                <View style={styles.productInfo}>
                  <Text style={styles.productName}>{prod.name}</Text>
                  <Text style={styles.productPrice}>À partir de {prod.price_monthly} €/mois</Text>
                </View>
              </TouchableOpacity>
            ))}
          </View>
        </>
      ) : (
        !loadingProducts && <Text style={styles.emptyNotice}>Aucun produit mis en avant.</Text>
      )}

      <View style={{ height: 90 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F5F7FA' },
  emptyNotice: { textAlign: 'center', color: '#95a5a6', marginTop: 40, fontSize: 15 },
  carouselContainer: { height: 220, backgroundColor: '#1a1a1a', justifyContent: 'flex-end' },
  carouselImage: { position: 'absolute', width: '100%', height: '100%' },
  overlay: { padding: 25, backgroundColor: 'rgba(0,0,0,0.3)' },
  carouselTitle: { fontSize: 26, fontWeight: 'bold', color: '#fff', marginBottom: 8, letterSpacing: 0.5 },
  carouselSubtitle: { fontSize: 15, color: '#e0e0e0', fontWeight: '500' },
  dotsContainer: { position: 'absolute', bottom: 15, right: 20, flexDirection: 'row' },
  dot: { width: 8, height: 8, borderRadius: 4, backgroundColor: 'rgba(255,255,255,0.4)', marginHorizontal: 4 },
  dotActive: { backgroundColor: '#fff', width: 24, borderRadius: 4 },
  contentBox: { backgroundColor: '#fff', margin: 20, padding: 24, borderRadius: 12, shadowColor: '#000', shadowOpacity: 0.03, shadowRadius: 8, elevation: 2, borderLeftWidth: 4, borderLeftColor: '#0056b3' },
  contentText: { fontSize: 15, color: '#555', lineHeight: 22, textAlign: 'left' },
  sectionTitle: { fontSize: 20, fontWeight: 'bold', marginLeft: 20, marginBottom: 15, color: '#2C3E50' },
  gridContainer: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between', paddingHorizontal: 20 },
  categoryCard: { backgroundColor: '#fff', width: '48%', marginBottom: 20, borderRadius: 12, padding: 20, alignItems: 'center', shadowColor: '#000', shadowOpacity: 0.04, shadowOffset: { width: 0, height: 2 }, shadowRadius: 8, elevation: 3 },
  iconBg: { width: 70, height: 70, borderRadius: 35, backgroundColor: '#f0f4f8', justifyContent: 'center', alignItems: 'center', marginBottom: 12 },
  categoryImage: { width: 40, height: 40, borderRadius: 20 },
  categoryName: { fontSize: 14, fontWeight: '700', color: '#34495E', textAlign: 'center' },
  productCard: { backgroundColor: '#fff', width: '48%', marginBottom: 20, borderRadius: 12, overflow: 'hidden', shadowColor: '#000', shadowOpacity: 0.04, shadowOffset: { width: 0, height: 4 }, shadowRadius: 10, elevation: 3 },
  productImage: { width: '100%', height: 110 },
  productInfo: { padding: 15 },
  productName: { fontSize: 15, fontWeight: 'bold', color: '#2C3E50', marginBottom: 6 },
  productPrice: { fontSize: 13, color: '#0056b3', fontWeight: '600' },
});