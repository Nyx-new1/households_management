<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}
require_once '../includes/language.php';
require_once '../config/db_connect.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

    // Fetch current user data - Always done so $user is available
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Handle Profile Update
        if (isset($_POST['update_profile'])) {
            $name = trim($_POST['name']);
            $phone = trim($_POST['phone']);
            $location = trim($_POST['location'] ?? '');
            
            // Handle File Upload
            $profile_picture = $user['profile_picture']; // Default to existing
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_picture']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $check = getimagesize($_FILES['profile_picture']['tmp_name']);
                    if($check !== false) {
                        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                        $upload_dir = '../assets/uploads/profiles/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename)) {
                            $profile_picture = $new_filename;
                        } else {
                            $error = "Failed to upload image.";
                        }
                    } else {
                        $error = "File is not an image.";
                    }
                } else {
                    $error = "Invalid file format. Only JPG, PNG, GIF allowed.";
                }
            }

            if (!$error) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, location = ?, profile_picture = ? WHERE id = ?");
                if ($stmt->execute([$name, $phone, $location, $profile_picture, $user_id])) {
                    $message = "Profile updated successfully!";
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['profile_picture'] = $user['profile_picture'];
                } else {
                    $error = "Failed to update profile.";
                }
            }
        }

        // Handle Password Update
        if (isset($_POST['update_password'])) {
            if (password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 6) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashed_password, $user_id]);
                        $message = "Password updated successfully!";
                    } else {
                        $error = "New password must be at least 6 characters long.";
                    }
                } else {
                    $error = "New passwords do not match.";
                }
            } else {
                $error = "Incorrect current password.";
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Settings - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .settings-card {
            max-width: 500px;
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
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo">HomeServe</a>
            <div class="nav-links">
                <!-- Language Switcher in Settings -->
                <div class="lang-switcher">
                    <button class="lang-btn">
                        <i class="fas fa-globe"></i> <span><?php echo $_SESSION['lang'] == 'en' ? 'English' : 'Swahili'; ?></span>
                    </button>
                    <div class="lang-dropdown">
                        <a href="?lang=en">English</a>
                        <a href="?lang=sw">Swahili</a>
                    </div>
                </div>

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
                <a href="dashboard.php"><?php echo $lang['dashboard']; ?></a>
                <a href="my_bookings.php"><?php echo $lang['my_bookings']; ?></a>
                <a href="logout.php" class="btn btn-secondary"><?php echo $lang['logout']; ?></a>
            </div>
        </div>
    </nav>

    <div class="container section">
        <div class="settings-card">
            <h2 style="margin-bottom: 1.5rem;"><?php echo $lang['account_settings']; ?></h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; color: var(--text-dark);"><?php echo $lang['profile_details']; ?></h3>
                <form method="POST" enctype="multipart/form-data">
                    <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; background: #e5e7eb; display: flex; align-items: center; justify-content: center;">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user" style="font-size: 2rem; color: #9ca3af;"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.9rem;"><?php echo $lang['change_photo']; ?></label>
                            <input type="file" name="profile_picture" accept="image/*" class="form-control" style="padding: 0.5rem;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo $lang['full_name']; ?></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo $lang['email_address']; ?> (Read Only)</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly style="background: #f3f4f6;">
                    </div>

                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label"><?php echo $lang['phone_number']; ?></label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">City / Location</label>
                            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%;"><?php echo $lang['save_profile_changes']; ?></button>
                </form>
            </div>

            <div style="border-top: 1px solid #e5e7eb; margin: 2rem 0;"></div>

            <h3 style="margin-bottom: 1rem; color: var(--text-dark);"><?php echo $lang['change_password']; ?></h3>
            <form method="POST">
                <input type="hidden" name="update_password" value="1">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang['current_password']; ?></label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $lang['new_password']; ?></label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $lang['confirm_new_password']; ?></label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-secondary" style="width: 100%;"><?php echo $lang['update_password']; ?></button>
            </form>
        </div>
    </div>
</body>
</html>
