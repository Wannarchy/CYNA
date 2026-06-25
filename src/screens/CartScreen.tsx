import React from 'react';
import { View, Text, FlatList, Image, StyleSheet, TouchableOpacity, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

// Imports mis à jour
import { useCart } from '../context/CartContext';
import { getFullImageUrl } from '../services/api';
import { RootStackParamList } from '../navigation/AppNavigator';

// Typage strict de la navigation
type CartScreenNavigationProp = NativeStackNavigationProp<RootStackParamList>;

export default function CartScreen() {
  // Utilisation du hook personnalisé et sécurisé
  const { items, removeItem, updateQuantity, getTotal } = useCart();
  const navigation = useNavigation<CartScreenNavigationProp>();

  const handleRemove = (itemId: number, itemName: string) => {
    Alert.alert(
      "Supprimer",
      `Voulez-vous retirer "${itemName}" de votre panier ?`,
      [
        { text: "Annuler", style: "cancel" },
        { text: "Supprimer", style: "destructive", onPress: () => removeItem(itemId) }
      ]
    );
  };

  // Si le panier est vide
  if (items.length === 0) {
    return (
      <View style={styles.emptyContainer}>
        <Ionicons name="cart-outline" size={80} color="#dcdde1" />
        <Text style={styles.emptyText}>Votre panier est vide</Text>
        <Text style={styles.emptySubText}>Découvrez nos solutions de cybersécurité</Text>
        <TouchableOpacity 
          style={styles.shopButton} 
          // Navigation typée (cast to any to satisfy nested navigator params)
          onPress={() => navigation.navigate('MainTabs' as any, { screen: 'Home' })}
        >
          <Text style={styles.shopButtonText}>Voir le catalogue</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // Si le panier contient des articles
  return (
    <View style={styles.container}>
      <FlatList
        data={items}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.list}
        renderItem={({ item }) => (
          <View style={styles.cartItem}>
            {/* Image du produit - URL corrigée */}
            <Image source={{ uri: getFullImageUrl(item.image_path) }} style={styles.itemImage} />
            
            {/* Infos du produit */}
            <View style={styles.itemInfo}>
              <Text style={styles.itemName}>{item.name}</Text>
              <View style={styles.cycleBadge}>
                <Text style={styles.itemCycle}>
                  {item.cycle === 'monthly' ? 'Mensuel' : 'Annuel'}
                </Text>
              </View>
              
              {/* Contrôles de quantité */}
              <View style={styles.quantityContainer}>
                <TouchableOpacity 
                  style={styles.quantityButton} 
                  onPress={() => updateQuantity(item.id, item.quantity - 1)}
                >
                  <Ionicons name="remove" size={16} color="#2C3E50" />
                </TouchableOpacity>
                <Text style={styles.quantityText}>{item.quantity}</Text>
                <TouchableOpacity 
                  style={styles.quantityButton} 
                  onPress={() => updateQuantity(item.id, item.quantity + 1)}
                >
                  <Ionicons name="add" size={16} color="#2C3E50" />
                </TouchableOpacity>
              </View>
            </View>

            {/* Prix et Bouton Supprimer */}
            <View style={styles.itemRight}>
              {/* Prix total pour cet article (prix unitaire * quantité) */}
              <Text style={styles.itemPrice}>{(item.price * item.quantity).toFixed(2)} €</Text>
              
              <TouchableOpacity 
                style={styles.deleteButton} 
                onPress={() => handleRemove(item.id, item.name)}
              >
                <Ionicons name="trash-outline" size={20} color="#e74c3c" />
              </TouchableOpacity>
            </View>
          </View>
        )}
      />
      
      {/* Pied de page fixe avec le total et le bouton de paiement */}
      <View style={styles.footer}>
        <View style={styles.totalContainer}>
          <Text style={styles.totalLabel}>Total TTC</Text>
          <Text style={styles.totalAmount}>{getTotal().toFixed(2)} €</Text>
        </View>
        
        <TouchableOpacity 
          style={styles.checkoutButton}
          // Navigation typée
          onPress={() => navigation.navigate('Checkout')}
        >
          <Text style={styles.checkoutText}>Passer à la caisse</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { 
    flex: 1, 
    backgroundColor: '#F5F7FA' 
  },
  emptyContainer: { 
    flex: 1, 
    justifyContent: 'center', 
    alignItems: 'center', 
    backgroundColor: '#fff',
    paddingHorizontal: 40
  },
  emptyText: { 
    fontSize: 20, 
    fontWeight: 'bold', 
    color: '#2C3E50', 
    marginTop: 25 
  },
  emptySubText: { 
    fontSize: 14, 
    color: '#7f8c8d', 
    marginTop: 8, 
    textAlign: 'center' 
  },
  shopButton: {
    marginTop: 30,
    backgroundColor: '#0056b3',
    paddingHorizontal: 30,
    paddingVertical: 12,
    borderRadius: 10
  },
  shopButtonText: {
    color: '#fff',
    fontWeight: 'bold',
    fontSize: 16
  },
  list: { 
    padding: 15, 
    paddingBottom: 200 
  },
  cartItem: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    padding: 15,
    borderRadius: 12,
    marginBottom: 15,
    shadowColor: '#000',
    shadowOpacity: 0.03,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 8,
    elevation: 2,
    alignItems: 'center',
  },
  itemImage: { 
    width: 60, 
    height: 60, 
    borderRadius: 10, 
    marginRight: 15 
  },
  itemInfo: { 
    flex: 1, 
    justifyContent: 'center' 
  },
  itemName: { 
    fontSize: 16, 
    fontWeight: 'bold', 
    color: '#2C3E50', 
    marginBottom: 8 
  },
  cycleBadge: { 
    backgroundColor: '#eaf2f8', 
    paddingHorizontal: 8, 
    paddingVertical: 4, 
    borderRadius: 4, 
    alignSelf: 'flex-start',
    marginBottom: 10
  },
  itemCycle: { 
    fontSize: 12, 
    color: '#2980b9', 
    fontWeight: '600' 
  },
  // Nouveaux styles pour les boutons de quantité
  quantityContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  quantityButton: {
    width: 26,
    height: 26,
    borderRadius: 13,
    backgroundColor: '#f0f4f8',
    justifyContent: 'center',
    alignItems: 'center',
  },
  quantityText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#2C3E50',
    minWidth: 20,
    textAlign: 'center',
  },
  itemRight: { 
    alignItems: 'flex-end', 
    justifyContent: 'space-between', 
    height: 80 // Un peu plus haut pour s'adapter aux boutons de quantité
  },
  itemPrice: { 
    fontSize: 16, 
    fontWeight: 'bold', 
    color: '#2C3E50', 
    marginBottom: 10 
  },
  deleteButton: { 
    padding: 10,
    borderRadius: 20,
    backgroundColor: '#ffeaea'
  },
  footer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: '#FFFFFF',
    padding: 20,
    paddingBottom: 30, // Un peu plus de marge en bas pour les téléphones avec encoche
    borderTopWidth: 1,
    borderColor: '#eee',
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowOffset: { height: -5, width: 0 },
    shadowRadius: 10,
    elevation: 10,
  },
  totalContainer: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    marginBottom: 15 
  },
  totalLabel: { 
    fontSize: 16, 
    color: '#7f8c8d' 
  },
  totalAmount: { 
    fontSize: 22, 
    fontWeight: 'bold', 
    color: '#2C3E50' 
  },
  checkoutButton: { 
    backgroundColor: '#27ae60', 
    padding: 18, 
    borderRadius: 12, 
    alignItems: 'center',
    shadowColor: '#27ae60',
    shadowOpacity: 0.3,
    shadowOffset: { height: 4, width: 0 },
    shadowRadius: 8,
    elevation: 5
  },
  checkoutText: { 
    color: '#fff', 
    fontSize: 18, 
    fontWeight: 'bold' 
  },
});