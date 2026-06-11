import React, { useState, useContext } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ScrollView, Alert } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { CartContext } from '../context/CartContext';
import { AuthContext } from '../context/AuthContext';
import { mockAddresses } from '../data/mockData'; // On utilise le mock des adresses pour l'instant

type NavProp = NativeStackNavigationProp<any>;

export default function CheckoutScreen() {
  const navigation = useNavigation<NavProp>();
  const { items, getTotal, clearCart } = useContext(CartContext);
  const { isLoggedIn, user } = useContext(AuthContext);
  
  const [step, setStep] = useState(1);
  
  // Gestion des adresses
  const [useExistingAddress, setUseExistingAddress] = useState(false);
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null);
  
  // Formulaire d'adresse complet (Selon votre BDD : user_addresses)
  const [address, setAddress] = useState({
    prenom: user?.prenom || '',
    nom: user?.nom || '',
    adresse1: '',
    adresse2: '', // AJOUT
    ville: '',
    region: '', // AJOUT
    code_postal: '',
    pays: 'France',
    telephone: ''
  });

  const [card, setCard] = useState({ number: '4242 4242 4242 4242', exp: '12/28', cvv: '123', name: 'Jean Dupont' });

  const handleNext = () => {
    if (step === 1) {
      if (!isLoggedIn) {
        Alert.alert("Information", "Vous devez être connecté ou créer un compte pour finaliser la commande.");
        return;
      }
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

  const handlePay = () => {
    Alert.alert("Paiement accepté", "Votre commande a été validée !", [
      { text: "OK", onPress: () => { clearCart(); navigation.replace('OrderSuccess'); }}
    ]);
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
                <Text style={styles.userInfo}>Vous n'êtes pas connecté.</Text>
                <TouchableOpacity style={styles.linkButton} onPress={() => navigation.navigate('Login')}>
                  <Text style={styles.linkText}>Se connecter / Créer un compte</Text>
                </TouchableOpacity>
                <TouchableOpacity style={styles.guestButton} onPress={handleNext}>
                  <Text style={styles.guestButtonText}>Continuer en invité</Text>
                </TouchableOpacity>
              </View>
            )}
            <TouchableOpacity style={styles.mainButton} onPress={handleNext}><Text style={styles.mainButtonText}>Suivant</Text></TouchableOpacity>
          </View>
        )}

        {/* ÉTAPE 2 : ADRESSE */}
        {step === 2 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Adresse de facturation</Text>
            
            {/* Toggle pour choisir une adresse existante */}
            {isLoggedIn && (
              <TouchableOpacity style={styles.existingAddressToggle} onPress={() => setUseExistingAddress(!useExistingAddress)}>
                <Text style={styles.toggleText}>Utiliser une adresse existante</Text>
                <View style={[styles.switchTrack, useExistingAddress && styles.switchTrackActive]}>
                  <View style={[styles.switchThumb, useExistingAddress && styles.switchThumbActive]} />
                </View>
              </TouchableOpacity>
            )}

            {/* Liste des adresses existantes */}
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

            {/* Formulaire d'adresse complète */}
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
            <TouchableOpacity style={styles.mainButton} onPress={handleNext}><Text style={styles.mainButtonText}>Vérifier ma commande</Text></TouchableOpacity>
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
                  <Text style={styles.summaryItemName}>{item.name} ({item.cycle === 'monthly' ? 'Mensuel' : 'Annuel'})</Text>
                  <Text style={styles.summaryPrice}>{item.price.toFixed(2)} €</Text>
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
            <TouchableOpacity style={styles.payButton} onPress={handlePay}><Text style={styles.payButtonText}>Payer {getTotal().toFixed(2)} €</Text></TouchableOpacity>
          </View>
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
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
  guestButton: { backgroundColor: '#ecf0f1', padding: 15, borderRadius: 8, alignItems: 'center' },
  guestButtonText: { color: '#7f8c8d', fontSize: 15, fontWeight: '600' },
  row: { flexDirection: 'row' },
  input: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#dcdde1', borderRadius: 8, padding: 15, fontSize: 16, marginBottom: 15, color: '#2C3E50' },
  label: { fontSize: 14, fontWeight: '600', color: '#34495E', marginBottom: 8, marginTop: 10 },
  mainButton: { backgroundColor: '#0056b3', padding: 16, borderRadius: 10, alignItems: 'center', marginTop: 20 },
  mainButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
  
  // Nouveaux styles pour les adresses existantes
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
  payButton: { backgroundColor: '#27ae60', padding: 18, borderRadius: 10, alignItems: 'center', shadowColor: '#27ae60', shadowOpacity: 0.3, shadowOffset: {
      height: 4,
      width: 0
  }, shadowRadius: 8, elevation: 5 },
  payButtonText: { color: '#fff', fontSize: 18, fontWeight: 'bold' },
});