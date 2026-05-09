<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit;
}
require_once '../includes/language.php';
require_once '../config/db_connect.php';

$provider_id = $_SESSION['user_id'];
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $service_type = trim($_POST['service_type']);
    $hourly_rate = trim($_POST['hourly_rate']);
    $experience = trim($_POST['experience']);
    
    try {
        $pdo->beginTransaction();

        // Update User Profile
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $provider_id]);
        $_SESSION['user_name'] = $name;

        // Update Provider Details
        $stmt = $pdo->prepare("INSERT INTO provider_details (user_id, service_type, hourly_rate, experience_years) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE service_type = ?, hourly_rate = ?, experience_years = ?");
        $stmt->execute([$provider_id, $service_type, $hourly_rate, $experience, $service_type, $hourly_rate, $experience]);

        $pdo->commit();
        $message = "Profile updated successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Error updating profile: " . $e->getMessage();
    }
}

// Fetch current user data
$stmt = $pdo->prepare("
    SELECT u.*, pd.service_type, pd.hourly_rate, pd.experience_years 
    FROM users u 
    LEFT JOIN provider_details pd ON u.id = pd.user_id 
    WHERE u.id = ?
");
$stmt->execute([$provider_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Provider Profile - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-card {
            max-width: 800px;
            margin: 0 auto;
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
        .alert {
            padding: 1rem;
            background: #d1fae5;
            color: #065f46;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo">HomeServe Pro</a>
            <div class="nav-links">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-right: 1rem;">
                    <div style="width: 35px; height: 35px; border-radius: 50%; overflow: hidden; background: #e5e7eb;">
                        <?php if(isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])): ?>
                            <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-circle" style="font-size: 35px; color: #9ca3af;"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Language Switcher in Profile -->
                <div class="lang-switcher" style="margin-right: 1rem;">
                    <button class="lang-btn">
                        <i class="fas fa-globe"></i> <span><?php echo $_SESSION['lang'] == 'en' ? 'English' : 'Swahili'; ?></span>
                    </button>
                    <div class="lang-dropdown">
                        <a href="?lang=en">English</a>
                        <a href="?lang=sw">Swahili</a>
                    </div>
                </div>
                <a href="dashboard.php"><?php echo $lang['dashboard']; ?></a>
                <a href="settings.php"><?php echo $lang['settings']; ?></a>
                <a href="../customer/logout.php" class="btn btn-secondary"><?php echo $lang['logout']; ?></a>
            </div>
        </div>
    </nav>

    <div class="container section">
        <div class="profile-card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="width: 150px; height: 150px; margin: 0 auto 1rem; border-radius: 50%; overflow: hidden; background: #e5e7eb; border: 4px solid white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Provider Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f3f4f6;">
                            <i class="fas fa-user-tie" style="font-size: 4rem; color: #9ca3af;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h2 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($user['name']); ?></h2>
                <span style="background: #eef2ff; color: var(--primary-color); padding: 0.25rem 0.75rem; border-radius: 9999px; font-weight: 500; font-size: 0.9rem;">
                    <?php echo htmlspecialchars($user['service_type'] ?? 'Service Provider'); ?>
                </span>
            </div>

            <?php if ($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label"><?php echo $lang['full_name']; ?></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo $lang['email_address']; ?></label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: #f3f4f6;">
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo $lang['phone_number']; ?></label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo $lang['service_type_label']; ?></label>
                        <select name="service_type" class="form-control" required>
                            <option value=""><?php echo $lang['select_service']; ?></option>
                            <option value="Cleaning" <?php echo ($user['service_type'] ?? '') == 'Cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                            <option value="Plumbing" <?php echo ($user['service_type'] ?? '') == 'Plumbing' ? 'selected' : ''; ?>>Plumbing</option>
                            <option value="Electrical" <?php echo ($user['service_type'] ?? '') == 'Electrical' ? 'selected' : ''; ?>>Electrical</option>
                            <option value="Laundry" <?php echo ($user['service_type'] ?? '') == 'Laundry' ? 'selected' : ''; ?>>Laundry</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo $lang['hourly_rate_label']; ?></label>
                        <input type="number" step="0.01" name="hourly_rate" class="form-control" value="<?php echo htmlspecialchars($user['hourly_rate'] ?? '0.00'); ?>">
                        <small style="color: var(--text-light);"><?php echo "Currently: " . formatPrice($user['hourly_rate'] ?? 0); ?></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo $lang['experience_label']; ?></label>
                        <input type="number" name="experience" class="form-control" value="<?php echo htmlspecialchars($user['experience_years'] ?? '0'); ?>">
                    </div>
                </div>

                <div style="margin-top: 1rem; border-top: 1px solid #e5e7eb; padding-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;"><?php echo $lang['update_profile']; ?></button>
                    <p style="text-align: center; margin-top: 1rem; color: var(--text-light); font-size: 0.9rem;">
                        <?php echo $lang['change_profile_settings']; ?> <a href="settings.php" style="color: var(--primary-color);"><?php echo $lang['settings']; ?></a>.
                    </p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
