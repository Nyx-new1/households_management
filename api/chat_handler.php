<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'not_authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'send') {
    $booking_id = $_POST['booking_id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    
    if (!$booking_id || !$message) {
        echo json_encode(['error' => 'missing_params']);
        exit;
    }

    // Verify user is part of this booking
    $stmt = $pdo->prepare("SELECT customer_id, provider_id FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking || ($booking['customer_id'] != $user_id && $booking['provider_id'] != $user_id)) {
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
    
    $receiver_id = ($booking['customer_id'] == $user_id) ? $booking['provider_id'] : $booking['customer_id'];
    
    if (!$receiver_id) {
        echo json_encode(['error' => 'no_provider_assigned']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, booking_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $booking_id, $message]);
        
        // Also trigger a general notification
        $sender_name = $_SESSION['user_name'];
        $notif_msg = "New message from $sender_name regarding Booking #$booking_id";
        $link = "chat.php?booking_id=$booking_id";
        
        // Check if a notification already exists for this chat to avoid spamming
        $checkNotif = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND message LIKE ? AND is_read = 0");
        $checkNotif->execute([$receiver_id, "%regarding Booking #$booking_id"]);
        if (!$checkNotif->fetch()) {
             $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$receiver_id, $notif_msg, $link]);
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'db_error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'fetch') {
    $booking_id = $_GET['booking_id'] ?? null;
    $last_id = $_GET['last_id'] ?? 0;

    if (!$booking_id) {
        echo json_encode(['error' => 'missing_booking_id']);
        exit;
    }
    
    // Fetch new messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.name as sender_name, u.profile_picture as sender_photo 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.booking_id = ? AND m.id > ? 
        ORDER BY m.id ASC
    ");
    $stmt->execute([$booking_id, $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch participants photos for header/bubbles
    $photos = $pdo->prepare("
        SELECT id, name, profile_picture FROM users 
        WHERE id IN (SELECT customer_id FROM bookings WHERE id = ?) 
        OR id IN (SELECT provider_id FROM bookings WHERE id = ?)
    ");
    $photos->execute([$booking_id, $booking_id]);
    $participants = $photos->fetchAll(PDO::FETCH_ASSOC);

    // Mark as read for the receiver
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE booking_id = ? AND receiver_id = ? AND is_read = 0")->execute([$booking_id, $user_id]);

    echo json_encode(['messages' => $messages, 'participants' => $participants]);
    exit;
}

echo json_encode(['error' => 'invalid_action']);
?>
