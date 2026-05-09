<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer' || !isset($_SESSION['last_payment'])) {
    header("Location: dashboard.php");
    exit;
}
require_once '../includes/language.php';

$payment = $_SESSION['last_payment'];
// Clean up session after fetching
unset($_SESSION['last_payment']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .success-card {
            max-width: 500px;
            margin: 4rem auto;
            background: white;
            padding: 3rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .success-icon-wrapper {
            width: 80px;
            height: 80px;
            background: #dcfce7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
            color: #22c55e;
            font-size: 2.5rem;
            animation: scaleCheck 0.5s 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
        }
        @keyframes scaleCheck {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .payment-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
            border: 1px solid #e2e8f0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }
        .detail-row:last-child { margin-bottom: 0; }
        .detail-label { color: var(--text-light); }
        .detail-value { font-weight: 600; color: var(--text-dark); }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn-group .btn { flex: 1; text-decoration: none; display: inline-block; }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); min-height: 100vh;">

    <div class="container">
        <div class="success-card">
            <div class="success-icon-wrapper">
                <i class="fas fa-check"></i>
            </div>
            
            <h1 style="color: var(--text-dark); margin-bottom: 0.5rem;">Payment Successful!</h1>
            <p style="color: var(--text-light);">Thank you for your payment. Your booking is now confirmed and paid.</p>

            <div class="payment-details">
                <div class="detail-row">
                    <span class="detail-label">Booking ID</span>
                    <span class="detail-value">#<?php echo $payment['booking_id']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount Paid</span>
                    <span class="detail-value" style="color: #22c55e;"><?php echo formatPrice($payment['amount'], $payment['exchange_rate'] ?? null); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Method</span>
                    <span class="detail-value"><?php echo htmlspecialchars($payment['method']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Transaction ID</span>
                    <span class="detail-value" style="font-family: monospace; font-size: 0.85rem;"><?php echo $payment['transaction_id']; ?></span>
                </div>
            </div>

            <div class="btn-group">
                <a href="my_bookings.php" class="btn btn-primary">My Bookings</a>
                <button onclick="window.print()" class="btn btn-secondary" style="background: white; border-color: #e2e8f0; color: var(--text-light);"><i class="fas fa-print"></i> Print Receipt</button>
            </div>
            
            <p style="margin-top: 2.5rem; font-size: 0.85rem; color: var(--text-light);">
                A receipt has been sent to your email address. 
                <br>Need help? <a href="mailto:support@homeserve.com?subject=Support Request for Booking #<?php echo $payment['booking_id']; ?>" style="color: var(--primary-color);">Contact Support</a>
            </p>
        </div>
    </div>

</body>
</html>
