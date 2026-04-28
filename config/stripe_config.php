<?php
// ─── Configuration Stripe ──────────────────────────────────────────────────
// Remplacez ces clés par vos vraies clés Stripe
// Clés de test disponibles sur : https://dashboard.stripe.com/test/apikeys

define('STRIPE_SECRET_KEY',      '');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51QHmSmEPtBD2qtSE8hzTxb36miSYT9Cu1oMzziqxbA0m3ztUZiurVdC1AvvgTrt483rcbNgG5rZEKqfFywygSD8n00EpJR815v');
define('STRIPE_WEBHOOK_SECRET',  'whsec_VOTRE_WEBHOOK_SECRET_ICI');

// URL de base du site (adapter selon votre environnement)
define('SITE_URL', 'http://localhost/Cyna');

// ─── Fonction utilitaire : appel API Stripe via cURL ─────────────────────────
function stripe_request($endpoint, $method = 'GET', $data = []) {
    $url = 'https://api.stripe.com/v1/' . $endpoint;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    // ⚠ Fix SSL pour WAMP en local — à retirer en production
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        throw new RuntimeException("Erreur cURL Stripe : $err");
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException("Réponse Stripe invalide.");
    }

    return $decoded;
}