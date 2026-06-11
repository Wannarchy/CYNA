import React, { createContext, useState, ReactNode } from 'react';
import { CartItem, Product, CycleType } from '../types';

interface CartContextType {
  items: CartItem[];
  addItem: (product: Product, cycle: CycleType) => void;
  removeItem: (productId: number) => void;
  updateQuantity: (productId: number, newQuantity: number) => void; // AJOUT
  getTotal: () => number;
  clearCart: () => void;
}

export const CartContext = createContext<CartContextType>({
  items: [],
  addItem: () => {},
  removeItem: () => {},
  updateQuantity: () => {}, // AJOUT ICI AUSSI
  getTotal: () => 0,
  clearCart: () => {},
});

export const CartProvider = ({ children }: { children: ReactNode }) => {
  const [items, setItems] = useState<CartItem[]>([]);

  const addItem = (product: Product, cycle: CycleType) => {
    setItems((prev) => {
      const existingIndex = prev.findIndex((item) => item.id === product.id);
      if (existingIndex !== -1) {
        const updated = [...prev];
        updated[existingIndex].cycle = cycle;
        updated[existingIndex].price = cycle === 'monthly' ? product.price_monthly : product.price_yearly;
        return updated;
      }
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
  };

  const removeItem = (productId: number) => {
    setItems((prev) => prev.filter((item) => item.id !== productId));
  };

  // FONCTION AJOUTÉE
  const updateQuantity = (productId: number, newQuantity: number) => {
    if (newQuantity <= 0) {
      removeItem(productId);
      return;
    }
    setItems((prev) =>
      prev.map((item) =>
        item.id === productId ? { ...item, quantity: newQuantity } : item
      )
    );
  };

  const getTotal = () => {
    // Calcul : Prix unitaire * Quantité
    return items.reduce((total, item) => total + (item.price * item.quantity), 0);
  };

  const clearCart = () => setItems([]);

  return (
    // AJOUT DE updateQuantity DANS LE PROVIDER
    <CartContext.Provider value={{ items, addItem, removeItem, updateQuantity, getTotal, clearCart }}>
      {children}
    </CartContext.Provider>
  );
};