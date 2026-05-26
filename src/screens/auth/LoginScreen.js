import React, { useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  KeyboardAvoidingView, Platform, ScrollView, ActivityIndicator,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import useAuthStore from '../../store/authStore';

export default function LoginScreen({ navigation }) {
  const { login, isLoading, error, clearError } = useAuthStore();
  const [email, setEmail]           = useState('');
  const [password, setPassword]     = useState('');
  const [showPwd, setShowPwd]       = useState(false);
  const [fieldErrors, setFieldErrors] = useState({});

  const validate = () => {
    const e = {};
    if (!email.trim()) e.email = 'Email requis.';
    else if (!/\S+@\S+\.\S+/.test(email)) e.email = 'Email invalide.';
    if (!password) e.password = 'Mot de passe requis.';
    setFieldErrors(e);
    return !Object.keys(e).length;
  };

  const handleLogin = async () => {
    clearError();
    if (!validate()) return;
    try { await login({ email: email.trim().toLowerCase(), mot_de_passe: password }); }
    catch {}
  };

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">

        <View style={styles.header}>
          <View style={styles.logoBox}><Text style={styles.logoText}>CYNA</Text></View>
          <Text style={styles.title}>Connexion</Text>
          <Text style={styles.subtitle}>Accédez à vos solutions cybersécurité</Text>
        </View>

        {error ? (
          <View style={styles.errorBanner}>
            <Ionicons name="alert-circle-outline" size={16} color="#DC2626" />
            <Text style={styles.errorBannerText}>{error}</Text>
          </View>
        ) : null}

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Email</Text>
          <View style={[styles.inputRow, fieldErrors.email && styles.inputError]}>
            <Ionicons name="mail-outline" size={18} color="#9CA3AF" style={styles.icon} />
            <TextInput style={styles.input} placeholder="votre@email.com" placeholderTextColor="#9CA3AF"
              value={email} onChangeText={(v) => { setEmail(v); setFieldErrors((p) => ({ ...p, email: null })); }}
              keyboardType="email-address" autoCapitalize="none" />
          </View>
          {fieldErrors.email ? <Text style={styles.fieldError}>{fieldErrors.email}</Text> : null}
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Mot de passe</Text>
          <View style={[styles.inputRow, fieldErrors.password && styles.inputError]}>
            <Ionicons name="lock-closed-outline" size={18} color="#9CA3AF" style={styles.icon} />
            <TextInput style={styles.input} placeholder="••••••••" placeholderTextColor="#9CA3AF"
              value={password} onChangeText={(v) => { setPassword(v); setFieldErrors((p) => ({ ...p, password: null })); }}
              secureTextEntry={!showPwd} />
            <TouchableOpacity onPress={() => setShowPwd(!showPwd)}>
              <Ionicons name={showPwd ? 'eye-off-outline' : 'eye-outline'} size={18} color="#9CA3AF" />
            </TouchableOpacity>
          </View>
          {fieldErrors.password ? <Text style={styles.fieldError}>{fieldErrors.password}</Text> : null}
        </View>

        <TouchableOpacity style={styles.forgot} onPress={() => navigation.navigate('ForgotPassword')}>
          <Text style={styles.forgotText}>Mot de passe oublié ?</Text>
        </TouchableOpacity>

        <TouchableOpacity style={[styles.btn, isLoading && styles.btnDisabled]} onPress={handleLogin} disabled={isLoading}>
          {isLoading ? <ActivityIndicator color="#FFF" /> : <Text style={styles.btnText}>Se connecter</Text>}
        </TouchableOpacity>

        <View style={styles.row}>
          <Text style={styles.rowText}>Pas encore de compte ? </Text>
          <TouchableOpacity onPress={() => navigation.navigate('Register')}>
            <Text style={styles.rowLink}>S'inscrire</Text>
          </TouchableOpacity>
        </View>

      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  scroll:    { flexGrow: 1, justifyContent: 'center', padding: 24 },
  header:    { alignItems: 'center', marginBottom: 36 },
  logoBox:   { width: 72, height: 72, borderRadius: 18, backgroundColor: '#4F46E5', justifyContent: 'center', alignItems: 'center', marginBottom: 16 },
  logoText:  { color: '#FFF', fontSize: 20, fontWeight: '700' },
  title:     { fontSize: 26, fontWeight: '700', color: '#111827', marginBottom: 6 },
  subtitle:  { fontSize: 14, color: '#6B7280', textAlign: 'center' },
  errorBanner: { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: '#FEF2F2', borderColor: '#FECACA', borderWidth: 1, borderRadius: 10, padding: 12, marginBottom: 16 },
  errorBannerText: { color: '#DC2626', fontSize: 13, flex: 1 },
  fieldGroup: { marginBottom: 16 },
  label:     { fontSize: 13, fontWeight: '600', color: '#374151', marginBottom: 6 },
  inputRow:  { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFF', borderRadius: 10, borderWidth: 1, borderColor: '#E5E7EB', paddingHorizontal: 12, height: 50 },
  inputError:{ borderColor: '#F87171' },
  icon:      { marginRight: 8 },
  input:     { flex: 1, fontSize: 15, color: '#111827' },
  fieldError:{ color: '#EF4444', fontSize: 12, marginTop: 4 },
  forgot:    { alignSelf: 'flex-end', marginBottom: 24 },
  forgotText:{ color: '#4F46E5', fontSize: 13 },
  btn:       { backgroundColor: '#4F46E5', borderRadius: 12, height: 52, justifyContent: 'center', alignItems: 'center', marginBottom: 20 },
  btnDisabled:{ opacity: 0.6 },
  btnText:   { color: '#FFF', fontSize: 16, fontWeight: '600' },
  row:       { flexDirection: 'row', justifyContent: 'center' },
  rowText:   { fontSize: 14, color: '#6B7280' },
  rowLink:   { fontSize: 14, color: '#4F46E5', fontWeight: '600' },
});
