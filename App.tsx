import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { AuthProvider } from './src/context/AuthContext';
import { CartProvider } from './src/context/CartContext';
import AppNavigator from './src/navigation/AppNavigator';

// On configure le client en dehors du composant pour qu'il ne soit recréé qu'une seule fois
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Les données restent "fraîches" pendant 1 minute avant d'être re-fetchées
      
      staleTime: 1000 * 60, 
      retry: 1, // Ne réessayer qu'une fois en cas d'erreur réseau
      refetchOnWindowFocus: false, // Désactiver le refetch quand on revient sur l'app 
    },
  },
});

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <SafeAreaProvider>
        <AuthProvider>
          <CartProvider>
            <AppNavigator />
            <StatusBar style="auto" />
          </CartProvider>
        </AuthProvider>
      </SafeAreaProvider>
    </QueryClientProvider>
  );
}