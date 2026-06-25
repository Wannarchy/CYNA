import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { RootStackParamList } from '../navigation/AppNavigator';

type OrderSuccessScreenNavigationProp = NativeStackNavigationProp<RootStackParamList>;

export default function OrderSuccessScreen() {
  const navigation = useNavigation<OrderSuccessScreenNavigationProp>();

  // popToTop remonte tout en haut de la pile d'écrans (jusqu'au MainTabs)
  // Ça nettoie l'historique (Checkout, etc.) pour éviter de retourner sur le paiement
  const goHome = () => {
    navigation.popToTop();
    // Optionnel : si tu veux aller sur l'onglet Account directement, utilise ça :
    // navigation.navigate('MainTabs', { screen: 'Account' });
  };

  return (
    <View style={styles.container}>
      {/* Cercle de succès animé */}
      <View style={styles.iconCircle}>
        <Ionicons name="checkmark" size={70} color="#fff" />
      </View>
      
      <Text style={styles.title}>Paiement Réussi !</Text>
      <Text style={styles.subtitle}>
        Vos abonnements SaaS sont maintenant actifs. Un email de confirmation vous a été envoyé.
      </Text>
      
      <TouchableOpacity 
        style={styles.primaryButton} 
        onPress={() => navigation.navigate('MainTabs')}
      >
        <Ionicons name="receipt-outline" size={20} color="#fff" style={{ marginRight: 8 }} />
        <Text style={styles.primaryButtonText}>Voir mes commandes</Text>
      </TouchableOpacity>

      <TouchableOpacity 
        style={styles.secondaryButton} 
        onPress={goHome}
      >
        <Text style={styles.secondaryButtonText}>Retour à l'accueil</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC', justifyContent: 'center', alignItems: 'center', padding: 30 },
  iconCircle: {
    width: 120,
    height: 120,
    borderRadius: 60,
    backgroundColor: '#10b981',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 40,
    shadowColor: '#10b981',
    shadowOpacity: 0.3,
    shadowOffset: { height: 10, width: 0 },
    shadowRadius: 20,
    elevation: 10,
  },
  title: { 
    fontSize: 26, 
    fontWeight: 'bold', 
    color: '#1E293B', 
    marginBottom: 15,
    textAlign: 'center'
  },
  subtitle: { 
    fontSize: 15, 
    color: '#64748B', 
    textAlign: 'center', 
    lineHeight: 24, 
    marginBottom: 50 
  },
  primaryButton: { 
    flexDirection: 'row',
    backgroundColor: '#0056b3', 
    paddingVertical: 16, 
    paddingHorizontal: 30,
    borderRadius: 12, 
    width: '100%', 
    alignItems: 'center', 
    justifyContent: 'center',
    marginBottom: 15,
    shadowColor: '#0056b3',
    shadowOpacity: 0.3,
    shadowOffset: { height: 4, width: 0 },
    shadowRadius: 8,
    elevation: 5,
  },
  primaryButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
  secondaryButton: { 
    padding: 15, 
    width: '100%', 
    alignItems: 'center' 
  },
  secondaryButtonText: { color: '#64748B', fontSize: 15, fontWeight: '600' }
});