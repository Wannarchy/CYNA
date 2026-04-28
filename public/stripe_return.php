<?php
// Page de retour après authentification 3D Secure Stripe
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stripe_config.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}

$payment_intent_id = $_GET['payment_intent'] ?? '';
$order_id          = $_SESSION['pending_order_id'] ?? 0;

if (empty($payment_intent_id)) {
    header('Location: panier.php');
    exit;
}

try {
    // Vérifier le statut du PaymentIntent
    $pi = stripe_request('payment_intents/' . $payment_intent_id);

    if (isset($pi['error'])) {
        error_log("Stripe return error: " . ($pi['error']['message'] ?? 'unknown'));
        header('Location: checkout.php?error=payment_failed');
        exit;
    }

    $status = $pi['status'];

    if ($status === 'succeeded') {
        // Mettre à jour la commande en base
        if ($order_id > 0) {
            $connexion->prepare("UPDATE orders SET status='paid' WHERE id=? AND user_id=?")
                      ->execute([$order_id, $_SESSION['utilisateur_id']]);
        }
        unset($_SESSION['cart'], $_SESSION['pending_order_id']);
        header("Location: confirmation.php?order_id=$order_id");
        exit;
    }

    // Paiement échoué ou annulé
    if ($order_id > 0) {
        $connexion->prepare("UPDATE orders SET status='failed' WHERE id=? AND user_id=?")
                  ->execute([$order_id, $_SESSION['utilisateur_id']]);
    }
    unset($_SESSION['pending_order_id']);
    $_SESSION['checkout_errors'] = ["Paiement non abouti. Veuillez réessayer."];
    header('Location: checkout.php');
    exit;

} catch (RuntimeException $e) {
    error_log("Stripe return exception: " . $e->getMessage());
    header('Location: checkout.php?error=stripe_error');
    exit;
}