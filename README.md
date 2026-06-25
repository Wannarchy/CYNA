🛡️ CYNA Mobile - Application de Cybersécurité SaaS
Application mobile React Native (Expo) pour la plateforme CYNA. Elle permet aux clients de souscrire à des services de cybersécurité (SOC, EDR, XDR), gérer leurs abonnements, leur profil et accéder à un support via un chatbot.

- Stack Technique
Framework : React Native (Expo SDK 54)
Langage : TypeScript
Navigation : React Navigation v7 (Stack & Bottom Tabs)
Gestion des données : TanStack React Query (cache serveur) + Context API (state local)
Requêtes HTTP : Axios
Stockage local : AsyncStorage (Token JWT, Panier, Cache)
Backend API : Laravel ( hébergé sur Render)
- Prérequis
Assure-toi d'avoir installé sur ta machine :

Node.js (version 18 ou supérieure recommandée)
npm ou yarn
L'application Expo Go sur ton smartphone physique (iOS ou Android) pour tester facilement.
(Optionnel) Android Studio si tu veux utiliser l'émulateur Android.
- Installation et Lancement
Cloner le dépôt (si hébergé sur GitHub) ou extraire les fichiers du projet.
git clone https://github.com/ton-utilisateur/cyna-mobile.gitcd cyna-mobile
Installer les dépendances
bash

npm install
Configurer l'API
Par défaut, l'application pointe vers l'API Laravel distante. Vérifie dans src/services/api.ts que l'URL correspond bien à ton backend :
typescript

const API_BASE_URL = 'https://laravel-api-1-zb19.onrender.com/api';
Lancer l'application
bash

npx expo start --tunnel
(Note : L'option --tunnel est recommandée pour bypasser les pare-feu locaux et faire fonctionner l'app sur ton téléphone physique sans être sur le même réseau Wi-Fi que ton ordinateur).
Ouvrir l'application
Scanne le QR code affiché dans le terminal avec l'application Expo Go (sur ton téléphone).
Ou appuie sur a dans le terminal pour lancer l'émulateur Android.
Ou appuie sur w pour lancer la version Web (attention au CORS, voir ci-dessous).
- Architecture du Projet
Le code source est organisé de manière modulaire dans le dossier src/ :

text

src/
├── components/       # Composants UI réutilisables (Header, SkeletonBox, etc.)
├── context/          # Contextes React (AuthContext, CartContext)
├── navigation/       # Configuration des routeurs (AppNavigator.tsx)
├── screens/          # Écrans de l'application (HomeScreen, ProductScreen, etc.)
├── services/         # Configuration Axios (api.ts) et logique d'appel API
├── types/            # Définitions des interfaces TypeScript (Product, User, etc.)
└── data/             # Données mockées (si besoin pour les tests)
- Troubleshooting (Problèmes connus)
1. Erreur 401 Unauthorized ou connexion impossible
Sur le Web : C'est un problème de CORS. Les navigateurs bloquent les requêtes vers un autre domaine. Teste plutôt sur mobile avec Expo Go, ou configure le fichier config/cors.php de ton Laravel pour autoriser ['*'].
Serveur Render endormi : Si l'API est sur Render en version gratuite, elle se met en veille. Ouvre l'URL de l'API dans ton navigateur pour la "réveiller" avant de lancer l'app.
Compte non vérifié : Laravel peut bloquer la connexion si l'email n'a pas été vérifié. Vérifie ta base de données (colonne email_verified_at).
2. L'émulateur Android plante au démarrage
Si l'émulateur affiche une erreur ou refuse de s'ouvrir :

Ouvre Android Studio > Device Manager.
Clique sur les trois points (⋮) à côté de ton émulateur.
Sélectionne Wipe Data (Effacer les données).
Relance l'émulateur, puis appuie sur a dans le terminal Expo.
3. Les listes (Produits, Catégories) sont vides
Vérifie la réponse de ton API dans Postman. Si Laravel renvoie les données sous forme de pagination ou de ressource (ex: { "data": [...] }), assure-toi que le code de l'écran "déballe" bien ces données :

typescript

const rawProducts = response?.data ?? response;
const products = Array.isArray(rawProducts) ? rawProducts : [];
- Dépendances Principales
axios
@tanstack/react-query
@react-navigation/native, @react-navigation/bottom-tabs, @react-navigation/native-stack
@react-native-async-storage/async-storage
expo-status-bar, expo-file-system, expo-sharing
@expo/vector-icons