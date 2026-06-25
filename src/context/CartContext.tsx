import React, { createContext, useState, useEffect, useCallback, useContext, useMemo, ReactNode } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage'; // using community AsyncStorage
import { CartItem, Product, CycleType } from '../types';

const CART_STORAGE_KEY = 'cart_items';

interface CartContextType {
  items: CartItem[];
  isLoadingCart: boolean;
  addItem: (product: Product, cycle: CycleType) => void;
  removeItem: (productId: number) => void;
  updateQuantity: (productId: number, newQuantity: number) => void;
  getTotal: () => number;
  clearCart: () => void;
}

// On met le contexte à undefined pour forcer l'utilisation du Provider
export const CartContext = createContext<CartContextType | undefined>(undefined);

export const CartProvider = ({ children }: { children: ReactNode }) => {
  const [items, setItems] = useState<CartItem[]>([]);
  const [isLoadingCart, setIsLoadingCart] = useState(true);

  // --- PERSISTANCE : Charger le panier au démarrage ---
  useEffect(() => {
    (async () => {
      try {
        const storedCart = await AsyncStorage.getItem(CART_STORAGE_KEY);
        if (storedCart) {
          setItems(JSON.parse(storedCart));
        }
      } catch (e) {
        console.error('Erreur lors du chargement du panier', e);
      } finally {
        setIsLoadingCart(false);
      }
    })();
  }, []);

  // --- PERSISTANCE : Sauvegarder le panier à chaque modification ---
  useEffect(() => {
    if (!isLoadingCart) {
      AsyncStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items)).catch((e) => 
        console.error('Erreur lors de la sauvegarde du panier', e)
      );
    }
  }, [items, isLoadingCart]);

  // --- LOGIQUE MÉTIER ---
  const addItem = useCallback((product: Product, cycle: CycleType) => {
    setItems((prev) => {
      const existingIndex = prev.findIndex((item) => item.id === product.id);
      
      if (existingIndex !== -1) {
        // L'article existe : on incrémente la quantité et on met à jour le cycle/prix
        const updated = [...prev];
        updated[existingIndex] = {
          ...updated[existingIndex],
          cycle: cycle,
          price: cycle === 'monthly' ? product.price_monthly : product.price_yearly,
          quantity: updated[existingIndex].quantity + 1, // <-- BUG CORRIGÉ ICI
        };
        return updated;
      }
      
      // Nouvel article
      return [
        ...prev,
        {
          id: product.id,
          name: product.name,
          image_path: product.image_path,
          cycle: cycle,
          quantity: 1,
          price: cycle === 'monthly' ? product.price_monthly : product.price_yearly,
        },
      ];
    });
  }, []);

  const removeItem = useCallback((productId: number) => {
    setItems((prev) => prev.filter((item) => item.id !== productId));
  }, []);

  const updateQuantity = useCallback((productId: number, newQuantity: number) => {
    if (newQuantity <= 0) {
      removeItem(productId);
      return;
    }
    setItems((prev) =>
      prev.map((item) =>
        item.id === productId ? { ...item, quantity: newQuantity } : item
      )
    );
  }, [removeItem]);

  const clearCart = useCallback(() => setItems([]), []);

  // Utilisation de useCallback pour que la fonction ne change pas de référence à chaque render
  const getTotal = useCallback(() => {
    return items.reduce((total, item) => total + (item.price * item.quantity), 0);
  }, [items]);

  // Mémorisation de la valeur pour éviter les re-renders inutiles des consommateurs
  const value = useMemo<CartContextType>(() => ({
    items,
    isLoadingCart,
    addItem,
    removeItem,
    updateQuantity,
    getTotal,
    clearCart,
  }), [items, isLoadingCart, addItem, removeItem, updateQuantity, getTotal, clearCart]);

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
};

// --- HOOK PERSONNALISÉ SÉCURISÉ ---
export function useCart() {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error('useCart doit être utilisé à l\'intérieur d\'un CartProvider');
  }
  return context;
}