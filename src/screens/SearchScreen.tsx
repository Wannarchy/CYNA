import React, { useState } from 'react';
import { View, Text, TextInput, FlatList, Image, StyleSheet, TouchableOpacity, ActivityIndicator } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useQuery } from '@tanstack/react-query';
import api, { getFullImageUrl } from '../services/api';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';

type NavProp = NativeStackNavigationProp<any>;

// Types pour les filtres
type SortByType = 'price' | 'newest' | 'available';

export default function SearchScreen() {
  const navigation = useNavigation<NavProp>();
  
  // --- ÉTATS DES FILTRES ---
  const [searchText, setSearchText] = useState('');
  const [showFilters, setShowFilters] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState<number | null>(null);
  const [minPrice, setMinPrice] = useState('');
  const [maxPrice, setMaxPrice] = useState('');
  const [isAvailableOnly, setIsAvailableOnly] = useState(false);
  const [sortBy, setSortBy] = useState<SortByType>('newest');
  const [sortAsc, setSortAsc] = useState(true);

  // --- REQUÊTE API ---
  const { data: productsData, data: categoriesData, isLoading } = useQuery({
    queryKey: ['products', 'categories'],
    queryFn: () => Promise.all([api.get('/products'), api.get('/categories')]),
  });

  const allProducts = Array.isArray(productsData?.[0]?.data) ? productsData[0].data : [];
  const categories = Array.isArray(categoriesData?.[1]?.data) ? categoriesData[1].data : [];

  // --- LOGIQUE DE FILTRAGE ET DE TRI ---
  let filteredProducts = allProducts.filter((product: any) => {
    // 1. Texte (Nom du produit)
    if (searchText && !product.name.toLowerCase().includes(searchText.toLowerCase())) {
      return false;
    }
    // 2. Catégorie
    if (selectedCategory !== null && product.category_id !== selectedCategory) {
      return false;
    }
    // 3. Uniquement disponibles
    if (isAvailableOnly && !product.is_available) {
      return false;
    }
    // 4. Prix minimum
    if (minPrice !== '' && product.price_monthly < parseFloat(minPrice)) {
      return false;
    }
    // 5. Prix maximum
    if (maxPrice !== '' && product.price_monthly > parseFloat(maxPrice)) {
      return false;
    }
    return true;
  });

  // Logique de tri
  filteredProducts.sort((a: any, b: any) => {
    let comparison = 0;
    if (sortBy === 'price') {
      comparison = a.price_monthly - b.price_monthly;
    } else if (sortBy === 'newest') {
      comparison = new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
    } else if (sortBy === 'available') {
      // Mettre les disponibles (1) en premier
      comparison = (b.is_available ? 1 : 0) - (a.is_available ? 1 : 0);
    }
    return sortAsc ? comparison : -comparison;
  });

  // Fonction pour réinitialiser les filtres
  const resetFilters = () => {
    setSelectedCategory(null);
    setMinPrice('');
    setMaxPrice('');
    setIsAvailableOnly(false);
    setSortBy('newest');
    setSortAsc(true);
  };

  if (isLoading) {
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
          <Text style={styles.closeButtonText}>✕</Text>
        </TouchableOpacity>
        <TextInput 
          style={styles.searchInput}
          placeholder="Rechercher un service (ex: SOC, EDR...)"
          placeholderTextColor="#7f8c8d"
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
          {/* Catégorie */}
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
                style={[styles.chip, selectedCategory === cat.id && styles.chipActive]} 
                onPress={() => setSelectedCategory(cat.id === selectedCategory ? null : cat.id)}
              >
                <Text style={[styles.chipText, selectedCategory === cat.id && styles.chipTextActive]}>{cat.name}</Text>
              </TouchableOpacity>
            ))}
          </View>

          {/* Prix */}
          <Text style={styles.label}>Prix (€/mois)</Text>
          <View style={styles.row}>
            <TextInput 
              style={styles.smallInput} 
              placeholder="Min" 
              value={minPrice} 
              onChangeText={setMinPrice} 
              keyboardType="numeric" 
            />
            <TextInput 
              style={styles.smallInput} 
              placeholder="Max" 
              value={maxPrice} 
              onChangeText={setMaxPrice} 
              keyboardType="numeric" 
            />
          </View>

          {/* Switch Disponibilité */}
          <View style={styles.switchRow}>
            <Text style={styles.switchLabel}>Uniquement les services disponibles</Text>
            <TouchableOpacity onPress={() => setIsAvailableOnly(!isAvailableOnly)}>
              <View style={[styles.switchTrack, isAvailableOnly && styles.switchTrackActive]}>
                <View style={[styles.switchThumb, isAvailableOnly && styles.switchThumbActive]} />
              </View>
            </TouchableOpacity>
          </View>

          {/* Tri */}
          <Text style={styles.label}>Trier par</Text>
          <View style={styles.row}>
            <TouchableOpacity style={[styles.sortButton, sortBy === 'newest' && styles.sortButtonActive]} onPress={() => setSortBy('newest')}>
              <Text style={styles.sortButtonText}>Nouveauté</Text>
            </TouchableOpacity>
            <TouchableOpacity style={[styles.sortButton, sortBy === 'price' && styles.sortButtonActive]} onPress={() => setSortBy('price')}>
              <Text style={styles.sortButtonText}>Prix</Text>
            </TouchableOpacity>
            <TouchableOpacity style={[styles.sortButton, sortBy === 'available' && styles.sortButtonActive]} onPress={() => setSortBy('available')}>
              <Text style={styles.sortButtonText}>Dispo.</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.orderButton} onPress={() => setSortAsc(!sortAsc)}>
              <Ionicons name={sortAsc ? "arrow-up" : "arrow-down"} size={18} color="#555" />
            </TouchableOpacity>
          </View>

          {/* Bouton Reset */}
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
          </TouchableOpacity>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F5F7FA' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#fff' },
  
  // Header
  searchContainer: { backgroundColor: '#fff', flexDirection: 'row', alignItems: 'center', paddingHorizontal: 15, paddingVertical: 10, shadowColor: '#000', shadowOpacity: 0.05, shadowOffset: {
      height: 2,
      width: 0
  }, shadowRadius: 4, elevation: 2 },
  closeButton: { marginRight: 10, width: 30, height: 30, borderRadius: 15, backgroundColor: '#f0f2f5', justifyContent: 'center', alignItems: 'center' },
  closeButtonText: { color: '#555', fontSize: 16, fontWeight: 'bold' },
  searchInput: { flex: 1, backgroundColor: '#f0f2f5', borderRadius: 10, padding: 12, fontSize: 16, color: '#2C3E50' },

  // Filtres Header
  filterHeader: { flexDirection: 'row', justifyContent: 'space-between', backgroundColor: '#fff', padding: 15, borderBottomWidth: 1, borderColor: '#eee' },
  filterHeaderText: { color: '#0056b3', fontSize: 16, fontWeight: 'bold', marginLeft: 10 },

  // Zone Filtres
  filtersContainer: { backgroundColor: '#fff', padding: 15, borderBottomWidth: 1, borderColor: '#ddd' },
  label: { fontSize: 14, fontWeight: 'bold', color: '#34495E', marginTop: 15, marginBottom: 8 },
  chipsContainer: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: { paddingHorizontal: 12, paddingVertical: 8, borderRadius: 20, backgroundColor: '#f0f2f5', borderWidth: 1, borderColor: '#dcdde1' },
  chipActive: { backgroundColor: '#eaf2f8', borderColor: '#0056b3' },
  chipText: { fontSize: 13, color: '#555', fontWeight: '600' },
  chipTextActive: { color: '#0056b3' },
  row: { flexDirection: 'row', gap: 10 },
  smallInput: { flex: 1, backgroundColor: '#f0f2f5', borderRadius: 8, paddingHorizontal: 12, paddingVertical: 10, fontSize: 14, color: '#2C3E50', borderWidth: 1, borderColor: '#dcdde1' },
  
  // Switch Custom (pour le style B2B)
  switchRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 15 },
  switchLabel: { fontSize: 14, color: '#34495E', flex: 1, marginRight: 10 },
  switchTrack: { width: 50, height: 28, borderRadius: 14, backgroundColor: '#dcdde1', justifyContent: 'center', padding: 2 },
  switchTrackActive: { backgroundColor: '#0056b3' },
  switchThumb: { width: 24, height: 24, borderRadius: 12, backgroundColor: '#fff', shadowColor: '#000', shadowOpacity: 0.1, shadowRadius: 2, elevation: 2 },
  switchThumbActive: { alignSelf: 'flex-end' },

  // Tri
  sortButton: { flex: 1, backgroundColor: '#f0f2f5', paddingVertical: 10, borderRadius: 8, alignItems: 'center', borderWidth: 1, borderColor: '#dcdde1' },
  sortButtonActive: { backgroundColor: '#0056b3', borderColor: '#0056b3' },
  sortButtonText: { fontSize: 13, fontWeight: 'bold', color: '#555' },
  orderButton: { width: 45, backgroundColor: '#f0f2f5', borderRadius: 8, justifyContent: 'center', alignItems: 'center', borderWidth: 1, borderColor: '#dcdde1' },
  resetButton: { alignSelf: 'center', marginTop: 20, marginBottom: 5 },
  resetButtonText: { color: '#e74c3c', fontSize: 14, fontWeight: 'bold' },

  // Résultats
  item: { flexDirection: 'row', backgroundColor: '#fff', padding: 15, borderRadius: 10, marginBottom: 10, alignItems: 'center', shadowColor: '#000', shadowOpacity: 0.02, shadowRadius: 4, elevation: 1 },
  image: { width: 50, height: 50, borderRadius: 8, marginRight: 15 },
  info: { flex: 1 },
  name: { fontSize: 16, fontWeight: 'bold', color: '#2C3E50' },
  price: { fontSize: 14, color: '#0056b3', marginTop: 4 },
  unavailable: { fontSize: 12, color: '#e74c3c', fontWeight: 'bold', marginTop: 2 },
  empty: { textAlign: 'center', color: '#7f8c8d', marginTop: 50, fontSize: 16 }
});