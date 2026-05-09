<?php
// C:\Users\THOMAS\.gemini\antigravity\scratch\household_services_platform\index.php
session_start();
require_once 'includes/language.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeServe - Premium Household Services</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo">
                <i class="fas fa-home"></i> HomeServe
            </a>
            <div class="nav-links">
                <a href="index.php"><?php echo $lang['home']; ?></a>
                <a href="#services"><?php echo $lang['services']; ?></a>
                
                <!-- Language Switcher -->
                <div class="lang-switcher">
                    <button class="lang-btn">
                        <i class="fas fa-globe"></i> <span><?php echo $_SESSION['lang'] == 'en' ? 'English' : 'Swahili'; ?></span>
                    </button>
                    <div class="lang-dropdown">
                        <a href="?lang=en">English (US)</a>
                        <a href="?lang=sw">Swahili</a>
                    </div>
                </div>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-right: 1rem;">
                        <div style="width: 35px; height: 35px; border-radius: 50%; overflow: hidden; background: #e5e7eb;">
                            <?php if(isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])): ?>
                                <img src="assets/uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle" style="font-size: 35px; color: #9ca3af;"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if($_SESSION['role'] == 'provider'): ?>
                        <a href="provider/dashboard.php"><?php echo $lang['dashboard']; ?></a>
                    <?php else: ?>
                        <a href="customer/dashboard.php"><?php echo $lang['dashboard']; ?></a>
                    <?php endif; ?>
                    <a href="customer/logout.php" class="btn btn-secondary"><?php echo $lang['logout']; ?></a>
                <?php else: ?>
                    <a href="customer/login.php"><?php echo $lang['login']; ?></a>
                    <a href="customer/register.php" class="btn btn-primary"><?php echo $lang['book_now']; ?></a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="about" class="hero" style="background: linear-gradient(rgba(240, 244, 255, 0.4), rgba(240, 244, 255, 0.5)), url('assets/img/about-bg.jpg') center/cover;">
        <div class="container hero-content fade-in" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 2rem; min-height: 50vh;">
            <div style="max-width: 800px; padding: 3.5rem 2rem; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-radius: var(--radius-xl); box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(255, 255, 255, 0.5);">
                <h1 style="text-align: center; margin-bottom: 1rem;"><?php echo $lang['hero_title']; ?></h1>
                <p style="text-align: center; margin-bottom: 2.5rem; color: var(--text-dark); font-weight: 500; font-size: 1.15rem;"><?php echo $lang['hero_desc']; ?></p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="customer/register.php" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem; box-shadow: var(--shadow-md);"><?php echo $lang['get_started']; ?></a>
                    <a href="#services" class="btn btn-secondary" style="padding: 1rem 2rem; font-size: 1.1rem; background: rgba(255,255,255,0.9); box-shadow: var(--shadow-sm); border: none;"><?php echo $lang['learn_more']; ?></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="section" style="background: linear-gradient(rgba(240, 244, 255, 0.85), rgba(240, 244, 255, 0.85)), url('assets/img/hero-image.jpg') center/cover; background-attachment: fixed;">
        <div class="container">
            <div class="section-title">
                <h2><?php echo $lang['our_services']; ?></h2>
                <p><?php echo $lang['services_desc']; ?></p>
                

            </div>
            
            <div class="grid">
                <!-- Service 1 -->
                <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                    <!-- INSTRUCTIONS: Upload your photo as 'cleaning.jpg' in 'assets/img/' directory. -->
                    <img src="assets/img/cleaning.jpg" alt="Home Cleaning" style="width: 100%; height: 180px; object-fit: cover; background: rgba(0,0,0,0.05);" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1527515637462-cff94eecc1ac?auto=format&fit=crop&q=80&w=400&h=250';">
                    <div style="padding: 2rem; flex: 1; display: flex; flex-direction: column;">
                        <div class="service-icon" style="margin-top: -3rem; background: var(--white); box-shadow: var(--shadow-md);">
                            <i class="fas fa-broom"></i>
                        </div>
                        <h3><?php echo $lang['home_cleaning']; ?></h3>
                        <p style="flex: 1;"><?php echo $lang['home_cleaning_desc']; ?></p>
                        <a href="customer/service_providers.php?service_id=1" style="color: var(--primary-color); text-decoration: none; font-weight: 600; margin-top: auto;"><?php echo $lang['book_cleaning']; ?> &rarr;</a>
                    </div>
                </div>

                <!-- Service 2 -->
                <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                    <!-- INSTRUCTIONS: Upload your photo as 'laundry.jpg' in 'assets/img/' directory. -->
                    <img src="assets/img/laundry.jpg" alt="Premium Laundry" style="width: 100%; height: 180px; object-fit: cover; background: rgba(0,0,0,0.05);" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1545173168-9f1947eebb7f?auto=format&fit=crop&q=80&w=400&h=250';">
                    <div style="padding: 2rem; flex: 1; display: flex; flex-direction: column;">
                        <div class="service-icon" style="margin-top: -3rem; background: var(--white); box-shadow: var(--shadow-md);">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <h3><?php echo $lang['premium_laundry']; ?></h3>
                        <p style="flex: 1;"><?php echo $lang['premium_laundry_desc']; ?></p>
                        <a href="customer/service_providers.php?service_id=4" style="color: var(--primary-color); text-decoration: none; font-weight: 600; margin-top: auto;"><?php echo $lang['schedule_pickup']; ?> &rarr;</a>
                    </div>
                </div>

                <!-- Service 3 -->
                <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                    <!-- INSTRUCTIONS: Upload your photo as 'plumbing.jpg' in 'assets/img/' directory. -->
                    <img src="assets/img/plumbing.jpg" alt="Plumbing Repair" style="width: 100%; height: 180px; object-fit: cover; background: rgba(0,0,0,0.05);" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1607472586893-edb57cb5b28b?auto=format&fit=crop&q=80&w=400&h=250';">
                    <div style="padding: 2rem; flex: 1; display: flex; flex-direction: column;">
                        <div class="service-icon" style="margin-top: -3rem; background: var(--white); box-shadow: var(--shadow-md);">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <h3><?php echo $lang['plumbing_repair']; ?></h3>
                        <p style="flex: 1;"><?php echo $lang['plumbing_repair_desc']; ?></p>
                        <a href="customer/service_providers.php?service_id=2" style="color: var(--primary-color); text-decoration: none; font-weight: 600; margin-top: auto;"><?php echo $lang['find_plumber']; ?> &rarr;</a>
                    </div>
                </div>

                <!-- Service 4 -->
                <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                    <!-- INSTRUCTIONS: Upload your photo as 'electrical.jpg' in 'assets/img/' directory. -->
                    <img src="assets/img/electrical.jpg" alt="Electrical Works" style="width: 100%; height: 180px; object-fit: cover; background: rgba(0,0,0,0.05);" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&q=80&w=400&h=250';">
                    <div style="padding: 2rem; flex: 1; display: flex; flex-direction: column;">
                        <div class="service-icon" style="margin-top: -3rem; background: var(--white); box-shadow: var(--shadow-md);">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3><?php echo $lang['electrical_works']; ?></h3>
                        <p style="flex: 1;"><?php echo $lang['electrical_works_desc']; ?></p>
                        <a href="customer/service_providers.php?service_id=3" style="color: var(--primary-color); text-decoration: none; font-weight: 600; margin-top: auto;"><?php echo $lang['hire_electrician']; ?> &rarr;</a>
                    </div>
                </div>

                <!-- Service 5: Painting & Decoration -->
                <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                    <!-- INSTRUCTIONS: Upload your photo as 'painting.jpg' in 'assets/img/' directory. -->
                    <img src="assets/img/painting.jpg" alt="Painting" style="width: 100%; height: 180px; object-fit: cover; background: rgba(0,0,0,0.05);" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1589939705384-5185137a7f0f?auto=format&fit=crop&q=80&w=400&h=250';">
                    <div style="padding: 2rem; flex: 1; display: flex; flex-direction: column;">
                        <div class="service-icon" style="margin-top: -3rem; background: var(--white); box-shadow: var(--shadow-md);">
                            <i class="fas fa-paint-roller"></i>
                        </div>
                        <h3><?php echo $lang['painting_decoration']; ?></h3>
                        <p style="flex: 1;"><?php echo $lang['painting_decoration_desc']; ?></p>
                        <a href="customer/service_providers.php?service_id=7" style="color: var(--primary-color); text-decoration: none; font-weight: 600; margin-top: auto;"><?php echo $lang['explore_painting']; ?> &rarr;</a>
                    </div>
                </div>

                <!-- Service 6: Carpentry -->
                <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                    <!-- INSTRUCTIONS: Upload your photo as 'carpentry.jpg' in 'assets/img/' directory. -->
                    <img src="assets/img/carpentry.jpg" alt="Carpentry" style="width: 100%; height: 180px; object-fit: cover; background: rgba(0,0,0,0.05);" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1533090481720-856c6e3c1fdc?auto=format&fit=crop&q=80&w=400&h=250';">
                    <div style="padding: 2rem; flex: 1; display: flex; flex-direction: column;">
                        <div class="service-icon" style="margin-top: -3rem; background: var(--white); box-shadow: var(--shadow-md);">
                            <i class="fas fa-hammer"></i>
                        </div>
                        <h3><?php echo $lang['carpentry_services']; ?></h3>
                        <p style="flex: 1;"><?php echo $lang['carpentry_services_desc']; ?></p>
                        <a href="customer/service_providers.php?service_id=6" style="color: var(--primary-color); text-decoration: none; font-weight: 600; margin-top: auto;"><?php echo $lang['hire_carpenter']; ?> &rarr;</a>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 3rem;">
                <a href="customer/register.php" class="btn btn-primary" style="padding: 1rem 3rem; font-size: 1.1rem;"><?php echo $lang['more']; ?></a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section" style="background-color: white;">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose HomeServe?</h2>
            </div>
            <div class="grid" style="grid-template-columns: repeat(3, 1fr); text-align: center;">
                <div>
                    <i class="fas fa-user-check" style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h4>Verified Professionals</h4>
                    <p style="color: var(--text-light); margin-top: 0.5rem;">All our providers are background checked and verified.</p>
                </div>
                <div>
                    <i class="fas fa-clock" style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h4>On-Demand Scheduling</h4>
                    <p style="color: var(--text-light); margin-top: 0.5rem;">Pick a time that works for you. 7 days a week.</p>
                </div>
                <div>
                    <i class="fas fa-shield-alt" style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h4>Secure Payments</h4>
                    <p style="color: var(--text-light); margin-top: 0.5rem;">Pay securely online or via mobile money.</p>
                </div>
            </div>
        </div>
    </section>

    <footer style="margin-top: auto; background: linear-gradient(rgba(17, 24, 39, 0.9), rgba(17, 24, 39, 0.9)), url('assets/img/footer-bg.jpg') center/cover; color: white; padding: 3rem 0;">
        <div class="container" style="text-align: center;">
            <div style="margin-bottom: 2rem; display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Company</h4>
                    <a href="#about" style="color: #9ca3af; text-decoration: none; display: block; margin-bottom: 0.5rem;">About Us</a>
                    <a href="#" style="color: #9ca3af; text-decoration: none; display: block; margin-bottom: 0.5rem;">Privacy Policy</a>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Contact Us</h4>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <a href="https://wa.me/255614470672" style="color: #9ca3af; text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" title="WhatsApp">
                            <i class="fab fa-whatsapp" style="font-size: 2rem; color: #25D366;"></i>
                        </a>
                        <a href="mailto:thomasmaketa89@gmail.com" style="color: #9ca3af; text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" title="Email">
                            <i class="fas fa-envelope" style="font-size: 2rem; color: #EA4335;"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Partners</h4>
                    <a href="provider/login.php" style="color: #9ca3af; text-decoration: none; display: block; margin-bottom: 0.5rem;">For Service Providers</a>
                    <a href="delivery/login.php" style="color: #9ca3af; text-decoration: none; display: block; margin-bottom: 0.5rem;">For Delivery Partners</a>
                </div>
            </div>
            <p style="color: #6b7280; border-top: 1px solid #374151; padding-top: 2rem;">&copy; 2026 HomeServe Platform. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Scripts can be added here if needed in the future
    </script>
</body>
</html>
