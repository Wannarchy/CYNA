import React, { useContext } from 'react';
import { View, Text, FlatList, Image, StyleSheet, TouchableOpacity, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { CartContext } from '../context/CartContext';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

// Typage strict pour éviter les erreurs rouges avec la navigation
type NavProp = NativeStackNavigationProp<any>;

export default function CartScreen() {
  const { items, removeItem, getTotal } = useContext(CartContext);
  const navigation = useNavigation<NavProp>();

  // Fonction pour supprimer avec une petite alerte de sécurité
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
          onPress={() => navigation.navigate('MainTabs', { screen: 'Home' })}
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
            {/* Image du produit */}
            <Image source={{ uri: item.image_path }} style={styles.itemImage} />
            
            {/* Infos du produit */}
            <View style={styles.itemInfo}>
              <Text style={styles.itemName}>{item.name}</Text>
              <View style={styles.cycleBadge}>
                <Text style={styles.itemCycle}>
                  {item.cycle === 'monthly' ? 'Mensuel' : 'Annuel'}
                </Text>
              </View>
            </View>

            {/* Prix et Bouton Supprimer */}
            <View style={styles.itemRight}>
              <Text style={styles.itemPrice}>{item.price.toFixed(2)} €</Text>
              
              {/* TouchableOpacity avec un gros padding pour faciliter le clic sur Web et Mobile */}
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
          // Navigation vers le tunnel d'achat
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
    paddingBottom: 200 // Espace suffisant pour ne pas cacher les articles derrière le footer
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
    alignSelf: 'flex-start' 
  },
  itemCycle: { 
    fontSize: 12, 
    color: '#2980b9', 
    fontWeight: '600' 
  },
  itemRight: { 
    alignItems: 'flex-end', 
    justifyContent: 'space-between', 
    height: 60 
  },
  itemPrice: { 
    fontSize: 16, 
    fontWeight: 'bold', 
    color: '#2C3E50', 
    marginBottom: 10 
  },
  deleteButton: { 
    padding: 10, // Rend la zone cliquable beaucoup plus grande
    borderRadius: 20,
    backgroundColor: '#ffeaea' // Léger fond rouge pour montrer que c'est cliquable
  },
  
  // Styles du pied de page
  footer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: '#FFFFFF',
    padding: 20,
    borderTopWidth: 1,
    borderColor: '#eee',
    // Ombre pour flotter au-dessus de la liste
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowOffset: {
        height: -5,
        width: 0
    },
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
    // Ombre verte pour le bouton d'action principal
    shadowColor: '#27ae60',
    shadowOpacity: 0.3,
    shadowOffset: {
        height: 4,
        width: 0
    },
    shadowRadius: 8,
    elevation: 5
  },
  checkoutText: { 
    color: '#fff', 
    fontSize: 18, 
    fontWeight: 'bold' 
  },
});