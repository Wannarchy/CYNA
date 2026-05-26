import React, { useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  KeyboardAvoidingView, Platform, ScrollView, ActivityIndicator,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import useAuthStore from '../../store/authStore';

export default function RegisterScreen({ navigation }) {
  const { register, isLoading, error, clearError } = useAuthStore();
  const [form, setForm] = useState({ prenom: '', nom: '', email: '', mot_de_passe: '', confirmation_mot_de_passe: '' });
  const [showPwd, setShowPwd]       = useState(false);
  const [fieldErrors, setFieldErrors] = useState({});
  const [success, setSuccess]       = useState(false);

  const update = (k, v) => { setForm((p) => ({ ...p, [k]: v })); setFieldErrors((p) => ({ ...p, [k]: null })); clearError(); };

  const validate = () => {
    const e = {};
    if (!form.prenom.trim()) e.prenom = 'Prénom requis.';
    if (!form.nom.trim()) e.nom = 'Nom requis.';
    if (!form.email.trim()) e.email = 'Email requis.';
    else if (!/\S+@\S+\.\S+/.test(form.email)) e.email = 'Email invalide.';
    if (!form.mot_de_passe) e.mot_de_passe = 'Mot de passe requis.';
    else if (form.mot_de_passe.length < 8) e.mot_de_passe = 'Minimum 8 caractères.';
    if (form.mot_de_passe !== form.confirmation_mot_de_passe) e.confirmation_mot_de_passe = 'Les mots de passe ne correspondent pas.';
    setFieldErrors(e);
    return !Object.keys(e).length;
  };

  const handleRegister = async () => {
    clearError();
    if (!validate()) return;
    try { await register({ ...form, email: form.email.trim().toLowerCase() }); setSuccess(true); }
    catch {}
  };

  if (success) return (
    <View style={styles.successContainer}>
      <View style={styles.successIcon}><Ionicons name="mail-open-outline" size={48} color="#4F46E5" /></View>
      <Text style={styles.successTitle}>Vérifiez votre email</Text>
      <Text style={styles.successBody}>Un lien de confirmation a été envoyé à{'\n'}<Text style={{ fontWeight: '600' }}>{form.email}</Text></Text>
      <TouchableOpacity style={styles.btn} onPress={() => navigation.navigate('Login')}>
        <Text style={styles.btnText}>Retour à la connexion</Text>
      </TouchableOpacity>
    </View>
  );

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <TouchableOpacity style={styles.back} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={22} color="#374151" />
        </TouchableOpacity>
        <Text style={styles.title}>Créer un compte</Text>
        <Text style={styles.subtitle}>Rejoignez CYNA et sécurisez votre entreprise</Text>

        {error ? <View style={styles.errorBanner}><Ionicons name="alert-circle-outline" size={16} color="#DC2626" /><Text style={styles.errorBannerText}>{error}</Text></View> : null}

        <View style={styles.rowFields}>
          {[['prenom', 'Prénom', 'Jean'], ['nom', 'Nom', 'Dupont']].map(([key, label, ph]) => (
            <View key={key} style={[styles.fieldGroup, { flex: 1, marginRight: key === 'prenom' ? 8 : 0 }]}>
              <Text style={styles.label}>{label}</Text>
              <View style={[styles.inputRow, fieldErrors[key] && styles.inputError]}>
                <TextInput style={styles.input} placeholder={ph} placeholderTextColor="#9CA3AF"
                  value={form[key]} onChangeText={(v) => update(key, v)} autoCapitalize="words" />
              </View>
              {fieldErrors[key] ? <Text style={styles.fieldError}>{fieldErrors[key]}</Text> : null}
            </View>
          ))}
        </View>

        {[
          ['email', 'Email professionnel', 'jean@entreprise.com', 'email-address', 'none'],
        ].map(([key, label, ph, kb, cap]) => (
          <View key={key} style={styles.fieldGroup}>
            <Text style={styles.label}>{label}</Text>
            <View style={[styles.inputRow, fieldErrors[key] && styles.inputError]}>
              <Ionicons name="mail-outline" size={18} color="#9CA3AF" style={styles.icon} />
              <TextInput style={styles.input} placeholder={ph} placeholderTextColor="#9CA3AF"
                value={form[key]} onChangeText={(v) => update(key, v)} keyboardType={kb} autoCapitalize={cap} />
            </View>
            {fieldErrors[key] ? <Text style={styles.fieldError}>{fieldErrors[key]}</Text> : null}
          </View>
        ))}

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Mot de passe</Text>
          <View style={[styles.inputRow, fieldErrors.mot_de_passe && styles.inputError]}>
            <Ionicons name="lock-closed-outline" size={18} color="#9CA3AF" style={styles.icon} />
            <TextInput style={styles.input} placeholder="Minimum 8 caractères" placeholderTextColor="#9CA3AF"
              value={form.mot_de_passe} onChangeText={(v) => update('mot_de_passe', v)} secureTextEntry={!showPwd} />
            <TouchableOpacity onPress={() => setShowPwd(!showPwd)}>
              <Ionicons name={showPwd ? 'eye-off-outline' : 'eye-outline'} size={18} color="#9CA3AF" />
            </TouchableOpacity>
          </View>
          {fieldErrors.mot_de_passe ? <Text style={styles.fieldError}>{fieldErrors.mot_de_passe}</Text> : null}
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Confirmer le mot de passe</Text>
          <View style={[styles.inputRow, fieldErrors.confirmation_mot_de_passe && styles.inputError]}>
            <Ionicons name="lock-closed-outline" size={18} color="#9CA3AF" style={styles.icon} />
            <TextInput style={styles.input} placeholder="••••••••" placeholderTextColor="#9CA3AF"
              value={form.confirmation_mot_de_passe} onChangeText={(v) => update('confirmation_mot_de_passe', v)} secureTextEntry={!showPwd} />
          </View>
          {fieldErrors.confirmation_mot_de_passe ? <Text style={styles.fieldError}>{fieldErrors.confirmation_mot_de_passe}</Text> : null}
        </View>

        <TouchableOpacity style={[styles.btn, isLoading && styles.btnDisabled]} onPress={handleRegister} disabled={isLoading}>
          {isLoading ? <ActivityIndicator color="#FFF" /> : <Text style={styles.btnText}>Créer mon compte</Text>}
        </TouchableOpacity>

        <View style={styles.row}>
          <Text style={styles.rowText}>Déjà un compte ? </Text>
          <TouchableOpacity onPress={() => navigation.navigate('Login')}>
            <Text style={styles.rowLink}>Se connecter</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  scroll:    { flexGrow: 1, padding: 24, paddingTop: 56 },
  back:      { marginBottom: 20 },
  title:     { fontSize: 26, fontWeight: '700', color: '#111827', marginBottom: 6 },
  subtitle:  { fontSize: 14, color: '#6B7280', marginBottom: 28 },
  errorBanner: { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: '#FEF2F2', borderColor: '#FECACA', borderWidth: 1, borderRadius: 10, padding: 12, marginBottom: 16 },
  errorBannerText: { color: '#DC2626', fontSize: 13, flex: 1 },
  rowFields: { flexDirection: 'row' },
  fieldGroup: { marginBottom: 16 },
  label:     { fontSize: 13, fontWeight: '600', color: '#374151', marginBottom: 6 },
  inputRow:  { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFF', borderRadius: 10, borderWidth: 1, borderColor: '#E5E7EB', paddingHorizontal: 12, height: 50 },
  inputError:{ borderColor: '#F87171' },
  icon:      { marginRight: 8 },
  input:     { flex: 1, fontSize: 15, color: '#111827' },
  fieldError:{ color: '#EF4444', fontSize: 12, marginTop: 4 },
  btn:       { backgroundColor: '#4F46E5', borderRadius: 12, height: 52, justifyContent: 'center', alignItems: 'center', marginTop: 8, marginBottom: 20 },
  btnDisabled:{ opacity: 0.6 },
  btnText:   { color: '#FFF', fontSize: 16, fontWeight: '600' },
  row:       { flexDirection: 'row', justifyContent: 'center' },
  rowText:   { fontSize: 14, color: '#6B7280' },
  rowLink:   { fontSize: 14, color: '#4F46E5', fontWeight: '600' },
  successContainer: { flex: 1, backgroundColor: '#F9FAFB', justifyContent: 'center', alignItems: 'center', padding: 32 },
  successIcon: { width: 96, height: 96, borderRadius: 24, backgroundColor: '#EEF2FF', justifyContent: 'center', alignItems: 'center', marginBottom: 24 },
  successTitle: { fontSize: 22, fontWeight: '700', color: '#111827', marginBottom: 12 },
  successBody:  { fontSize: 15, color: '#6B7280', textAlign: 'center', marginBottom: 32, lineHeight: 22 },
});
