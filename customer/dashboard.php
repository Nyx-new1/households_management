<?php
// C:\Users\THOMAS\.gemini\antigravity\scratch\household_services_platform\customer\dashboard.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}
require_once '../includes/language.php';
require_once '../config/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-grid {
            margin-top: 2rem;
            grid-template-columns: 250px 1fr;
            align-items: start;
        }
        .sidebar {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
        .sidebar-menu a {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: var(--radius-md);
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #eef2ff;
            color: var(--primary-color);
        }
        .sidebar-menu i {
            width: 24px;
        }
        .dashboard-content {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
        .stat-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
        }
        .stat-card:hover {
            border-color: #cbd5e1;
            box-shadow: var(--shadow-sm);
            transform: translateY(-2px);
        }
        .detail-section {
            display: none;
            margin-top: 2rem;
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.detail-section').forEach(el => el.style.display = 'none');
            document.getElementById('section-' + sectionId).style.display = 'block';
        }
        
        window.onload = function() {
            showSection('active');
        }
    </script>
</head>
<body>

    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo">
                <i class="fas fa-home"></i> HomeServe
            </a>
            <div class="nav-links">
                <?php
                $uid = $_SESSION['user_id'];
                $unread_notifs = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = $uid AND is_read = 0")->fetchColumn();
                ?>
                <a href="notifications.php" style="text-decoration: none; color: inherit; display: flex; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-right: 1rem;">
                        <div style="position: relative;">
                            <div style="width: 35px; height: 35px; border-radius: 50%; overflow: hidden; background: #e5e7eb; border: 2px solid transparent; transition: border-color 0.2s;" onmouseover="this.style.borderColor='var(--primary-color)'" onmouseout="this.style.borderColor='transparent'">
                                <?php if(isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])): ?>
                                    <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle" style="font-size: 35px; color: #9ca3af;"></i>
                                <?php endif; ?>
                            </div>
                            <?php if($unread_notifs > 0): ?>
                                <span style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 0.65rem; font-weight: bold; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: 2px solid white;"><?php echo $unread_notifs; ?></span>
                            <?php endif; ?>
                        </div>
                        <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                    </div>
                </a>
                <a href="logout.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;"><?php echo $lang['logout']; ?></a>
            </div>
        </div>
    </nav>

    <div class="container section">
        <div class="grid dashboard-grid">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-menu">
                    <a href="dashboard.php" class="active"><i class="fas fa-th-large"></i> <?php echo $lang['dashboard']; ?></a>
                    <a href="book.php"><i class="fas fa-plus-circle"></i> Book Service</a>
                    <a href="my_bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="dashboard-content">
                <h2 style="margin-bottom: 1.5rem;"><?php echo $lang['dashboard_overview'] ?? 'Dashboard Overview'; ?></h2>
                
                <?php
                // Fetch stats
                $customer_id = $_SESSION['user_id'];
                
                // Active Bookings (pending, accepted, in_progress)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND status IN ('pending', 'accepted', 'in_progress')");
                $stmt->execute([$customer_id]);
                $active_bookings = $stmt->fetchColumn();

                // Completed Jobs
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND status = 'completed'");
                $stmt->execute([$customer_id]);
                $completed_jobs = $stmt->fetchColumn();

                // Total Spent
                $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments pay JOIN bookings b ON pay.booking_id = b.id WHERE b.customer_id = ? AND pay.status = 'completed'");
                $stmt->execute([$customer_id]);
                $total_spent = $stmt->fetchColumn() ?: 0;
                
                $stmt = $pdo->prepare("SELECT SUM(amount * exchange_rate) FROM payments pay JOIN bookings b ON pay.booking_id = b.id WHERE b.customer_id = ? AND pay.status = 'completed'");
                $stmt->execute([$customer_id]);
                $total_spent_tzs = $stmt->fetchColumn() ?: 0;
                
                // Fetch Detailed Lists
                $stmt = $pdo->prepare("SELECT b.*, s.name as service_name FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.customer_id = ? AND b.status IN ('pending', 'accepted', 'in_progress') ORDER BY b.scheduled_date ASC");
                $stmt->execute([$customer_id]);
                $active_bookings_list = $stmt->fetchAll();

                $stmt = $pdo->prepare("SELECT b.*, s.name as service_name FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.customer_id = ? AND b.status = 'completed' ORDER BY b.created_at DESC");
                $stmt->execute([$customer_id]);
                $completed_jobs_list = $stmt->fetchAll();

                $stmt = $pdo->prepare("SELECT pay.amount, pay.method, pay.created_at, s.name as service_name, b.exchange_rate FROM payments pay JOIN bookings b ON pay.booking_id = b.id JOIN services s ON b.service_id = s.id WHERE b.customer_id = ? AND pay.status = 'completed' ORDER BY pay.created_at DESC");
                $stmt->execute([$customer_id]);
                $spending_history = $stmt->fetchAll();
                ?>
                
                <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="stat-card" onclick="showSection('active')">
                        <h3 style="color: var(--primary-color); font-size: 2rem;"><?php echo $active_bookings; ?></h3>
                        <p style="color: var(--text-light);">Active Bookings</p>
                    </div>
                    <div class="stat-card" onclick="showSection('completed')">
                        <h3 style="color: var(--success-color, #10b981); font-size: 2rem;"><?php echo $completed_jobs; ?></h3>
                        <p style="color: var(--text-light);">Completed Jobs</p>
                    </div>
                    <div class="stat-card" onclick="showSection('spent')">
                        <h3 style="color: var(--text-dark); font-size: 1.5rem; margin-bottom: 0.25rem;">$<?php echo number_format($total_spent, 2); ?></h3>
                        <h4 style="color: var(--text-dark); font-size: 1rem; margin-top: 0; font-weight: 600;"><?php echo number_format($total_spent_tzs, 0); ?> TZS</h4>
                        <p style="color: var(--text-light);">Total Spent</p>
                    </div>
                </div>

                <!-- Section: Active Bookings -->
                <div id="section-active" class="detail-section">
                    <h3>Active Bookings</h3>
                    <?php if (count($active_bookings_list) > 0): ?>
                        <div style="margin-top: 1rem; background: white; border-radius: var(--radius-lg); overflow: hidden; border: 1px solid #e5e7eb;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-light);">Service</th>
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-light);">Date & Time</th>
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-light);">Status</th>
                                        <th style="padding: 1rem; text-align: right; font-weight: 600; color: var(--text-light);">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($active_bookings_list as $booking): ?>
                                        <tr style="border-bottom: 1px solid #e5e7eb;">
                                            <td style="padding: 1rem;"><strong><?php echo htmlspecialchars($booking['service_name']); ?></strong></td>
                                            <td style="padding: 1rem; color: var(--text-light);"><?php echo date('M d, Y h:i A', strtotime($booking['scheduled_date'])); ?></td>
                                            <td style="padding: 1rem;">
                                                <?php
                                                $status_colors = [
                                                    'pending' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                                                    'accepted' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                                                    'in_progress' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                                                ];
                                                $status = $booking['status'];
                                                $color = $status_colors[$status] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                                                ?>
                                                <span style="background: <?php echo $color['bg']; ?>; color: <?php echo $color['text']; ?>; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.875rem; font-weight: 500;">
                                                    <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 1rem; text-align: right;">
                                                <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary" style="padding: 0.25rem 0.75rem; font-size: 0.875rem; border: 1px solid var(--primary-color); color: var(--primary-color); border-radius: var(--radius-md); text-decoration: none;">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 1rem; padding: 2rem; text-align: center; background: #f9fafb; border-radius: var(--radius-lg); color: var(--text-light);">
                            <i class="fas fa-calendar-check" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No active bookings.</p>
                            <a href="book.php" class="btn btn-primary" style="margin-top: 1rem;">Book a Service</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Section: Completed Jobs -->
                <div id="section-completed" class="detail-section">
                    <h3>Completed Jobs</h3>
                    <?php if (count($completed_jobs_list) > 0): ?>
                        <div style="margin-top: 1rem; background: white; border-radius: var(--radius-lg); overflow: hidden; border: 1px solid #e5e7eb;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-light);">Service</th>
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-light);">Date Completed</th>
                                        <th style="padding: 1rem; text-align: right; font-weight: 600; color: var(--text-light);">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($completed_jobs_list as $booking): ?>
                                        <tr style="border-bottom: 1px solid #e5e7eb;">
                                            <td style="padding: 1rem;"><strong><?php echo htmlspecialchars($booking['service_name']); ?></strong></td>
                                            <td style="padding: 1rem; color: var(--text-light);"><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                                            <td style="padding: 1rem; text-align: right;">
                                                <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary" style="padding: 0.25rem 0.75rem; font-size: 0.875rem; border: 1px solid var(--primary-color); color: var(--primary-color); border-radius: var(--radius-md); text-decoration: none;">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 1rem; padding: 2rem; text-align: center; background: #f9fafb; border-radius: var(--radius-lg); color: var(--text-light);">
                            <p>No completed jobs yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Section: Total Spent -->
                <div id="section-spent" class="detail-section">
                    <h3>Spending History</h3>
                    <?php if (count($spending_history) > 0): ?>
                        <div style="margin-top: 1rem; background: white; border-radius: var(--radius-lg); overflow: hidden; border: 1px solid #e5e7eb;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-light);">Date</th>
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-light);">Service</th>
                                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-light);">Method</th>
                                        <th style="padding: 1rem; text-align: right; font-weight: 600; color: var(--text-light);">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($spending_history as $payment): ?>
                                        <tr style="border-bottom: 1px solid #e5e7eb;">
                                            <td style="padding: 1rem; color: var(--text-light);"><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                                            <td style="padding: 1rem;"><strong><?php echo htmlspecialchars($payment['service_name']); ?></strong></td>
                                            <td style="padding: 1rem;"><?php echo ucfirst(str_replace('_', ' ', $payment['method'])); ?></td>
                                            <td style="padding: 1rem; text-align: right; font-weight: 600; color: var(--text-dark);"><?php echo formatPrice($payment['amount'], $payment['exchange_rate'] ?? null); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 1rem; padding: 2rem; text-align: center; background: #f9fafb; border-radius: var(--radius-lg); color: var(--text-light);">
                            <p>No successful payments found.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

</body>
</html>
