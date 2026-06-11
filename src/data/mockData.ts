// src/data/mockData.ts
import { HomeSlide, HomeContent, Category, Product } from '../types';

export const mockSlides: HomeSlide[] = [
  {
    id: 1,
    title: 'Sécurisez votre infrastructure',
    subtitle: 'SOC, EDR, XDR — déploiement en 24h',
    image_path: 'https://via.placeholder.com/800x400/0056b3/ffffff?text=SOC+-+EDR',
    link_url: 'public/catalogue.php',
    sort_order: 1,
    is_active: true,
  },
  {
    id: 2,
    title: 'Surveillance 24/7',
    subtitle: 'Nos experts SOC veillent sur vos systèmes en permanence',
    image_path: 'https://via.placeholder.com/800x400/333333/ffffff?text=XDR+-+24/7',
    link_url: 'public/catalogue.php?category_id=1',
    sort_order: 2,
    is_active: true,
  },
  {
    id: 3,
    title: 'Protéger vos données',
    subtitle: 'SOC, EDR - Déploiement rapide',
    image_path: 'https://via.placeholder.com/800x400/28a745/ffffff?text=Audit',
    link_url: 'public/catalogue.php',
    sort_order: 3,
    is_active: true,
  },
];

export const mockHomeContent: HomeContent = {
  id: 1,
  content_text: 'CYNA propose des solutions SaaS de cybersécurité pour les entreprises : SOC managé, EDR, XDR et bien plus. Déploiement rapide, supervision 24/7 et conformité renforcé.',
};

export const mockCategories: Category[] = [
  { id: 1, name: 'SOC & Surveillance', image_path: 'https://via.placeholder.com/150/0056b3/ffffff?text=SOC', sort_order: 1, is_active: true },
  { id: 2, name: 'EDR & Endpoints', image_path: 'https://via.placeholder.com/150/dc3545/ffffff?text=EDR', sort_order: 2, is_active: true },
  { id: 5, name: 'XDR & Corrélation', image_path: 'https://via.placeholder.com/150/ffc107/333333?text=XDR', sort_order: 3, is_active: true },
  { id: 4, name: 'Conformité & Audit', image_path: 'https://via.placeholder.com/150/17a2b8/ffffff?text=Audit', sort_order: 4, is_active: true },
];

// J'ajoute aussi les produits "Top" pour la page d'accueil
export const mockTopProducts: Product[] = [
  { id: 1, category_id: 1, name: 'SOC Starter', image_path: 'https://via.placeholder.com/150/0056b3/ffffff?text=SOC1', price_monthly: 299, price_yearly: 2990, is_available: true, is_featured: true, featured_order: 1, created_at: '' },
  { id: 2, category_id: 1, name: 'SOC Business', image_path: 'https://via.placeholder.com/150/0056b3/ffffff?text=SOC2', price_monthly: 599, price_yearly: 5990, is_available: true, is_featured: true, featured_order: 2, created_at: '' },
  { id: 4, category_id: 2, name: 'EDR Protect', image_path: 'https://via.placeholder.com/150/dc3545/ffffff?text=EDR1', price_monthly: 149, price_yearly: 1490, is_available: true, is_featured: true, featured_order: 4, created_at: '' },
  { id: 6, category_id: null, name: 'XDR Core', image_path: 'https://via.placeholder.com/150/ffc107/333333?text=XDR1', price_monthly: 499, price_yearly: 4990, is_available: true, is_featured: true, featured_order: 3, created_at: '' },
];
// Ajout pour tester l'affichage
export const mockAllProducts: Product[] = [
  ...mockTopProducts, // On récupère les 4 produits précédents
  { id: 3, category_id: 1, name: 'SOC Enterprise', image_path: 'https://via.placeholder.com/150/0056b3/ffffff?text=SOC3', price_monthly: 1299, price_yearly: 12990, is_available: false, is_featured: false, featured_order: 3, created_at: '' }, // INDISPONIBLE
  { id: 5, category_id: 2, name: 'EDR Advanced', image_path: 'https://via.placeholder.com/150/dc3545/ffffff?text=EDR2', price_monthly: 349, price_yearly: 3490, is_available: true, is_featured: false, featured_order: 5, created_at: '' },
  { id: 7, category_id: null, name: 'XDR Premium', image_path: 'https://via.placeholder.com/150/ffc107/333333?text=XDR2', price_monthly: 899, price_yearly: 8990, is_available: true, is_featured: false, featured_order: 6, created_at: '' },
  { id: 8, category_id: 4, name: 'Audit Conformité', image_path: 'https://via.placeholder.com/150/17a2b8/ffffff?text=Audit', price_monthly: 199, price_yearly: 1990, is_available: true, is_featured: false, featured_order: 7, created_at: '' },
];
// --- MOCK ORDERS & ADDRESSES ---

export interface MockOrderItem {
  id: number;
  product_name: string;
  cycle: 'monthly' | 'yearly';
  price: number;
}

export interface MockOrder {
  id: number;
  created_at: string;
  total: number;
  status: 'paid' | 'pending';
  card_last4: string | null;
  billing_address: string;
  items: MockOrderItem[];
}

export interface MockAddress {
  id: number;
  label: string;
  prenom: string;
  nom: string;
  adresse1: string;
  ville: string;
  code_postal: string;
  is_default: boolean;
}

export const mockOrders: MockOrder[] = [
  {
    id: 16,
    created_at: '2026-04-28T14:17:25.000000Z',
    total: 349.00,
    status: 'paid',
    card_last4: '4242',
    billing_address: 'Aksel MEKCHICHE, 24 avenue foussene, 75008 Paris',
    items: [{ id: 16, product_name: 'EDR Advanced', cycle: 'yearly', price: 3490.00 }]
  },
  {
    id: 15,
    created_at: '2026-04-28T13:13:54.000000Z',
    total: 349.00,
    status: 'paid',
    card_last4: '4242',
    billing_address: 'Teo Rebelo, 24 Avenue gambetta, 75001 Paris',
    items: [{ id: 15, product_name: 'EDR Advanced', cycle: 'monthly', price: 349.00 }]
  },
  {
    id: 1,
    created_at: '2025-11-30T12:30:25.000000Z',
    total: 599.00,
    status: 'pending',
    card_last4: '0002',
    billing_address: 'o minho, ssss, 75002 Paris',
    items: [{ id: 1, product_name: 'SOC Business', cycle: 'monthly', price: 599.00 }]
  }
];

export const mockAddresses: MockAddress[] = [
  { id: 1, label: 'Bureau', prenom: 'Teo', nom: 'Rebelo', adresse1: '24 Avenue gambetta', ville: 'Paris', code_postal: '75001', is_default: true },
  { id: 2, label: 'Personnel', prenom: 'Teo', nom: 'Rebelo', adresse1: '10 Rue de la paix', ville: 'Lyon', code_postal: '69001', is_default: false }
];