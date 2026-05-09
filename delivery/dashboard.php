<?php
// C:\Users\THOMAS\.gemini\antigravity\scratch\household_services_platform\delivery\dashboard.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    header("Location: login.php");
    exit;
}
require_once '../config/db_connect.php';

$success = '';

// Handle Delivery Status
if (isset($_POST['mark_delivered'])) {
    $job_id = $_POST['job_id'];
    $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?")->execute([$job_id]);
    $success = "Order marked as delivered/completed.";
}

// Fetch Active Deliveries (Logic: Service name contains 'Laundry' and status is 'in_progress' or ready)
// For simplicity, we show 'Laundry' jobs that are 'in_progress' (assuming cleaning is done, now delivery)
$deliveries = $pdo->query("
    SELECT b.*, u.name as customer_name, u.phone, s.name as service_name 
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    JOIN services s ON b.service_id = s.id
    WHERE s.name LIKE '%Laundry%' 
    AND b.status = 'in_progress'
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Dashboard - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="navbar">
        <div class="container nav-content">
            <a href="#" class="logo">HomeServe Delivery</a>
            <div class="nav-links">
                <a href="../customer/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container section">

        <?php if($success): ?>
            <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <h2 style="margin-bottom: 1.5rem;">Active Deliveries</h2>
        
        <?php if(empty($deliveries)): ?>
            <div class="card" style="text-align: center; color: var(--text-light);">
                <i class="fas fa-truck" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>No active delivery tasks found.</p>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach($deliveries as $job): ?>
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <h3 style="color: var(--primary-color);">Order #<?php echo $job['id']; ?></h3>
                            <span class="badge" style="background: #dbeafe; color: #1e40af; padding: 0.25rem 0.5rem; border-radius: 4px;">In Transit</span>
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($job['customer_name']); ?></p>
                            <p><strong>Contact:</strong> <?php echo htmlspecialchars($job['phone']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($job['address']); ?></p>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            <button type="submit" name="mark_delivered" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-check"></i> Mark as Delivered
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
