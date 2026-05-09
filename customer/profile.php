<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}
require_once '../config/db_connect.php';

$user_id = $_SESSION['user_id'];
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $bio = $_POST['bio'] ?? null;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, bio = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $bio, $user_id]);
        $_SESSION['user_name'] = $name; // Update session name
        $message = "Profile updated successfully!";
    } catch (PDOException $e) {
        $message = "Error updating profile: " . $e->getMessage();
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-card {
            max-width: 600px;
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
            <a href="../index.php" class="logo">HomeServe</a>
            <div class="nav-links">
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
                <a href="dashboard.php">Dashboard</a>
                <a href="my_bookings.php">My Bookings</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container section">
        <div class="profile-card">
            <h2 style="margin-bottom: 1.5rem;">My Profile</h2>

            <div style="display: flex; justify-content: center; margin-bottom: 2rem;">
                <div style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; background: #e5e7eb; display: flex; align-items: center; justify-content: center; border: 4px solid white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user" style="font-size: 3rem; color: #9ca3af;"></i>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: #f3f4f6;">
                    <small style="color: var(--text-light);">Email cannot be changed.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="+123 456 7890">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Bio / About Me</label>
                    <textarea name="bio" class="form-control" rows="4" placeholder="Tell us a bit about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Profile</button>
            </form>
        </div>
    </div>
</body>
</html>
