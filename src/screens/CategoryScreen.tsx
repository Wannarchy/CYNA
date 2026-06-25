import React from 'react';
import { View, Text, FlatList, Image, StyleSheet, TouchableOpacity, ActivityIndicator } from 'react-native';
import { useRoute, useNavigation } from '@react-navigation/native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery } from '@tanstack/react-query';
import { Ionicons } from '@expo/vector-icons';
import api, { getFullImageUrl } from '../services/api';
import { RootStackParamList } from '../navigation/AppNavigator';
import { Product } from '../types';

type CategoryScreenRouteProp = NativeStackScreenProps<RootStackParamList, 'Category'>['route'];
type CategoryScreenNavigationProp = NativeStackScreenProps<RootStackParamList, 'Category'>['navigation'];

export default function CategoryScreen() {
  const route = useRoute<CategoryScreenRouteProp>();
  const navigation = useNavigation<CategoryScreenNavigationProp>();
  const { categoryId, categoryName } = route.params;

  // On récupère tous les produits (React Query va utiliser le cache si déjà chargé sur l'accueil)
  const { data: productsData, isLoading } = useQuery({
    queryKey: ['products'],
    queryFn: async () => (await api.get('/products')).data,
  });

  // On récupère les catégories pour trouver le nom si on ne l'a pas passé en paramètres
  const { data: categoriesData } = useQuery({
    queryKey: ['categories'],
    queryFn: async () => (await api.get('/categories')).data,
  });

  // Déballage Laravel
  const rawProducts = productsData?.data ?? productsData;
  const allProducts: Product[] = Array.isArray(rawProducts) ? rawProducts : [];

  const rawCategories = categoriesData?.data ?? categoriesData;
  const allCategories = Array.isArray(rawCategories) ? rawCategories : [];

  // FILTRAGE : On garde uniquement les produits de la catégorie cliquée
  const filteredProducts = allProducts.filter(
    (p) => String(p.category_id) === String(categoryId)
  );

  // Trouver le nom de la catégorie
  const currentCategory = allCategories.find((c: any) => String(c.id) === String(categoryId));
  const title = categoryName || currentCategory?.name || 'Catégorie';

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color="#0056b3" />
      </View>
    );
  }

  const renderProduct = ({ item }: { item: Product }) => (
    <TouchableOpacity 
      style={styles.productCard} 
      activeOpacity={0.8}
      onPress={() => navigation.navigate('Product', { productId: String(item.id) })}
    >
      <Image source={{ uri: getFullImageUrl(item.image_path) }} style={styles.productImage} />
      <View style={styles.productInfo}>
        <View style={styles.productHeader}>
          <Text style={styles.productName}>{item.name}</Text>
          {!item.is_available && (
            <View style={styles.unavailableBadge}>
              <Text style={styles.unavailableText}>Indisponible</Text>
            </View>
          )}
        </View>
        <Text style={styles.productPrice}>À partir de {Number(item.price_monthly).toFixed(2)} €/mois</Text>
      </View>
      <Ionicons name="chevron-forward" size={20} color="#cbd5e1" style={{ marginRight: 15 }} />
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color="#1E293B" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>{title}</Text>
        <View style={{ width: 24 }} />
      </View>

      {/* Liste des produits filtrés */}
      <FlatList
        data={filteredProducts}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={{ padding: 20 }}
        renderItem={renderProduct}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Ionicons name="file-tray-outline" size={60} color="#CBD5E1" />
            <Text style={styles.emptyTitle}>Aucun service trouvé</Text>
            <Text style={styles.emptySubtitle}>Il n'y a pas encore de produits dans cette catégorie.</Text>
          </View>
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F8FAFC' },
  
  // Header
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 20, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  backBtn: { padding: 5 },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B' },

  // Carte Produit
  productCard: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    borderRadius: 16,
    marginBottom: 15,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOpacity: 0.03,
    shadowRadius: 10,
    elevation: 2,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  productImage: { width: 70, height: 70, borderRadius: 12, margin: 15 },
  productInfo: { flex: 1, paddingVertical: 15 },
  productHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 6 },
  productName: { fontSize: 16, fontWeight: 'bold', color: '#1E293B', flex: 1 },
  unavailableBadge: { backgroundColor: '#FEF2F2', paddingHorizontal: 8, paddingVertical: 3, borderRadius: 12, marginLeft: 10 },
  unavailableText: { color: '#EF4444', fontSize: 10, fontWeight: 'bold' },
  productPrice: { fontSize: 14, color: '#0056b3', fontWeight: '600' },

  // Empty State
  emptyContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 40, marginTop: 50 },
  emptyTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B', marginTop: 20 },
  emptySubtitle: { fontSize: 14, color: '#64748B', textAlign: 'center', marginTop: 8 }
});