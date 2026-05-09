<?php
// admin/dashboard.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
require_once '../config/db_connect.php';
require_once '../includes/language.php';

// Stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND is_active = 1")->fetchColumn();
$total_providers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'provider' AND is_active = 1")->fetchColumn();
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'completed'")->fetchColumn() ?: 0;
$total_revenue_tzs = $pdo->query("SELECT SUM(total_price * exchange_rate) FROM bookings WHERE status = 'completed'")->fetchColumn() ?: 0;

// Handle Provider Verification OR Rejection
if (isset($_POST['action']) && isset($_POST['provider_id'])) {
    $pid = $_POST['provider_id'];
    if ($_POST['action'] === 'approve') {
        $pdo->prepare("UPDATE provider_details SET is_verified = 1, rejection_reason = NULL WHERE user_id = ?")->execute([$pid]);
        
        $msg = "Congratulations! Your provider account has been approved by the Admin.";
        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, 'dashboard.php')")->execute([$pid, $msg]);
    } elseif ($_POST['action'] === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        $pdo->prepare("UPDATE provider_details SET is_verified = 0, rejection_reason = ? WHERE user_id = ?")->execute([$reason, $pid]);
    }
}
// Legacy POST kept for fallback
if (isset($_POST['verify_provider_id'])) {
    $pid = $_POST['verify_provider_id'];
    $pdo->prepare("UPDATE provider_details SET is_verified = 1 WHERE user_id = ?")->execute([$pid]);
}

// Handle Booking Overrides
if (isset($_POST['booking_action']) && isset($_POST['booking_id'])) {
    $bid = $_POST['booking_id'];
    $action = $_POST['booking_action'];
    if ($action === 'complete') {
        $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?")->execute([$bid]);
    } elseif ($action === 'cancel') {
        $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$bid]);
    }
}

