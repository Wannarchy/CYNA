import React from 'react';
import { View, Text, FlatList, Image, StyleSheet, TouchableOpacity } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { mockCategories } from '../data/mockData';

export default function CategoriesScreen() {
  const navigation = useNavigation<any>();

  const renderCategory = ({ item }: { item: typeof mockCategories[0] }) => (
    <TouchableOpacity 
      style={styles.card}
      onPress={() => navigation.navigate('Category', { categoryId: item.id })}
    >
      <Image source={{ uri: item.image_path }} style={styles.image} />
      <View style={styles.textContainer}>
        <Text style={styles.name}>{item.name}</Text>
        <Text style={styles.subtitle}>Voir les services →</Text>
      </View>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      <FlatList
        data={mockCategories}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.list}
        renderItem={renderCategory}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f8f9fa' },
  list: { padding: 15 },
  card: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    borderRadius: 10,
    marginBottom: 15,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 2,
    alignItems: 'center',
  },
  image: { width: 100, height: 100 },
  textContainer: { flex: 1, padding: 15 },
  name: { fontSize: 18, fontWeight: 'bold', color: '#333', marginBottom: 5 },
  subtitle: { fontSize: 14, color: '#0056b3', fontWeight: '600' },
});