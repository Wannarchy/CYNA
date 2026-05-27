import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ScrollView, Alert, Switch } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import useAuthStore from '../../store/authStore';

export default function ProfileScreen({ navigation }) {
  const { user, logout } = useAuthStore();

  const handleLogout = () => {
    Alert.alert('Déconnexion', 'Êtes-vous sûr de vouloir vous déconnecter ?', [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Se déconnecter', style: 'destructive', onPress: logout },
    ]);
  };

  const MenuItem = ({ icon, label, onPress, danger = false, right }) => (
    <TouchableOpacity style={styles.menuItem} onPress={onPress} activeOpacity={0.7}>
      <View style={[styles.menuIcon, danger && styles.menuIconDanger]}>
        <Ionicons name={icon} size={18} color={danger ? '#EF4444' : '#4F46E5'} />
      </View>
      <Text style={[styles.menuLabel, danger && styles.menuLabelDanger]}>{label}</Text>
      {right ?? <Ionicons name="chevron-forward" size={16} color="#D1D5DB" />}
    </TouchableOpacity>
  );

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      <View style={styles.profileHeader}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>
            {user?.prenom?.[0]?.toUpperCase() ?? '?'}{user?.nom?.[0]?.toUpperCase() ?? ''}
          </Text>
        </View>
        <Text style={styles.name}>{user?.prenom} {user?.nom}</Text>
        <Text style={styles.email}>{user?.email}</Text>
        {user?.est_confirme
          ? <View style={styles.verifiedBadge}><Ionicons name="checkmark-circle" size={13} color="#059669" /><Text style={styles.verifiedText}>Email vérifié</Text></View>
          : <View style={[styles.verifiedBadge, { backgroundColor: '#FEF3C7' }]}><Ionicons name="alert-circle" size={13} color="#D97706" /><Text style={[styles.verifiedText, { color: '#D97706' }]}>Email non vérifié</Text></View>
        }
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>MON COMPTE</Text>
        <View style={styles.menuCard}>
          <MenuItem icon="person-outline"         label="Modifier le profil"       onPress={() => {}} />
          <View style={styles.separator} />
          <MenuItem icon="location-outline"       label="Mes adresses"             onPress={() => {}} />
          <View style={styles.separator} />
          <MenuItem icon="receipt-outline"        label="Historique des commandes" onPress={() => navigation.navigate('Commandes')} />
          <View style={styles.separator} />
          <MenuItem icon="refresh-circle-outline" label="Mes abonnements"          onPress={() => navigation.navigate('Abonnements')} />
        </View>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>SÉCURITÉ</Text>
        <View style={styles.menuCard}>
          <MenuItem icon="lock-closed-outline" label="Changer le mot de passe" onPress={() => navigation.navigate('ForgotPassword')} />
          <View style={styles.separator} />
          <MenuItem icon="shield-checkmark-outline" label="Authentification 2FA" onPress={() => {}}
            right={<Switch value={false} trackColor={{ false: '#E5E7EB', true: '#4F46E5' }} thumbColor="#FFF" onValueChange={() => {}} />} />
        </View>
      </View>

      <View style={styles.section}>
        <View style={styles.menuCard}>
          <MenuItem icon="log-out-outline" label="Se déconnecter" onPress={handleLogout} danger />
        </View>
      </View>

      <Text style={styles.version}>CYNA Mobile v1.0.0</Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  profileHeader: { alignItems: 'center', paddingTop: 64, paddingBottom: 28, backgroundColor: '#FFF', borderBottomWidth: 1, borderBottomColor: '#F3F4F6' },
  avatar:    { width: 80, height: 80, borderRadius: 24, backgroundColor: '#4F46E5', justifyContent: 'center', alignItems: 'center', marginBottom: 14 },
  avatarText:{ color: '#FFF', fontSize: 28, fontWeight: '700' },
  name:      { fontSize: 20, fontWeight: '700', color: '#111827', marginBottom: 4 },
  email:     { fontSize: 14, color: '#6B7280', marginBottom: 8 },
  verifiedBadge: { flexDirection: 'row', alignItems: 'center', gap: 4, backgroundColor: '#ECFDF5', borderRadius: 6, paddingHorizontal: 10, paddingVertical: 3 },
  verifiedText:  { fontSize: 12, color: '#059669', fontWeight: '500' },
  section:   { padding: 16, paddingBottom: 0 },
  sectionTitle: { fontSize: 11, fontWeight: '600', color: '#9CA3AF', marginBottom: 8, letterSpacing: 0.8 },
  menuCard:  { backgroundColor: '#FFF', borderRadius: 14, overflow: 'hidden', shadowColor: '#000', shadowOpacity: 0.04, shadowRadius: 4, shadowOffset: { width: 0, height: 1 }, elevation: 1 },
  menuItem:  { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 14, gap: 12 },
  menuIcon:  { width: 36, height: 36, borderRadius: 10, backgroundColor: '#EEF2FF', justifyContent: 'center', alignItems: 'center' },
  menuIconDanger: { backgroundColor: '#FEF2F2' },
  menuLabel: { flex: 1, fontSize: 15, color: '#111827' },
  menuLabelDanger: { color: '#EF4444' },
  separator: { height: 1, backgroundColor: '#F3F4F6', marginLeft: 64 },
  version:   { textAlign: 'center', color: '#D1D5DB', fontSize: 12, padding: 24, paddingTop: 20 },
});
