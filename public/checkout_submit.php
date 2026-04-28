<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stripe_config.php';
require_once __DIR__ . '/../includes/cart_repository.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── Email confirmation commande ───────────────────────────────────────────────
function envoyer_email_commande($email, $prenom, $order_id, $items, $total, $billing_name) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreplycyna@gmail.com';
        $mail->Password   = 'uaws jfaf jqal cahx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('noreply@cyna.com', 'CYNA Sécurité');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Confirmation commande #' . str_pad($order_id, 6, '0', STR_PAD_LEFT);

        $items_html   = '';
        foreach ($items as $it) {
            $cycle       = $it['cycle'] === 'yearly' ? 'Annuel' : 'Mensuel';
            $items_html .= "<tr>
                <td style='padding:10px;border-bottom:1px solid #eee'>" . htmlspecialchars($it['name']) . "</td>
                <td style='padding:10px;border-bottom:1px solid #eee;text-align:center'>" . $cycle . "</td>
                <td style='padding:10px;border-bottom:1px solid #eee;text-align:right'>" . number_format($it['unit_price'], 2, ',', ' ') . " €</td>
            </tr>";
        }

        $num  = str_pad($order_id, 6, '0', STR_PAD_LEFT);
        $ttl  = number_format($total, 2, ',', ' ');
        $lien = 'http://localhost/Cyna/public/mes-commandes.php';

        $mail->Body = "<html><body style='font-family:Arial,sans-serif;background:#f5f5f5;padding:20px'>
            <div style='max-width:600px;margin:auto;background:#fff;border-radius:12px;overflow:hidden'>
                <div style='background:linear-gradient(135deg,#1a2980,#26d0ce);padding:28px;text-align:center'>
                    <h1 style='color:#fff;margin:0;font-size:22px'>CYNA</h1>
                </div>
                <div style='padding:28px'>
                    <h2 style='color:#1a2980;margin-top:0'>✅ Commande confirmée !</h2>
                    <p>Bonjour <strong>" . htmlspecialchars($prenom) . "</strong>, merci pour votre commande.</p>
                    <div style='background:#f8f9ff;border:1px solid #e0e4ff;border-radius:8px;padding:14px;margin:16px 0'>
                        <p style='margin:0;font-size:12px;color:#666'>Numéro de commande</p>
                        <p style='margin:4px 0 0;font-size:20px;font-weight:800;color:#1a2980'>#$num</p>
                    </div>
                    <table style='width:100%;border-collapse:collapse'>
                        <thead><tr style='background:#f5f5f5'>
                            <th style='padding:8px;text-align:left;font-size:11px;color:#888'>Service</th>
                            <th style='padding:8px;text-align:center;font-size:11px;color:#888'>Cycle</th>
                            <th style='padding:8px;text-align:right;font-size:11px;color:#888'>Prix</th>
                        </tr></thead>
                        <tbody>$items_html</tbody>
                        <tfoot><tr>
                            <td colspan='2' style='padding:10px;text-align:right;font-weight:700'>Total</td>
                            <td style='padding:10px;text-align:right;font-weight:800;color:#1a2980;font-size:16px'>$ttl €</td>
                        </tr></tfoot>
                    </table>
                    <div style='text-align:center;margin:24px 0'>
                        <a href='$lien' style='background:linear-gradient(135deg,#1a2980,#26d0ce);color:#fff;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:700'>Voir mes commandes</a>
                    </div>
                </div>
                <div style='background:#f8f9ff;padding:16px;text-align:center;border-top:1px solid #eee'>
                    <p style='color:#888;font-size:11px;margin:0'>CYNA-IT — 10 Rue de Penthièvre, 75008 Paris</p>
                </div>
            </div>
        </body></html>";

        $mail->AltBody = "Commande #$num confirmée. Total : $ttl €";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur email commande : " . $mail->ErrorInfo);
        return false;
    }
}

// ─────────────────────────────────────────────────────────────────────────────

if (!isset($_SESSION['utilisateur_id'])) { header('Location: connexion.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: panier.php'); exit; }

$cart  = $_SESSION['cart'] ?? [];
$items = cart_get_products($connexion, $cart);
$total = cart_total($items);

if (count($items) === 0) { header('Location: panier.php'); exit; }

$billing_name    = trim($_POST['billing_name']    ?? '');
$billing_address = trim($_POST['billing_address'] ?? '');
$card_holder     = trim($_POST['card_holder']     ?? '');
$stripe_token    = trim($_POST['stripe_token']    ?? '');
$card_last4      = preg_replace('/\D/', '', $_POST['card_last4'] ?? '');
$saved_card_id   = (int)($_POST['saved_card_id']  ?? 0);

$errors = [];
if (empty($billing_name))    $errors[] = "Nom de facturation requis.";
if (empty($billing_address)) $errors[] = "Adresse de facturation requise.";

// Si carte enregistrée sélectionnée → charger ses infos
$saved_card = null;
if ($saved_card_id > 0) {
    try {
        $sc = $connexion->prepare("SELECT * FROM user_payment_methods WHERE id=? AND user_id=?");
        $sc->execute([$saved_card_id, $_SESSION['utilisateur_id']]);
        $saved_card = $sc->fetch();
        if ($saved_card) {
            $card_last4  = $saved_card['card_last4'];
            $card_holder = $saved_card['card_holder'];
        } else {
            $errors[] = "Carte introuvable.";
        }
    } catch (Exception $e) {
        $errors[] = "Erreur carte enregistrée.";
    }
} else {
    // Nouvelle carte → token requis
    if (empty($stripe_token)) $errors[] = "Informations de carte manquantes.";
}

if (!empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    header('Location: checkout.php');
    exit;
}

