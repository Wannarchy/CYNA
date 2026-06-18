import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  ReactNode,
} from 'react';
import { Alert } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api, { setOnUnauthorized } from '../services/api';
import { User } from '../types';

const AUTH_TOKEN_KEY = 'auth_token';
const USER_DATA_KEY = 'user_data';

interface AuthResponse {
  token: string;
  user: User;
}

export interface ProfileUpdateData {
  prenom?: string;
  nom?: string;
  email?: string;
  current_password?: string;
  password?: string;
  password_confirmation?: string;
}

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  isLoggedIn: boolean;
  login: (email: string, password: string) => Promise<boolean>;
  register: (prenom: string, nom: string, email: string, password: string) => Promise<boolean>;
  logout: () => Promise<void>;
  updateProfile: (data: ProfileUpdateData) => Promise<boolean>;
  forgotPassword: (email: string) => Promise<boolean>;
  resendVerification: () => Promise<boolean>;
  refreshUser: () => Promise<void>;
}

const defaultContext: AuthContextType = {
  user: null,
  isLoading: true,
  isLoggedIn: false,
  login: async () => false,
  register: async () => false,
  logout: async () => {},
  updateProfile: async () => false,
  forgotPassword: async () => false,
  resendVerification: async () => false,
  refreshUser: async () => {},
};

const AuthContext = createContext<AuthContextType>(defaultContext);

function extractApiMessage(error: unknown, fallback: string): string {
  const response = (error as { response?: { data?: Record<string, unknown> } })?.response?.data;
  if (!response) return fallback;

  if (typeof response.message === 'string') {
    return response.message;
  }

  if (response.errors && typeof response.errors === 'object') {
    const errors = response.errors as Record<string, string | string[]>;
    const firstKey = Object.keys(errors)[0];
    const value = errors[firstKey];
    return Array.isArray(value) ? value[0] : String(value);
  }

  return fallback;
}

async function persistSession(token: string, user: User): Promise<void> {
  await AsyncStorage.multiSet([
    [AUTH_TOKEN_KEY, token],
    [USER_DATA_KEY, JSON.stringify(user)],
  ]);
}

async function clearSession(): Promise<void> {
  await AsyncStorage.multiRemove([AUTH_TOKEN_KEY, USER_DATA_KEY]);
}

function parseAuthResponse(data: unknown): AuthResponse | null {
  const payload = (data as { data?: AuthResponse })?.data ?? data;
  const token = (payload as AuthResponse)?.token;
  const user = (payload as AuthResponse)?.user;

  if (token && user) {
    return { token, user };
  }

  return null;
}

function isAdminUser(user: User): boolean {
  return user.is_admin === true;
}