// Unverified Providers
$unverified_providers = $pdo->query("
    SELECT u.id, u.name, u.email, u.phone, u.location, pd.service_type, pd.bio, pd.hourly_rate
    FROM users u 
    JOIN provider_details pd ON u.id = pd.user_id 
    WHERE pd.is_verified = 0 AND pd.rejection_reason IS NULL AND u.is_active = 1
")->fetchAll();

// Handle Exchange Rate Update
if (isset($_POST['update_rate'])) {
    $new_rate = (float)$_POST['usd_to_tzs_rate'];
    if ($new_rate > 0) {
        $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'usd_to_tzs_rate'")->execute([$new_rate]);
        $rate_update_success = "Exchange rate updated to 1 USD = " . number_format($new_rate, 2) . " TZS";
    }
}

// Fetch current rate
$stmt_rate = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'usd_to_tzs_rate'");
$current_rate = (float)($stmt_rate->fetchColumn() ?: 2500);

// Recent Bookings
$recent_bookings = $pdo->query("
    SELECT b.*, u.name as customer, s.name as service 
    FROM bookings b 
    JOIN users u ON b.customer_id = u.id 
    JOIN services s ON b.service_id = s.id 
    ORDER BY b.created_at DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow-sm); cursor: pointer; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .table-container { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow-sm); margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        
        .detail-section {
            display: none;
            max-width: 1000px;
            margin: 0 auto;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
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
        
        <!-- Stats Grid -->
        <div class="grid" style="grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="stat-card" onclick="showSection('customers')">
                <h3 style="color: var(--primary-color); font-size: 2rem;"><?php echo $total_users; ?></h3>
                <p style="color: var(--text-light);">Total Customers</p>
            </div>
            <div class="stat-card" onclick="showSection('providers')">
                <h3 style="color: var(--secondary-color); font-size: 2rem;"><?php echo $total_providers; ?></h3>
                <p style="color: var(--text-light);">Total Providers</p>
            </div>
            <div class="stat-card" onclick="showSection('bookings')">
                <h3 style="color: var(--text-dark); font-size: 2rem;"><?php echo $total_bookings; ?></h3>
                <p style="color: var(--text-light);">Total Bookings</p>
            </div>
            <div class="stat-card" onclick="showSection('revenue')">
                <h3 style="color: #059669; font-size: 1.5rem; margin-bottom: 0.25rem;">$<?php echo number_format($total_revenue, 2); ?></h3>
                <h4 style="color: #059669; font-size: 1rem; margin-top: 0; font-weight: 600;"><?php echo number_format($total_revenue_tzs, 0); ?> TZS</h4>
                <p style="color: var(--text-light);">Total Revenue</p>
            </div>
        </div>

        <!-- Exchange Rate Manager -->
        <div class="table-container" style="margin-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Currency Exchange Settings</h3>
                <?php if(isset($rate_update_success)): ?>
                    <span style="background: #d1fae5; color: #065f46; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.85rem;"><?php echo $rate_update_success; ?></span>
                <?php endif; ?>
            </div>
            <form method="POST" style="margin-top: 1.5rem; display: flex; align-items: flex-end; gap: 1rem; max-width: 500px;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.85rem; color: var(--text-light); margin-bottom: 0.5rem;">USD to TZS Rate (Fairness Rate)</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-weight: 600;">1 USD =</span>
                        <input type="number" name="usd_to_tzs_rate" step="0.01" value="<?php echo $current_rate; ?>" 
                            style="flex: 1; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-weight: bold;" required>
                        <span style="font-weight: 600;">TZS</span>
                    </div>
                </div>
                <button type="submit" name="update_rate" class="btn btn-primary" style="padding: 0.6rem 1.5rem;">Update Rate</button>
            </form>
            <p style="font-size: 0.8rem; color: var(--text-light); margin-top: 1rem;">
                <i class="fas fa-info-circle"></i> This rate is used for all new bookings. Existing bookings keep the rate they had at the time of booking for fairness.
            </p>
        </div>

        <!-- Section: Bookings -->
        <div id="section-bookings" class="detail-section" style="display: block;">
            <!-- Recent Bookings -->
            <div class="table-container" style="text-align: center;">
                <h3 style="margin-bottom: 1rem;">Recent Bookings</h3>
                <table style="margin: 0 auto; width: 100%;">
                    <thead>
                        <tr>
                            <th style="text-align: center;">ID</th>
                            <th style="text-align: center;">Customer</th>
                            <th style="text-align: center;">Service</th>
                            <th style="text-align: center;">Amount</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_bookings as $b): ?>
                            <tr>
                                <td style="text-align: center;">#<?php echo $b['id']; ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($b['customer']); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($b['service']); ?></td>
                                <td style="text-align: center;"><?php echo formatPrice($b['total_price'], $b['exchange_rate'] ?? null); ?></td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                        <span class="status-badge status-<?php echo $b['status']; ?>" 
                                            style="padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.8rem; 
                                            background: <?php echo match($b['status']) { 'completed' => '#d1fae5', 'cancelled' => '#f3f4f6', 'pending' => '#fee2e2', 'accepted' => '#fef3c7', default => '#dbeafe' }; ?>;
                                            color: <?php echo match($b['status']) { 'completed' => '#065f46', 'cancelled' => '#374151', 'pending' => '#991b1b', 'accepted' => '#92400e', default => '#1e40af' }; ?>;">
                                            <?php echo ucfirst($b['status']); ?>
                                        </span>
                                        <?php if($b['status'] === 'pending' || $b['status'] === 'accepted'): ?>
                                            <form method="POST" style="margin: 0; display: inline-flex; gap: 0.25rem;">
                                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                <button type="submit" name="booking_action" value="complete" title="Force Complete" style="background: #10b981; color: white; border: none; border-radius: 4px; padding: 0.2rem 0.4rem; cursor: pointer; font-size: 0.75rem;">Complete</button>
                                                <button type="submit" name="booking_action" value="cancel" title="Force Cancel" style="background: #ef4444; color: white; border: none; border-radius: 4px; padding: 0.2rem 0.4rem; cursor: pointer; font-size: 0.75rem;" onclick="return confirm('Cancel this booking?');">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section: Revenue -->
        <div id="section-revenue" class="detail-section">
            <!-- Recent Revenue -->
            <?php
            $recent_payments = $pdo->query("
                SELECT p.*, b.service_id, b.exchange_rate, s.name as service_name, u_cust.name as customer_name
                FROM payments p
                JOIN bookings b ON p.booking_id = b.id
                JOIN services s ON b.service_id = s.id
                JOIN users u_cust ON b.customer_id = u_cust.id
                WHERE p.status = 'completed'
                ORDER BY p.created_at DESC
                LIMIT 5
            ")->fetchAll();
            ?>
            <div class="table-container" style="text-align: center;">
                <h3 style="margin-bottom: 1rem;">Recent Revenue Sources</h3>
                <?php if (count($recent_payments) > 0): ?>
                    <table style="margin: 0 auto; width: 100%;">
                        <thead>
                            <tr>
                                <th style="text-align: center;">Date</th>
                                <th style="text-align: center;">Source (Service)</th>
                                <th style="text-align: center;">Payer</th>
                                <th style="text-align: center;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_payments as $pay): ?>
                                <tr>
                                    <td style="text-align: center;"><?php echo date('M d, Y', strtotime($pay['created_at'])); ?></td>
                                    <td style="text-align: center;"><?php echo htmlspecialchars($pay['service_name']); ?></td>
                                    <td style="text-align: center;"><?php echo htmlspecialchars($pay['customer_name']); ?></td>
                                    <td style="text-align: center; color: #059669; font-weight: 600;">+<?php echo formatPrice($pay['amount'], $pay['exchange_rate'] ?? null); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: var(--text-light);">No recent revenue recorded.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section: Providers -->
        <div id="section-providers" class="detail-section">
            <!-- Verification Queue (Providers) -->
            <div class="table-container" style="max-width: 800px; margin: 0 auto 2rem auto;">
                <h3 style="margin-bottom: 1rem; text-align: center;">Pending Provider Approvals</h3>
                <?php if(empty($unverified_providers)): ?>
                    <p style="color: var(--text-light); font-size: 0.9rem; text-align: center;">No pending approvals.</p>
                <?php else: ?>
                    <?php foreach($unverified_providers as $p): ?>
                        <div style="border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem; text-align: left;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <div>
                                    <strong style="display: block; font-size: 1rem;"><?php echo htmlspecialchars($p['name']); ?></strong>
                                    <a href="mailto:<?php echo htmlspecialchars($p['email']); ?>" style="color: var(--primary-color); text-decoration: none; font-size: 0.85rem;"><i class="fas fa-envelope" style="margin-right: 0.35rem;"></i><?php echo htmlspecialchars($p['email']); ?></a>
                                    <a href="tel:<?php echo htmlspecialchars($p['phone'] ?? ''); ?>" style="display: block; color: var(--text-dark); text-decoration: none; font-size: 0.85rem; margin-top: 0.25rem;"><i class="fas fa-phone" style="margin-right: 0.35rem;"></i><?php echo htmlspecialchars($p['phone'] ?? 'N/A'); ?></a>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" style="padding: 0.2rem 0.6rem; font-size: 0.8rem; background: transparent; color: var(--primary-color); border: 1px solid var(--primary-color); border-radius: 4px; cursor: pointer;" onclick="document.getElementById('modal-provider-<?php echo $p['id']; ?>').style.display='flex'">View Profile</button>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="background: #e0f2fe; color: #0369a1; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">
                                    <?php echo htmlspecialchars($p['service_type']); ?>
                                </span>
                                <form method="POST" style="margin: 0; display: flex; gap: 0.5rem;">
                                    <input type="hidden" name="provider_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">Approve</button>
                                    <button type="button" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;" onclick="document.getElementById('modal-provider-<?php echo $p['id']; ?>').style.display='flex'">View & Reject</button>
                                </form>
                            </div>

                            <!-- Provider Detail Modal -->
                            <div id="modal-provider-<?php echo $p['id']; ?>" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                                <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%; position: relative;">
                                    <button type="button" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;" onclick="document.getElementById('modal-provider-<?php echo $p['id']; ?>').style.display='none'">&times;</button>
                                    <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">Provider Application</h3>
                                    
                                    <div style="margin-bottom: 1rem;">
                                        <p style="margin-bottom: 0.25rem;"><strong style="color: var(--text-dark);">Name:</strong> <?php echo htmlspecialchars($p['name']); ?></p>
                                        <p style="margin-bottom: 0.25rem;"><strong style="color: var(--text-dark);">Email:</strong> <a href="mailto:<?php echo htmlspecialchars($p['email']); ?>"><?php echo htmlspecialchars($p['email']); ?></a></p>
                                        <p style="margin-bottom: 0.25rem;"><strong style="color: var(--text-dark);">Phone:</strong> <a href="tel:<?php echo htmlspecialchars($p['phone'] ?? ''); ?>"><?php echo htmlspecialchars($p['phone'] ?? 'N/A'); ?></a></p>
                                        <p style="margin-bottom: 0.25rem;"><strong style="color: var(--text-dark);">Location:</strong> <?php echo htmlspecialchars($p['location'] ?? 'N/A'); ?></p>
                                        <p style="margin-bottom: 0.25rem;"><strong style="color: var(--text-dark);">Requested Service:</strong> <?php echo htmlspecialchars($p['service_type']); ?></p>
                                        <p style="margin-bottom: 0.25rem;"><strong style="color: var(--text-dark);">Hourly Rate:</strong> $<?php echo number_format($p['hourly_rate'], 2); ?>/hr</p>
                                    </div>
                                    
                                    <div>
                                        <strong style="display: block; color: var(--text-dark); margin-bottom: 0.5rem;">Short Bio / Experience:</strong>
                                        <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; font-size: 0.95rem; line-height: 1.5; color: var(--text-light); max-height: 200px; overflow-y: auto;">
                                            <?php echo nl2br(htmlspecialchars($p['bio'])); ?>
                                        </div>
                                    </div>

                                    <div style="margin-top: 1.5rem; border-top: 1px solid #e5e7eb; padding-top: 1.5rem;">
                                        <form method="POST" style="margin: 0; display: flex; flex-direction: column; gap: 1rem; width: 100%;">
                                            <input type="hidden" name="provider_id" value="<?php echo $p['id']; ?>">
                                            
                                            <!-- Approve Button -->
                                            <button type="submit" name="action" value="approve" class="btn btn-primary" style="width: 100%;">Approve Application</button>
                                            
                                            <div style="position: relative; padding-top: 1rem; margin-top: 0.5rem; border-top: 1px dashed #e5e7eb;">
                                            <p style="margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-dark);"><strong>Reject Application:</strong></p>
                                            <textarea name="rejection_reason" class="form-control" rows="2" placeholder="Provide a reason for rejection (Required if rejecting)..." style="width: 100%; border: 1px solid #fca5a5; margin-bottom: 0.5rem;" oninput="this.form.reject_btn.disabled = this.value.trim() === '';"></textarea>
                                            <button type="submit" name="action" value="reject" id="reject_btn" class="btn btn-secondary" style="width: 100%; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;" onclick="return confirm('Reject this provider application?');" disabled>Reject Application</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Provider List Summary -->
            <?php
            $active_providers = $pdo->query("SELECT * FROM users WHERE role = 'provider' AND is_active = 1 LIMIT 5")->fetchAll();
            ?>
            <div class="table-container" style="text-align: center; max-width: 800px; margin: 0 auto;">
                <h3 style="margin-bottom: 1rem;">Registered Providers</h3>
                <ul style="list-style: none; padding: 0; display: inline-block; text-align: left; min-width: 300px;">
                    <?php foreach($active_providers as $ap): ?>
                        <li style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid #f3f4f6;">
                            <div style="width: 32px; height: 32px; background: #f3f4f6; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                                <?php if (!empty($ap['profile_picture'])): ?>
                                    <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($ap['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span style="display: block; font-weight: 500; font-size: 0.9rem;"><?php echo htmlspecialchars($ap['name']); ?></span>
                                <a href="mailto:<?php echo htmlspecialchars($ap['email']); ?>" style="display: block; font-size: 0.8rem; color: var(--primary-color); text-decoration: none;"><i class="fas fa-envelope" style="margin-right: 0.35rem;"></i><?php echo htmlspecialchars($ap['email']); ?></a>
                                <a href="tel:<?php echo htmlspecialchars($ap['phone'] ?? ''); ?>" style="display: block; font-size: 0.8rem; color: var(--text-light); text-decoration: none; margin-top: 0.15rem;"><i class="fas fa-phone" style="margin-right: 0.35rem;"></i><?php echo htmlspecialchars($ap['phone'] ?? 'N/A'); ?></a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <br>
                <a href="providers.php" style="display: inline-block; text-align: center; font-size: 0.85rem; margin-top: 1rem; color: var(--primary-color); text-decoration: none;">View All Providers</a>
            </div>
        </div>

        <!-- Section: Customers -->
        <div id="section-customers" class="detail-section">
            <!-- Customer List Summary -->
            <?php
            $active_customers = $pdo->query("SELECT * FROM users WHERE role = 'customer' AND is_active = 1 LIMIT 5")->fetchAll();
            ?>
            <div class="table-container" style="text-align: center; max-width: 800px; margin: 0 auto;">
                <h3 style="margin-bottom: 1rem;">Registered Customers</h3>
                <ul style="list-style: none; padding: 0; display: inline-block; text-align: left; min-width: 300px;">
                    <?php foreach($active_customers as $ac): ?>
                        <li style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid #f3f4f6;">
                            <div style="width: 32px; height: 32px; background: #f3f4f6; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                                <?php if (!empty($ac['profile_picture'])): ?>
                                    <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($ac['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span style="display: block; font-weight: 500; font-size: 0.9rem;"><?php echo htmlspecialchars($ac['name']); ?></span>
                                <a href="mailto:<?php echo htmlspecialchars($ac['email']); ?>" style="display: block; font-size: 0.8rem; color: var(--primary-color); text-decoration: none;"><i class="fas fa-envelope" style="margin-right: 0.35rem;"></i><?php echo htmlspecialchars($ac['email']); ?></a>
                                <a href="tel:<?php echo htmlspecialchars($ac['phone'] ?? ''); ?>" style="display: block; font-size: 0.8rem; color: var(--text-light); text-decoration: none; margin-top: 0.15rem;"><i class="fas fa-phone" style="margin-right: 0.35rem;"></i><?php echo htmlspecialchars($ac['phone'] ?? 'N/A'); ?></a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <br>
                <a href="customers.php" style="display: inline-block; text-align: center; font-size: 0.85rem; margin-top: 1rem; color: var(--primary-color); text-decoration: none;">View All Customers</a>
            </div>
        </div>

    </div>

    <script>
        function showSection(sectionId) {
            const sections = document.querySelectorAll('.detail-section');
            sections.forEach(sec => sec.style.display = 'none');
            document.getElementById('section-' + sectionId).style.display = 'block';
        }
    </script>
</body>
</html>
