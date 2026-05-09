<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}
require_once '../config/db_connect.php';
require_once '../includes/language.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'];
    $amount = $_POST['amount'];
    $transaction_id = 'TXN_' . strtoupper(bin2hex(random_bytes(4)));
    $status = 'completed';

    $method_names = [
        'card' => 'Credit/Debit Card',
        'mpesa' => 'Vodacom M-Pesa',
        'airtel' => 'Airtel Money',
        'tpesa' => 'TTCL T-Pesa',
        'halopesa' => 'Halopesa',
        'mixx' => 'Mixx by Yas',
        'cash' => 'Cash on Completion'
    ];
    $method_display = $method_names[$method] ?? ucfirst($method);

    try {
        $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, method, status, transaction_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$booking_id, $amount, $method_display, $status, $transaction_id]);

        // Notify Provider
        $b = $pdo->prepare("SELECT provider_id, exchange_rate FROM bookings WHERE id = ?"); $b->execute([$booking_id]); 
        $booking_data = $b->fetch();
        $pid = $booking_data['provider_id'];
        $ex_rate = $booking_data['exchange_rate'] ?? $global_exchange_rate;
        if ($pid) {
            $msg = "Payment of " . formatPrice($amount, $ex_rate) . " received for Job #$booking_id.";
            $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, 'dashboard.php')")->execute([$pid, $msg]);
        }

        // Optionally update booking status if needed, but 'completed' status usually refers to service completion
        // We might want to mark it as 'confirmed' if it was pending, but the logic says 'accepted' bookings are paid
        
        $_SESSION['last_payment'] = [
            'booking_id' => $booking_id,
            'amount' => $amount,
            'transaction_id' => $transaction_id,
            'method' => $method_display,
            'exchange_rate' => $ex_rate
        ];
        
        header("Location: payment_success.php");
        exit;
    } catch (PDOException $e) {
        die("Error processing payment: " . $e->getMessage());
    }
} else {
    header("Location: my_bookings.php");
    exit;
}
?>
