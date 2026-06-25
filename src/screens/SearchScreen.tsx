import React, { useState } from 'react';
import { View, Text, TextInput, FlatList, Image, StyleSheet, TouchableOpacity, ActivityIndicator } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useQuery } from '@tanstack/react-query';
import api, { getFullImageUrl } from '../services/api';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { RootStackParamList } from '../navigation/AppNavigator';

type SearchScreenNavigationProp = NativeStackNavigationProp<RootStackParamList>;

type SortByType = 'price' | 'newest' | 'available';

export default function SearchScreen() {
  const navigation = useNavigation<SearchScreenNavigationProp>();
  
  // --- ÉTATS DES FILTRES ---
  const [searchText, setSearchText] = useState('');
  const [showFilters, setShowFilters] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [minPrice, setMinPrice] = useState('');
  const [maxPrice, setMaxPrice] = useState('');
  const [isAvailableOnly, setIsAvailableOnly] = useState(false);
  const [sortBy, setSortBy] = useState<SortByType>('newest');
  const [sortAsc, setSortAsc] = useState(true);

  // --- REQUÊTES API SÉPARÉES ---
  const { data: productsResponse, isLoading: loadingProducts } = useQuery({
    queryKey: ['products'],
    queryFn: async () => (await api.get('/products')).data,
  });

  const { data: categoriesResponse, isLoading: loadingCategories } = useQuery({
    queryKey: ['categories'],
    queryFn: async () => (await api.get('/categories')).data,
  });

  // --- DÉBALLAGE DES DONNÉES LARAVEL ---
  const rawProducts = productsResponse?.data ?? productsResponse;
  const allProducts = Array.isArray(rawProducts) ? rawProducts : [];

  const rawCategories = categoriesResponse?.data ?? categoriesResponse;
  const categories = Array.isArray(rawCategories) ? rawCategories : [];

  // --- LOGIQUE DE FILTRAGE ET DE TRI ---
  let filteredProducts = allProducts.filter((product: any) => {
    if (searchText && !product.name.toLowerCase().includes(searchText.toLowerCase())) return false;
    if (selectedCategory !== null && String(product.category_id) !== String(selectedCategory)) return false;
    if (isAvailableOnly && !product.is_available) return false;
    if (minPrice !== '' && product.price_monthly < parseFloat(minPrice)) return false;
    if (maxPrice !== '' && product.price_monthly > parseFloat(maxPrice)) return false;
    return true;
  });

  filteredProducts.sort((a: any, b: any) => {
    let comparison = 0;
    if (sortBy === 'price') comparison = a.price_monthly - b.price_monthly;
    else if (sortBy === 'newest') comparison = new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
    else if (sortBy === 'available') comparison = (b.is_available ? 1 : 0) - (a.is_available ? 1 : 0);
    return sortAsc ? comparison : -comparison;
  });

  const resetFilters = () => {
    setSelectedCategory(null);
    setMinPrice('');
    setMaxPrice('');
    setIsAvailableOnly(false);
    setSortBy('newest');
    setSortAsc(true);
  };

  if (loadingProducts || loadingCategories) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color="#0056b3" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* HEADER SEARCH */}
      <View style={styles.searchContainer}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.closeButton}>
          <Ionicons name="close" size={22} color="#64748b" />
        </TouchableOpacity>
        <TextInput 
          style={styles.searchInput}
          placeholder="Rechercher un service (ex: SOC, EDR...)"
          placeholderTextColor="#94a3b8"
          value={searchText}
          onChangeText={setSearchText}
          autoFocus={true}
          autoCapitalize="none"
        />
      </View>

      {/* BOUTON D'OUVERTURE DES FILTRES */}
      <TouchableOpacity style={styles.filterHeader} onPress={() => setShowFilters(!showFilters)}>
        <View style={{ flexDirection: 'row', alignItems: 'center' }}>
          <Ionicons name="options-outline" size={20} color="#0056b3" />
          <Text style={styles.filterHeaderText}>Filtres & Tri</Text>
        </View>
        <Ionicons name={showFilters ? "chevron-up" : "chevron-down"} size={20} color="#0056b3" />
      </TouchableOpacity>

      {/* ZONE DES FILTRES (Rétractable) */}
      {showFilters && (
        <View style={styles.filtersContainer}>
          <Text style={styles.label}>Catégorie</Text>
          <View style={styles.chipsContainer}>
            <TouchableOpacity 
              style={[styles.chip, !selectedCategory && styles.chipActive]} 
              onPress={() => setSelectedCategory(null)}
            >
              <Text style={[styles.chipText, !selectedCategory && styles.chipTextActive]}>Toutes</Text>
            </TouchableOpacity>
            {categories.map((cat: any) => (
              <TouchableOpacity 
                key={cat.id} 
                style={[styles.chip, selectedCategory === String(cat.id) && styles.chipActive]} 
                onPress={() => setSelectedCategory(selectedCategory === String(cat.id) ? null : String(cat.id))}
              >
                <Text style={[styles.chipText, selectedCategory === String(cat.id) && styles.chipTextActive]}>{cat.name}</Text>
              </TouchableOpacity>
            ))}
          </View>

          <Text style={styles.label}>Prix (€/mois)</Text>
          <View style={styles.row}>
            <TextInput 
              style={styles.smallInput} 
              placeholder="Min" 
              placeholderTextColor="#94a3b8"
              value={minPrice} 
              onChangeText={setMinPrice} 
              keyboardType="numeric" 
            />
            <TextInput 
              style={styles.smallInput} 
              placeholder="Max" 
              placeholderTextColor="#94a3b8"
              value={maxPrice} 
              onChangeText={setMaxPrice} 
              keyboardType="numeric" 
            />
          </View>

          <View style={styles.switchRow}>
            <Text style={styles.switchLabel}>Uniquement les services disponibles</Text>
            <TouchableOpacity onPress={() => setIsAvailableOnly(!isAvailableOnly)}>
              <View style={[styles.switchTrack, isAvailableOnly && styles.switchTrackActive]}>
                <View style={[styles.switchThumb, isAvailableOnly && styles.switchThumbActive]} />
              </View>
            </TouchableOpacity>
          </View>

          <Text style={styles.label}>Trier par</Text>
          <View style={styles.row}>
            <TouchableOpacity style={[styles.sortButton, sortBy === 'newest' && styles.sortButtonActive]} onPress={() => setSortBy('newest')}>
              <Text style={[styles.sortButtonText, sortBy === 'newest' && styles.sortButtonTextActive]}>Nouveauté</Text>
            </TouchableOpacity>
            <TouchableOpacity style={[styles.sortButton, sortBy === 'price' && styles.sortButtonActive]} onPress={() => setSortBy('price')}>
              <Text style={[styles.sortButtonText, sortBy === 'price' && styles.sortButtonTextActive]}>Prix</Text>
            </TouchableOpacity>
            <TouchableOpacity style={[styles.sortButton, sortBy === 'available' && styles.sortButtonActive]} onPress={() => setSortBy('available')}>
              <Text style={[styles.sortButtonText, sortBy === 'available' && styles.sortButtonTextActive]}>Dispo.</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.orderButton} onPress={() => setSortAsc(!sortAsc)}>
              <Ionicons name={sortAsc ? "arrow-up" : "arrow-down"} size={18} color="#64748b" />
            </TouchableOpacity>
          </View>

          <TouchableOpacity style={styles.resetButton} onPress={resetFilters}>
            <Text style={styles.resetButtonText}>Réinitialiser les filtres</Text>
          </TouchableOpacity>
        </View>
      )}

      {/* RÉSULTATS */}
      <FlatList
        data={filteredProducts}
        keyExtractor={item => item.id.toString()}
        contentContainerStyle={{ padding: 15, paddingBottom: 50 }}
        ListEmptyComponent={<Text style={styles.empty}>Aucun résultat ne correspond à vos critères.</Text>}
        renderItem={({ item }) => (
          <TouchableOpacity 
            style={styles.item} 
            onPress={() => navigation.navigate('Product', { productId: item.id })}
          >
            <Image source={{ uri: getFullImageUrl(item.image_path) }} style={styles.image} />
            <View style={styles.info}>
              <Text style={styles.name}>{item.name}</Text>
              <Text style={styles.price}>À partir de {item.price_monthly} €/mois</Text>
              {!item.is_available && <Text style={styles.unavailable}>Indisponible</Text>}
            </View>
            <Ionicons name="chevron-forward" size={20} color="#cbd5e1" />
          </TouchableOpacity>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F8FAFC' },
  
  // Header
  searchContainer: { backgroundColor: '#fff', flexDirection: 'row', alignItems: 'center', paddingHorizontal: 15, paddingVertical: 15, shadowColor: '#000', shadowOpacity: 0.03, shadowOffset: { height: 2, width: 0 }, shadowRadius: 4, elevation: 2 },
  closeButton: { marginRight: 10, width: 36, height: 36, borderRadius: 18, backgroundColor: '#F1F5F9', justifyContent: 'center', alignItems: 'center' },
  searchInput: { flex: 1, backgroundColor: '#F1F5F9', borderRadius: 12, paddingHorizontal: 15, paddingVertical: 12, fontSize: 15, color: '#1E293B' },

  // Filtres Header
  filterHeader: { flexDirection: 'row', justifyContent: 'space-between', backgroundColor: '#fff', padding: 15, borderBottomWidth: 1, borderColor: '#F1F5F9' },
  filterHeaderText: { color: '#0056b3', fontSize: 15, fontWeight: 'bold', marginLeft: 10 },

  // Zone Filtres
  filtersContainer: { backgroundColor: '#fff', padding: 15, borderBottomWidth: 1, borderColor: '#F1F5F9' },
  label: { fontSize: 13, fontWeight: 'bold', color: '#64748B', marginTop: 15, marginBottom: 8, textTransform: 'uppercase', letterSpacing: 0.5 },
  chipsContainer: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: { paddingHorizontal: 12, paddingVertical: 8, borderRadius: 20, backgroundColor: '#F8FAFC', borderWidth: 1, borderColor: '#E2E8F0' },
  chipActive: { backgroundColor: '#EFF6FF', borderColor: '#0056b3' },
  chipText: { fontSize: 13, color: '#64748b', fontWeight: '600' },
  chipTextActive: { color: '#0056b3' },
  row: { flexDirection: 'row', gap: 10 },
  smallInput: { flex: 1, backgroundColor: '#F8FAFC', borderRadius: 10, paddingHorizontal: 12, paddingVertical: 12, fontSize: 14, color: '#1E293B', borderWidth: 1, borderColor: '#E2E8F0' },
  
  // Switch Custom
  switchRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 15 },
  switchLabel: { fontSize: 14, color: '#334155', flex: 1, marginRight: 10 },
  switchTrack: { width: 50, height: 28, borderRadius: 14, backgroundColor: '#E2E8F0', justifyContent: 'center', padding: 2 },
  switchTrackActive: { backgroundColor: '#0056b3' },
  switchThumb: { width: 24, height: 24, borderRadius: 12, backgroundColor: '#fff' },
  switchThumbActive: { alignSelf: 'flex-end' },

  // Tri
  sortButton: { flex: 1, backgroundColor: '#F8FAFC', paddingVertical: 12, borderRadius: 10, alignItems: 'center', borderWidth: 1, borderColor: '#E2E8F0' },
  sortButtonActive: { backgroundColor: '#0056b3', borderColor: '#0056b3' },
  sortButtonText: { fontSize: 13, fontWeight: 'bold', color: '#64748b' },
  sortButtonTextActive: { color: '#fff' },
  orderButton: { width: 45, backgroundColor: '#F8FAFC', borderRadius: 10, justifyContent: 'center', alignItems: 'center', borderWidth: 1, borderColor: '#E2E8F0' },
  resetButton: { alignSelf: 'center', marginTop: 20, marginBottom: 5 },
  resetButtonText: { color: '#EF4444', fontSize: 14, fontWeight: 'bold' },

  // Résultats
  item: { flexDirection: 'row', backgroundColor: '#fff', padding: 15, borderRadius: 16, marginBottom: 12, alignItems: 'center', shadowColor: '#000', shadowOpacity: 0.02, shadowRadius: 10, elevation: 1, borderWidth: 1, borderColor: '#F1F5F9' },
  image: { width: 50, height: 50, borderRadius: 12, marginRight: 15 },
  info: { flex: 1 },
  name: { fontSize: 16, fontWeight: 'bold', color: '#1E293B' },
  price: { fontSize: 14, color: '#0056b3', marginTop: 4, fontWeight: '600' },
  unavailable: { fontSize: 12, color: '#EF4444', fontWeight: 'bold', marginTop: 2 },
  empty: { textAlign: 'center', color: '#94a3b8', marginTop: 50, fontSize: 15 }
});