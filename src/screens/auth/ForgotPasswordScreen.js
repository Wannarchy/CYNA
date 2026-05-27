import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as authService from '../../services/authService';

export default function ForgotPasswordScreen({ navigation }) {
  const [email, setEmail]     = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError]     = useState(null);
  const [sent, setSent]       = useState(false);

  const handleSubmit = async () => {
    setError(null);
    if (!email.trim() || !/\S+@\S+\.\S+/.test(email)) { setError('Email invalide.'); return; }
    setLoading(true);
    try { await authService.forgotPassword({ email: email.trim().toLowerCase() }); setSent(true); }
    catch (err) { setError(err.response?.data?.message || 'Une erreur est survenue.'); }
    finally { setLoading(false); }
  };

  if (sent) return (
    <View style={styles.centered}>
      <View style={styles.iconBox}><Ionicons name="checkmark-circle-outline" size={48} color="#10B981" /></View>
      <Text style={styles.sentTitle}>Email envoyé !</Text>
      <Text style={styles.sentBody}>Consultez votre boîte mail pour réinitialiser votre mot de passe.</Text>
      <TouchableOpacity style={styles.btn} onPress={() => navigation.navigate('Login')}>
        <Text style={styles.btnText}>Retour à la connexion</Text>
      </TouchableOpacity>
    </View>
  );

  return (
    <View style={styles.container}>
      <TouchableOpacity style={styles.back} onPress={() => navigation.goBack()}>
        <Ionicons name="arrow-back" size={22} color="#374151" />
      </TouchableOpacity>
      <View style={styles.iconBox}><Ionicons name="key-outline" size={32} color="#4F46E5" /></View>
      <Text style={styles.title}>Mot de passe oublié ?</Text>
      <Text style={styles.subtitle}>Entrez votre email. Nous vous enverrons un lien de réinitialisation.</Text>

      {error ? <View style={styles.errorBanner}><Ionicons name="alert-circle-outline" size={16} color="#DC2626" /><Text style={styles.errorText}>{error}</Text></View> : null}

      <Text style={styles.label}>Email</Text>
      <View style={[styles.inputRow, error && styles.inputError]}>
        <Ionicons name="mail-outline" size={18} color="#9CA3AF" style={{ marginRight: 8 }} />
        <TextInput style={styles.input} placeholder="votre@email.com" placeholderTextColor="#9CA3AF"
          value={email} onChangeText={(v) => { setEmail(v); setError(null); }}
          keyboardType="email-address" autoCapitalize="none" />
      </View>

      <TouchableOpacity style={[styles.btn, loading && styles.btnDisabled]} onPress={handleSubmit} disabled={loading}>
        {loading ? <ActivityIndicator color="#FFF" /> : <Text style={styles.btnText}>Envoyer le lien</Text>}
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB', padding: 24, paddingTop: 56 },
  centered:  { flex: 1, backgroundColor: '#F9FAFB', justifyContent: 'center', alignItems: 'center', padding: 32 },
  back:      { marginBottom: 32 },
  iconBox:   { width: 72, height: 72, borderRadius: 18, backgroundColor: '#EEF2FF', justifyContent: 'center', alignItems: 'center', marginBottom: 20 },
  title:     { fontSize: 24, fontWeight: '700', color: '#111827', marginBottom: 8 },
  subtitle:  { fontSize: 14, color: '#6B7280', marginBottom: 28, lineHeight: 20 },
  errorBanner: { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: '#FEF2F2', borderColor: '#FECACA', borderWidth: 1, borderRadius: 10, padding: 12, marginBottom: 16 },
  errorText: { color: '#DC2626', fontSize: 13, flex: 1 },
  label:     { fontSize: 13, fontWeight: '600', color: '#374151', marginBottom: 6 },
  inputRow:  { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFF', borderRadius: 10, borderWidth: 1, borderColor: '#E5E7EB', paddingHorizontal: 12, height: 50, marginBottom: 24 },
  inputError:{ borderColor: '#F87171' },
  input:     { flex: 1, fontSize: 15, color: '#111827' },
  btn:       { backgroundColor: '#4F46E5', borderRadius: 12, height: 52, justifyContent: 'center', alignItems: 'center' },
  btnDisabled:{ opacity: 0.6 },
  btnText:   { color: '#FFF', fontSize: 16, fontWeight: '600' },
  sentTitle: { fontSize: 22, fontWeight: '700', color: '#111827', marginBottom: 12 },
  sentBody:  { fontSize: 15, color: '#6B7280', textAlign: 'center', marginBottom: 32, lineHeight: 22 },
});
