import React, { createContext, useState, useEffect, ReactNode } from 'react';
import { Alert } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from '../services/api';
import { User } from '../types';

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  isLoggedIn: boolean;
  login: (email: string, password: string) => Promise<boolean>;
  register: (prenom: string, nom: string, email: string, password: string) => Promise<boolean>;
  logout: () => void;
}

export const AuthContext = createContext<AuthContextType>({
  user: null,
  isLoading: true, // true au démarrage pour vérifier le token
  isLoggedIn: false,
  login: async () => false,
  register: async () => false,
  logout: () => {},
});

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Au démarrage de l'app, on vérifie si un token existe déjà
  useEffect(() => {
    checkLoginStatus();
  }, []);

  const checkLoginStatus = async () => {
    try {
      const token = await AsyncStorage.getItem('auth_token');
      const userJson = await AsyncStorage.getItem('user_data');
      
      if (token && userJson) {
        setUser(JSON.parse(userJson));
      }
    } catch (error) {
      console.log("Erreur lors de la vérification du token", error);
    } finally {
      setIsLoading(false); // Fin du chargement initial
    }
  };

  // Connexion réelle via l'API
  const login = async (email: string, password: string): Promise<boolean> => {
    setIsLoading(true);
    try {
      const response = await api.post('/auth/login', { email, password });
      
      // Adaptation selon le format de réponse de votre Laravel (souvent response.data.token ou response.data.data.token)
      const { token, user: userData } = response.data.data || response.data;

      if (token && userData) {
        // Sauvegarde dans le téléphone
        await AsyncStorage.setItem('auth_token', token);
        await AsyncStorage.setItem('user_data', JSON.stringify(userData));
        
        // Mise à jour de l'état de l'app
        setUser(userData);
        setIsLoading(false);
        return true;
      } else {
        setIsLoading(false);
        return false;
      }
    } catch (error: any) {
      setIsLoading(false);
      const message = error.response?.data?.message || "Email ou mot de passe incorrect.";
      Alert.alert("Erreur de connexion", message);
      return false;
    }
  };

  // --- REGISTER ---
  const register = async (prenom: string, nom: string, email: string, password: string): Promise<boolean> => {
    setIsLoading(true);
    try {
      await api.post('/auth/register', { 
        prenom, nom, email, password,
        password_confirmation: password 
      });
      setIsLoading(false);
      // On affiche le message de succès
      Alert.alert("Succès", "Votre compte a été créé avec succès !\n\nVérifiez vos emails pour l'activer, puis connectez-vous depuis le menu Compte.");
      return true;
    } catch (error: any) {
      setIsLoading(false);
      let message = "Une erreur est survenue lors de la création du compte.";
      
      // On extrait le vrai message d'erreur de Laravel
      if (error.response?.data?.message) {
        message = error.response.data.message;
      } else if (error.response?.data?.errors) {
        const errors = error.response.data.errors;
        const firstErrorKey = Object.keys(errors)[0];
        message = Array.isArray(errors[firstErrorKey]) ? errors[firstErrorKey][0] : String(errors[firstErrorKey]);
      }
      
      Alert.alert("Échec de l'inscription", message);
      return false;
    }
  };

   // --- LOGOUT FORCE ---
  const logout = async () => {
    try {
      // On tente de prévenir l'invalidation du token sur Laravel (échoue silencieusement si le réseau est down)
      if (user) {
        api.post('/auth/logout').catch(() => {}); // <-- CORRECTION ICI
      }
    } catch (error) {
      console.log("Erreur réseau lors de la déconnexion :", error);
    } finally {
      // ON OBLIGE de vider le stockage local
      try {
        await AsyncStorage.removeItem('auth_token');
        await AsyncStorage.removeItem('user_data');
      } catch (storageError) {
        console.warn("Impossible de vider le stockage local, on force la déconnexion de toute façon.");
      }
      // Mise à jour immédiate de l'interface
      setUser(null);
    }
  };

  return (
    <AuthContext.Provider value={{ user, isLoading, isLoggedIn: !!user, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  );
};