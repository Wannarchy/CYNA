import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
import apiClient from '../utils/apiClient';

const CART_KEY = 'cyna_cart';

const useCartStore = create((set, get) => ({
  items: [],

  loadCart: async () => {
    try {
      const raw = await AsyncStorage.getItem(CART_KEY);
      if (raw) set({ items: JSON.parse(raw) });
    } catch {}
  },

  _persist: async (items) => {
    try { await AsyncStorage.setItem(CART_KEY, JSON.stringify(items)); } catch {}
  },

  // product : { id, name, price_monthly, price_yearly, is_available, category_name }
  addItem: (product) => {
    const items = get().items;
    if (items.find((i) => i.id === product.id)) return;
    const updated = [...items, { ...product, cycle: product.cycle ?? 'monthly' }];
    set({ items: updated });
    get()._persist(updated);
  },

  removeItem: (id) => {
    const updated = get().items.filter((i) => i.id !== id);
    set({ items: updated });
    get()._persist(updated);
  },

  setCycle: (id, cycle) => {
    const updated = get().items.map((i) => i.id === id ? { ...i, cycle } : i);
    set({ items: updated });
    get()._persist(updated);
  },

  clearCart: () => { set({ items: [] }); get()._persist([]); },

  getUnitPrice: (item) =>
    item.cycle === 'yearly' ? parseFloat(item.price_yearly) : parseFloat(item.price_monthly),

  getYearlySaving: (item) =>
    parseFloat((parseFloat(item.price_monthly) * 12 - parseFloat(item.price_yearly)).toFixed(2)),

  getTotal: () =>
    get().items
      .filter((i) => i.is_available)
      .reduce((sum, i) => {
        const p = i.cycle === 'yearly' ? parseFloat(i.price_yearly) : parseFloat(i.price_monthly);
        return sum + p;
      }, 0)
      .toFixed(2),

  getCount: () => get().items.length,

  syncWithServer: async () => {
    const items = get().items;
    if (!items.length) return;
    try {
      const cart = {};
      items.forEach((i) => { cart[i.id] = { cycle: i.cycle }; });
      await apiClient.post('/cart/sync.php', { cart });
    } catch {}
  },
}));

export default useCartStore;
