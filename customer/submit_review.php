<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    // Server-side validation
    if ($rating < 1 || $rating > 5) {
        die("Invalid rating");
    }

    try {
        // Verify booking belongs to user and is completed
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND customer_id = ? AND status = 'completed'");
        $stmt->execute([$booking_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            die("Invalid booking or not completed");
        }

        // Check if already reviewed
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        if ($stmt->fetch()) {
            die("Booking already reviewed");
        }

        // Insert review
        $stmt = $pdo->prepare("INSERT INTO reviews (booking_id, rating, comment) VALUES (?, ?, ?)");
        $stmt->execute([$booking_id, $rating, $comment]);

        header("Location: my_bookings.php?success=review_submitted");
        exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: my_bookings.php");
    exit;
}
?>
