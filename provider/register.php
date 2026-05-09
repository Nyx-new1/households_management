<?php
// C:\Users\THOMAS\.gemini\antigravity\scratch\household_services_platform\provider\register.php
session_start();
require_once '../includes/language.php';
require_once '../config/db_connect.php';

$error = '';
$success = '';

// Fetch services for dropdown
$services = $pdo->query("SELECT * FROM services")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location'] ?? '');
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Provider Details
    $service_type = $_POST['service_type'];
    $hourly_rate = $_POST['hourly_rate'];
    $bio = trim($_POST['bio']);

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email already registered.";
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Insert User
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, location, role) VALUES (?, ?, ?, ?, ?, 'provider')");
                $stmt->execute([$name, $email, $hashed_password, $phone, $location]);
                $user_id = $pdo->lastInsertId();

                // 2. Insert Provider Details
                $stmt = $pdo->prepare("INSERT INTO provider_details (user_id, service_type, hourly_rate, bio, is_verified) VALUES (?, ?, ?, ?, 0)");
                $stmt->execute([$user_id, $service_type, $hourly_rate, $bio]);

                $pdo->commit();
                $success = "Provider account created! Please login.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Provider Register - HomeServe</title>
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
        <div class="auth-card fade-in" style="max-width: 600px;">
            <div class="auth-header">
                <h2><?php echo $lang['join_as_pro']; ?></h2>
                <p><?php echo $lang['grow_business']; ?></p>
            </div>

            <?php if($error): ?>
                <div style="background: #fef2f2; color: #991b1b; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; text-align: center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div style="background: #ecfdf5; color: #065f46; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; text-align: center;">
                    <?php echo $success; ?> <a href="login.php" style="color: #065f46; font-weight: bold;"><?php echo $lang['login']; ?></a>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label><?php echo $lang['full_name']; ?></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $lang['email_address']; ?></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['phone_number']; ?></label>
                    <input type="text" name="phone" class="form-control" required>
                </div>

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>City / Location</label>
                        <input type="text" name="location" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $lang['service_category']; ?></label>
                        <select name="service_type" class="form-control" required>
                            <option value="">-- <?php echo $lang['select_service']; ?> --</option>
                            <?php foreach($services as $s): ?>
                                <option value="<?php echo htmlspecialchars($s['name']); ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['hourly_rate_label']; ?></label>
                    <input type="number" name="hourly_rate" class="form-control" step="0.01" required>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['short_bio']; ?></label>
                    <textarea name="bio" class="form-control" rows="3" required></textarea>
                </div>

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label><?php echo $lang['password']; ?></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $lang['confirm_password']; ?></label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;"><?php echo $lang['create_provider_account']; ?></button>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; color: var(--text-light);">
                <?php echo $lang['already_registered']; ?> <a href="login.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;"><?php echo $lang['login']; ?></a>
            </p>
        </div>
    </div>

</body>
</html>
