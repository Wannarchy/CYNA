import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, TextInput, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import apiClient from '../../utils/apiClient';
import ProductCard from '../../components/ProductCard';

export default function CatalogueScreen({ navigation }) {
  const [products, setProducts]   = useState([]);
  const [filtered, setFiltered]   = useState([]);
  const [search, setSearch]       = useState('');
  const [loading, setLoading]     = useState(true);
  const [error, setError]         = useState(null);

  useEffect(() => { fetchProducts(); }, []);

  useEffect(() => {
    if (!search.trim()) { setFiltered(products); return; }
    const q = search.toLowerCase();
    setFiltered(products.filter((p) => p.name.toLowerCase().includes(q)));
  }, [search, products]);

  const fetchProducts = async () => {
    setLoading(true); setError(null);
    try {
      const res = await apiClient.get('/products/index.php');
      const data = res.data.products ?? res.data;
      setProducts(data); setFiltered(data);
    } catch { setError('Impossible de charger le catalogue.'); }
    finally { setLoading(false); }
  };

  if (loading) return <View style={styles.centered}><ActivityIndicator size="large" color="#4F46E5" /></View>;
  if (error)   return (
    <View style={styles.centered}>
      <Text style={styles.errorText}>{error}</Text>
      <TouchableOpacity style={styles.retryBtn} onPress={fetchProducts}><Text style={styles.retryText}>Réessayer</Text></TouchableOpacity>
    </View>
  );

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Catalogue</Text>
        <Text style={styles.sub}>{filtered.length} solution{filtered.length > 1 ? 's' : ''}</Text>
      </View>
      <View style={styles.searchRow}>
        <Ionicons name="search-outline" size={18} color="#9CA3AF" style={{ marginRight: 8 }} />
        <TextInput style={styles.searchInput} placeholder="Rechercher EDR, SOC, XDR…" placeholderTextColor="#9CA3AF"
          value={search} onChangeText={setSearch} />
        {search ? <TouchableOpacity onPress={() => setSearch('')}><Ionicons name="close-circle" size={18} color="#9CA3AF" /></TouchableOpacity> : null}
      </View>
      <FlatList data={filtered} keyExtractor={(i) => String(i.id)}
        renderItem={({ item }) => (
          <ProductCard product={item} onPress={() => navigation.navigate('ProductDetail', { productId: item.id })} />
        )}
        contentContainerStyle={styles.list} showsVerticalScrollIndicator={false}
        ListEmptyComponent={<View style={styles.centered}><Ionicons name="search-outline" size={40} color="#D1D5DB" /><Text style={styles.emptyText}>Aucun résultat pour "{search}"</Text></View>}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  centered:  { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
  header:    { paddingHorizontal: 20, paddingTop: 56, paddingBottom: 16, backgroundColor: '#FFF' },
  title:     { fontSize: 26, fontWeight: '700', color: '#111827' },
  sub:       { fontSize: 13, color: '#6B7280', marginTop: 2 },
  searchRow: { flexDirection: 'row', alignItems: 'center', margin: 16, backgroundColor: '#FFF', borderRadius: 12, borderWidth: 1, borderColor: '#E5E7EB', paddingHorizontal: 12, height: 46 },
  searchInput: { flex: 1, fontSize: 14, color: '#111827' },
  list:      { paddingHorizontal: 16, paddingBottom: 24 },
  errorText: { color: '#EF4444', marginBottom: 12 },
  retryBtn:  { backgroundColor: '#4F46E5', borderRadius: 10, paddingHorizontal: 20, paddingVertical: 10 },
  retryText: { color: '#FFF', fontWeight: '600' },
  emptyText: { color: '#9CA3AF', marginTop: 12, fontSize: 14 },
});
