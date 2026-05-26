import React, { useState } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as authService from '../../services/authService';

export default function EmailVerificationScreen({ navigation, route }) {
  const email = route.params?.email || '';
  const [resending, setResending] = useState(false);
  const [resent, setResent]       = useState(false);
  const [error, setError]         = useState(null);

  const handleResend = async () => {
    setResending(true); setError(null);
    try { await authService.resendConfirmation({ email }); setResent(true); }
    catch { setError('Impossible de renvoyer. Réessayez plus tard.'); }
    finally { setResending(false); }
  };

  return (
    <View style={styles.container}>
      <View style={styles.iconBox}><Ionicons name="mail-open-outline" size={52} color="#4F46E5" /></View>
      <Text style={styles.title}>Confirmez votre email</Text>
      <Text style={styles.body}>
        Un email de vérification a été envoyé à{'\n'}
        {email ? <Text style={styles.emailHL}>{email}</Text> : 'votre adresse email'}.
        {'\n\n'}Cliquez sur le lien reçu pour activer votre compte.
      </Text>

      {error ? <View style={styles.errorBanner}><Text style={styles.errorText}>{error}</Text></View> : null}
      {resent ? <View style={styles.successBanner}><Ionicons name="checkmark-circle-outline" size={16} color="#10B981" /><Text style={styles.successText}>Email renvoyé !</Text></View> : null}

      <TouchableOpacity style={[styles.btnOutline, (resending || resent) && styles.btnDisabled]} onPress={handleResend} disabled={resending || resent}>
        {resending ? <ActivityIndicator color="#4F46E5" /> : <Text style={styles.btnOutlineText}>{resent ? 'Email renvoyé ✓' : "Renvoyer l'email"}</Text>}
      </TouchableOpacity>

      <TouchableOpacity style={styles.btn} onPress={() => navigation.navigate('Login')}>
        <Text style={styles.btnText}>Aller à la connexion</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB', justifyContent: 'center', alignItems: 'center', padding: 32 },
  iconBox:   { width: 100, height: 100, borderRadius: 24, backgroundColor: '#EEF2FF', justifyContent: 'center', alignItems: 'center', marginBottom: 28 },
  title:     { fontSize: 24, fontWeight: '700', color: '#111827', marginBottom: 16, textAlign: 'center' },
  body:      { fontSize: 15, color: '#6B7280', textAlign: 'center', lineHeight: 22, marginBottom: 28 },
  emailHL:   { fontWeight: '600', color: '#374151' },
  errorBanner: { backgroundColor: '#FEF2F2', borderRadius: 10, padding: 12, marginBottom: 16, width: '100%' },
  errorText: { color: '#DC2626', fontSize: 13 },
  successBanner: { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: '#ECFDF5', borderRadius: 10, padding: 12, marginBottom: 16, width: '100%' },
  successText: { color: '#059669', fontSize: 13 },
  btnOutline: { width: '100%', borderRadius: 12, height: 52, justifyContent: 'center', alignItems: 'center', borderWidth: 1.5, borderColor: '#4F46E5', marginBottom: 12 },
  btnDisabled:{ opacity: 0.5 },
  btnOutlineText: { color: '#4F46E5', fontSize: 15, fontWeight: '600' },
  btn:       { width: '100%', backgroundColor: '#4F46E5', borderRadius: 12, height: 52, justifyContent: 'center', alignItems: 'center' },
  btnText:   { color: '#FFF', fontSize: 16, fontWeight: '600' },
});
