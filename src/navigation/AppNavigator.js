import React, { useEffect } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { ActivityIndicator, View } from 'react-native';

import useAuthStore from '../store/authStore';

import LoginScreen             from '../screens/auth/LoginScreen';
import RegisterScreen          from '../screens/auth/RegisterScreen';
import ForgotPasswordScreen    from '../screens/auth/ForgotPasswordScreen';
import EmailVerificationScreen from '../screens/auth/EmailVerificationScreen';

import CatalogueScreen from '../screens/catalogue/CatalogueScreen';
import ProductScreen   from '../screens/catalogue/ProductScreen';

import CartScreen         from '../screens/order/CartScreen';
import CheckoutScreen     from '../screens/order/CheckoutScreen';
import ConfirmationScreen from '../screens/order/ConfirmationScreen';
import OrdersScreen       from '../screens/order/OrderScreen';

import SubscriptionsScreen from '../screens/subscription/SubscriptionsScreen';
import ProfileScreen       from '../screens/account/ProfileScreen';

const Stack = createNativeStackNavigator();
const Tab   = createBottomTabNavigator();

function AuthStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="Login"             component={LoginScreen} />
      <Stack.Screen name="Register"          component={RegisterScreen} />
      <Stack.Screen name="ForgotPassword"    component={ForgotPasswordScreen} />
      <Stack.Screen name="EmailVerification" component={EmailVerificationScreen} />
    </Stack.Navigator>
  );
}

function CatalogueStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="CatalogueHome" component={CatalogueScreen} />
      <Stack.Screen name="ProductDetail" component={ProductScreen} />
    </Stack.Navigator>
  );
}

function CartStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="CartHome"     component={CartScreen} />
      <Stack.Screen name="Checkout"     component={CheckoutScreen} />
      <Stack.Screen name="Confirmation" component={ConfirmationScreen} />
    </Stack.Navigator>
  );
}

function AppTabs() {
  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarActiveTintColor:   '#4F46E5',
        tabBarInactiveTintColor: '#9CA3AF',
        tabBarStyle: { backgroundColor: '#FFF', borderTopColor: '#E5E7EB', height: 62, paddingBottom: 6 },
        tabBarLabelStyle: { fontSize: 11, fontWeight: '500' },
        tabBarIcon: ({ focused, color, size }) => {
          const icons = {
            Catalogue:   focused ? 'grid'           : 'grid-outline',
            Panier:      focused ? 'cart'           : 'cart-outline',
            Abonnements: focused ? 'refresh-circle' : 'refresh-circle-outline',
            Commandes:   focused ? 'receipt'        : 'receipt-outline',
            Compte:      focused ? 'person'         : 'person-outline',
          };
          return <Ionicons name={icons[route.name]} size={size} color={color} />;
        },
      })}
    >
      <Tab.Screen name="Catalogue"   component={CatalogueStack} />
      <Tab.Screen name="Panier"      component={CartStack} />
      <Tab.Screen name="Abonnements" component={SubscriptionsScreen} />
      <Tab.Screen name="Commandes"   component={OrderScreen} />
      <Tab.Screen name="Compte"      component={ProfileScreen} />
    </Tab.Navigator>
  );
}

export default function AppNavigator() {
  const { isAuthenticated, isLoading, initAuth } = useAuthStore();

  useEffect(() => { initAuth(); }, []);

  if (isLoading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F9FAFB' }}>
        <ActivityIndicator size="large" color="#4F46E5" />
      </View>
    );
  }

  return (
    <NavigationContainer>
      {isAuthenticated ? <AppTabs /> : <AuthStack />}
    </NavigationContainer>
  );
}
