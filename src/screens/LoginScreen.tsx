import React, { useState, useContext } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator } from 'react-native';
import { AuthContext } from '../context/AuthContext';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

type NavProp = NativeStackNavigationProp<any>;

export default function LoginScreen() {
  const navigation = useNavigation<NavProp>();
  const { login, isLoading } = useContext(AuthContext);
  
  // État local pour savoir si le mot de passe est visible ou masqué
  const [showPassword, setShowPassword] = useState(false);
  
  // État local pour voir ce que l'utilisateur tape en temps réel (pour le débogage ou s'il s'est trompé)
  const [currentTypedPassword, setCurrentTypedPassword] = useState('');

  const [email, setEmail] = useState(''); // Laissez vide pour le test

  const handleLogin = async () => {
    if (!email || !currentTypedPassword) {
      Alert.alert('Erreur', 'Veuillez remplir tous les champs.');
      return;
    }
    
    const success = await login(email, currentTypedPassword);
    if (success) {
      if (navigation.canGoBack()) {
        navigation.goBack(); // Retourne là où l'utilisateur était avant de cliquer sur "Se connecter"
      } else {
        navigation.navigate('MainTabs', { screen: 'Account' }); // Sinon va sur l'espace client
      }
    }
  };

  return (
    <View style={styles.container}>
      <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
        <Text style={styles.backButtonText}>← Retour</Text>
      </TouchableOpacity>

      <Text style={styles.title}>Connexion</Text>
      <Text style={styles.subtitle}>Accédez à vos abonnements SaaS</Text>

      <View style={styles.inputContainer}>
        <Text style={styles.label}>Adresse e-mail</Text>
        <TextInput 
          style={styles.input} 
          value={email} 
          onChangeText={setEmail} 
          keyboardType="email-address" 
          autoCapitalize="none" 
          placeholder="votre@email.com"
          placeholderTextColor="#aaa"
        />
      </View>

      <View style={styles.inputContainer}>
        <Text style={styles.label}>Mot de passe</Text>
        <View style={styles.passwordRow}>
          <TextInput 
            style={[styles.input, { flex: 1 }]} 
            value={currentTypedPassword} 
            onChangeText={setCurrentTypedPassword} 
            secureTextEntry={!showPassword} // S'inverse la sécurité selon l'état du toggle
          />
          
          {/* Bouton Œil pour masquer/afficher le mot de passe */}
          <TouchableOpacity 
            style={styles.eyeButton} 
            onPress={() => setShowPassword(!showPassword)}
          >
            <Text style={styles.eyeIcon}>{showPassword ? '👁️' : '👁️‍🗨️'}</Text>
          </TouchableOpacity>
        </View>
      </View>

      <TouchableOpacity style={styles.forgotPassword} onPress={() => Alert.alert("Mot de passe oublié", "La fonctionnalité de réinitialisation sera bientôt disponible.")}>
        <Text style={styles.forgotPasswordText}>Mot de passe oublié ?</Text>
      </TouchableOpacity>

      {isLoading ? (
        <ActivityIndicator size="large" color="#0056b3" style={{ marginTop: 20 }} />
      ) : (
        <TouchableOpacity style={styles.button} onPress={handleLogin}>
          <Text style={styles.buttonText}>Se connecter</Text>
        </TouchableOpacity>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff', padding: 25, paddingTop: 60 },
  backButton: { marginBottom: 30 },
  backButtonText: { color: '#0056b3', fontSize: 16, fontWeight: '600' },
  title: { fontSize: 28, fontWeight: 'bold', color: '#2C3E50' },
  subtitle: { fontSize: 15, color: '#7f8c8d', marginTop: 5, marginBottom: 30 },
  inputContainer: { marginBottom: 20 },
  label: { fontSize: 14, fontWeight: '600', color: '#34495E', marginBottom: 8 },
  input: { borderWidth: 1, borderColor: '#dcdde1', borderRadius: 8, padding: 15, fontSize: 16, color: '#2C3E50' },
  passwordRow: { flexDirection: 'row', alignItems: 'center' },
  eyeButton: { 
    backgroundColor: '#f0f2f5', 
    width: 50, 
    height: 50, 
    borderRadius: 25, 
    justifyContent: 'center', 
    marginLeft: 10 
  },
  eyeIcon: { fontSize: 24 },
  forgotPassword: { alignSelf: 'flex-end', marginBottom: 20 },
  forgotPasswordText: { color: '#0056b3', fontSize: 14, fontWeight: '600' },
  button: { backgroundColor: '#0056b3', padding: 16, borderRadius: 10, alignItems: 'center', marginTop: 10, shadowColor: '#0056b3', shadowOpacity: 0.2, shadowOffset: {
      height: 4,
      width: 0
  }, shadowRadius: 8, elevation: 4 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
});