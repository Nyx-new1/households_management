<?php
session_start();
require_once 'config/db_connect.php';
require_once 'includes/language.php';

if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$user_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT id, name, role, location, bio, profile_picture FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

$provider_details = null;
$reviews = [];
$avg_rating = 0;
$review_count = 0;

if ($user['role'] === 'provider') {
    $stmt = $pdo->prepare("SELECT * FROM provider_details WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $provider_details = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT r.rating, r.comment, r.created_at, u.name as customer_name
        FROM reviews r
        JOIN bookings b ON r.booking_id = b.id
        JOIN users u ON b.customer_id = u.id
        WHERE b.provider_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $reviews = $stmt->fetchAll();

    if (count($reviews) > 0) {
        $total_rating = array_sum(array_column($reviews, 'rating'));
        $review_count = count($reviews);
        $avg_rating = $total_rating / $review_count;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['name']); ?>'s Profile - HomeServe</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), #2563eb);
            color: white;
            padding: 3rem 1rem;
            text-align: center;
            border-radius: 12px 12px 0 0;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            margin: -75px auto 1rem auto;
            background: #e5e7eb;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
        }
        .profile-body {
            padding: 2rem;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            margin: 0.5rem 0;
        }
        .badge-customer { background: #dbeafe; color: #1e40af; }
        .badge-provider { background: #fef3c7; color: #92400e; }
        .badge-verified { background: #d1fae5; color: #065f46; margin-left: 0.5rem; }
    </style>
</head>
<body style="background-color: var(--bg-light);">

    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo">HomeServe</a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <!-- Decorative Header Background -->
            </div>
            
            <div class="profile-avatar">
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="assets/uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;" onclick="openProfileModal(this.src)">
                <?php else: ?>
                    <i class="fas fa-user" style="font-size: 4rem; color: #9ca3af;"></i>
                <?php endif; ?>
            </div>

            <div class="profile-body">
                <h1 style="margin-bottom: 0.25rem;"><?php echo htmlspecialchars($user['name']); ?></h1>
                
                <div style="margin-bottom: 1.5rem;">
                    <?php if ($user['role'] === 'customer'): ?>
                        <span class="badge badge-customer">Customer</span>
                    <?php elseif ($user['role'] === 'provider'): ?>
                        <span class="badge badge-provider">Service Provider</span>
                        <?php if ($provider_details && $provider_details['is_verified']): ?>
                            <span class="badge badge-verified"><i class="fas fa-check-circle"></i> Verified</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($user['location'])): ?>
                    <p style="color: var(--text-light); margin-bottom: 1rem;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['location']); ?></p>
                <?php endif; ?>

                <?php if ($user['role'] === 'provider' && $provider_details): ?>
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px; display: inline-block; text-align: left;">
                        <p style="margin-bottom: 0.5rem;"><strong>Expertise:</strong> <?php echo htmlspecialchars($provider_details['service_type']); ?></p>
                        <p style="margin-bottom: 0.5rem;"><strong>Hourly Rate:</strong> <?php echo formatPrice($provider_details['hourly_rate'] ?? 0); ?>/hr</p>
                        <?php if (isset($provider_details['experience_years']) && $provider_details['experience_years'] > 0): ?>
                            <p style="margin-bottom: 0;"><strong>Experience:</strong> <?php echo (int)$provider_details['experience_years']; ?> years</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div style="text-align: left; margin-top: 1rem;">
                    <h3 style="margin-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">About</h3>
                    <?php
                        $bio = $user['bio'];
                        if ($user['role'] === 'provider' && empty($bio)) {
                            $bio = $provider_details['bio'] ?? '';
                        }
                    ?>
                    <?php if (!empty($bio)): ?>
                        <p style="color: var(--text-dark); line-height: 1.6; font-size: 0.95rem;">
                            <?php echo nl2br(htmlspecialchars($bio)); ?>
                        </p>
                    <?php else: ?>
                        <p style="color: var(--text-light); font-style: italic;">This user hasn't added a bio yet.</p>
                    <?php endif; ?>
                </div>

                <?php if ($user['role'] === 'provider'): ?>
                    <div style="text-align: left; margin-top: 3rem;">
                        <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
                            <span>Reviews (<?php echo $review_count; ?>)</span>
                            <?php if ($review_count > 0): ?>
                                <span style="color: #fbbf24; font-size: 1.1rem;"><i class="fas fa-star"></i> <?php echo number_format($avg_rating, 1); ?></span>
                            <?php endif; ?>
                        </h3>
                        
                        <?php if (empty($reviews)): ?>
                            <p style="color: var(--text-light);">No reviews yet.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php foreach ($reviews as $rev): ?>
                                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <strong style="color: var(--text-dark);"><?php echo htmlspecialchars($rev['customer_name']); ?></strong>
                                            <span style="color: #fbbf24; letter-spacing: 2px;">
                                                <?php for($i=0; $i<$rev['rating']; $i++) echo '&#9733;'; ?>
                                            </span>
                                        </div>
                                        <p style="font-size: 0.9rem; color: var(--text-dark); line-height: 1.5;">"<?php echo nl2br(htmlspecialchars($rev['comment'])); ?>"</p>
                                        <small style="color: var(--text-light); display: block; margin-top: 0.5rem;"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Instagram-like Image Modal Component -->
    <div id="imageModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.8); backdrop-filter:blur(5px); justify-content:center; align-items:center; opacity: 0; transition: opacity 0.3s ease;">
        <span onclick="closeProfileModal()" style="position:absolute; top:20px; right:35px; color:#f1f1f1; font-size:40px; font-weight:bold; cursor:pointer; transition: 0.3s;">&times;</span>
        <img id="modalImage" style="margin:auto; display:block; max-width:90%; max-height:90%; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.5); transform: scale(0.8); transition: transform 0.3s ease;">
    </div>

    <script>
        function openProfileModal(src) {
            if(!src || src.includes('fa-user')) return;
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'flex';
            
            // Trigger reflow
            void modal.offsetWidth;
            
            modal.style.opacity = '1';
            modalImg.src = src;
            modalImg.style.transform = 'scale(1)';
            document.body.style.overflow = 'hidden';
        }

        function closeProfileModal() {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.opacity = '0';
            modalImg.style.transform = 'scale(0.8)';
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }

        // Close on background click
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) closeProfileModal();
        });
    </script>
</body>
</html>
