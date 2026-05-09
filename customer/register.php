<?php
session_start();
require_once '../includes/language.php';
require_once '../config/db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location'] ?? '');
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email already registered.";
        } else {
            // Insert user
            $bio = $_POST['bio'] ?? null;
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, location, bio, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')");
            if ($stmt->execute([$name, $email, $hashed_password, $phone, $location, $bio])) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="background-color: var(--bg-light);">

    <!-- Simple Language Switcher -->
    <div style="position: absolute; top: 1rem; right: 1rem;">
       <div class="lang-switcher">
            <button class="lang-btn" style="background: white; border: 1px solid #e5e7eb;">
                <i class="fas fa-globe"></i> <span><?php echo $_SESSION['lang'] == 'en' ? 'English' : 'Swahili'; ?></span>
            </button>
            <div class="lang-dropdown">
                <a href="?lang=en">English</a>
                <a href="?lang=sw">Swahili</a>
            </div>
        </div>
    </div>

    <div class="auth-container">
        <div class="auth-card fade-in">
            <div class="auth-header">
                <a href="../index.php" class="logo" style="justify-content: center; margin-bottom: 1rem;">
                    HomeServe
                </a>
                <h2><?php echo $lang['create_account']; ?></h2>
                <p style="color: var(--text-light);"><?php echo $lang['register_subtitle']; ?></p>
            </div>

            <?php if($error): ?>
                <div style="background: #fef2f2; color: #991b1b; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; text-align: center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div style="background: #ecfdf5; color: #065f46; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; text-align: center;">
                    <?php echo $success; ?> <a href="login.php" style="color: #065f46; font-weight: bold;"><?php echo $lang['login']; ?> here</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label><?php echo $lang['full_name']; ?></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['email_address']; ?></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['phone_number']; ?></label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>City / Location</label>
                    <input type="text" name="location" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Bio / About Me (Optional)</label>
                    <textarea name="bio" class="form-control" rows="3" placeholder="Tell us a bit about yourself..."></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['password']; ?></label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['confirm_password']; ?></label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;"><?php echo $lang['create_account']; ?></button>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; color: var(--text-light);">
                <?php echo $lang['already_have_account']; ?> <a href="login.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;"><?php echo $lang['login']; ?></a>
            </p>
        </div>
    </div>

</body>
</html>
