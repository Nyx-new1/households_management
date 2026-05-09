<?php
// C:\Users\THOMAS\.gemini\antigravity\scratch\household_services_platform\customer\book.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}
require_once '../includes/language.php';
require_once '../config/db_connect.php';

$success = '';
$error = '';
$selected_service_id = isset($_GET['service_id']) ? $_GET['service_id'] : '';
$selected_provider_id = isset($_GET['provider_id']) ? $_GET['provider_id'] : '';

// Fetch services
$services = $pdo->query("SELECT * FROM services")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_id = $_POST['service_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $address = trim($_POST['address']);
    $notes = trim($_POST['notes']);
    $customer_id = $_SESSION['user_id'];

    $scheduled_date = $date . ' ' . $time . ':00';

    $provider_id = !empty($_POST['provider_id']) ? $_POST['provider_id'] : NULL;

    // Get Base Price or Provider's Hourly Rate
    if ($provider_id) {
        $stmt = $pdo->prepare("SELECT hourly_rate FROM provider_details WHERE user_id = ?");
        $stmt->execute([$provider_id]);
        $provider_rate = $stmt->fetchColumn();
        $total_price = $provider_rate ? $provider_rate : 0; // Use provider's specific rate
    } else {
        $stmt = $pdo->prepare("SELECT base_price FROM services WHERE id = ?");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch();
        $total_price = $service['base_price']; // Initial estimated price
    }

    $stmt = $pdo->prepare("INSERT INTO bookings (customer_id, service_id, provider_id, scheduled_date, address, notes, total_price, exchange_rate, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    if ($stmt->execute([$customer_id, $service_id, $provider_id, $scheduled_date, $address, $notes, $total_price, $global_exchange_rate])) {
        $success = "Booking request submitted successfully! A provider will accept it soon.";
        
        if ($provider_id) {
            $msg = "New direct booking request from " . $_SESSION['user_name'] . "!";
            $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, 'dashboard.php')")->execute([$provider_id, $msg]);
        }
    } else {
        $error = "Failed to submit booking.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo">HomeServe</a>
            <div class="nav-links">
                <!-- Language Switcher in Book Page -->
                <div class="lang-switcher">
                    <button class="lang-btn">
                        <i class="fas fa-globe"></i> <span><?php echo $_SESSION['lang'] == 'en' ? 'English' : 'Swahili'; ?></span>
                    </button>
                    <div class="lang-dropdown">
                        <a href="?provider_id=<?php echo $selected_provider_id; ?>&service_id=<?php echo $selected_service_id; ?>&lang=en">English</a>
                        <a href="?provider_id=<?php echo $selected_provider_id; ?>&service_id=<?php echo $selected_service_id; ?>&lang=sw">Swahili</a>
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
        <div class="auth-card" style="max-width: 600px; margin: 0 auto;">
            <h2 style="margin-bottom: 1.5rem; text-align: center;"><?php echo $lang['book_a_service']; ?></h2>

            <?php if($success): ?>
                <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                    <?php echo $success; ?> <a href="my_bookings.php" style="font-weight: bold;"><?php echo $lang['view_booking']; ?></a>
                </div>
            <?php endif; ?>

            <?php 
            // If a provider is selected, show who we are booking with
            if($selected_provider_id) {
                $p_stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                $p_stmt->execute([$selected_provider_id]);
                $provider_name = $p_stmt->fetchColumn();
                if($provider_name) {
                    echo "<div style='text-align:center; margin-bottom: 1rem; color: var(--primary-color); font-weight: 600;'>" . $lang['booking_with'] . " " . htmlspecialchars($provider_name) . "</div>";
                }
            }
            ?>

            <form method="POST" action="">
                <?php if($selected_provider_id): ?>
                    <input type="hidden" name="provider_id" value="<?php echo htmlspecialchars($selected_provider_id); ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label><?php echo $lang['select_service']; ?></label>
                    <select name="service_id" class="form-control" required>
                        <option value="">-- <?php echo $lang['select_service']; ?> --</option>
                        <?php foreach($services as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $selected_service_id == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['name']); ?> - <?php echo formatPrice($s['base_price']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label><?php echo $lang['date_label']; ?></label>
                        <input type="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label>Time</label>
                        <input type="time" name="time" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['address_label']; ?></label>
                    <textarea name="address" class="form-control" rows="3" required placeholder="<?php echo $lang['enter_address_placeholder']; ?>"></textarea>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['additional_notes']; ?></label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="<?php echo $lang['describe_issue_placeholder']; ?>"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;"><?php echo $lang['confirm_booking']; ?></button>
            </form>
        </div>
    </div>

</body>
</html>
