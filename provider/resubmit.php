<?php
session_start();
require_once '../includes/language.php';
require_once '../config/db_connect.php';

if (!isset($_SESSION['rejected_user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['rejected_user_id'];
$success = '';
$error = '';

// Fetch current details
$stmt = $pdo->prepare("
    SELECT u.*, pd.* 
    FROM users u 
    JOIN provider_details pd ON u.id = pd.user_id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$provider = $stmt->fetch();

if (!$provider || $provider['is_verified'] == 1) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bio = trim($_POST['bio']);
    $hourly_rate = $_POST['hourly_rate'];
    $service_type = $_POST['service_type'];

    try {
        $stmt = $pdo->prepare("
            UPDATE provider_details 
            SET bio = ?, hourly_rate = ?, service_type = ?, rejection_reason = NULL 
            WHERE user_id = ?
        ");
        $stmt->execute([$bio, $hourly_rate, $service_type, $user_id]);
        
        $success = "Your profile has been updated and resubmitted for admin approval!";
        unset($_SESSION['rejected_user_id']); // Clear temporary session
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

// Fetch services for dropdown
$services = $pdo->query("SELECT * FROM services")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resubmit Application - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="background-color: var(--bg-light);">

    <div class="auth-container">
        <div class="auth-card fade-in" style="max-width: 600px;">
            <div class="auth-header">
                <h2>Fix Your Profile</h2>
                <p>Address the admin's feedback and resubmit your application.</p>
            </div>

            <?php if($provider['rejection_reason']): ?>
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #fbbf24; padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm);">
                    <h4 style="color: #92400e; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-exclamation-triangle"></i> Admin Feedback
                    </h4>
                    <p style="color: #92400e; font-style: italic; line-height: 1.5;">"<?php echo htmlspecialchars($provider['rejection_reason']); ?>"</p>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                    <?php echo $success; ?>
                    <br><br>
                    <a href="login.php" class="btn btn-primary">Back to Login</a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label><?php echo $lang['service_category']; ?></label>
                        <select name="service_type" class="form-control" required>
                            <?php foreach($services as $s): ?>
                                <option value="<?php echo htmlspecialchars($s['name']); ?>" <?php echo ($provider['service_type'] == $s['name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?php echo $lang['hourly_rate_label']; ?></label>
                        <input type="number" name="hourly_rate" class="form-control" step="0.01" value="<?php echo $provider['hourly_rate']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label><?php echo $lang['short_bio']; ?></label>
                        <textarea name="bio" class="form-control" rows="5" required><?php echo htmlspecialchars($provider['bio']); ?></textarea>
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 2;">Resubmit Application</button>
                        <a href="login.php" class="btn btn-secondary" style="flex: 1; text-align: center;">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
