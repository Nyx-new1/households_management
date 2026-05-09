<?php
// C:\Users\THOMAS\.gemini\antigravity\scratch\household_services_platform\provider\dashboard.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit;
}
require_once '../includes/language.php';
require_once '../config/db_connect.php';

$provider_id = $_SESSION['user_id'];
$success = '';

// Get Provider Details (Service Type)
$stmt = $pdo->prepare("SELECT * FROM provider_details WHERE user_id = ?");
$stmt->execute([$provider_id]);
$details = $stmt->fetch();
$my_service_type = $details['service_type'] ?? '';

// Handle Job Acceptance
if (isset($_POST['accept_job_id'])) {
    $job_id = $_POST['accept_job_id'];
    $update = $pdo->prepare("UPDATE bookings SET provider_id = ?, status = 'accepted' WHERE id = ? AND status = 'pending'");
    if ($update->execute([$provider_id, $job_id])) {
        $success = "Job Accepted!";
        
        $b = $pdo->prepare("SELECT customer_id FROM bookings WHERE id = ?"); $b->execute([$job_id]); $cid = $b->fetchColumn();
        $msg = "A provider has accepted your booking (ID: #$job_id).";
        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, 'my_bookings.php')")->execute([$cid, $msg]);
    }
}

// Handle Status Update
if (isset($_POST['update_status'])) {
    $job_id = $_POST['job_id'];
    $new_status = $_POST['status'];
    $update = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND provider_id = ?");
    if ($update->execute([$new_status, $job_id, $provider_id])) {
        $success = "Job status updated to $new_status";
        
        $b = $pdo->prepare("SELECT customer_id FROM bookings WHERE id = ?"); $b->execute([$job_id]); $cid = $b->fetchColumn();
        $status_label = str_replace('_', ' ', $new_status);
        $msg = "Your job (ID: #$job_id) status was updated to: '$status_label'.";
        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, 'booking_details.php?id=$job_id')")->execute([$cid, $msg]);
    }
}

// Fetch Available Jobs (Pending, Matching Service Type, No Provider Assigned)
// We join services table to match name
$sql_available = "
    SELECT b.*, u.name as customer_name, s.name as service_name
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.customer_id = u.id
    WHERE b.status = 'pending' 
    AND s.name = ?
    AND b.provider_id IS NULL
    ORDER BY b.created_at ASC
";
$stmt = $pdo->prepare($sql_available);
$stmt->execute([$my_service_type]);
$available_jobs = $stmt->fetchAll();

// Fetch My Active Jobs
$sql_my_jobs = "
    SELECT b.*, u.name as customer_name, s.name as service_name, pay.status as payment_status
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.customer_id = u.id
    LEFT JOIN payments pay ON b.id = pay.booking_id
    WHERE b.provider_id = ?
    AND b.status IN ('accepted', 'in_progress')
    ORDER BY b.scheduled_date ASC
";
$stmt = $pdo->prepare($sql_my_jobs);
$stmt->execute([$provider_id]);
$my_jobs = $stmt->fetchAll();

// Fetch My Completed Jobs
$sql_completed_jobs = "
    SELECT b.*, u.name as customer_name, s.name as service_name
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.customer_id = u.id
    WHERE b.provider_id = ?
    AND b.status = 'completed'
    ORDER BY b.scheduled_date DESC
    LIMIT 10
";
$stmt = $pdo->prepare($sql_completed_jobs);
$stmt->execute([$provider_id]);
$completed_jobs = $stmt->fetchAll();

// Fetch My Recent Reviews
$sql_my_reviews = "
    SELECT r.rating, r.comment, r.created_at, u.name as customer_name, s.name as service_name
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.id
    JOIN users u ON b.customer_id = u.id
    JOIN services s ON b.service_id = s.id
    WHERE b.provider_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
";
$stmt = $pdo->prepare($sql_my_reviews);
$stmt->execute([$provider_id]);
$my_reviews = $stmt->fetchAll();

// Fetch Upcoming Jobs (Within 1 Hour)
$sql_upcoming = "
    SELECT b.*, u.name as customer_name, s.name as service_name
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.customer_id = u.id
    WHERE b.provider_id = ?
    AND b.status IN ('accepted', 'in_progress')
    AND b.scheduled_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
    ORDER BY b.scheduled_date ASC
