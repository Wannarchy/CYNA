import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ScrollView, Alert, ActivityIndicator } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

// Import des hooks sécurisés et types
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import api from '../services/api';
import { RootStackParamList } from '../navigation/AppNavigator';
import { mockAddresses } from '../data/mockData';

type CheckoutScreenNavigationProp = NativeStackNavigationProp<RootStackParamList>;

export default function CheckoutScreen() {
  const navigation = useNavigation<CheckoutScreenNavigationProp>();
  const { items, getTotal, clearCart } = useCart();
  const { isLoggedIn, user } = useAuth();
  
  const [step, setStep] = useState(1);
  const [isProcessing, setIsProcessing] = useState(false); // Pour bloquer le bouton payer
  
  // Gestion des adresses
  const [useExistingAddress, setUseExistingAddress] = useState(false);
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null);
  
  // Formulaire d'adresse
  const [address, setAddress] = useState({
    prenom: user?.prenom || '',
    nom: user?.nom || '',
    adresse1: '',
    adresse2: '',
    ville: '',
    region: '',
    code_postal: '',
    pays: 'France',
    telephone: ''
  });

  // Pour la simulation, on garde les infos en dur
  const [card] = useState({ number: '4242 4242 4242 4242', exp: '12/28', cvv: '123', name: 'Jean Dupont' });

  const handleNext = () => {
    if (step === 1 && !isLoggedIn) {
      Alert.alert("Connexion requise", "Vous devez être connecté ou créer un compte pour finaliser la commande.");
      return;
    }
    
    if (step === 2) {
      if (useExistingAddress && !selectedAddressId) {
        Alert.alert('Erreur', 'Veuillez sélectionner une adresse existante.');
        return;
      }
      if (!useExistingAddress && (!address.prenom || !address.adresse1 || !address.ville || !address.code_postal)) {
        Alert.alert('Erreur', 'Veuillez remplir les champs obligatoires de l\'adresse.');
        return;
      }
    }
    if (step < 3) setStep(step + 1);
  };

  const handlePay = async () => {
    // Empêche le double-clic
    if (isProcessing) return;
    
    setIsProcessing(true);
    
    try {
      // --- ICI ON FERA L'APPEL API LARAVEL ---
      // Exemple de ce qu'il faudra faire plus tard :
      // const response = await api.post('/orders', {
      //   items: items.map(i => ({ product_id: i.id, quantity: i.quantity, cycle: i.cycle })),
      //   address: useExistingAddress ? { id: selectedAddressId } : address,
      //   payment_method: 'stripe_simulation'
      // });

      // Pour l'instant, on simule une requête réseau de 1.5 seconde
      await new Promise(resolve => setTimeout(resolve, 1500));

      // Succès !
      clearCart();
      navigation.replace('OrderSuccess', {});
      
    } catch (error) {
      Alert.alert('Erreur de paiement', "Une erreur est survenue lors de la transaction. Veuillez réessayer.");
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => step === 1 ? navigation.goBack() : setStep(step - 1)}>
          <Text style={styles.backText}>← Retour</Text>
        </TouchableOpacity>
        <Text style={styles.title}>Paiement</Text>
        <Text style={styles.stepText}>Étape {step}/3</Text>
      </View>
      <View style={styles.progressBar}><View style={[styles.progressFill, { width: `${(step / 3) * 100}%` }]} /></View>

      <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
        
        {/* ÉTAPE 1 : IDENTITE */}
        {step === 1 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Votre identité</Text>
            {isLoggedIn ? (
              <View style={styles.card}>
                <Text style={styles.userInfo}>Connecté en tant que : <Text style={{fontWeight:'bold'}}>{user?.email}</Text></Text>
              </View>
            ) : (
              <View style={styles.card}>
                <Text style={styles.userInfo}>Vous devez être connecté pour continuer.</Text>
                <TouchableOpacity style={styles.linkButton} onPress={() => navigation.navigate('Login')}>
                  <Text style={styles.linkText}>Se connecter / Créer un compte</Text>
                </TouchableOpacity>
              </View>
            )}
            <TouchableOpacity style={styles.mainButton} onPress={handleNext}>
              <Text style={styles.mainButtonText}>Suivant</Text>
            </TouchableOpacity>
          </View>
        )}

        {/* ÉTAPE 2 : ADRESSE */}
        {step === 2 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Adresse de facturation</Text>
            
            {isLoggedIn && (
              <TouchableOpacity style={styles.existingAddressToggle} onPress={() => setUseExistingAddress(!useExistingAddress)}>
                <Text style={styles.toggleText}>Utiliser une adresse existante</Text>
                <View style={[styles.switchTrack, useExistingAddress && styles.switchTrackActive]}>
                  <View style={[styles.switchThumb, useExistingAddress && styles.switchThumbActive]} />
                </View>
              </TouchableOpacity>
            )}

            {useExistingAddress && (
              <View style={{ marginBottom: 20 }}>
                {mockAddresses.map((addr) => (
                  <TouchableOpacity 
                    key={addr.id} 
                    style={[styles.addressCard, selectedAddressId === addr.id && styles.addressCardSelected]}
                    onPress={() => setSelectedAddressId(addr.id)}
                  >
                    <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                      <View style={[styles.radio, selectedAddressId === addr.id && styles.radioActive]} />
                      <Text style={styles.addressLabel}>{addr.label} - {addr.prenom} {addr.nom}</Text>
                    </View>
                    <Text style={styles.addressText}>{addr.adresse1}, {addr.code_postal} {addr.ville}</Text>
                  </TouchableOpacity>
                ))}
              </View>
            )}

            {!useExistingAddress && (
              <>
                <View style={styles.row}>
                  <TextInput style={[styles.input, { flex: 1, marginRight: 10 }]} placeholder="Prénom *" value={address.prenom} onChangeText={(t) => setAddress({...address, prenom: t})} />
                  <TextInput style={[styles.input, { flex: 1 }]} placeholder="Nom *" value={address.nom} onChangeText={(t) => setAddress({...address, nom: t})} />
                </View>
                <TextInput style={styles.input} placeholder="Adresse * (Rue, numéro)" value={address.adresse1} onChangeText={(t) => setAddress({...address, adresse1: t})} />
                <TextInput style={styles.input} placeholder="Complément d'adresse (Bâtiment, étage...)" value={address.adresse2} onChangeText={(t) => setAddress({...address, adresse2: t})} />
                <View style={styles.row}>
                  <TextInput style={[styles.input, { flex: 2, marginRight: 10 }]} placeholder="Ville *" value={address.ville} onChangeText={(t) => setAddress({...address, ville: t})} />
                  <TextInput style={[styles.input, { flex: 1, marginRight: 10 }]} placeholder="CP *" value={address.code_postal} onChangeText={(t) => setAddress({...address, code_postal: t})} keyboardType="numeric" />
                </View>
                <View style={styles.row}>
                  <TextInput style={[styles.input, { flex: 1, marginRight: 10 }]} placeholder="Région / État" value={address.region} onChangeText={(t) => setAddress({...address, region: t})} />
                  <TextInput style={[styles.input, { flex: 1 }]} placeholder="Téléphone" value={address.telephone} onChangeText={(t) => setAddress({...address, telephone: t})} keyboardType="phone-pad" />
                </View>
              </>
            )}
            <TouchableOpacity style={styles.mainButton} onPress={handleNext}>
              <Text style={styles.mainButtonText}>Vérifier ma commande</Text>
            </TouchableOpacity>
          </View>
        )}

        {/* ÉTAPE 3 : PAIEMENT */}
        {step === 3 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Paiement sécurisé</Text>
            <View style={styles.summaryCard}>
              <Text style={styles.summaryTitle}>Récapitulatif</Text>
              {items.map(item => (
                <View key={item.id} style={styles.summaryRow}>
                  <Text style={styles.summaryItemName}>{item.name} ({item.cycle === 'monthly' ? 'Mensuel' : 'Annuel'}) x{item.quantity}</Text>
                  <Text style={styles.summaryPrice}>{(item.price * item.quantity).toFixed(2)} €</Text>
                </View>
              ))}
              <View style={styles.separator} />
              <View style={styles.summaryRow}>
                <Text style={styles.totalText}>Total TTC</Text>
                <Text style={styles.totalAmount}>{getTotal().toFixed(2)} €</Text>
              </View>
            </View>
            <Text style={styles.label}>Informations bancaires</Text>
            <TextInput style={styles.input} placeholder="Nom sur la carte" value={card.name} editable={false} />
            <TextInput style={styles.input} placeholder="Numéro de carte" value={card.number} editable={false} keyboardType="numeric" />
            <View style={styles.row}>
              <TextInput style={[styles.input, { flex: 1, marginRight: 10 }]} placeholder="Expiration" value={card.exp} editable={false} />
              <TextInput style={[styles.input, { flex: 1 }]} placeholder="CVV" value={card.cvv} editable={false} keyboardType="numeric" />
            </View>
            <Text style={styles.secureNote}>🔒 Paiement sécurisé par Stripe (Simulation)</Text>
            
            {/* Bouton de paiement avec état de chargement */}
            <TouchableOpacity 
              style={[styles.payButton, isProcessing && { backgroundColor: '#a0d8b4' }]} 
              onPress={handlePay}
              disabled={isProcessing}
            >
              {isProcessing ? (
                <ActivityIndicator color="#fff" size="small" />
              ) : (
                <Text style={styles.payButtonText}>Payer {getTotal().toFixed(2)} €</Text>
              )}
            </TouchableOpacity>
          </View>
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  // ... (Tes styles restent exactement les mêmes, je les raccourcis pour l'affichage ici, mais garde les tiens !)
  container: { flex: 1, backgroundColor: '#F5F7FA' },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 20, backgroundColor: '#fff' },
  backText: { color: '#0056b3', fontSize: 16, fontWeight: '600' },
  title: { fontSize: 18, fontWeight: 'bold', color: '#2C3E50' },
  stepText: { color: '#7f8c8d', fontSize: 14 },
  progressBar: { height: 4, backgroundColor: '#e0e0e0' },
  progressFill: { height: '100%', backgroundColor: '#0056b3' },
  content: { padding: 20 },
  section: { paddingBottom: 30 },
  sectionTitle: { fontSize: 20, fontWeight: 'bold', color: '#2C3E50', marginBottom: 20 },
  card: { backgroundColor: '#fff', padding: 20, borderRadius: 10, shadowColor: '#000', shadowOpacity: 0.02, shadowRadius: 4, elevation: 1 },
  userInfo: { fontSize: 16, color: '#555', marginBottom: 15 },
  linkButton: { marginBottom: 15 },
  linkText: { color: '#0056b3', fontSize: 16, fontWeight: 'bold' },
  row: { flexDirection: 'row' },
  input: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#dcdde1', borderRadius: 8, padding: 15, fontSize: 16, marginBottom: 15, color: '#2C3E50' },
  label: { fontSize: 14, fontWeight: '600', color: '#34495E', marginBottom: 8, marginTop: 10 },
  mainButton: { backgroundColor: '#0056b3', padding: 16, borderRadius: 10, alignItems: 'center', marginTop: 20 },
  mainButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
  existingAddressToggle: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: '#fff', padding: 15, borderRadius: 8, borderWidth: 1, borderColor: '#dcdde1', marginBottom: 20 },
  toggleText: { fontSize: 15, color: '#34495E', fontWeight: '600' },
  switchTrack: { width: 50, height: 28, borderRadius: 14, backgroundColor: '#dcdde1', justifyContent: 'center', padding: 2 },
  switchTrackActive: { backgroundColor: '#0056b3' },
  switchThumb: { width: 24, height: 24, borderRadius: 12, backgroundColor: '#fff' },
  switchThumbActive: { alignSelf: 'flex-end' },
  addressCard: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#dcdde1', borderRadius: 8, padding: 15, marginBottom: 10 },
  addressCardSelected: { borderColor: '#0056b3', backgroundColor: '#eaf2f8' },
  radio: { width: 20, height: 20, borderRadius: 10, borderWidth: 2, borderColor: '#ccc', marginRight: 15 },
  radioActive: { borderColor: '#0056b3', backgroundColor: '#0056b3' },
  addressLabel: { fontSize: 15, fontWeight: 'bold', color: '#2C3E50', flex: 1 },
  addressText: { fontSize: 13, color: '#7f8c8d', marginTop: 5, marginLeft: 35 },
  summaryCard: { backgroundColor: '#fff', padding: 20, borderRadius: 10, marginBottom: 20, shadowColor: '#000', shadowOpacity: 0.02, shadowRadius: 4, elevation: 1 },
  summaryTitle: { fontSize: 16, fontWeight: 'bold', marginBottom: 15, color: '#2C3E50' },
  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 10 },
  summaryItemName: { color: '#555', flex: 1, marginRight: 10 },
  summaryPrice: { color: '#2C3E50', fontWeight: '600' },
  separator: { height: 1, backgroundColor: '#eee', marginVertical: 10 },
  totalText: { fontSize: 18, fontWeight: 'bold', color: '#2C3E50' },
  totalAmount: { fontSize: 20, fontWeight: 'bold', color: '#0056b3' },
  secureNote: { textAlign: 'center', color: '#27ae60', fontSize: 12, marginTop: 10, marginBottom: 20 },
  payButton: { backgroundColor: '#27ae60', padding: 18, borderRadius: 10, alignItems: 'center', shadowColor: '#27ae60', shadowOpacity: 0.3, shadowOffset: { height: 4, width: 0 }, shadowRadius: 8, elevation: 5 },
  payButtonText: { color: '#fff', fontSize: 18, fontWeight: 'bold' },
});