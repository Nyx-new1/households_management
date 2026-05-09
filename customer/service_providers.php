<?php
// C:\Users\THOMAS\.gemini\antigravity\scratch\household_services_platform\customer\service_providers.php
session_start();
require_once '../includes/language.php';
require_once '../config/db_connect.php';

$service_id = isset($_GET['service_id']) ? $_GET['service_id'] : '';

if (!$service_id) {
    header("Location: ../index.php");
    exit;
}

// Fetch Service Details
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    die("Service not found.");
}

// Fetch Providers for this service
// Note: provider_details.service_type stores the Service Name, not ID.
$stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.phone, u.profile_picture, pd.bio, pd.hourly_rate, pd.is_verified, pd.service_type,
    (SELECT COALESCE(AVG(rating), 0) FROM reviews r JOIN bookings b ON r.booking_id = b.id WHERE b.provider_id = u.id) as avg_rating,
    (SELECT COUNT(r.id) FROM reviews r JOIN bookings b ON r.booking_id = b.id WHERE b.provider_id = u.id) as review_count
    FROM users u 
    JOIN provider_details pd ON u.id = pd.user_id 
    WHERE u.role = 'provider' AND pd.service_type = ?");
$stmt->execute([$service['name']]);
$providers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best <?php echo htmlspecialchars($service['name']); ?> Providers - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .provider-card {
            background: white;
            border-radius: var(--radius-md);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .provider-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .provider-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .verified-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .price-tag {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.125rem;
        }
    </style>
</head>
<body style="background-color: var(--bg-light);">

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo">
                <i class="fas fa-home"></i> HomeServe
            </a>
            <div class="nav-links">
                <a href="../index.php"><?php echo $lang['home']; ?></a>
                <a href="../index.php#services"><?php echo $lang['services']; ?></a>
                
                <!-- Language Switcher -->
                <div class="lang-switcher">
                    <button class="lang-btn">
                        <i class="fas fa-globe"></i> <span><?php echo $_SESSION['lang'] == 'en' ? 'English' : 'Swahili'; ?></span>
                    </button>
                    <div class="lang-dropdown">
                        <a href="?service_id=<?php echo $service_id; ?>&lang=en">English</a>
                        <a href="?service_id=<?php echo $service_id; ?>&lang=sw">Swahili</a>
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
                    <a href="dashboard.php"><?php echo $lang['dashboard']; ?></a>
                    <a href="logout.php" class="btn btn-secondary"><?php echo $lang['logout']; ?></a>
                <?php else: ?>
                    <a href="login.php"><?php echo $lang['login']; ?></a>
                    <a href="register.php" class="btn btn-primary"><?php echo $lang['register']; ?></a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container section">
        <div class="section-title">
            <h2><?php echo sprintf($lang['top_rated_professionals'], htmlspecialchars($service['name'])); ?></h2>
            <p><?php echo $lang['select_expert']; ?></p>
        </div>

        <?php if(count($providers) > 0): ?>
            <div class="grid">
                <?php foreach($providers as $p): ?>
                    <div class="provider-card">
                        <div class="provider-header">
                            <div style="display: flex; gap: 1rem;">
                                <div style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; background: #e5e7eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <?php if (!empty($p['profile_picture'])): ?>
                                        <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($p['profile_picture']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;" onclick="openProfileModal(this.src)">
                                    <?php else: ?>
                                        <i class="fas fa-user" style="font-size: 1.5rem; color: #9ca3af;"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3 style="margin-bottom: 0.25rem;"><a href="../public_profile.php?id=<?php echo $p['id']; ?>" style="text-decoration: none; color: inherit;"><?php echo htmlspecialchars($p['name']); ?></a></h3>
                                    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; margin-top: 0.25rem;">
                                        <?php if($p['is_verified']): ?>
                                            <span class="verified-badge"><i class="fas fa-check-circle"></i> <?php echo $lang['verified_provider']; ?></span>
                                        <?php endif; ?>
                                        <span style="font-size: 0.85rem; color: #fbbf24; background: #fffbeb; padding: 0.15rem 0.4rem; border-radius: 4px; font-weight: 600; border: 1px solid #fde68a;">
                                            <i class="fas fa-star"></i> <?php echo $p['review_count'] > 0 ? number_format($p['avg_rating'], 1) : 'New'; ?> 
                                            <span style="color: #92400e; font-weight: 400; font-size: 0.75rem;">(<?php echo $p['review_count']; ?>)</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="price-tag"><?php echo formatPrice($p['hourly_rate']); ?><?php echo $lang['per_hour']; ?></div>
                        </div>
                        
                        <p style="color: var(--text-light); font-size: 0.95rem; line-height: 1.5;">
                            <?php echo nl2br(htmlspecialchars($p['bio'])); ?>
                        </p>

                        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                            <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; justify-content: center;">
                                    <a href="mailto:<?php echo htmlspecialchars($p['email']); ?>" style="flex: 1; text-align: center; background: #f3f4f6; color: #4b5563; padding: 0.4rem 0.75rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 500; display: inline-block; transition: background 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                                        <i class="fas fa-envelope"></i> Email
                                    </a>
                                    <?php if(!empty($p['phone'])): ?>
                                    <a href="tel:<?php echo htmlspecialchars($p['phone']); ?>" style="flex: 1; text-align: center; background: #f3f4f6; color: #4b5563; padding: 0.4rem 0.75rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 500; display: inline-block; transition: background 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                                        <i class="fas fa-phone"></i> Call
                                    </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; margin-bottom: 1rem;">
                                    <span style="font-size: 0.85rem; color: var(--text-light);"><i class="fas fa-lock"></i> Login to view contact info</span>
                                </div>
                            <?php endif; ?>
                            <!-- Link to book.php with service_id AND provider_id -->
                            <a href="book.php?service_id=<?php echo $service['id']; ?>&provider_id=<?php echo $p['id']; ?>" class="btn btn-primary" style="width: 100%; text-align: center;"><?php echo $lang['book_now']; ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 4rem; background: white; border-radius: var(--radius-md);">
                <i class="fas fa-search" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                <h3><?php echo $lang['no_providers_found']; ?></h3>
                <p style="color: var(--text-light);"><?php echo $lang['no_providers_desc']; ?></p>
                <div style="margin-top: 1.5rem;">
                    <a href="../index.php#services" class="btn btn-secondary"><?php echo $lang['browse_other_services']; ?></a>
                </div>
            </div>
        <?php endif; ?>
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

        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) closeProfileModal();
        });
    </script>
</body>
</html>
