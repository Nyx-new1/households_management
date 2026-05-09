<?php
// C:\Users\THOMAS\.gemini\antigravity\scratch\household_services_platform\customer\login.php
session_start();
require_once '../includes/language.php';
require_once '../config/db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'customer' AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="background-color: var(--bg-light);">

    <div class="auth-container">
        <div class="auth-card fade-in">
            <div class="auth-header">
                <a href="../index.php" class="logo" style="justify-content: center; margin-bottom: 1rem;">
                    HomeServe
                </a>
                <h2><?php echo $lang['welcome_back']; ?></h2>
                <p style="color: var(--text-light);"><?php echo $lang['login_subtitle']; ?></p>
            </div>

            <?php if($error): ?>
                <div style="background: #fef2f2; color: #991b1b; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; text-align: center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label><?php echo $lang['email_address']; ?></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['password']; ?></label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;"><?php echo $lang['login']; ?></button>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; color: var(--text-light);">
                <?php echo $lang['dont_have_account']; ?> <a href="register.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;"><?php echo $lang['register']; ?></a>
            </p>
             <p style="text-align: center; margin-top: 0.5rem; font-size: 0.9rem;">
                <a href="../index.php" style="color: var(--text-light); text-decoration: none;">&larr; <?php echo $lang['back_to_home']; ?></a>
            </p>
        </div>
    </div>

</body>
</html>
