<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}
require_once '../config/db_connect.php';
require_once '../includes/language.php';

if (!isset($_GET['id'])) {
    header("Location: my_bookings.php");
    exit;
}

$booking_id = $_GET['id'];
$customer_id = $_SESSION['user_id'];

// Fetch full booking details
$stmt = $pdo->prepare("
    SELECT b.*, 
           s.name as service_name, s.description as service_desc, s.base_price, s.image_url,
           p.name as provider_name, p.email as provider_email, p.phone as provider_phone,
           pay.status as payment_status, pay.amount as payment_amount, pay.method as payment_method, pay.created_at as payment_date,
           r.rating, r.comment as review_comment, r.created_at as review_date
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    LEFT JOIN users p ON b.provider_id = p.id 
    LEFT JOIN payments pay ON b.id = pay.booking_id
    LEFT JOIN reviews r ON b.id = r.booking_id
    WHERE b.id = ? AND b.customer_id = ?
");
$stmt->execute([$booking_id, $customer_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Details - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        .details-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending { background: #fee2e2; color: #991b1b; }
        .status-accepted { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #f3f4f6; color: #374151; }

        .timeline {
            border-left: 2px solid #e5e7eb;
            margin-left: 1rem;
            padding-left: 1.5rem;
            position: relative;
        }
        .timeline-item {
            margin-bottom: 1.5rem;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2.1rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: var(--primary-color);
            border: 2px solid white;
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
        <div class="details-grid">
            <div class="main-info">
                <div class="details-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem;">
                        <div>
                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                            </span>
                            <h1 style="margin-top: 0.5rem;"><?php echo htmlspecialchars($booking['service_name']); ?></h1>
                            <p style="color: var(--text-light);">Booking ID: #<?php echo $booking['id']; ?></p>
                        </div>
                        <div style="text-align: right;">
                            <p style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);"><?php echo formatPrice($booking['total_price'], $booking['exchange_rate'] ?? null); ?></p>
                            <?php if($booking['payment_status'] == 'completed'): ?>
                                <span style="color: #059669; font-size: 0.875rem;"><i class="fas fa-check-circle"></i> Paid</span>
                            <?php else: ?>
                                <span style="color: #d97706; font-size: 0.875rem;"><i class="fas fa-clock"></i> Payment Pending</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                        <div>
                            <h3 style="margin-bottom: 0.5rem;"><i class="far fa-calendar"></i> Scheduled Date</h3>
                            <p><?php echo date('l, F j, Y', strtotime($booking['scheduled_date'])); ?></p>
                            <p><?php echo date('g:i A', strtotime($booking['scheduled_date'])); ?></p>
                        </div>
                        <div>
                            <h3 style="margin-bottom: 0.5rem;"><i class="fas fa-map-marker-alt"></i> Location</h3>
                            <p><?php echo htmlspecialchars($booking['address']); ?></p>
                        </div>
                    </div>
                    
                    <?php if($booking['notes']): ?>
                        <div style="margin-bottom: 2rem;">
                            <h3 style="margin-bottom: 0.5rem;">Notes</h3>
                            <p style="background: #f9fafb; padding: 1rem; border-radius: 8px;"><?php echo htmlspecialchars($booking['notes']); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if($booking['rating']): ?>
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
                            <h3 style="margin-bottom: 1rem;">Your Review</h3>
                            <div style="background: #fffbeb; padding: 1rem; border-radius: 8px; border: 1px solid #fcd34d;">
                                <div style="color: #fbbf24; margin-bottom: 0.5rem;">
                                    <?php for($i=0; $i<$booking['rating']; $i++) echo '&#9733;'; ?>
                                </div>
                                <p><?php echo htmlspecialchars($booking['review_comment']); ?></p>
                                <small style="color: var(--text-light); display: block; margin-top: 0.5rem;">Submitted on <?php echo date('M d, Y', strtotime($booking['review_date'])); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar-info">
                <div class="details-card">
                    <h3 style="margin-bottom: 1rem;">Service Provider</h3>
                    <?php if($booking['provider_id']): ?>
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                            <div style="width: 50px; height: 50px; background: #e5e7eb; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user" style="color: #6b7280; font-size: 1.25rem;"></i>
                            </div>
                            <div>
                                <h4 style="margin: 0;"><?php echo htmlspecialchars($booking['provider_name']); ?></h4>
                                <p style="margin: 0; font-size: 0.875rem; color: var(--text-light);">Verified Provider</p>
                            </div>
                        </div>
                        <div style="font-size: 0.875rem;">
                            <p style="margin-bottom: 0.5rem;"><a href="tel:<?php echo htmlspecialchars($booking['provider_phone']); ?>" style="color: var(--text-dark); text-decoration: none;"><i class="fas fa-phone" style="width: 20px;"></i> <?php echo htmlspecialchars($booking['provider_phone']); ?></a></p>
                            <p><a href="mailto:<?php echo htmlspecialchars($booking['provider_email']); ?>" style="color: var(--text-dark); text-decoration: none;"><i class="fas fa-envelope" style="width: 20px;"></i> <?php echo htmlspecialchars($booking['provider_email']); ?></a></p>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--text-light);">A provider will be assigned once your booking is confirmed.</p>
                    <?php endif; ?>
                </div>

                <div class="details-card">
                    <h3 style="margin-bottom: 1rem;">Actions</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php if ($booking['status'] == 'accepted' || $booking['status'] == 'in_progress'): ?>
                            <a href="chat.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-secondary" style="text-align: center; background-color: var(--primary-color); color: white; border-color: var(--primary-color);">
                                <i class="fas fa-comments"></i> <?php echo $lang['chat_with_provider']; ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($booking['status'] == 'completed' && !$booking['rating']): ?>
                            <a href="review_booking.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-primary" style="text-align: center;">Rate & Review</a>
                        <?php elseif (($booking['status'] == 'accepted' || $booking['status'] == 'in_progress' || $booking['status'] == 'completed') && $booking['payment_status'] != 'completed'): ?>
                            <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-primary" style="text-align: center; background-color: var(--success-color, #10b981);">Pay Now</a>
                        <?php endif; ?>
                        
                        <?php if ($booking['status'] == 'pending'): ?>
                            <form action="cancel_booking.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <button type="submit" class="btn btn-secondary" style="width: 100%; color: #ef4444; border-color: #ef4444; cursor: pointer;">Cancel Booking</button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="my_bookings.php" class="btn btn-secondary" style="text-align: center;">Back to Bookings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
