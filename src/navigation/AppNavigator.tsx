import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';

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
import ProfileScreen from '../screens/ProfileScreen';
import ForgotPasswordScreen from '../screens/ForgotPasswordScreen'; // L'IMPORT CRITIQUE

const Tab = createBottomTabNavigator();
const Stack = createNativeStackNavigator();

function TabNavigator() {
  return (
    <Tab.Navigator
      screenOptions={{
        header: () => <Header />,
        tabBarActiveTintColor: '#0056b3',
        tabBarInactiveTintColor: 'gray',
      }}
    >
      <Tab.Screen name="Home" component={HomeScreen} options={{ tabBarLabel: 'Accueil', tabBarIcon: ({ color, size }) => <Ionicons name="home-outline" color={color} size={size} /> }} />
      <Tab.Screen name="Categories" component={CategoriesScreen} options={{ tabBarLabel: 'Catégories', tabBarIcon: ({ color, size }) => <Ionicons name="grid-outline" color={color} size={size} /> }} />
      <Tab.Screen name="Cart" component={CartScreen} options={{ tabBarLabel: 'Panier', tabBarIcon: ({ color, size }) => <Ionicons name="cart-outline" color={color} size={size} /> }} />
      <Tab.Screen name="Account" component={AccountScreen} options={{ tabBarLabel: 'Compte', tabBarIcon: ({ color, size }) => <Ionicons name="person-outline" color={color} size={size} /> }} />
    </Tab.Navigator>
  );
}

export default function AppNavigator() {
  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        
        {/* Menu principal */}
        <Stack.Screen name="MainTabs" component={TabNavigator} />
        
        {/* Catalogue & Recherche */}
        <Stack.Screen name="Category" component={CategoryScreen} options={{ headerShown: true, title: 'Catalogue', headerTintColor: '#0056b3', headerBackTitle: 'Retour' }} />
        <Stack.Screen name="Product" component={ProductScreen} options={{ headerShown: true, title: 'Détail du service', headerTintColor: '#0056b3', headerBackTitle: 'Retour' }} />
        <Stack.Screen name="Search" component={SearchScreen} options={{ headerShown: false }} />

        {/* Authentification */}
        <Stack.Screen name="Login" component={LoginScreen} options={{ headerShown: false }} />
        <Stack.Screen name="Register" component={RegisterScreen} options={{ headerShown: false }} />
        <Stack.Screen name="ForgotPassword" component={ForgotPasswordScreen} options={{ headerShown: false }} />

        {/* Tunnel d'achat */}
        <Stack.Screen name="Checkout" component={CheckoutScreen} options={{ headerShown: false }} />
        <Stack.Screen name="OrderSuccess" component={OrderSuccessScreen} options={{ headerShown: false }} />

        {/* Espace Client */}
        <Stack.Screen name="Profile" component={ProfileScreen} options={{ headerShown: false }} />
        <Stack.Screen name="OrderHistory" component={OrderHistoryScreen} options={{ headerShown: false }} />
        <Stack.Screen name="AddressBook" component={AddressBookScreen} options={{ headerShown: false }} />

        {/* Support */}
        <Stack.Screen name="Contact" component={ContactScreen} options={{ headerShown: false }} />
        <Stack.Screen name="Chatbot" component={ChatbotScreen} options={{ headerShown: false }} />

      </Stack.Navigator>
    </NavigationContainer>
  );
}