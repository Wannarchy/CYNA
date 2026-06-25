import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { ActivityIndicator, View } from 'react-native';

// --- IMPORTS DES ÉCRANS ---
import Header from '../components/Header';
import HomeScreen from '../screens/HomeScreen';
import CategoriesScreen from '../screens/CategoriesScreen';
import CartScreen from '../screens/CartScreen';
import AccountScreen from '../screens/AccountScreen';
import CategoryScreen from '../screens/CategoryScreen';
import ProductScreen from '../screens/ProductScreen';
import LoginScreen from '../screens/LoginScreen';
import RegisterScreen from '../screens/RegisterScreen';
import SearchScreen from '../screens/SearchScreen';
import CheckoutScreen from '../screens/CheckoutScreen';
import OrderSuccessScreen from '../screens/OrderSuccessScreen';
import ContactScreen from '../screens/ContactScreen';
import ChatbotScreen from '../screens/ChatbotScreen';
import OrderHistoryScreen from '../screens/OrderHistoryScreen';
import AddressBookScreen from '../screens/AddressBookScreen';
import EditAddressScreen from '../screens/EditAddressScreen'; // <-- NOUVEL IMPORT
import ProfileScreen from '../screens/ProfileScreen';
import ForgotPasswordScreen from '../screens/ForgotPasswordScreen';

// --- IMPORTS CONTEXT ---
import { useAuth } from '../context/AuthContext';

// --- TYPAGE DES ROUTES ---
export type RootStackParamList = {
  MainTabs: undefined;
  Category: { categoryId?: string; categoryName?: string };
  Product: { productId: string };
  Search: undefined;
  Login: undefined;
  Register: undefined;
  ForgotPassword: undefined;
  Checkout: undefined;
  OrderSuccess: { orderId?: string };
  Profile: undefined;
  OrderHistory: undefined;
  AddressBook: undefined;
  EditAddress: { address?: any }; // <-- NOUVELLE ROUTE TYPÉE
  Contact: undefined;
  Chatbot: undefined;
};

export type TabParamList = {
  Home: undefined;
  Categories: undefined;
  Cart: undefined;
  Account: undefined;
};

const Tab = createBottomTabNavigator<TabParamList>();
const Stack = createNativeStackNavigator<RootStackParamList>();

// --- NAVIGATEUR DES ONGLETS ---
function TabNavigator() {
  return (
    <Tab.Navigator
      screenOptions={{
        header: () => <Header />,
        tabBarActiveTintColor: '#0056b3',
        tabBarInactiveTintColor: 'gray',
      }}
    >
      <Tab.Screen 
        name="Home" 
        component={HomeScreen} 
        options={{ 
          tabBarLabel: 'Accueil', 
          tabBarIcon: ({ color, size }) => <Ionicons name="home-outline" color={color} size={size} /> 
        }} 
      />
      <Tab.Screen 
        name="Categories" 
        component={CategoriesScreen} 
        options={{ 
          tabBarLabel: 'Catégories', 
          tabBarIcon: ({ color, size }) => <Ionicons name="grid-outline" color={color} size={size} /> 
        }} 
      />
      <Tab.Screen 
        name="Cart" 
        component={CartScreen} 
        options={{ 
          tabBarLabel: 'Panier', 
          tabBarIcon: ({ color, size }) => <Ionicons name="cart-outline" color={color} size={size} /> 
        }} 
      />
      <Tab.Screen 
        name="Account" 
        component={AccountScreen} 
        options={{ 
          tabBarLabel: 'Compte', 
          tabBarIcon: ({ color, size }) => <Ionicons name="person-outline" color={color} size={size} /> 
        }} 
      />
    </Tab.Navigator>
  );
}

// --- NAVIGATEUR PRINCIPAL ---
export default function AppNavigator() {
  const { isLoggedIn, isLoading } = useAuth();

  // Pendant qu'on vérifie le token dans l'AsyncStorage au démarrage, on affiche un loader
  if (isLoading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F8FAFC' }}>
        <ActivityIndicator size="large" color="#0056b3" />
      </View>
    );
  }

  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        
        {/* --- SI CONNECTÉ : ON AFFICHE L'APPLICATION --- */}
        {isLoggedIn ? (
          <>
            {/* Menu principal */}
            <Stack.Screen name="MainTabs" component={TabNavigator} />
            
            {/* Catalogue & Recherche */}
            <Stack.Screen 
              name="Category" 
              component={CategoryScreen} 
              options={{ headerShown: true, title: 'Catalogue', headerTintColor: '#0056b3', headerBackTitle: 'Retour' }} 
            />
            <Stack.Screen 
              name="Product" 
              component={ProductScreen} 
              options={{ headerShown: true, title: 'Détail du service', headerTintColor: '#0056b3', headerBackTitle: 'Retour' }} 
            />
            <Stack.Screen name="Search" component={SearchScreen} />

            {/* Tunnel d'achat */}
            <Stack.Screen name="Checkout" component={CheckoutScreen} />
            <Stack.Screen name="OrderSuccess" component={OrderSuccessScreen} />

            {/* Espace Client */}
            <Stack.Screen name="Profile" component={ProfileScreen} />
            <Stack.Screen name="OrderHistory" component={OrderHistoryScreen} />
            <Stack.Screen name="AddressBook" component={AddressBookScreen} />
            {/* NOUVEL ÉCRAN : Édition / Ajout d'adresse */}
            <Stack.Screen name="EditAddress" component={EditAddressScreen} />

            {/* Support */}
            <Stack.Screen name="Contact" component={ContactScreen} />
            <Stack.Screen name="Chatbot" component={ChatbotScreen} />
          </>
        ) : (
          /* --- SINON : ON AFFICHE LES ÉCRANS DE CONNEXION --- */
          <>
            <Stack.Screen name="Login" component={LoginScreen} />
            <Stack.Screen name="Register" component={RegisterScreen} />
            <Stack.Screen name="ForgotPassword" component={ForgotPasswordScreen} />
          </>
        )}

      </Stack.Navigator>
    </NavigationContainer>
  );
}