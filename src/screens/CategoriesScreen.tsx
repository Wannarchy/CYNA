import React from 'react';
import { View, Text, FlatList, Image, StyleSheet, TouchableOpacity, ActivityIndicator } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { useQuery } from '@tanstack/react-query';
import api, { getFullImageUrl } from '../services/api';
import { RootStackParamList } from '../navigation/AppNavigator';

type CategoriesScreenNavigationProp = NativeStackNavigationProp<RootStackParamList>;

interface Category {
  id: number;
  name: string;
  image_path: string;
}

export default function CategoriesScreen() {
  const navigation = useNavigation<CategoriesScreenNavigationProp>();

  // --- REQUÊTE API ---
  const { data: categoriesData, isLoading } = useQuery({
    queryKey: ['categories'],
    queryFn: async () => (await api.get('/categories')).data,
  });

  // --- DÉBALLAGE DES DONNÉES LARAVEL ---
  const rawCategories = categoriesData?.data ?? categoriesData;
  const categories: Category[] = Array.isArray(rawCategories) ? rawCategories : [];

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color="#0056b3" />
      </View>
    );
  }

  const renderCategory = ({ item }: { item: Category }) => (
    <TouchableOpacity 
      style={styles.card}
      // On envoie l'ID et le Nom pour que le CategoryScreen n'ait pas à le chercher
      onPress={() => navigation.navigate('Category', { categoryId: String(item.id), categoryName: item.name })}
      activeOpacity={0.8}
    >
      <View style={styles.imageContainer}>
        <Image source={{ uri: getFullImageUrl(item.image_path) }} style={styles.image} />
      </View>
      
      <View style={styles.textContainer}>
        <Text style={styles.name}>{item.name}</Text>
        <Text style={styles.subtitle}>Voir les services</Text>
      </View>

      <View style={styles.arrowContainer}>
        <Ionicons name="chevron-forward" size={20} color="#94a3b8" />
      </View>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      <FlatList
        data={categories}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={{ padding: 20 }}
        renderItem={renderCategory}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Ionicons name="folder-open-outline" size={60} color="#CBD5E1" />
            <Text style={styles.emptyTitle}>Aucune catégorie</Text>
            <Text style={styles.emptySubtitle}>Les catégories apparaîtront ici.</Text>
          </View>
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F8FAFC' },
  
  // Carte
  card: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    borderRadius: 16,
    marginBottom: 15,
    padding: 15,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOpacity: 0.03,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 10,
    elevation: 2,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  imageContainer: {
    width: 60,
    height: 60,
    borderRadius: 12,
    backgroundColor: '#F1F5F9',
    justifyContent: 'center',
    alignItems: 'center',
    overflow: 'hidden',
  },
  image: {
    width: 40,
    height: 40,
    borderRadius: 20,
  },
  textContainer: {
    flex: 1,
    marginLeft: 15,
  },
  name: { 
    fontSize: 17, 
    fontWeight: 'bold', 
    color: '#1E293B', 
    marginBottom: 4 
  },
  subtitle: { 
    fontSize: 13, 
    color: '#0056b3', 
    fontWeight: '600' 
  },
  arrowContainer: {
    paddingLeft: 10,
  },

  // Empty State
  emptyContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 40, marginTop: 50 },
  emptyTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B', marginTop: 20 },
  emptySubtitle: { fontSize: 14, color: '#64748B', textAlign: 'center', marginTop: 8 }
});