// Charger infos user
$u = $connexion->prepare("SELECT email, prenom FROM utilisateurs WHERE id=?");
$u->execute([$_SESSION['utilisateur_id']]);
$udata = $u->fetch();

// ── Appel Stripe ──────────────────────────────────────────────────────────────
try {
    // Si carte enregistrée → on utilise un token fictif pour la démo
    // En prod il faudrait stocker le payment_method_id Stripe
    if ($saved_card) {
        // Mode carte enregistrée : on simule le paiement (pas de vrai token)
        // En production : utiliser le customer_id et payment_method Stripe
        $connexion->beginTransaction();
        $stmt = $connexion->prepare("INSERT INTO orders (user_id, total, billing_name, billing_address, card_last4, status) VALUES (?,?,?,?,?,'paid')");
        $stmt->execute([$_SESSION['utilisateur_id'], $total, $billing_name, $billing_address, $card_last4]);
        $order_id = $connexion->lastInsertId();
        $stmtItem = $connexion->prepare("INSERT INTO order_items (order_id, product_id, cycle, price) VALUES (?,?,?,?)");
        foreach ($items as $it) {
            if (!$it['is_available']) continue;
            $stmtItem->execute([$order_id, $it['id'], $it['cycle'], $it['unit_price']]);
        }
        $connexion->commit();
        envoyer_email_commande($udata['email'], $udata['prenom'], $order_id, $items, $total, $billing_name);
        unset($_SESSION['cart']);
        header("Location: confirmation.php?order_id=$order_id");
        exit;
    }

    // Créer le PaymentIntent avec le TOKEN Stripe (nouvelle carte)
    $pi = stripe_request('payment_intents', 'POST', [
        'amount'                    => (int)round($total * 100),
        'currency'                  => 'eur',
        'payment_method_data[type]'              => 'card',
        'payment_method_data[card][token]'       => $stripe_token,
        'payment_method_data[billing_details][name]' => $card_holder,
        'confirm'                   => 'true',
        'return_url'                => SITE_URL . '/public/stripe_return.php',
        'description'               => 'Commande CYNA - ' . $billing_name,
        'metadata[user_id]'         => $_SESSION['utilisateur_id'],
    ]);

    error_log("[STRIPE] PI response: " . json_encode($pi));

    if (isset($pi['error'])) {
        $msg = urlencode($pi['error']['message'] ?? "Paiement refusé.");
        header("Location: paiement_refuse.php?raison=$msg");
        exit;
    }

    $status = $pi['status'];

    // Statut commande
    if ($status === 'succeeded') {
        $order_status = 'paid';
    } elseif ($status === 'requires_action') {
        $order_status = 'pending_3ds';
    } elseif ($status === 'requires_payment_method') {
        $order_status = 'failed';
    } else {
        $order_status = 'pending';
    }

    // 3. Enregistrer commande
    $connexion->beginTransaction();
    $stmt = $connexion->prepare("INSERT INTO orders (user_id, total, billing_name, billing_address, stripe_payment_intent, card_last4, status) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$_SESSION['utilisateur_id'], $total, $billing_name, $billing_address, $pi['id'], $card_last4, $order_status]);
    $order_id = $connexion->lastInsertId();

    $stmtItem = $connexion->prepare("INSERT INTO order_items (order_id, product_id, cycle, price) VALUES (?,?,?,?)");
    foreach ($items as $it) {
        if (!$it['is_available']) continue;
        $stmtItem->execute([$order_id, $it['id'], $it['cycle'], $it['unit_price']]);
    }
    $connexion->commit();

    // 4. Résultat

    // 3D Secure
    if ($status === 'requires_action' && isset($pi['next_action']['redirect_to_url']['url'])) {
        $_SESSION['pending_order_id'] = $order_id;
        header('Location: ' . $pi['next_action']['redirect_to_url']['url']);
        exit;
    }

    // Refusé par la banque
    if ($status === 'requires_payment_method' || $status === 'canceled') {
        $connexion->prepare("UPDATE orders SET status='failed' WHERE id=?")->execute([$order_id]);
        header("Location: paiement_refuse.php?raison=" . urlencode("Paiement refusé par votre banque."));
        exit;
    }

    // Succès
    unset($_SESSION['cart']);
    envoyer_email_commande($udata['email'], $udata['prenom'], $order_id, $items, $total, $billing_name);
    header("Location: confirmation.php?order_id=$order_id");
    exit;

} catch (RuntimeException $e) {

    // Stripe injoignable → mode fallback test
    error_log("[STRIPE] Exception: " . $e->getMessage());

    try {
        if ($connexion->inTransaction()) $connexion->rollBack();

        $connexion->beginTransaction();
        $stmt = $connexion->prepare("INSERT INTO orders (user_id, total, billing_name, billing_address, card_last4, status) VALUES (?,?,?,?,?,'test')");
        $stmt->execute([$_SESSION['utilisateur_id'], $total, $billing_name, $billing_address, $card_last4]);
        $order_id = $connexion->lastInsertId();

        $stmtItem = $connexion->prepare("INSERT INTO order_items (order_id, product_id, cycle, price) VALUES (?,?,?,?)");
        foreach ($items as $it) {
            if (!$it['is_available']) continue;
            $stmtItem->execute([$order_id, $it['id'], $it['cycle'], $it['unit_price']]);
        }
        $connexion->commit();

        envoyer_email_commande($udata['email'], $udata['prenom'], $order_id, $items, $total, $billing_name);
        unset($_SESSION['cart']);
        header("Location: confirmation.php?order_id=$order_id");
        exit;

    } catch (Exception $e2) {
        if ($connexion->inTransaction()) $connexion->rollBack();
        die("Erreur critique : " . htmlspecialchars($e2->getMessage()));
    }
}