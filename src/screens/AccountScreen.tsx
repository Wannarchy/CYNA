import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet, Alert, ScrollView } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { RootStackParamList } from '../navigation/AppNavigator';

type AccountScreenNavigationProp = NativeStackNavigationProp<RootStackParamList>;

export default function AccountScreen() {
  const navigation = useNavigation<AccountScreenNavigationProp>();
  const { user, logout } = useAuth();

  const handleLogout = () => {
    Alert.alert(
      "Déconnexion",
      "Êtes-vous sûr de vouloir vous déconnecter ?",
      [
        { text: "Annuler", style: "cancel" },
        { text: "Déconnecter", style: "destructive", onPress: () => logout() }
      ]
    );
  };

  // Menu items (avec les couleurs et icônes de ton ancien code)
  const menuItems = [
    { icon: 'person-outline', title: 'Modifier mes informations', screen: 'Profile', color: '#3b82f6' },
    { icon: 'receipt-outline', title: 'Mes commandes & Factures', screen: 'OrderHistory', color: '#10b981' },
    { icon: 'location-outline', title: 'Adresses de facturation', screen: 'AddressBook', color: '#f59e0b' },
    { icon: 'chatbubbles-outline', title: 'Chatbot d\'assistance', screen: 'Chatbot', color: '#0056b3' }, // Mis en avant
    { icon: 'mail-outline', title: 'Aide & Contact', screen: 'Contact', color: '#ec4899' },
  ];

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      {/* HEADER PROFILE */}
      <View style={styles.profileHeader}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{user?.prenom?.[0] || 'U'}{user?.nom?.[0] || ''}</Text>
        </View>
        <Text style={styles.userName}>Bonjour, {user?.prenom}</Text>
        <Text style={styles.userEmail}>{user?.email}</Text>
      </View>

      {/* MENU */}
      <View style={styles.menuContainer}>
        {menuItems.map((item, index) => (
          <TouchableOpacity 
            key={index} 
            style={[
              styles.menuItem, 
              // Si c'est le Chatbot, on met un fond légèrement bleu pour le faire ressortir comme dans ton ancien code
              item.title.includes('Chatbot') && { backgroundColor: '#F8FAFC' }
            ]} 
            onPress={() => navigation.navigate(item.screen as any)}
          >
            <View style={[styles.menuIconBg, { backgroundColor: `${item.color}15` }]}>
              <Ionicons name={item.icon as any} size={22} color={item.color} />
            </View>
            <Text 
              style={[
                styles.menuText, 
                item.title.includes('Chatbot') && { color: '#0056b3', fontWeight: '600' }
              ]}
            >
              {item.title}
            </Text>
            <Ionicons name="chevron-forward" size={20} color="#cbd5e1" />
          </TouchableOpacity>
        ))}
      </View>

      {/* LOGOUT BUTTON */}
      <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
        <Ionicons name="log-out-outline" size={22} color="#EF4444" />
        <Text style={styles.logoutText}>Se déconnecter</Text>
      </TouchableOpacity>

      <View style={{ height: 40 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  
  // Header
  profileHeader: { alignItems: 'center', paddingVertical: 40, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  avatar: { width: 90, height: 90, borderRadius: 45, backgroundColor: '#0056b3', justifyContent: 'center', alignItems: 'center', marginBottom: 15 },
  avatarText: { color: '#fff', fontSize: 32, fontWeight: 'bold' },
  userName: { fontSize: 22, fontWeight: 'bold', color: '#1E293B' },
  userEmail: { fontSize: 14, color: '#64748B', marginTop: 5 },
  
  // Menu
  menuContainer: { backgroundColor: '#fff', margin: 20, borderRadius: 16, overflow: 'hidden', shadowColor: '#000', shadowOpacity: 0.03, shadowRadius: 15, elevation: 2 },
  menuItem: { flexDirection: 'row', alignItems: 'center', padding: 18, borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  menuIconBg: { width: 40, height: 40, borderRadius: 12, justifyContent: 'center', alignItems: 'center', marginRight: 15 },
  menuText: { flex: 1, fontSize: 16, fontWeight: '500', color: '#334155' },
  
  // Logout
  logoutButton: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: '#FEF2F2', marginHorizontal: 20, padding: 18, borderRadius: 12, borderWidth: 1, borderColor: '#FECACA' },
  logoutText: { color: '#EF4444', fontSize: 16, fontWeight: 'bold', marginLeft: 10 }
});