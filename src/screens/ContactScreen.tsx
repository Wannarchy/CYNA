import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ScrollView, KeyboardAvoidingView, Platform, ActivityIndicator } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { RootStackParamList } from '../navigation/AppNavigator';

type ContactScreenNavigationProp = NativeStackNavigationProp<RootStackParamList>;

export default function ContactScreen() {
  const navigation = useNavigation<ContactScreenNavigationProp>();
  
  const [email, setEmail] = useState('');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const subjects = ['Problème technique', 'Question sur les abonnements', 'Assistance générale'];

  const handleSubmit = async () => {
    if (!email || !subject || !message) {
      Alert.alert('Erreur', 'Veuillez remplir tous les champs.');
      return;
    }

    setIsSubmitting(true);
    // Simulation de l'envoi de l'email via Laravel (à remplacer par api.post('/contact', {...}) si tu as la route)
    await new Promise(resolve => setTimeout(resolve, 1500));
    
    Alert.alert('Envoyé', 'Votre message a bien été transmis à notre équipe.');
    setEmail('');
    setSubject('');
    setMessage('');
    setIsSubmitting(false);
  };

  return (
    <KeyboardAvoidingView 
      style={{ flex: 1, backgroundColor: '#F8FAFC' }} 
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView contentContainerStyle={{ flexGrow: 1 }} keyboardShouldPersistTaps="handled">
        
        {/* Header */}
        <View style={styles.header}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={24} color="#1E293B" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Besoin d'aide ?</Text>
          <View style={{ width: 24 }} />
        </View>

        {/* Bouton d'accès rapide au Chatbot */}
        <TouchableOpacity 
          style={styles.chatButton} 
          onPress={() => navigation.navigate('Chatbot')}
          activeOpacity={0.9}
        >
          <View style={styles.chatIconBg}>
            <Ionicons name="chatbubbles" size={28} color="#fff" />
          </View>
          <View style={{ marginLeft: 15, flex: 1 }}>
            <Text style={styles.chatButtonText}>Parler à l'Assistant CYNA</Text>
            <Text style={styles.chatButtonSubText}>Réponses instantanées 24/7</Text>
          </View>
          <Ionicons name="chevron-forward" size={22} color="#cbd5e1" />
        </TouchableOpacity>

        <Text style={styles.formTitle}>Ou envoyez-nous un message</Text>

        <View style={styles.formContainer}>
          {/* Email */}
          <View style={styles.inputWrapper}>
            <Ionicons name="mail-outline" size={20} color="#94a3b8" style={styles.inputIcon} />
            <TextInput 
              style={styles.input} 
              value={email} 
              onChangeText={setEmail} 
              keyboardType="email-address" 
              placeholder="Votre adresse e-mail"
              placeholderTextColor="#94a3b8"
              autoCapitalize="none"
            />
          </View>

          {/* Sujets (Pastilles) */}
          <Text style={styles.label}>Sujet du message</Text>
          <View style={styles.chipsContainer}>
            {subjects.map((s) => (
              <TouchableOpacity 
                key={s} 
                style={[styles.chip, subject === s && styles.chipActive]} 
                onPress={() => setSubject(s)}
              >
                <Text style={[styles.chipText, subject === s && styles.chipTextActive]}>{s}</Text>
              </TouchableOpacity>
            ))}
          </View>

          {/* Message */}
          <View style={[styles.inputWrapper, { alignItems: 'flex-start', paddingVertical: 15 }]}>
            <Ionicons name="create-outline" size={20} color="#94a3b8" style={[styles.inputIcon, { marginTop: 3 }]} />
            <TextInput 
              style={[styles.input, { height: 120 }]} 
              value={message} 
              onChangeText={setMessage} 
              multiline={true}
              textAlignVertical="top"
              placeholder="Décrivez votre demande..."
              placeholderTextColor="#94a3b8"
            />
          </View>

          {/* Bouton Envoyer */}
          <TouchableOpacity style={styles.submitButton} onPress={handleSubmit} disabled={isSubmitting}>
            {isSubmitting ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <>
                <Ionicons name="send-outline" size={18} color="#fff" style={{ marginRight: 8 }} />
                <Text style={styles.submitButtonText}>Envoyer le message</Text>
              </>
            )}
          </TouchableOpacity>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 20, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  backBtn: { padding: 5 },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B' },

  // Chatbot CTA
  chatButton: { 
    backgroundColor: '#fff', 
    margin: 20, 
    padding: 20, 
    borderRadius: 16, 
    flexDirection: 'row', 
    alignItems: 'center', 
    shadowColor: '#000', 
    shadowOpacity: 0.03, 
    shadowRadius: 10, 
    elevation: 2,
    borderWidth: 1,
    borderColor: '#F1F5F9'
  },
  chatIconBg: { width: 50, height: 50, borderRadius: 25, backgroundColor: '#0056b3', justifyContent: 'center', alignItems: 'center' },
  chatButtonText: { color: '#1E293B', fontSize: 16, fontWeight: 'bold' },
  chatButtonSubText: { color: '#64748B', fontSize: 13, marginTop: 4 },

  // Form
  formTitle: { fontSize: 16, fontWeight: 'bold', color: '#64748B', marginHorizontal: 20, marginTop: 10, marginBottom: 15, textTransform: 'uppercase', letterSpacing: 0.5 },
  formContainer: { paddingHorizontal: 20, paddingBottom: 40 },
  label: { fontSize: 14, fontWeight: '600', color: '#334155', marginBottom: 10, marginTop: 10 },
  
  inputWrapper: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 12, marginBottom: 20, paddingHorizontal: 15, height: 55 },
  inputIcon: { marginRight: 10 },
  input: { flex: 1, color: '#1E293B', fontSize: 15 },

  // Chips (Pastilles de sujet)
  chipsContainer: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 20 },
  chip: { paddingHorizontal: 15, paddingVertical: 10, borderRadius: 20, backgroundColor: '#fff', borderWidth: 1, borderColor: '#E2E8F0' },
  chipActive: { backgroundColor: '#EFF6FF', borderColor: '#0056b3' },
  chipText: { fontSize: 13, color: '#64748b', fontWeight: '600' },
  chipTextActive: { color: '#0056b3' },

  // Submit
  submitButton: { 
    backgroundColor: '#1E293B', 
    padding: 18, 
    borderRadius: 12, 
    alignItems: 'center', 
    marginTop: 10, 
    flexDirection: 'row', 
    justifyContent: 'center',
    shadowColor: '#1E293B', 
    shadowOpacity: 0.2, 
    shadowOffset: { height: 4, width: 0 }, 
    shadowRadius: 8, 
    elevation: 4 
  },
  submitButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' }
});