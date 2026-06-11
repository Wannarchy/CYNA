import React, { useContext } from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { CartContext } from '../context/CartContext';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

type NavProp = NativeStackNavigationProp<any>;

export default function Header() {
  const { items } = useContext(CartContext);
  const itemCount = items.length;
  const navigation = useNavigation<NavProp>(); // On récupère la navigation

  return (
    <View style={styles.header}>
      <TouchableOpacity onPress={() => navigation.navigate('MainTabs', { screen: 'Home' })}>
        <Text style={styles.logo}>CYNA</Text>
      </TouchableOpacity>

      <View style={styles.iconsContainer}>
        <TouchableOpacity style={styles.icon} onPress={() => navigation.navigate('Search')}>
          <Ionicons name="search-outline" size={24} color="#34495E" />
        </TouchableOpacity>

        <TouchableOpacity 
          style={styles.icon}
          // LE BOUTON PANIER EST MAINTENANT CLIQUABLE
          onPress={() => navigation.navigate('MainTabs', { screen: 'Cart' })}
        >
          <Ionicons name="cart-outline" size={24} color="#34495E" />
          {itemCount > 0 && (
            <View style={styles.badge}>
              <Text style={styles.badgeText}>{itemCount}</Text>
            </View>
          )}
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  header: {
    height: 60,
    backgroundColor: '#FFFFFF',
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 3,
  },
  logo: {
    fontSize: 24,
    fontWeight: '900',
    color: '#0056b3',
    letterSpacing: 1,
  },
  iconsContainer: {
    flexDirection: 'row',
  },
  icon: {
    marginLeft: 25,
    position: 'relative',
  },
  badge: {
    position: 'absolute',
    right: -8,
    top: -8,
    backgroundColor: '#e74c3c',
    borderRadius: 10,
    minWidth: 20,
    height: 20,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#FFFFFF', 
  },
  badgeText: {
    color: '#FFFFFF',
    fontSize: 11,
    fontWeight: 'bold',
  },
});