<?php
// admin/reviews.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
require_once '../config/db_connect.php';

// Handle Delete Review
if (isset($_POST['delete_review_id'])) {
    $rid = $_POST['delete_review_id'];
    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$rid]);
    header("Location: reviews.php?deleted=1");
    exit;
}

// Fetch all reviews
$reviews = $pdo->query("
    SELECT r.*, u_cust.name as customer_name, u_prov.name as provider_name, s.name as service_name
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.id
    JOIN users u_cust ON b.customer_id = u_cust.id
    JOIN users u_prov ON b.provider_id = u_prov.id
    JOIN services s ON b.service_id = s.id
    ORDER BY r.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Reviews - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-container { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow-sm); margin-bottom: 2rem; max-width: 1200px; margin-left: auto; margin-right: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .review-text { max-width: 400px; color: var(--text-dark); font-style: italic; font-size: 0.95rem; }
    </style>
</head>
<body style="background-color: var(--bg-light);">

    <nav class="navbar" style="background: var(--text-dark); border-bottom: none;">
        <div class="container nav-content">
            <a href="dashboard.php" class="logo" style="color: white;">Admin Panel</a>
            <div class="nav-links">
                <a href="dashboard.php" style="color: white; text-decoration: none; margin-right: 1.5rem; font-weight: 500;">Dashboard</a>
                <a href="reviews.php" style="color: white; text-decoration: none; margin-right: 1.5rem; font-weight: 500;">Reviews</a>
                <a href="services.php" style="color: white; text-decoration: none; margin-right: 1.5rem; font-weight: 500;">Services</a>
                <a href="customers.php" style="color: white; text-decoration: none; margin-right: 1.5rem; font-weight: 500;">Customers</a>
                <a href="providers.php" style="color: white; text-decoration: none; margin-right: 1.5rem; font-weight: 500;">Providers</a>
                <a href="../customer/logout.php" class="btn btn-secondary" style="background: transparent; color: white; border-color: white;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container section">
        <h2 style="text-align: center; margin-bottom: 2rem;">Manage Platform Reviews</h2>

        <?php if(isset($_GET['deleted'])): ?>
            <div style="background: #dbeafe; color: #1e40af; padding: 1rem; border-radius: 8px; margin: 0 auto 2rem auto; max-width: 1200px; text-align: center;">
                Review successfully deleted.
            </div>
        <?php endif; ?>

        <div class="table-container">
            <h3>Recent Reviews</h3>
            <table style="margin-top: 1rem;">
                <thead>
                    <tr>
                        <th style="width: 15%;">Date</th>
                        <th style="width: 15%;">Customer</th>
                        <th style="width: 15%;">Provider</th>
                        <th style="width: 15%;">Rating</th>
                        <th style="width: 30%;">Comment</th>
                        <th style="width: 10%; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reviews as $r): ?>
                        <tr>
                            <td><?php echo date('M d, Y h:i A', strtotime($r['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($r['customer_name']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($r['provider_name']); ?></strong><br>
                                <span style="font-size: 0.8rem; color: var(--text-light);"><?php echo htmlspecialchars($r['service_name']); ?></span>
                            </td>
                            <td>
                                <span style="color: #fbbf24; font-size: 1rem; letter-spacing: 1px;">
                                    <?php for($i=0; $i<$r['rating']; $i++) echo '&#9733;'; ?>
                                </span>
                            </td>
                            <td class="review-text">"<?php echo nl2br(htmlspecialchars($r['comment'])); ?>"</td>
                            <td style="text-align: right;">
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="delete_review_id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="btn btn-secondary" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 0.35rem 0.5rem; font-size: 0.8rem;" onclick="return confirm('Delete this review completely?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($reviews)): ?>
                <p style="text-align: center; color: var(--text-light); margin-top: 1rem; padding: 1rem;">No reviews found on the platform yet.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
