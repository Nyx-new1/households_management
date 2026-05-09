<?php
// admin/services.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
require_once '../config/db_connect.php';
require_once '../includes/language.php';

// Handle Add Service
if (isset($_POST['add_service'])) {
    $name = trim($_POST['service_name']);
    $price = (float)($_POST['base_price'] ?? 0);
    if (!empty($name)) {
        // Check for duplicate (case-insensitive)
        $stmt = $pdo->prepare("SELECT id FROM services WHERE LOWER(name) = LOWER(?)");
        $stmt->execute([$name]);
        if ($stmt->rowCount() > 0) {
            $error = "A service with that name already exists.";
        } else {
            $pdo->prepare("INSERT INTO services (name, base_price) VALUES (?, ?)")->execute([$name, $price]);
            header("Location: services.php?success=1");
            exit;
        }
    }
}

// Handle Delete Service
if (isset($_POST['delete_service'])) {
    $sid = $_POST['service_id'];
    // Try to hard delete
    try {
        $pdo->prepare("DELETE FROM services WHERE id = ?")->execute([$sid]);
        header("Location: services.php?deleted=1");
        exit;
    } catch (PDOException $e) {
        $error = "Cannot delete service. It is likely linked to existing bookings.";
    }
}

$services = $pdo->query("SELECT * FROM services ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Services - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .table-container { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow-sm); margin-bottom: 2rem; max-width: 800px; margin-left: auto; margin-right: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .form-container { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow-sm); max-width: 800px; margin: 0 auto 2rem auto; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; }
    </style>
</head>
<body style="background-color: var(--bg-light);">

    <nav class="navbar" style="background: var(--text-dark); border-bottom: none;">
        <div class="container nav-content">
            <a href="dashboard.php" class="logo" style="color: white;">Admin Panel</a>
            <div class="nav-links">
                <a href="dashboard.php" style="color: white; text-decoration: none; margin-right: 1.5rem; font-weight: 500;">Dashboard</a>
                <a href="reviews.php" style="color: white; text-decoration: none; margin-right: 1.5rem; font-weight: 500;">Reviews</a>
                <a href="services.php" style="color: white; text-decoration: none; margin-right: 1.5rem; font-weight: 500;">Services</a>
                <a href="customers.php" style="color: white; text-decoration: none; margin-right: 1.5rem; font-weight: 500;">Customers</a>
                <a href="providers.php" style="color: white; text-decoration: none; margin-right: 1.5rem; font-weight: 500;">Providers</a>
                <a href="../customer/logout.php" class="btn btn-secondary" style="background: transparent; color: white; border-color: white;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container section">
        <h2 style="text-align: center; margin-bottom: 2rem;">Manage Service Categories</h2>

        <?php if(isset($error)): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 8px; margin: 0 auto 2rem auto; max-width: 800px; text-align: center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['success'])): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin: 0 auto 2rem auto; max-width: 800px; text-align: center;">
                Service category added successfully.
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['deleted'])): ?>
            <div style="background: #dbeafe; color: #1e40af; padding: 1rem; border-radius: 8px; margin: 0 auto 2rem auto; max-width: 800px; text-align: center;">
                Service category deleted successfully.
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h3>Add New Service</h3>
            <form method="POST" style="margin-top: 1rem; display: flex; gap: 1rem; align-items: flex-end;">
                <div style="flex: 2;">
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 0.25rem;">Service Name</label>
                    <input type="text" name="service_name" class="form-control" placeholder="e.g. Pest Control" required>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 0.25rem;">Base Price (USD)</label>
                    <input type="number" name="base_price" step="0.01" class="form-control" placeholder="0.00" required>
                </div>
                <button type="submit" name="add_service" class="btn btn-primary" style="height: 48px;">Add Service</button>
            </form>
        </div>

        <div class="table-container">
            <h3>Existing Services</h3>
            <table style="margin-top: 1rem;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Service Name</th>
                        <th>Base Price</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($services as $s): ?>
                        <tr>
                            <td>#<?php echo $s['id']; ?></td>
                            <td><?php echo htmlspecialchars($s['name']); ?></td>
                            <td><?php echo formatPrice($s['base_price'] ?: 0); ?></td>
                            <td style="text-align: right;">
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="service_id" value="<?php echo $s['id']; ?>">
                                    <button type="submit" name="delete_service" class="btn btn-secondary" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="return confirm('Delete this service permanently? Note: This may fail if there are bookings attached to it.');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($services)): ?>
                <p style="text-align: center; color: var(--text-light); margin-top: 1rem;">No services found.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
