<?php
// admin/customers.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
require_once '../config/db_connect.php';

// Handle Delete Customer
if (isset($_POST['delete_customer_id'])) {
    $cid = $_POST['delete_customer_id'];
    try {
        $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND role = 'customer'")->execute([$cid]);
        header("Location: customers.php?deleted=1");
        exit;
    } catch (PDOException $e) {
        $error = "Cannot delete customer. An unexpected database error occurred.";
    }
}

$customers = $pdo->query("SELECT * FROM users WHERE role = 'customer' AND is_active = 1 ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .table-container { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow-sm); margin-bottom: 2rem; max-width: 1000px; margin-left: auto; margin-right: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
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
        <h2 style="text-align: center; margin-bottom: 2rem;">Manage Registered Customers</h2>

        <?php if(isset($error)): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 8px; margin: 0 auto 2rem auto; max-width: 1000px; text-align: center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['deleted'])): ?>
            <div style="background: #dbeafe; color: #1e40af; padding: 1rem; border-radius: 8px; margin: 0 auto 2rem auto; max-width: 1000px; text-align: center;">
                Customer effectively removed.
            </div>
        <?php endif; ?>

        <div class="table-container">
            <h3>All Customers</h3>
            <table style="margin-top: 1rem;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Registered Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($customers as $c): ?>
                        <tr>
                            <td>#<?php echo $c['id']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 32px; height: 32px; background: #f3f4f6; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                                        <?php if (!empty($c['profile_picture'])): ?>
                                            <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($c['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fas fa-user"></i>
                                        <?php endif; ?>
                                    </div>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </div>
                            </td>
                            <td><a href="mailto:<?php echo htmlspecialchars($c['email']); ?>" style="color: var(--primary-color); text-decoration: none;"><i class="fas fa-envelope" style="margin-right: 0.35rem; font-size: 0.85rem;"></i><?php echo htmlspecialchars($c['email']); ?></a></td>
                            <td><a href="tel:<?php echo htmlspecialchars($c['phone'] ?? ''); ?>" style="color: var(--text-dark); text-decoration: none;"><i class="fas fa-phone" style="margin-right: 0.35rem; font-size: 0.85rem;"></i><?php echo htmlspecialchars($c['phone'] ?? 'N/A'); ?></a></td>
                            <td><?php echo htmlspecialchars($c['location'] ?? 'N/A'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                            <td style="text-align: right;">
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="delete_customer_id" value="<?php echo $c['id']; ?>">
                                    <button type="submit" class="btn btn-secondary" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="return confirm('Delete this customer? This action is permanent.');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($customers)): ?>
                <p style="text-align: center; color: var(--text-light); margin-top: 1rem;">No customers found.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