";
$stmt = $pdo->prepare($sql_upcoming);
$stmt->execute([$provider_id]);
$upcoming_jobs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Provider Dashboard - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .job-card {
            border: 1px solid #e5e7eb;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            transition: transform 0.2s;
        }
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .tag {
            background: #eef2ff;
            color: var(--primary-color);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo">HomeServe Pro</a>
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
                        <span>Hello, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (<?php echo htmlspecialchars($my_service_type); ?>)</span>
                    </div>
                </a>
                <!-- Language Switcher in Dashboard -->
                <div class="lang-switcher" style="margin-right: 1rem;">
                    <button class="lang-btn">
                        <i class="fas fa-globe"></i> <span><?php echo $_SESSION['lang'] == 'en' ? 'English' : 'Swahili'; ?></span>
                    </button>
                    <div class="lang-dropdown">
                        <a href="?lang=en">English</a>
                        <a href="?lang=sw">Swahili</a>
                    </div>
                </div>
                <a href="settings.php" style="margin-right: 1rem; color: var(--primary-color); font-weight: 500;">Settings</a>
                <a href="../customer/logout.php" class="btn btn-secondary"><?php echo $lang['logout']; ?></a>
            </div>
        </div>
    </nav>

    <div class="container section">
        <?php if($success): ?>
            <div style="background: #ecfdf5; color: #065f46; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($upcoming_jobs)): ?>
            <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; box-shadow: var(--shadow-sm);">
                <h3 style="color: #991b1b; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Upcoming Job Reminder</h3>
                <p style="color: #b91c1c; margin-bottom: 0;">You have <strong><?php echo count($upcoming_jobs); ?></strong> job(s) scheduled in the next hour. Please be prepared!</p>
            </div>
        <?php endif; ?>

        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 2rem;">
            
            <!-- Available Jobs Column -->
            <div>
                <h2 style="margin-bottom: 1.5rem; color: var(--text-dark);"><?php echo $lang['available_jobs']; ?></h2>
                <?php if(empty($available_jobs)): ?>
                    <p style="color: var(--text-light);"><?php echo $lang['no_new_jobs']; ?> <?php echo $my_service_type; ?> currently.</p>
                <?php else: ?>
                    <?php foreach($available_jobs as $job): ?>
                        <div class="job-card">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <h3 style="font-size: 1.1rem;"><?php echo htmlspecialchars($job['service_name']); ?></h3>
                                <span class="tag" style="height: fit-content;"><?php echo formatPrice($job['total_price'], $job['exchange_rate'] ?? null); ?></span>
                            </div>
                            <p><strong><?php echo $lang['customer_label']; ?></strong> <a href="../public_profile.php?id=<?php echo $job['customer_id']; ?>" style="text-decoration: none; color: var(--primary-color); font-weight: 600;"><?php echo htmlspecialchars($job['customer_name']); ?></a></p>
                            <p><strong><?php echo $lang['address_label']; ?></strong> <?php echo htmlspecialchars($job['address']); ?></p>
                            <p><strong><?php echo $lang['date_label']; ?></strong> <?php echo date('M d, h:i A', strtotime($job['scheduled_date'])); ?></p>
                            <?php if($job['notes']): ?>
                                <p style="color: var(--text-light); font-size: 0.9rem; margin-top: 0.5rem;">"<?php echo htmlspecialchars($job['notes']); ?>"</p>
                            <?php endif; ?>
                            
                            <form method="POST" style="margin-top: 1rem;">
                                <input type="hidden" name="accept_job_id" value="<?php echo $job['id']; ?>">
                                <button type="submit" class="btn btn-primary" style="width: 100%;"><?php echo $lang['accept_job']; ?></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- My Active Jobs Column -->
            <div>
                <h2 style="margin-bottom: 1.5rem; color: var(--text-dark);"><?php echo $lang['my_active_jobs']; ?></h2>
                <?php if(empty($my_jobs)): ?>
                    <p style="color: var(--text-light);"><?php echo $lang['no_active_jobs']; ?></p>
                <?php else: ?>
                    <?php foreach($my_jobs as $job): ?>
                        <div class="job-card" style="border-left: 4px solid var(--primary-color);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <h3 style="font-size: 1.1rem;"><?php echo htmlspecialchars($job['service_name']); ?></h3>
                                <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;"><?php echo strtoupper($job['status']); ?></span>
                            </div>
                            <p><strong><?php echo $lang['customer_label']; ?></strong> <a href="../public_profile.php?id=<?php echo $job['customer_id']; ?>" style="text-decoration: none; color: var(--primary-color); font-weight: 600;"><?php echo htmlspecialchars($job['customer_name']); ?></a></p>
                            <p><strong><?php echo $lang['address_label']; ?></strong> <?php echo htmlspecialchars($job['address']); ?></p>
                            <p><strong><?php echo $lang['date_label']; ?></strong> <?php echo date('M d, h:i A', strtotime($job['scheduled_date'])); ?></p>
                            
                            <form method="POST" style="margin-top: 1rem; display: flex; gap: 0.5rem; width: 100%;">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                <?php if($job['status'] == 'accepted'): ?>
                                    <button type="submit" name="status" value="in_progress" class="btn btn-secondary" style="flex: 1; padding: 0.5rem; font-size: 0.85rem;"><?php echo $lang['start_job']; ?></button>
                                <?php elseif($job['status'] == 'in_progress'): ?>
                                    <?php if($job['payment_status'] == 'completed'): ?>
                                        <button type="submit" name="status" value="completed" class="btn btn-primary" style="flex: 1; padding: 0.5rem; font-size: 0.85rem;"><?php echo $lang['complete_job']; ?></button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary" style="flex: 1; opacity: 0.6; cursor: not-allowed; padding: 0.5rem; font-size: 0.85rem;" title="Payment must be confirmed first">
                                            <i class="fas fa-lock"></i> <?php echo $lang['complete_job']; ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <a href="chat.php?booking_id=<?php echo $job['id']; ?>" class="btn btn-primary" title="Chat" style="padding: 0.5rem 1rem; display: flex; align-items: center; justify-content: center; background-color: #2563eb; color: white; font-size: 0.85rem;">Chat</a>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- My Job History -->
        <div style="margin-top: 3rem;">
            <h2 style="margin-bottom: 1.5rem; color: var(--text-dark);">Job History & Completed Jobs</h2>
            <div class="table-container" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow-sm);">
                <?php if(empty($completed_jobs)): ?>
                    <p style="color: var(--text-light); text-align: center; padding: 1rem;">No completed jobs in your history yet.</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #e5e7eb;">
                                <th style="padding: 0.75rem;">ID</th>
                                <th style="padding: 0.75rem;">Customer</th>
                                <th style="padding: 0.75rem;">Service</th>
                                <th style="padding: 0.75rem;">Date</th>
                                <th style="padding: 0.75rem;">Amount</th>
                                <th style="padding: 0.75rem; text-align: right;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($completed_jobs as $job): ?>
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem;">#<?php echo $job['id']; ?></td>
                                    <td style="padding: 0.75rem;"><?php echo htmlspecialchars($job['customer_name']); ?></td>
                                    <td style="padding: 0.75rem;"><?php echo htmlspecialchars($job['service_name']); ?></td>
                                    <td style="padding: 0.75rem;"><?php echo date('M d, Y', strtotime($job['scheduled_date'])); ?></td>
                                    <td style="padding: 0.75rem;"><?php echo formatPrice($job['total_price'], $job['exchange_rate'] ?? null); ?></td>
                                    <td style="padding: 0.75rem; text-align: right;">
                                        <span style="background: #ecfdf5; color: #065f46; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">COMPLETED</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- My Recent Reviews -->
        <h2 style="margin: 3rem 0 1.5rem 0; color: var(--text-dark);">My Recent Reviews</h2>
        <?php if(empty($my_reviews)): ?>
            <p style="color: var(--text-light); text-align: center; padding: 2rem; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">No reviews yet. Complete jobs successfully to earn reviews!</p>
        <?php else: ?>
            <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                <?php foreach($my_reviews as $rev): ?>
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow-sm); border-top: 4px solid #fbbf24;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <h4 style="margin: 0; font-size: 1.05rem;"><?php echo htmlspecialchars($rev['customer_name']); ?></h4>
                            <span style="color: #fbbf24; font-size: 1.1rem; letter-spacing: 2px;">
                                <?php for($i=0; $i<$rev['rating']; $i++) echo '&#9733;'; ?>
                            </span>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 0.75rem;">
                            <?php echo htmlspecialchars($rev['service_name']); ?> &bull; <?php echo date('M d, Y', strtotime($rev['created_at'])); ?>
                        </p>
                        <p style="color: var(--text-dark); font-size: 0.95rem; line-height: 1.5; font-style: italic;">
                            "<?php echo nl2br(htmlspecialchars($rev['comment'])); ?>"
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
