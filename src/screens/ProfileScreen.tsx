import React, { useState, useContext } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ScrollView } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { AuthContext } from '../context/AuthContext';

type NavProp = NativeStackNavigationProp<any>;

export default function ProfileScreen() {
  const navigation = useNavigation<NavProp>();
  const { user } = useContext(AuthContext);

  // Infos personnelles
  const [prenom, setPrenom] = useState(user?.prenom || '');
  const [nom, setNom] = useState(user?.nom || '');
  const [email, setEmail] = useState(user?.email || '');

  // Mot de passe
  const [oldPassword, setOldPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmNewPassword, setConfirmNewPassword] = useState('');

  const handleSaveInfos = () => {
    if (!prenom || !nom || !email) {
      Alert.alert('Erreur', 'Tous les champs sont obligatoires.');
      return;
    }
    // Simulation API PUT /api/user (ou similaire selon votre Laravel)
    Alert.alert('Succès', 'Vos informations personnelles ont été mises à jour.');
  };

  const handleChangePassword = () => {
    if (!oldPassword || !newPassword || !confirmNewPassword) {
      Alert.alert('Erreur', 'Veuillez remplir tous les champs de mot de passe.');
      return;
    }
    if (newPassword !== confirmNewPassword) {
      Alert.alert('Erreur', 'Les nouveaux mots de passe ne correspondent pas.');
      return;
    }
    if (newPassword.length < 8) {
      Alert.alert('Erreur', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
      return;
    }
    // Simulation API
    Alert.alert('Succès', 'Votre mot de passe a été changé avec succès.');
    setOldPassword('');
    setNewPassword('');
    setConfirmNewPassword('');
  };

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}>
          <Text style={styles.backText}>← Retour</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Modifier mon profil</Text>
        <View style={{ width: 50 }} />
      </View>

      {/* SECTION 1 : Informations personnelles */}
      <Text style={styles.sectionTitle}>Informations personnelles</Text>
      <View style={styles.card}>
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
          <Text style={styles.hint}>Un e-mail de validation sera envoyé pour confirmer ce changement.</Text>
        </View>
        <TouchableOpacity style={styles.mainButton} onPress={handleSaveInfos}>
          <Text style={styles.mainButtonText}>Sauvegarder les informations</Text>
        </TouchableOpacity>
      </View>

      {/* SECTION 2 : Mot de passe */}
      <Text style={styles.sectionTitle}>Changer le mot de passe</Text>
      <View style={styles.card}>
        <View style={styles.inputContainer}>
          <Text style={styles.label}>Ancien mot de passe</Text>
          <TextInput style={styles.input} value={oldPassword} onChangeText={setOldPassword} secureTextEntry placeholder="Saisissez votre mot de passe actuel" placeholderTextColor="#aaa" />
        </View>
        <View style={styles.inputContainer}>
          <Text style={styles.label}>Nouveau mot de passe</Text>
          <TextInput style={styles.input} value={newPassword} onChangeText={setNewPassword} secureTextEntry placeholder="Min. 8 caractères" placeholderTextColor="#aaa" />
        </View>
        <View style={styles.inputContainer}>
          <Text style={styles.label}>Confirmer le nouveau mot de passe</Text>
          <TextInput style={styles.input} value={confirmNewPassword} onChangeText={setConfirmNewPassword} secureTextEntry />
        </View>
        <TouchableOpacity style={styles.secondaryButton} onPress={handleChangePassword}>
          <Text style={styles.secondaryButtonText}>Mettre à jour le mot de passe</Text>
        </TouchableOpacity>
      </View>

      <View style={{ height: 50 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F5F7FA' },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 20, backgroundColor: '#fff' },
  backText: { color: '#0056b3', fontSize: 16, fontWeight: '600' },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#2C3E50' },
  sectionTitle: { fontSize: 18, fontWeight: 'bold', color: '#2C3E50', marginLeft: 20, marginTop: 25, marginBottom: 10 },
  card: { backgroundColor: '#fff', marginHorizontal: 20, borderRadius: 12, padding: 20, shadowColor: '#000', shadowOpacity: 0.03, shadowRadius: 4, elevation: 2 },
  row: { flexDirection: 'row' },
  inputContainer: { marginBottom: 20 },
  label: { fontSize: 14, fontWeight: '600', color: '#34495E', marginBottom: 8 },
  input: { borderWidth: 1, borderColor: '#dcdde1', borderRadius: 8, padding: 15, fontSize: 16, color: '#2C3E50' },
  hint: { fontSize: 12, color: '#7f8c8d', marginTop: 6 },
  mainButton: { backgroundColor: '#0056b3', padding: 16, borderRadius: 10, alignItems: 'center', marginTop: 10 },
  mainButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
  secondaryButton: { backgroundColor: '#2C3E50', padding: 16, borderRadius: 10, alignItems: 'center', marginTop: 10 },
  secondaryButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
});