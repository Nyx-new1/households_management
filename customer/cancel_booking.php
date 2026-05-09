<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $customer_id = $_SESSION['user_id'];

    // Verify booking exists and belongs to user and is pending
    $stmt = $pdo->prepare("SELECT id, status FROM bookings WHERE id = ? AND customer_id = ?");
    $stmt->execute([$booking_id, $customer_id]);
    $booking = $stmt->fetch();

    if ($booking && $booking['status'] === 'pending') {
        // Update status to cancelled
        $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        
        if ($updateStmt->execute([$booking_id])) {
            // Success
            header("Location: booking_details.php?id=" . $booking_id . "&msg=cancelled");
            exit;
        } else {
            // Database error
            header("Location: booking_details.php?id=" . $booking_id . "&error=db_error");
            exit;
        }
    } else {
        // Invalid booking or status
        header("Location: my_bookings.php?error=invalid_booking");
        exit;
    }
} else {
    // Invalid request method
    header("Location: my_bookings.php");
    exit;
}
?>
