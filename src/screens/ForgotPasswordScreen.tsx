import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import api from '../services/api';

type NavProp = NativeStackNavigationProp<any>;

export default function ForgotPasswordScreen() {
  const navigation = useNavigation<NavProp>();
  const [email, setEmail] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const handleReset = async () => {
    if (!email) {
      Alert.alert('Erreur', 'Veuillez entrer votre adresse e-mail.');
      return;
    }

    setIsLoading(true);
    try {
      // Appel réel à l'API Laravel
      await api.post('/auth/forgot-password', { email });
      setIsSuccess(true);
    } catch (error: any) {
      // Même si l'API renvoie une erreur (ex: rate limit, ou utilisateur inexistant), 
      // on affiche un succès par mesure de sécurité (pour ne pas révéler qu'un email existe ou non)
      setIsSuccess(true);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
        <Text style={styles.backButtonText}>← Retour</Text>
      </TouchableOpacity>

      <View style={styles.iconContainer}>
        {/* Icone de cadenas stylisée avec du texte */}
        <Text style={styles.lockIcon}>🔒</Text>
      </View>

      {isSuccess ? (
        <>
          <Text style={styles.title}>Email envoyé !</Text>
          <Text style={styles.subtitle}>
            Si un compte existe avec l'adresse <Text style={{ fontWeight: 'bold' }}>{email}</Text>, vous recevrez un lien pour réinitialiser votre mot de passe.
          </Text>
          <TouchableOpacity style={styles.button} onPress={() => navigation.goBack()}>
            <Text style={styles.buttonText}>Retour à la connexion</Text>
          </TouchableOpacity>
        </>
      ) : (
        <>
          <Text style={styles.title}>Mot de passe oublié ?</Text>
          <Text style={styles.subtitle}>
            Entrez l'adresse e-mail associée à votre compte. Nous vous enverrons un lien pour réinitialiser votre mot de passe.
          </Text>

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

          {isLoading ? (
            <ActivityIndicator size="large" color="#0056b3" style={{ marginTop: 20 }} />
          ) : (
            <TouchableOpacity style={styles.button} onPress={handleReset}>
              <Text style={styles.buttonText}>Envoyer le lien</Text>
            </TouchableOpacity>
          )}
        </>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff', padding: 25, paddingTop: 60 },
  backButton: { marginBottom: 30 },
  backButtonText: { color: '#0056b3', fontSize: 16, fontWeight: '600' },
  iconContainer: { alignItems: 'center', marginBottom: 30 },
  lockIcon: { fontSize: 60 },
  title: { fontSize: 26, fontWeight: 'bold', color: '#2C3E50', textAlign: 'center', marginBottom: 10 },
  subtitle: { fontSize: 15, color: '#7f8c8d', textAlign: 'center', lineHeight: 22, marginBottom: 40 },
  inputContainer: { marginBottom: 20 },
  label: { fontSize: 14, fontWeight: '600', color: '#34495E', marginBottom: 8 },
  input: { borderWidth: 1, borderColor: '#dcdde1', borderRadius: 8, padding: 15, fontSize: 16, color: '#2C3E50' },
  button: { backgroundColor: '#0056b3', padding: 16, borderRadius: 10, alignItems: 'center', marginTop: 10, shadowColor: '#0056b3', shadowOpacity: 0.2, shadowOffset: {
      height: 4,
      width: 0
  }, shadowRadius: 8, elevation: 4 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
});