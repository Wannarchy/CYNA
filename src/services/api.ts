import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

// --- CONSTANTES ---
export const API_BASE_URL = 'https://laravel-api-1-zb19.onrender.com/api';
const STORAGE_BASE_URL = 'https://laravel-api-1-zb19.onrender.com/storage';
export const AUTH_TOKEN_KEY = 'auth_token'; // Exporté pour être réutilisé dans AuthContext

// --- CONFIGURATION AXIOS ---
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 30000,
});

// --- INTERCEPTORS ---
api.interceptors.request.use(
  async (config: InternalAxiosRequestConfig) => {
    const token = await AsyncStorage.getItem(AUTH_TOKEN_KEY);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error: AxiosError) => Promise.reject(error)
);

let onUnauthorized: (() => void) | null = null;
// Verrou pour éviter de déclencher la déconnexion 100 fois si 100 requêtes tombent en 401
let isUnauthorizedTriggered = false; 

export function setOnUnauthorized(callback: (() => void) | null) {
  onUnauthorized = callback;
  // Réinitialise le verrou quand on attache un nouveau callback
  if (callback) isUnauthorizedTriggered = false; 
}

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    if (error.response?.status === 401 && !isUnauthorizedTriggered) {
      isUnauthorizedTriggered = true; // On bloque les futurs appels
      await AsyncStorage.multiRemove([AUTH_TOKEN_KEY, 'user_data']);
      onUnauthorized?.();
    }
    return Promise.reject(error);
  }
);

export default api;

// --- HELPERS ---
export const getFullImageUrl = (path: string | null | undefined): string => {
  if (!path) return 'https://placehold.co/150x150?text=No+Image'; // placeholder plus moderne et fiable
  if (path.startsWith('http')) return path;
  return `${STORAGE_BASE_URL}/${path}`;
};