const ADMIN_LOGIN_MESSAGE =
  'Les comptes administrateur ne peuvent pas se connecter sur l\'application mobile. Utilisez le back-office web.';

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const applySession = useCallback(async (token: string, userData: User) => {
    if (isAdminUser(userData)) {
      await clearSession();
      setUser(null);
      return false;
    }

    await persistSession(token, userData);
    setUser(userData);
    return true;
  }, []);

  const clearAuthState = useCallback(async () => {
    try {
      await clearSession();
    } catch {
      // On force la déconnexion même si le stockage local échoue.
    }
    setUser(null);
  }, []);

  const refreshUser = useCallback(async () => {
    const token = await AsyncStorage.getItem(AUTH_TOKEN_KEY);
    if (!token) {
      setUser(null);
      return;
    }

    const response = await api.get('/profile');
    const userData = response.data?.data as User | undefined;

    if (userData) {
      if (isAdminUser(userData)) {
        await clearAuthState();
        return;
      }

      await AsyncStorage.setItem(USER_DATA_KEY, JSON.stringify(userData));
      setUser(userData);
    }
  }, [clearAuthState]);

  const restoreSession = useCallback(async () => {
    try {
      const token = await AsyncStorage.getItem(AUTH_TOKEN_KEY);
      if (!token) return;

      const userJson = await AsyncStorage.getItem(USER_DATA_KEY);
      if (userJson) {
        const cachedUser = JSON.parse(userJson) as User;
        if (isAdminUser(cachedUser)) {
          await clearAuthState();
          return;
        }
        setUser(cachedUser);
      }

      await refreshUser();
    } catch {
      await clearAuthState();
    }
  }, [clearAuthState, refreshUser]);

  useEffect(() => {
    setOnUnauthorized(() => {
      clearAuthState();
    });

    restoreSession().finally(() => setIsLoading(false));

    return () => setOnUnauthorized(null);
  }, [clearAuthState, restoreSession]);

  const login = useCallback(async (email: string, password: string): Promise<boolean> => {
    setIsLoading(true);
    try {
      const response = await api.post('/auth/login', { email, password });
      const session = parseAuthResponse(response.data);

      if (!session) {
        return false;
      }

      if (isAdminUser(session.user)) {
        Alert.alert('Accès refusé', ADMIN_LOGIN_MESSAGE);
        return false;
      }

      const sessionApplied = await applySession(session.token, session.user);
      return sessionApplied;
    } catch (error) {
      Alert.alert('Erreur de connexion', extractApiMessage(error, 'Email ou mot de passe incorrect.'));
      return false;
    } finally {
      setIsLoading(false);
    }
  }, [applySession]);

  const register = useCallback(async (
    prenom: string,
    nom: string,
    email: string,
    password: string,
  ): Promise<boolean> => {
    setIsLoading(true);
    try {
      await api.post('/auth/register', {
        prenom,
        nom,
        email,
        password,
        password_confirmation: password,
      });

      Alert.alert(
        'Succès',
        'Votre compte a été créé avec succès.\n\nVérifiez vos emails pour l\'activer, puis connectez-vous depuis le menu Compte.',
      );
      return true;
    } catch (error) {
      Alert.alert(
        'Échec de l\'inscription',
        extractApiMessage(error, 'Une erreur est survenue lors de la création du compte.'),
      );
      return false;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const logout = useCallback(async () => {
    try {
      if (user) {
        await api.post('/auth/logout');
      }
    } catch {
      // La déconnexion locale reste prioritaire si le réseau est indisponible.
    } finally {
      await clearAuthState();
    }
  }, [clearAuthState, user]);

  const updateProfile = useCallback(async (data: ProfileUpdateData): Promise<boolean> => {
    setIsLoading(true);
    try {
      const response = await api.put('/profile', data);
      const userData = response.data?.data as User | undefined;

      if (userData) {
        if (isAdminUser(userData)) {
          await clearAuthState();
          Alert.alert('Accès refusé', ADMIN_LOGIN_MESSAGE);
          return false;
        }

        const token = await AsyncStorage.getItem(AUTH_TOKEN_KEY);
        if (token) {
          await persistSession(token, userData);
        }
        setUser(userData);
      }

      return true;
    } catch (error) {
      Alert.alert('Erreur', extractApiMessage(error, 'Impossible de mettre à jour le profil.'));
      return false;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const forgotPassword = useCallback(async (email: string): Promise<boolean> => {
    setIsLoading(true);
    try {
      await api.post('/auth/forgot-password', { email });
      return true;
    } catch {
      // Ne pas révéler si l'email existe ou non.
      return true;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const resendVerification = useCallback(async (): Promise<boolean> => {
    try {
      await api.post('/auth/resend-verification');
      Alert.alert('Email envoyé', 'Un nouvel email de vérification vient d\'être envoyé.');
      return true;
    } catch (error) {
      Alert.alert('Erreur', extractApiMessage(error, 'Impossible de renvoyer l\'email de vérification.'));
      return false;
    }
  }, []);

  const value = useMemo<AuthContextType>(() => ({
    user,
    isLoading,
    isLoggedIn: !!user,
    login,
    register,
    logout,
    updateProfile,
    forgotPassword,
    resendVerification,
    refreshUser,
  }), [
    user,
    isLoading,
    login,
    register,
    logout,
    updateProfile,
    forgotPassword,
    resendVerification,
    refreshUser,
  ]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export function useAuth(): AuthContextType {
  return useContext(AuthContext);
}

export { AuthContext };
