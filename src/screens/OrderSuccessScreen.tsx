import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';

type NavProp = NativeStackNavigationProp<any>;

export default function OrderSuccessScreen() {
  const navigation = useNavigation<NavProp>();

  return (
    <View style={styles.container}>
      <Ionicons name="checkmark-circle" size={100} color="#27ae60" />
      <Text style={styles.title}>Paiement Réussi !</Text>
      <Text style={styles.subtitle}>Vos abonnements SaaS sont maintenant actifs. Un email de confirmation vous a été envoyé.</Text>
      
      <TouchableOpacity 
        style={styles.button} 
        onPress={() => navigation.navigate('MainTabs', { screen: 'Account' })}
      >
        <Text style={styles.buttonText}>Voir mes commandes</Text>
      </TouchableOpacity>

      <TouchableOpacity 
        style={styles.linkButton} 
        onPress={() => navigation.navigate('MainTabs', { screen: 'Home' })}
      >
        <Text style={styles.linkText}>Retour à l'accueil</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff', justifyContent: 'center', alignItems: 'center', padding: 40 },
  title: { fontSize: 26, fontWeight: 'bold', color: '#2C3E50', marginTop: 20, marginBottom: 10 },
  subtitle: { fontSize: 16, color: '#7f8c8d', textAlign: 'center', lineHeight: 24, marginBottom: 40 },
  button: { backgroundColor: '#0056b3', padding: 16, borderRadius: 10, width: '100%', alignItems: 'center', marginBottom: 15 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
  linkButton: { padding: 15 },
  linkText: { color: '#0056b3', fontSize: 16, fontWeight: '600' }
});