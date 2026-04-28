<?php
// Webhook Stripe — à configurer dans le dashboard Stripe
// URL à renseigner : https://votre-domaine.com/public/stripe_webhook.php
// Événements à écouter : payment_intent.succeeded, payment_intent.payment_failed

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stripe_config.php';

// Lire le payload brut
$payload   = file_get_contents('php://input');
$sig       = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Vérifier la signature webhook
if (!empty(STRIPE_WEBHOOK_SECRET) && STRIPE_WEBHOOK_SECRET !== 'whsec_VOTRE_WEBHOOK_SECRET_ICI') {
    $tolerance = 300; // 5 minutes
    $parts     = [];
    foreach (explode(',', $sig) as $part) {
        [$k, $v] = explode('=', $part, 2);
        $parts[$k][] = $v;
    }
    $timestamp = (int)($parts['t'][0] ?? 0);

    if (abs(time() - $timestamp) > $tolerance) {
        http_response_code(400);
        die("Timestamp trop ancien.");
    }

    $signed   = $timestamp . '.' . $payload;
    $expected = hash_hmac('sha256', $signed, STRIPE_WEBHOOK_SECRET);
    $received = $parts['v1'][0] ?? '';

    if (!hash_equals($expected, $received)) {
        http_response_code(400);
        die("Signature invalide.");
    }
}

$event = json_decode($payload, true);
if (!$event) {
    http_response_code(400);
    die("Payload invalide.");
}

$type   = $event['type'] ?? '';
$object = $event['data']['object'] ?? [];
$pi_id  = $object['id'] ?? '';

switch ($type) {

    case 'payment_intent.succeeded':
        // Marquer la commande comme payée
        if ($pi_id) {
            $connexion->prepare("UPDATE orders SET status='paid' WHERE stripe_payment_intent=?")
                      ->execute([$pi_id]);
        }
        break;

    case 'payment_intent.payment_failed':
        // Marquer la commande comme échouée
        if ($pi_id) {
            $connexion->prepare("UPDATE orders SET status='failed' WHERE stripe_payment_intent=?")
                      ->execute([$pi_id]);
        }
        break;

    case 'charge.refunded':
        // Optionnel : gérer les remboursements
        $charge_pi = $object['payment_intent'] ?? '';
        if ($charge_pi) {
            $connexion->prepare("UPDATE orders SET status='refunded' WHERE stripe_payment_intent=?")
                      ->execute([$charge_pi]);
        }
        break;

    default:
        // Événement ignoré
        break;
}

http_response_code(200);
echo json_encode(['received' => true]);