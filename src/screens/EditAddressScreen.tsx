import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, ScrollView } from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import type { NativeStackNavigationProp, NativeStackScreenProps } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../services/api';
import { RootStackParamList } from '../navigation/AppNavigator';

type EditAddressRouteProp = NativeStackScreenProps<RootStackParamList, 'EditAddress'>['route'];
type EditAddressNavigationProp = NativeStackScreenProps<RootStackParamList, 'EditAddress'>['navigation'];

export default function EditAddressScreen() {
  const route = useRoute<EditAddressRouteProp>();
  const navigation = useNavigation<EditAddressNavigationProp>();
  const queryClient = useQueryClient();
  
  const existingAddress = route.params?.address;

  const [form, setForm] = useState({
    label: existingAddress?.label || '',
    prenom: existingAddress?.prenom || '',
    nom: existingAddress?.nom || '',
    adresse1: existingAddress?.adresse1 || '',
    code_postal: existingAddress?.code_postal || '',
    ville: existingAddress?.ville || '',
    pays: existingAddress?.pays || 'France',
  });

  const mutation = useMutation({
    mutationFn: async (data: typeof form) => {
      if (existingAddress) {
        // Modifier
        await api.put(`/addresses/${existingAddress.id}`, data);
      } else {
        // Ajouter
        await api.post('/addresses', data);
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['addresses'] });
      Alert.alert('Succès', existingAddress ? 'Adresse modifiée.' : 'Adresse ajoutée.');
      navigation.goBack();
    },
    onError: () => Alert.alert('Erreur', 'Une erreur est survenue.')
  });

  const handleSave = () => {
    if (!form.label || !form.prenom || !form.adresse1 || !form.ville || !form.code_postal) {
      Alert.alert('Erreur', 'Veuillez remplir les champs obligatoires.');
      return;
    }
    mutation.mutate(form);
  };

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color="#1E293B" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>{existingAddress ? 'Modifier l\'adresse' : 'Nouvelle adresse'}</Text>
        <View style={{ width: 24 }} />
      </View>

      <View style={styles.formContainer}>
        <View style={styles.inputWrapper}>
          <Ionicons name="bookmark-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
          <TextInput style={styles.input} placeholder="Titre (Ex: Domicile, Travail) *" value={form.label} onChangeText={(t) => setForm({ ...form, label: t })} />
        </View>

        <View style={styles.row}>
          <View style={[styles.inputWrapper, { flex: 1, marginRight: 10 }]}>
            <Ionicons name="person-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
            <TextInput style={styles.input} placeholder="Prénom *" value={form.prenom} onChangeText={(t) => setForm({ ...form, prenom: t })} />
          </View>
          <View style={[styles.inputWrapper, { flex: 1 }]}>
            <Ionicons name="person-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
            <TextInput style={styles.input} placeholder="Nom *" value={form.nom} onChangeText={(t) => setForm({ ...form, nom: t })} />
          </View>
        </View>

        <View style={styles.inputWrapper}>
          <Ionicons name="location-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
          <TextInput style={styles.input} placeholder="Adresse * (Rue, numéro)" value={form.adresse1} onChangeText={(t) => setForm({ ...form, adresse1: t })} />
        </View>

        <View style={styles.row}>
          <View style={[styles.inputWrapper, { flex: 2, marginRight: 10 }]}>
            <Ionicons name="business-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
            <TextInput style={styles.input} placeholder="Ville *" value={form.ville} onChangeText={(t) => setForm({ ...form, ville: t })} />
          </View>
          <View style={[styles.inputWrapper, { flex: 1, marginRight: 10 }]}>
            <Ionicons name="mail-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
            <TextInput style={styles.input} placeholder="CP *" value={form.code_postal} onChangeText={(t) => setForm({ ...form, code_postal: t })} keyboardType="numeric" />
          </View>
        </View>

        <View style={styles.inputWrapper}>
          <Ionicons name="flag-outline" size={18} color="#94a3b8" style={styles.inputIcon} />
          <TextInput style={styles.input} placeholder="Pays" value={form.pays} onChangeText={(t) => setForm({ ...form, pays: t })} />
        </View>

        <TouchableOpacity style={styles.saveButton} onPress={handleSave} disabled={mutation.isPending}>
          {mutation.isPending ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.saveButtonText}>Sauvegarder l'adresse</Text>
          )}
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 20, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  backBtn: { padding: 5 },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B' },
  formContainer: { padding: 20 },
  row: { flexDirection: 'row' },
  inputWrapper: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 12, marginBottom: 15, paddingHorizontal: 15 },
  inputIcon: { marginRight: 10 },
  input: { flex: 1, paddingVertical: 15, fontSize: 15, color: '#1E293B' },
  saveButton: { backgroundColor: '#0056b3', padding: 18, borderRadius: 12, alignItems: 'center', marginTop: 10, shadowColor: '#0056b3', shadowOpacity: 0.3, shadowOffset: { height: 4, width: 0 }, shadowRadius: 8, elevation: 5 },
  saveButtonText: { color: '#fff', fontSize: 16, fontWeight: 'bold' }
});