<?php
// C:\Users\THOMAS\.gemini\antigravity\scratch\household_services_platform\admin\login.php
session_start();
require_once '../config/db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid admin credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: var(--text-dark); display: flex; align-items: center; justify-content: center; height: 100vh;">

    <div class="auth-card fade-in">
        <div class="auth-header">
            <h2>Admin Portal</h2>
            <p>System Management</p>
        </div>

        <?php if($error): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; text-align: center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login to Dashboard</button>
        </form>
        
        <p style="text-align: center; margin-top: 0.5rem; font-size: 0.9rem;">
            <a href="../index.php" style="color: var(--text-light); text-decoration: none;">&larr; Back to Home</a>
        </p>
    </div>

</body>
</html>
