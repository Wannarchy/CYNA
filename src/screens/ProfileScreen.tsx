import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ScrollView, ActivityIndicator } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';

// Import du hook sécurisé et des types
import { useAuth, ProfileUpdateData } from '../context/AuthContext';
import { RootStackParamList } from '../navigation/AppNavigator';

type ProfileScreenNavigationProp = NativeStackNavigationProp<RootStackParamList>;

export default function ProfileScreen() {
  const navigation = useNavigation<ProfileScreenNavigationProp>();
  const { user, updateProfile } = useAuth(); // La vraie fonction de mise à jour

  // États pour les infos personnelles
  const [prenom, setPrenom] = useState(user?.prenom || '');
  const [nom, setNom] = useState(user?.nom || '');
  const [email, setEmail] = useState(user?.email || '');

  // États pour le mot de passe
  const [oldPassword, setOldPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmNewPassword, setConfirmNewPassword] = useState('');

  // États de chargement pour les boutons
  const [isSavingInfos, setIsSavingInfos] = useState(false);
  const [isSavingPassword, setIsSavingPassword] = useState(false);

  const handleSaveInfos = async () => {
    if (!prenom || !nom || !email) {
      Alert.alert('Erreur', 'Tous les champs sont obligatoires.');
      return;
    }
    setIsSavingInfos(true);
    try {
      const data: ProfileUpdateData = { prenom, nom, email };
      const success = await updateProfile(data);
      if (success) {
        Alert.alert('Succès', 'Vos informations personnelles ont été mises à jour.');
      }
    } catch (error) {
      Alert.alert('Erreur', "Une erreur est survenue lors de la mise à jour.");
    } finally {
      setIsSavingInfos(false);
    }
  };

  const handleChangePassword = async () => {
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

    setIsSavingPassword(true);
    try {
      const data: ProfileUpdateData = {
        current_password: oldPassword,
        password: newPassword,
        password_confirmation: confirmNewPassword,
      };
      const success = await updateProfile(data);
      if (success) {
        Alert.alert('Succès', 'Votre mot de passe a été changé avec succès.');
        setOldPassword('');
        setNewPassword('');
        setConfirmNewPassword('');
      }
    } catch (error) {
      Alert.alert('Erreur', "Une erreur est survenue lors du changement de mot de passe.");
    } finally {
      setIsSavingPassword(false);
    }
  };

  // Initiales pour l'avatar
  const userInitials = `${prenom?.[0] || ''}${nom?.[0] || ''}`.toUpperCase();

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color="#1E293B" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Mon Profil</Text>
        <View style={{ width: 24 }} />
      </View>

      {/* AVATAR */}
      <View style={styles.avatarContainer}>
        <View style={styles.avatarCircle}>
          <Text style={styles.avatarText}>{userInitials}</Text>
        </View>
        <Text style={styles.userName}>{prenom} {nom}</Text>
        <Text style={styles.userEmail}>{email}</Text>
      </View>

      {/* SECTION 1 : INFORMATIONS PERSONNELLES */}
      <Text style={styles.sectionTitle}>Informations personnelles</Text>
      <View style={styles.card}>
        <View style={styles.row}>
          {/* Input Prénom */}
          <View style={[styles.inputWrapper, { flex: 1, marginRight: 10 }]}>
            <Ionicons name="person-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
            <TextInput style={styles.input} value={prenom} onChangeText={setPrenom} placeholder="Prénom" placeholderTextColor="#94a3b8" />
          </View>
          {/* Input Nom */}
          <View style={[styles.inputWrapper, { flex: 1 }]}>
            <Ionicons name="person-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
            <TextInput style={styles.input} value={nom} onChangeText={setNom} placeholder="Nom" placeholderTextColor="#94a3b8" />
          </View>
        </View>
        
        {/* Input Email */}
        <View style={styles.inputWrapper}>
          <Ionicons name="mail-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
          <TextInput 
            style={styles.input} 
            value={email} 
            onChangeText={setEmail} 
            keyboardType="email-address" 
            autoCapitalize="none"
            placeholder="Adresse e-mail"
            placeholderTextColor="#94a3b8"
          />
        </View>
        <Text style={styles.hint}>Un e-mail de validation sera envoyé si vous changez votre adresse actuelle.</Text>

        <TouchableOpacity style={styles.primaryButton} onPress={handleSaveInfos} disabled={isSavingInfos}>
          {isSavingInfos ? (
            <ActivityIndicator color="#fff" size="small" />
          ) : (
            <Text style={styles.primaryButtonText}>Sauvegarder</Text>
          )}
        </TouchableOpacity>
      </View>

      {/* SECTION 2 : MOT DE PASSE */}
      <Text style={styles.sectionTitle}>Sécurité du compte</Text>
      <View style={styles.card}>
        <View style={styles.inputWrapper}>
          <Ionicons name="lock-closed-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
          <TextInput style={styles.input} value={oldPassword} onChangeText={setOldPassword} secureTextEntry placeholder="Mot de passe actuel" placeholderTextColor="#94a3b8" />
        </View>
        <View style={styles.inputWrapper}>
          <Ionicons name="key-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
          <TextInput style={styles.input} value={newPassword} onChangeText={setNewPassword} secureTextEntry placeholder="Nouveau mot de passe" placeholderTextColor="#94a3b8" />
        </View>
        <View style={styles.inputWrapper}>
          <Ionicons name="shield-checkmark-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
          <TextInput style={styles.input} value={confirmNewPassword} onChangeText={setConfirmNewPassword} secureTextEntry placeholder="Confirmer le mot de passe" placeholderTextColor="#94a3b8" />
        </View>

        <TouchableOpacity style={styles.secondaryButton} onPress={handleChangePassword} disabled={isSavingPassword}>
          {isSavingPassword ? (
            <ActivityIndicator color="#fff" size="small" />
          ) : (
            <Text style={styles.secondaryButtonText}>Mettre à jour le mot de passe</Text>
          )}
        </TouchableOpacity>
      </View>

      <View style={{ height: 50 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  
  // Header
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 20, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  backBtn: { padding: 5 },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B' },

  // Avatar
  avatarContainer: { alignItems: 'center', paddingVertical: 30, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  avatarCircle: { width: 80, height: 80, borderRadius: 40, backgroundColor: '#0056b3', justifyContent: 'center', alignItems: 'center', marginBottom: 12 },
  avatarText: { color: '#fff', fontSize: 28, fontWeight: 'bold' },
  userName: { fontSize: 20, fontWeight: 'bold', color: '#1E293B' },
  userEmail: { fontSize: 14, color: '#64748B', marginTop: 4 },

  // Sections
  sectionTitle: { fontSize: 16, fontWeight: 'bold', color: '#64748B', marginLeft: 20, marginTop: 25, marginBottom: 15, textTransform: 'uppercase', letterSpacing: 0.5 },
  card: { backgroundColor: '#fff', marginHorizontal: 20, borderRadius: 16, padding: 20, shadowColor: '#000', shadowOpacity: 0.03, shadowRadius: 15, elevation: 2 },
  row: { flexDirection: 'row' },

  // Inputs
  inputWrapper: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#F8FAFC', borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 12, marginBottom: 15, paddingHorizontal: 15 },
  inputIcon: { marginRight: 10 },
  input: { flex: 1, paddingVertical: 15, fontSize: 15, color: '#1E293B' },
  hint: { fontSize: 12, color: '#94a3b8', marginTop: -5, marginBottom: 15, lineHeight: 16 },

  // Buttons
  primaryButton: { backgroundColor: '#0056b3', padding: 16, borderRadius: 12, alignItems: 'center', marginTop: 5 },
  primaryButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
  secondaryButton: { backgroundColor: '#1E293B', padding: 16, borderRadius: 12, alignItems: 'center', marginTop: 5 },
  secondaryButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
});