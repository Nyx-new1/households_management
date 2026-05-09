<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit;
}
require_once '../config/db_connect.php';
require_once '../includes/language.php';

$uid = $_SESSION['user_id'];

// Mark read
$pdo->query("UPDATE notifications SET is_read = 1 WHERE user_id = $uid");

// Fetch 50 most recent
$notifications = $pdo->query("SELECT * FROM notifications WHERE user_id = $uid ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Notifications</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .notif-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid #e2e8f0;
            margin-bottom: 1rem;
            transition: all 0.2s;
            display: block;
            text-decoration: none;
            color: inherit;
        }
        .notif-card:hover {
            border-color: #cbd5e1;
            box-shadow: var(--shadow-sm);
            transform: translateY(-2px);
        }
        .notif-unread {
            background-color: #f0f9ff;
            border-left: 4px solid var(--primary-color);
        }
    </style>
</head>
<body style="background-color: var(--bg-light);">
    <nav class="navbar">
        <div class="container nav-content">
            <a href="dashboard.php" class="logo"><i class="fas fa-home"></i> HomeServe Pro</a>
            <div class="nav-links">
                <a href="dashboard.php"><?php echo $lang['dashboard']; ?></a>
                <a href="../customer/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container section" style="max-width: 800px; margin: 0 auto; min-height: 80vh;">
        <h2 style="margin-bottom: 2rem;">My Notifications</h2>

        <?php if(empty($notifications)): ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 12px; border: 1px dashed #cbd5e1; color: var(--text-light);">
                <i class="fas fa-bell-slash" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>You're all caught up! No notifications yet.</p>
            </div>
        <?php else: ?>
            <?php foreach($notifications as $n): ?>
                <a href="<?php echo htmlspecialchars($n['link'] ?? '#'); ?>" class="notif-card <?php echo $n['is_read'] ? '' : 'notif-unread'; ?>">
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #e0e7ff; color: var(--primary-color); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div>
                            <p style="margin-bottom: 0.25rem; font-size: 1.05rem;"><?php echo htmlspecialchars($n['message']); ?></p>
                            <span style="font-size: 0.85rem; color: var(--text-light);"><i class="far fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($n['created_at'])); ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
