<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}
require_once '../config/db_connect.php';

if (!isset($_GET['booking_id'])) {
    header("Location: my_bookings.php");
    exit;
}

$booking_id = $_GET['booking_id'];
$customer_id = $_SESSION['user_id'];

// Verify booking belongs to user and is completed
$stmt = $pdo->prepare("
    SELECT b.*, s.name as service_name, u.name as provider_name 
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    LEFT JOIN users u ON b.provider_id = u.id 
    WHERE b.id = ? AND b.customer_id = ? AND b.status = 'completed'
");
$stmt->execute([$booking_id, $customer_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Invalid booking or not eligible for review.");
}

// Check if already reviewed
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ?");
$stmt->execute([$booking_id]);
if ($stmt->fetch()) {
    header("Location: my_bookings.php?message=already_reviewed");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Service - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .review-form {
            max-width: 500px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
        }
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 2rem;
            color: #d1d5db;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #fbbf24;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo">HomeServe</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="my_bookings.php">My Bookings</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container section">
        <div class="review-form">
            <h2 style="margin-bottom: 1.5rem;">Rate & Review</h2>
            <p style="margin-bottom: 1.5rem; color: var(--text-light);">
                How was your <strong><?php echo htmlspecialchars($booking['service_name']); ?></strong> service?
            </p>

            <form action="submit_review.php" method="POST">
                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                
                <div class="form-group">
                    <label class="form-label">Rating</label>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" required>
                        <label for="star5" title="5 stars">&#9733;</label>
                        <input type="radio" id="star4" name="rating" value="4">
                        <label for="star4" title="4 stars">&#9733;</label>
                        <input type="radio" id="star3" name="rating" value="3">
                        <label for="star3" title="3 stars">&#9733;</label>
                        <input type="radio" id="star2" name="rating" value="2">
                        <label for="star2" title="2 stars">&#9733;</label>
                        <input type="radio" id="star1" name="rating" value="1">
                        <label for="star1" title="1 star">&#9733;</label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Comment</label>
                    <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Review</button>
            </form>
        </div>
    </div>
</body>
</html>
