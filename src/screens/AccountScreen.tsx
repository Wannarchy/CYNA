import React, { useContext } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { AuthContext } from '../context/AuthContext';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

type NavProp = NativeStackNavigationProp<any>;

export default function AccountScreen() {
  const navigation = useNavigation<NavProp>();
  const { user, isLoggedIn, logout } = useContext(AuthContext);

  if (isLoggedIn && user) {
    return (
      <View style={styles.container}>
        <View style={styles.headerAccount}>
          <Ionicons name="person-circle" size={80} color="#0056b3" />
          <Text style={styles.title}>Bonjour, {user.prenom}</Text>
          <Text style={styles.subtitle}>{user.email}</Text>
        </View>

        <View style={styles.menuContainer}>
          <TouchableOpacity style={styles.menuItem} onPress={() => navigation.navigate('Profile')}>
            <Ionicons name="person-outline" size={24} color="#333" />
            <Text style={styles.menuText}>Modifier mes informations</Text>
            <Ionicons name="chevron-forward" size={20} color="#ccc" />
          </TouchableOpacity>
          <TouchableOpacity style={styles.menuItem} onPress={() => navigation.navigate('OrderHistory')}>
            <Ionicons name="receipt-outline" size={24} color="#333" />
            <Text style={styles.menuText}>Mes commandes & Factures</Text>
            <Ionicons name="chevron-forward" size={20} color="#ccc" />
          </TouchableOpacity>
          <TouchableOpacity style={styles.menuItem} onPress={() => navigation.navigate('AddressBook')}>
            <Ionicons name="location-outline" size={24} color="#333" />
            <Text style={styles.menuText}>Adresses de facturation</Text>
            <Ionicons name="chevron-forward" size={20} color="#ccc" />
          </TouchableOpacity>
          
          <View style={styles.separator} />

          {/* NOUVEAU : Accès direct au Chatbot */}
          <TouchableOpacity style={styles.menuItem} onPress={() => navigation.navigate('Chatbot')}>
            <Ionicons name="chatbubbles-outline" size={24} color="#0056b3" />
            <Text style={[styles.menuText, { color: '#0056b3', fontWeight: '600' }]}>Chatbot d'assistance</Text>
            <Ionicons name="chevron-forward" size={20} color="#ccc" />
          </TouchableOpacity>

          <TouchableOpacity style={styles.menuItem} onPress={() => navigation.navigate('Contact')}>
            <Ionicons name="mail-outline" size={24} color="#333" />
            <Text style={styles.menuText}>Aide & Contact</Text>
            <Ionicons name="chevron-forward" size={20} color="#ccc" />
          </TouchableOpacity>

          <View style={{ height: 10 }} />
                    <TouchableOpacity 
            style={styles.menuItem} 
            onPress={() => {
              Alert.alert("Déconnexion", "Voulez-vous vous déconnecter ?", [
                { text: "Annuler", style: "cancel" },
                { text: "Déconnexion", style: "destructive", onPress: logout } // CORRIGÉ
              ]);
            }}
          >
            <Ionicons name="log-out-outline" size={24} color="#e74c3c" />
            <Text style={[styles.menuText, { color: '#e74c3c' }]}>Se déconnecter</Text>
            <Ionicons name="chevron-forward" size={20} color="#ccc" />
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  // Si l'utilisateur n'est pas connecté
  return (
    <View style={styles.container}>
      <View style={styles.headerAccount}>
        <Ionicons name="person-circle-outline" size={80} color="#005b3" />
        <Text style={styles.title}>Mon Espace Client</Text>
        <Text style={styles.subtitle}>Connectez-vous pour gérer vos abonnements</Text>
      </View>

      <View style={styles.menuContainer}>
        <TouchableOpacity style={styles.menuItem} onPress={() => navigation.navigate('Login')}>
          <Ionicons name="log-in-outline" size={24} color="#333" />
          <Text style={styles.menuText}>Se connecter</Text>
          <Ionicons name="chevron-forward" size={20} color="#ccc" />
        </TouchableOpacity>

        <TouchableOpacity style={styles.menuItem} onPress={() => navigation.navigate('Register')}>
          <Ionicons name="person-add-outline" size={24} color="#333" />
          <Text style={styles.menuText}>Créer un compte</Text>
          <Ionicons name="chevron-forward" size={20} color="#ccc" />
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f8f9fa' },
  headerAccount: { backgroundColor: '#fff', padding: 30, alignItems: 'center', borderBottomWidth: 1, borderColor: '#eee', paddingTop: 40 },
  title: { fontSize: 22, fontWeight: 'bold', color: '#333', marginTop: 15 },
  subtitle: { fontSize: 14, color: '#666', marginTop: 5, textAlign: 'center' },
  menuContainer: { marginTop: 20 },
  menuItem: { flexDirection: 'row', backgroundColor: '#fff', padding: 18, alignItems: 'center', borderBottomWidth: 1, borderColor: '#f0f0f0' },
  menuText: { flex: 1, fontSize: 16, color: '#333', marginLeft: 15 },
  separator: { height: 1, backgroundColor: '#eee', marginHorizontal: 20, marginVertical: 15 },
});