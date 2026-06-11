export type CycleType = 'monthly' | 'yearly';

export interface CartItem {
  id: number;
  name: string;
  image_path: string; // AJOUT
  cycle: CycleType;
  quantity: number;
  price: number;
}

// --- NOUVEAUX TYPES POUR L'ACCUEIL ---

export interface HomeSlide {
  id: number;
  title: string;
  subtitle: string | null;
  image_path: string;
  link_url: string | null;
  sort_order: number;
  is_active: boolean;
}

export interface HomeContent {
  id: number;
  content_text: string;
}

export interface Category {
  id: number;
  name: string;
  image_path: string;
  sort_order: number;
  is_active: boolean;
}

export interface Product {
  id: number;
  category_id: number | null;
  name: string;
  image_path: string;
  price_monthly: number;
  price_yearly: number;
  is_available: boolean;
  is_featured: boolean;
  featured_order: number;
  created_at: string;
}

export interface User {
  id: number;
  prenom: string;
  nom: string;
  email: string;
  est_confirme: boolean;
  is_admin: boolean;
  est_actif: boolean;
  date_inscription: string;
  derniere_connexion: string | null;
}