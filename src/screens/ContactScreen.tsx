import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ScrollView } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

type NavProp = NativeStackNavigationProp<any>;

export default function ContactScreen() {
  const navigation = useNavigation<NavProp>();
  
  const [email, setEmail] = useState('');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [showSubjects, setShowSubjects] = useState(false);
  const subjects = ['Problème technique', 'Question sur les abonnements', 'Assistance générale'];

  const handleSubmit = () => {
    if (!email || !subject || !message) {
      Alert.alert('Erreur', 'Veuillez remplir tous les champs.');
      return;
    }
    Alert.alert('Envoyé', 'Votre message a bien été transmis à notre équipe.');
    setMessage('');
  };

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}>
          <Text style={styles.backText}>✕</Text>
        </TouchableOpacity>
        <View style={{ flex: 1, alignItems: 'center' }}>
          <Text style={styles.headerTitle}>Besoin d'aide ?</Text>
        </View>
        <View style={{ width: 50 }} />
      </View>

      {/* Bouton d'accès rapide au Chatbot */}
      <TouchableOpacity 
        style={styles.chatButton} 
        onPress={() => navigation.navigate('Chatbot')}
      >
        <Ionicons name="chatbubbles-outline" size={24} color="#fff" />
        <View style={{ marginLeft: 15, flex: 1 }}>
          <Text style={styles.chatButtonText}>Parler à notre Chatbot</Text>
          <Text style={styles.chatButtonSubText}>Réponses instantanées 24/7</Text>
        </View>
        <Ionicons name="chevron-forward" size={20} color="rgba(255,255,255,0.8)" />
      </TouchableOpacity>

      <Text style={styles.formTitle}>Ou envoyez-nous un message</Text>

      <View style={styles.inputContainer}>
        <Text style={styles.label}>Adresse e-mail</Text>
        <TextInput 
          style={styles.input} 
          value={email} 
          onChangeText={setEmail} 
          keyboardType="email-address" 
          placeholder="votre@email.com"
          placeholderTextColor="#aaa"
        />
      </View>

      <View style={styles.inputContainer}>
        <Text style={styles.label}>Sujet du message</Text>
        <TouchableOpacity 
          style={styles.input} 
          onPress={() => setShowSubjects(!showSubjects)}
        >
          <Text style={[styles.textInput, !subject && { color: '#aaa' }]}>{subject || 'Choisissez un sujet'}</Text>
        </TouchableOpacity>
        
        {showSubjects && (
          <View style={styles.dropdown}>
            {subjects.map((s) => (
              <TouchableOpacity key={s} style={styles.dropdownItem} onPress={() => { setSubject(s); setShowSubjects(false); }}>
                <Text style={styles.dropdownText}>{s}</Text>
              </TouchableOpacity>
            ))}
          </View>
        )}
      </View>

      <View style={styles.inputContainer}>
        <Text style={styles.label}>Votre message</Text>
        <TextInput 
          style={[styles.input, { height: 120, textAlignVertical: 'top' }]} 
          value={message} 
          onChangeText={setMessage} 
          multiline={true}
          placeholder="Décrivez votre demande..."
          placeholderTextColor="#aaa"
        />
      </View>

      <TouchableOpacity style={styles.submitButton} onPress={handleSubmit}>
        <Text style={styles.submitButtonText}>Envoyer le message</Text>
      </TouchableOpacity>
      
      <View style={{ height: 40 }} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F5F7FA' },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 15, paddingVertical: 10, backgroundColor: '#fff', shadowColor: '#000', shadowOpacity: 0.05, shadowOffset: {
      height: 2,
      width: 0
  }, shadowRadius: 4, elevation: 2 },
  backText: { color: '#555', fontSize: 16, fontWeight: 'bold' },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#2C3E50' },
  chatButton: { backgroundColor: '#0056b3', margin: 20, padding: 20, borderRadius: 12, flexDirection: 'row', alignItems: 'center', shadowColor: '#0056b3', shadowOpacity: 0.2, shadowOffset: {
      height: 4,
      width: 0
  }, shadowRadius: 8, elevation: 4 },
  chatButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
  chatButtonSubText: { color: 'rgba(255,255,255,0.8)', fontSize: 12, marginTop: 4 },
  formTitle: { fontSize: 18, fontWeight: 'bold', marginHorizontal: 20, marginTop: 10, marginBottom: 20, color: '#2C3E50' },
  inputContainer: { marginHorizontal: 20, marginBottom: 20 },
  label: { fontSize: 14, fontWeight: '600', color: '#34495E', marginBottom: 8 },
  input: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#dcdde1', borderRadius: 8, padding: 15, fontSize: 16, color: '#2C3E50', justifyContent: 'center' },
  textInput: { fontSize: 16, color: '#2C3E50' },
  dropdown: { backgroundColor: '#fff', borderWidth: 1, borderTopWidth: 0, borderBottomLeftRadius: 8, borderBottomRightRadius: 8, overflow: 'hidden', borderColor: '#dcdde1' },
  dropdownItem: { padding: 15, borderBottomWidth: 1, borderColor: '#f0f0f0' },
  dropdownText: { fontSize: 16, color: '#2C3E50' },
  submitButton: { backgroundColor: '#2C3E50', marginHorizontal: 20, padding: 16, borderRadius: 10, alignItems: 'center', marginTop: 10 },
  submitButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
});