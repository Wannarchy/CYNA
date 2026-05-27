import { create } from 'zustand';
import * as authService from '../services/authService';

const useAuthStore = create((set) => ({
  user:            null,
  isAuthenticated: false,
  isLoading:       false,
  error:           null,

  initAuth: async () => {
    set({ isLoading: true });
    try {
      const user = await authService.getMe();
      set({ user, isAuthenticated: true });
    } catch {
      set({ user: null, isAuthenticated: false });
    } finally {
      set({ isLoading: false });
    }
  },

  login: async (credentials) => {
    set({ isLoading: true, error: null });
    try {
      const data = await authService.login(credentials);
      set({ user: data.user, isAuthenticated: true, isLoading: false });
    } catch (err) {
      const msg = err.response?.data?.message || 'Email ou mot de passe incorrect.';
      set({ isLoading: false, error: msg });
      throw err;
    }
  },

  register: async (formData) => {
    set({ isLoading: true, error: null });
    try {
      const data = await authService.register(formData);
      set({ isLoading: false });
      return data;
    } catch (err) {
      const msg = err.response?.data?.message || "Erreur lors de l'inscription.";
      set({ isLoading: false, error: msg });
      throw err;
    }
  },

  logout: async () => {
    set({ isLoading: true });
    try { await authService.logout(); } finally {
      set({ user: null, isAuthenticated: false, isLoading: false });
    }
  },

  clearError: () => set({ error: null }),
}));

export default useAuthStore;
