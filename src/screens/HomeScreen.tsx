import React, { useState } from 'react';
import { View, Text, ScrollView, Image, ImageBackground, StyleSheet, TouchableOpacity, Dimensions } from 'react-native';
import { CompositeNavigationProp, useNavigation } from '@react-navigation/native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { BottomTabNavigationProp } from '@react-navigation/bottom-tabs';
import { useQuery } from '@tanstack/react-query';
import { Ionicons } from '@expo/vector-icons';
import api, { getFullImageUrl } from '../services/api';
import SkeletonBox from '../components/SkeletonBox';
import { RootStackParamList, TabParamList } from '../navigation/AppNavigator';

type HomeScreenNavigation = CompositeNavigationProp<
  NativeStackNavigationProp<RootStackParamList>,
  BottomTabNavigationProp<TabParamList>
>;

interface Category { id: string; name: string; image_path: string; }
interface Product { id: string; name: string; price_monthly: number; image_path: string; is_featured: boolean; featured_order: number; }
interface Slide { image_path: string; title: string; subtitle: string; }

const { width } = Dimensions.get('window');
// Largeur d'une carte produit dans le carrousel horizontal
const PRODUCT_CARD_WIDTH = width * 0.65;

export default function HomeScreen() {
  const [activeSlide, setActiveSlide] = useState(0);
  const navigation = useNavigation<HomeScreenNavigation>();

  // --- REQUÊTES API ---
  const { data: homeData, isLoading: loadingHome } = useQuery({
    queryKey: ['homepage'],
    queryFn: async () => (await api.get('/homepage')).data,
  });

  const { data: categoriesData, isLoading: loadingCategories } = useQuery({
    queryKey: ['categories'],
    queryFn: async () => (await api.get('/categories')).data,
  });

  const { data: productsData, isLoading: loadingProducts } = useQuery({
    queryKey: ['products'],
    queryFn: async () => (await api.get('/products')).data,
  });

    // --- EXTRACTION SÉCURISÉE ET TYPÉE ---
  const rawCategories = categoriesData?.data ?? categoriesData;
  const rawProducts = productsData?.data ?? productsData;
  const rawSlides = homeData?.data?.slides ?? homeData?.slides;

  const slides: Slide[] = Array.isArray(rawSlides) ? rawSlides : [];
  const contentText: string = homeData?.data?.content?.content_text || homeData?.content?.content_text || '';
  const categories: Category[] = Array.isArray(rawCategories) ? rawCategories : [];
  const allProducts: Product[] = Array.isArray(rawProducts) ? rawProducts : [];
  
  const topProducts = allProducts
    .filter((p) => p.is_featured)
    .sort((a, b) => a.featured_order - b.featured_order);

  // --- SQUELETTE DE CHARGEMENT ---
  if (loadingHome || loadingCategories || loadingProducts) {
    return (
      <View style={styles.container}>
        <SkeletonBox width="100%" height={60} style={{ marginBottom: 10 }} />
        <SkeletonBox width="100%" height={200} borderRadius={0} />
        <View style={{ margin: 20 }}>
          <SkeletonBox width="100%" height={100} borderRadius={16} />
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
      </View>
    );
  }

  // --- GESTION DU SCROLL DU CARROUSEL ---
  const handleScroll = (event: any) => {
    const slideIndex = Math.round(event.nativeEvent.contentOffset.x / width);
    if (slideIndex !== activeSlide) {
      setActiveSlide(slideIndex);
    }
  };

  // --- VUE NORMALE ---
  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      
      {/* FAUSSE BARRE DE RECHERCHE */}
      <TouchableOpacity 
        style={styles.searchBar} 
        onPress={() => navigation.navigate('Search')}
        activeOpacity={0.8}
      >
        <Ionicons name="search-outline" size={20} color="#94a3b8" />
        <Text style={styles.searchPlaceholder}>Rechercher un service, une catégorie...</Text>
      </TouchableOpacity>

      {/* CARROUSEL SWIPEABLE */}
      {slides.length > 0 && (
        <View style={styles.carouselContainer}>
          <ScrollView 
            horizontal 
            pagingEnabled 
            showsHorizontalScrollIndicator={false}
            onScroll={handleScroll}
            scrollEventThrottle={16}
          >
            {slides.map((slide, index) => (
              <View key={index} style={{ width }}>
                <ImageBackground 
                  source={{ uri: getFullImageUrl(slide.image_path) }} 
                  style={styles.carouselImage} 
                  imageStyle={{ opacity: 0.3 }}
                >
                  <View style={styles.overlay}>
                    <Text style={styles.carouselTitle}>{slide.title}</Text>
                    <Text style={styles.carouselSubtitle}>{slide.subtitle}</Text>
                    <TouchableOpacity style={styles.carouselButton} onPress={() => navigation.navigate('Categories')}>
                      <Text style={styles.carouselButtonText}>Découvrir</Text>
                      <Ionicons name="arrow-forward" size={14} color="#fff" style={{ marginLeft: 5 }} />
                    </TouchableOpacity>
                  </View>
                </ImageBackground>
              </View>
            ))}
          </ScrollView>
          
          {/* PAGINATION */}
          {slides.length > 1 && (
            <View style={styles.dotsContainer}>
              {slides.map((_, index) => (
                <View key={index} style={[styles.dot, activeSlide === index && styles.dotActive]} />
              ))}
            </View>
          )}
        </View>
      )}

      {/* TEXTE FIXE */}
      {contentText !== '' && (
        <View style={styles.contentBox}>
          <Ionicons name="shield-checkmark" size={24} color="#0056b3" style={{ marginBottom: 10 }} />
          <Text style={styles.contentText}>{contentText}</Text>
        </View>
      )}

      {/* GRILLE DE CATÉGORIES */}
      {categories.length > 0 ? (
        <View style={styles.sectionContainer}>
          <Text style={styles.sectionTitle}>Nos Catégories</Text>
          <View style={styles.gridContainer}>
            {categories.map((cat) => (
              <TouchableOpacity 
                key={cat.id} 
                style={styles.categoryCard}
                onPress={() => navigation.navigate('Category', { categoryId: cat.id })}
                activeOpacity={0.8}
              >
                <View style={styles.iconBg}>
                  <Image source={{ uri: getFullImageUrl(cat.image_path) }} style={styles.categoryImage} />
                </View>
                <Text style={styles.categoryName}>{cat.name}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>
      ) : (
        !loadingCategories && <Text style={styles.emptyNotice}>Aucune catégorie disponible.</Text>
      )}

      {/* TOP PRODUITS (CARROUSEL HORIZONTAL) */}
      {topProducts.length > 0 ? (
        <View style={styles.sectionContainer}>
          <View style={styles.headerRow}>
            <Text style={styles.sectionTitle}>Les Top Produits</Text>
            <TouchableOpacity onPress={() => navigation.navigate('Categories')}>
              <Text style={styles.seeAllText}>Voir tout</Text>
            </TouchableOpacity>
          </View>
          
          <ScrollView 
            horizontal 
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={{ paddingRight: 20 }}
          >
            {topProducts.map((prod) => (
              <TouchableOpacity 
                key={prod.id} 
                style={styles.productCard} 
                activeOpacity={0.8}
                onPress={() => navigation.navigate('Product', { productId: prod.id })}
              >
                <Image source={{ uri: getFullImageUrl(prod.image_path) }} style={styles.productImage} />
                <View style={styles.productInfo}>
                  <Text style={styles.productName} numberOfLines={2}>{prod.name}</Text>
                  <View style={styles.productFooter}>
                    <Text style={styles.productPrice}>Dès {prod.price_monthly} €/mois</Text>
                    <View style={styles.productArrow}>
                      <Ionicons name="arrow-forward" size={14} color="#fff" />
                    </View>
                  </View>
                </View>
              </TouchableOpacity>
            ))}
          </ScrollView>
        </View>
      ) : (
        !loadingProducts && <Text style={styles.emptyNotice}>Aucun produit mis en avant.</Text>
      )}

      <View style={{ height: 90 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  emptyNotice: { textAlign: 'center', color: '#94a3b8', marginTop: 40, fontSize: 15 },
  
  // Search Bar
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    margin: 15,
    paddingHorizontal: 15,
    paddingVertical: 12,
    borderRadius: 12,
    shadowColor: '#000',
    shadowOpacity: 0.03,
    shadowRadius: 10,
    elevation: 2,
  },
  searchPlaceholder: { color: '#94a3b8', fontSize: 15, marginLeft: 10 },

  // Carousel
  carouselContainer: { height: 200, backgroundColor: '#0F172A', marginBottom: 10 },
  carouselImage: { width: '100%', height: '100%', justifyContent: 'center' },
  overlay: { padding: 25, backgroundColor: 'rgba(15, 23, 42, 0.6)' },
  carouselTitle: { fontSize: 24, fontWeight: 'bold', color: '#fff', marginBottom: 8, letterSpacing: 0.5 },
  carouselSubtitle: { fontSize: 14, color: '#cbd5e1', fontWeight: '500', marginBottom: 15 },
  carouselButton: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#0056b3', paddingHorizontal: 15, paddingVertical: 8, borderRadius: 20, alignSelf: 'flex-start' },
  carouselButtonText: { color: '#fff', fontSize: 13, fontWeight: 'bold' },
  dotsContainer: { position: 'absolute', bottom: 15, right: 20, flexDirection: 'row' },
  dot: { width: 8, height: 8, borderRadius: 4, backgroundColor: 'rgba(255,255,255,0.4)', marginHorizontal: 4 },
  dotActive: { backgroundColor: '#fff', width: 24, borderRadius: 4 },

  // Content Box
  contentBox: { backgroundColor: '#fff', margin: 20, padding: 20, borderRadius: 16, shadowColor: '#000', shadowOpacity: 0.03, shadowRadius: 15, elevation: 2, borderLeftWidth: 4, borderLeftColor: '#0056b3' },
  contentText: { fontSize: 14, color: '#64748B', lineHeight: 22, textAlign: 'left' },

  // Sections
  sectionContainer: { marginTop: 10, marginBottom: 20 },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, marginBottom: 15 },
  sectionTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B', marginBottom: 15, paddingHorizontal: 20 },
  seeAllText: { color: '#0056b3', fontSize: 14, fontWeight: '600' },

  // Categories Grid
  gridContainer: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between', paddingHorizontal: 20 },
  categoryCard: { backgroundColor: '#fff', width: '48%', marginBottom: 15, borderRadius: 16, padding: 20, alignItems: 'center', shadowColor: '#000', shadowOpacity: 0.03, shadowOffset: { width: 0, height: 2 }, shadowRadius: 10, elevation: 2 },
  iconBg: { width: 60, height: 60, borderRadius: 30, backgroundColor: '#F1F5F9', justifyContent: 'center', alignItems: 'center', marginBottom: 12 },
  categoryImage: { width: 35, height: 35, borderRadius: 17 },
  categoryName: { fontSize: 14, fontWeight: '600', color: '#334155', textAlign: 'center' },

  // Horizontal Products
  productCard: { backgroundColor: '#fff', width: PRODUCT_CARD_WIDTH, marginLeft: 20, borderRadius: 16, overflow: 'hidden', shadowColor: '#000', shadowOpacity: 0.03, shadowOffset: { width: 0, height: 4 }, shadowRadius: 10, elevation: 3 },
  productImage: { width: '100%', height: 120 },
  productInfo: { padding: 15 },
  productName: { fontSize: 15, fontWeight: 'bold', color: '#1E293B', marginBottom: 10, minHeight: 40 },
  productFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  productPrice: { fontSize: 13, color: '#0056b3', fontWeight: '600' },
  productArrow: { backgroundColor: '#0056b3', width: 28, height: 28, borderRadius: 14, justifyContent: 'center', alignItems: 'center' },
});