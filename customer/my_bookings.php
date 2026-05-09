<?php
// C:\Users\THOMAS\.gemini\antigravity\scratch\household_services_platform\customer\my_bookings.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}
require_once '../includes/language.php';
require_once '../config/db_connect.php';

$customer_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT b.*, s.name as service_name, s.base_price,
    (SELECT COUNT(*) FROM payments p WHERE p.booking_id = b.id AND p.status = 'completed') as is_paid,
    (SELECT COUNT(*) FROM reviews r WHERE r.booking_id = b.id) as has_review
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    WHERE b.customer_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$customer_id]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .badge-pending { background: #fee2e2; color: #991b1b; }
        .badge-accepted { background: #fef3c7; color: #92400e; }
        .badge-in_progress { background: #dbeafe; color: #1e40af; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #f3f4f6; color: #374151; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { font-weight: 600; color: var(--text-light); }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo">HomeServe</a>
            <div class="nav-links">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-right: 1rem;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; background: #e5e7eb;">
                            <?php if(isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])): ?>
                                <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle" style="font-size: 32px; color: #9ca3af;"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="book.php" class="btn btn-primary">New Booking</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container section">
        <div class="card" style="width: 100%;">
            <h2 style="margin-bottom: 1.5rem;">My Bookings</h2>

            <?php if(count($bookings) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['service_name']); ?></strong>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($booking['scheduled_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatPrice($booking['total_price'], $booking['exchange_rate'] ?? null); ?></td>
                                    <td>
                                        <?php if ($booking['status'] == 'completed' && !$booking['has_review']): ?>
                                            <a href="review_booking.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Rate & Review</a>
                                        <?php elseif (($booking['status'] == 'accepted' || $booking['status'] == 'in_progress' || $booking['status'] == 'completed') && !$booking['is_paid']): ?>
                                            <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; background-color: var(--success-color, #10b981);">Pay Now</a>
                                        <?php elseif ($booking['status'] == 'pending'): ?>
                                            <span style="color: #6b7280; font-size: 0.875rem;">Waiting for acceptance</span>
                                        <?php elseif ($booking['status'] == 'cancelled'): ?>
                                            <span style="color: #ef4444; font-size: 0.875rem;">Cancelled</span>
                                        <?php else: ?>
                                            <a href="booking_details.php?id=<?php echo $booking['id']; ?>" style="color: var(--primary-color);">View Details</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-light); text-align: center; padding: 2rem;">You haven't made any bookings yet.</p>
                <div style="text-align: center;">
                    <a href="book.php" class="btn btn-primary">Book Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
