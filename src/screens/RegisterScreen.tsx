import React, { useState, useContext } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator } from 'react-native';
import { AuthContext } from '../context/AuthContext';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

type NavProp = NativeStackNavigationProp<any>;

export default function RegisterScreen() {
  const [prenom, setPrenom] = useState('');
  const [nom, setNom] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const { register, isLoading } = useContext(AuthContext);
  
  // AJOUT : On initialise la navigation
  const navigation = useNavigation<NavProp>();

  const handleRegister = async () => {
    if (!prenom || !nom || !email || !password) {
      Alert.alert('Erreur', 'Tous les champs sont obligatoires.');
      return;
    }
    if (password !== confirmPassword) {
      Alert.alert('Erreur', 'Les mots de passe ne correspondent pas.');
      return;
    }
    
    const success = await register(prenom, nom, email, password);
    if (success) {
      // Utilise replace pour empêcher l'utilisateur de revenir en arrière avec le bouton retour
      if (navigation.canGoBack()) {
        navigation.replace('Login');
      }
    }
  };

  return (
    <View style={styles.container}>
        <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
        <Text style={styles.backButtonText}>← Retour</Text>
      </TouchableOpacity>

      <Text style={styles.title}>Inscription</Text>
      <Text style={styles.subtitle}>Créez votre espace client CYNA</Text>

      <View style={styles.row}>
        <View style={[styles.inputContainer, { flex: 1, marginRight: 10 }]}>
          <Text style={styles.label}>Prénom</Text>
          <TextInput style={styles.input} value={prenom} onChangeText={setPrenom} />
        </View>
        <View style={[styles.inputContainer, { flex: 1 }]}>
          <Text style={styles.label}>Nom</Text>
          <TextInput style={styles.input} value={nom} onChangeText={setNom} />
        </View>
      </View>

      <View style={styles.inputContainer}>
        <Text style={styles.label}>Adresse e-mail</Text>
        <TextInput style={styles.input} value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" />
      </View>

      <View style={styles.inputContainer}>
        <Text style={styles.label}>Mot de passe</Text>
        <TextInput style={styles.input} value={password} onChangeText={setPassword} secureTextEntry placeholder="Min. 8 caractères" placeholderTextColor="#aaa" />
      </View>

      <View style={styles.inputContainer}>
        <Text style={styles.label}>Confirmer le mot de passe</Text>
        <TextInput style={styles.input} value={confirmPassword} onChangeText={setConfirmPassword} secureTextEntry />
      </View>

      {isLoading ? (
        <ActivityIndicator size="large" color="#0056b3" style={{ marginTop: 20 }} />
      ) : (
        <TouchableOpacity style={styles.button} onPress={handleRegister}>
          <Text style={styles.buttonText}>Créer mon compte</Text>
        </TouchableOpacity>
      )}

      <View style={styles.footer}>
        <Text style={styles.footerText}>Déjà un compte ?</Text>
        {/* CORRECTION ICI : Navigation vers la page Login */}
        <TouchableOpacity onPress={() => navigation.replace('Login')}>
          <Text style={styles.footerLink}>Se connecter</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff', padding: 25, paddingTop: 60 },
  backButton: { marginBottom: 20 },
  backButtonText: { color: '#0056b3', fontSize: 16, fontWeight: '600' },
  title: { fontSize: 28, fontWeight: 'bold', color: '#2C3E50' },
  subtitle: { fontSize: 15, color: '#7f8c8d', marginTop: 5, marginBottom: 30 },
  row: { flexDirection: 'row' },
  inputContainer: { marginBottom: 20 },
  label: { fontSize: 14, fontWeight: '600', color: '#34495E', marginBottom: 8 },
  input: { borderWidth: 1, borderColor: '#dcdde1', borderRadius: 8, padding: 15, fontSize: 16, color: '#2C3E50' },
  button: { backgroundColor: '#0056b3', padding: 16, borderRadius: 10, alignItems: 'center', marginTop: 10, shadowColor: '#0056b3', shadowOpacity: 0.2, shadowOffset: {
      height: 4,
      width: 0
  }, shadowRadius: 8, elevation: 4 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
  footer: { flexDirection: 'row', justifyContent: 'center', marginTop: 30 },
  footerText: { color: '#7f8c8d', marginRight: 5 },
  footerLink: { color: '#0056b3', fontWeight: 'bold' },
});