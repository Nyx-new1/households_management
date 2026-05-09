<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}
require_once '../includes/language.php';
require_once '../config/db_connect.php';

if (!isset($_GET['booking_id'])) {
    header("Location: my_bookings.php");
    exit;
}

$booking_id = $_GET['booking_id'];
$customer_id = $_SESSION['user_id'];

// Fetch booking details
$stmt = $pdo->prepare("
    SELECT b.*, s.name as service_name, u.name as provider_name 
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    LEFT JOIN users u ON b.provider_id = u.id 
    WHERE b.id = ? AND b.customer_id = ?
");
$stmt->execute([$booking_id, $customer_id]);
$booking = $stmt->fetch();

if (!$booking) {
    echo "Booking not found.";
    exit;
}

// Check if already paid
$stmt = $pdo->prepare("SELECT id FROM payments WHERE booking_id = ? AND status = 'completed'");
$stmt->execute([$booking_id]);
if ($stmt->fetch()) {
    header("Location: my_bookings.php?message=already_paid");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .payment-form {
            max-width: 550px;
            margin: 2rem auto;
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.75rem; font-weight: 600; color: var(--text-dark); }
        .form-control {
            width: 100%;
            padding: 0.85rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 0.5rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .payment-methods-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .method-card {
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .method-card:hover { border-color: var(--primary-color); background: #f8fafc; }
        .method-card.active {
            border-color: var(--primary-color);
            background: #eff6ff;
            box-shadow: 0 0 0 2px var(--primary-color);
        }
        .method-icon { font-size: 1.5rem; margin-bottom: 0.25rem; }
        
        /* Carrier Colors */
        .color-mpesa { color: #e11d48; }
        .color-airtel { color: #dc2626; }
        .color-tpesa { color: #0284c7; }
        .color-halopesa { color: #f59e0b; }
        .color-mixx { color: #ca8a04; }

        .booking-summary {
            background: #f8fafc;
            padding: 1.25rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border: 1px solid #f1f5f9;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .total-row {
            font-weight: 700;
            font-size: 1.2rem;
            border-top: 2px dashed #e2e8f0;
            padding-top: 0.75rem;
            margin-top: 0.75rem;
            color: var(--text-dark);
        }

        /* Processing Overlay */
        #payment-loader {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.9);
            z-index: 9999;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .spinner {
            width: 50px; height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1.5rem;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo">HomeServe</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="my_bookings.php">My Bookings</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div id="payment-loader">
        <div class="spinner"></div>
        <h2 id="loader-title">Processing Payment</h2>
        <p id="loader-msg" style="color: var(--text-light); margin-top: 0.5rem;">Please check your phone for the STK push notification...</p>
    </div>

    <div class="container section">
        <div class="payment-form">
            <h2 style="margin-bottom: 0.5rem; text-align: center;">Checkout</h2>
            <p style="text-align: center; color: var(--text-light); margin-bottom: 2rem; font-size: 0.9rem;">Secure payment for your service booking</p>
            
            <div class="booking-summary">
                <div class="summary-row">
                    <span>Service:</span>
                    <strong><?php echo htmlspecialchars($booking['service_name']); ?></strong>
                </div>
                <div class="summary-row">
                    <span>Scheduled Date:</span>
                    <span><?php echo date('M d, Y', strtotime($booking['scheduled_date'])); ?></span>
                </div>
                <div class="summary-row total-row">
                    <span>Amount Payable:</span>
                    <span style="color: var(--primary-color);"><?php echo formatPrice($booking['total_price'], $booking['exchange_rate'] ?? null); ?></span>
                </div>
            </div>

            <form id="main-payment-form" action="process_payment.php" method="POST">
                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                <input type="hidden" name="amount" value="<?php echo $booking['total_price']; ?>">
                <input type="hidden" name="method" id="selected-method" value="">
                
                <label class="form-label">Select Payment Method</label>
                <div class="payment-methods-grid">
                    <div class="method-card" data-method="card">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                            <div style="display: flex; gap: 4px; align-items: center;">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" style="height: 18px; width: auto;">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" style="height: 25px; width: auto;">
                            </div>
                            <span style="font-size: 0.85rem; font-weight: 700;">Card</span>
                        </div>
                    </div>
                    <div class="method-card" data-method="mpesa">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/03/M-pesa_logo.png" alt="M-Pesa" style="height: 35px; width: auto; object-fit: contain;">
                        <span style="font-size: 0.8rem; font-weight: 600;">M-Pesa</span>
                    </div>
                    <div class="method-card" data-method="airtel">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Airtel_logo.svg/1024px-Airtel_logo.svg.png" alt="Airtel" style="height: 30px; width: auto; object-fit: contain;">
                        <span style="font-size: 0.8rem; font-weight: 600;">Airtel</span>
                    </div>
                    <div class="method-card" data-method="tpesa">
                        <img src="https://www.ttcl.co.tz/themes/ttcl/images/T-pesa%20Logo.png" alt="T-Pesa" style="height: 35px; width: auto; object-fit: contain;">
                        <span style="font-size: 0.8rem; font-weight: 600;">T-Pesa</span>
                    </div>
                    <div class="method-card" data-method="halopesa">
                        <img src="https://halotel.co.tz/uploads/Halopesa-Logo-Recovered.png" alt="HaloPesa" style="height: 35px; width: auto; object-fit: contain;">
                        <span style="font-size: 0.8rem; font-weight: 600;">HaloPesa</span>
                    </div>
                    <div class="method-card" data-method="mixx">
                        <img src="https://www.zantel.co.tz/img/mixx_by_yas_logo.png" alt="Mixx/Yas" style="height: 35px; width: auto; object-fit: contain;">
                        <span style="font-size: 0.8rem; font-weight: 600;">Mixx/Yas</span>
                    </div>
                    <div class="method-card" data-method="cash" style="grid-column: span 3; padding: 1.25rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; justify-content: center;">
                            <i class="fas fa-money-bill-wave" style="color: #10b981; font-size: 1.5rem;"></i>
                            <span style="font-size: 0.9rem; font-weight: 700;">Pay with Cash on Completion</span>
                        </div>
                    </div>
                </div>

                <!-- Simulation of card details -->
                <div id="card-details-fields" style="display: none; animation: fadeIn 0.3s ease;">
                    <div class="form-group">
                        <label class="form-label">Cardholder Name</label>
                        <input type="text" class="form-control" name="card_name" placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Card Number</label>
                        <div style="position: relative;">
                            <input type="text" class="form-control" name="card_num" placeholder="0000 0000 0000 0000" maxlength="19">
                            <div style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); display: flex; gap: 10px; align-items: center; background: white; padding-left: 10px;">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" style="height: 14px; width: auto;">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" style="height: 22px; width: auto;">
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" name="card_expiry" placeholder="MM/YY" maxlength="5">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">CVV</label>
                            <input type="password" class="form-control" name="card_cvv" placeholder="123" maxlength="3">
                        </div>
                    </div>
                </div>

                <div id="mobile-money-fields" style="display: none; animation: fadeIn 0.3s ease;">
                    <div class="form-group">
                        <label class="form-label">Mobile Number</label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span style="background: #f1f5f9; padding: 0.85rem; border-radius: 0.5rem; font-weight: 600; border: 1px solid #e2e8f0;">+255</span>
                            <input type="tel" name="phone_number" class="form-control" placeholder="7XXXXXXXX" maxlength="9">
                        </div>
                        <p style="font-size: 0.75rem; color: var(--text-light); margin-top: 0.5rem;">Enter number without leading 0 or +255</p>
                    </div>
                </div>

                <button type="submit" id="pay-btn" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; border-radius: 0.75rem;" disabled>Complete Booking & Pay</button>
            </form>
        </div>
    </div>

    <script>
        const methodCards = document.querySelectorAll('.method-card');
        const selectedMethodInput = document.getElementById('selected-method');
        const cardFields = document.getElementById('card-details-fields');
        const mobileFields = document.getElementById('mobile-money-fields');
        const payBtn = document.getElementById('pay-btn');
        const form = document.getElementById('main-payment-form');
        const loader = document.getElementById('payment-loader');
        const loaderTitle = document.getElementById('loader-title');
        const loaderMsg = document.getElementById('loader-msg');

        methodCards.forEach(card => {
            card.addEventListener('click', () => {
                // UI Toggle
                methodCards.forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                
                const method = card.dataset.method;
                selectedMethodInput.value = method;
                payBtn.disabled = false;

                // Field Toggle
                if (method === 'card') {
                    cardFields.style.display = 'block';
                    mobileFields.style.display = 'none';
                    payBtn.textContent = 'Pay with Card';
                } else if (['mpesa', 'airtel', 'tpesa', 'halopesa', 'mixx'].includes(method)) {
                    cardFields.style.display = 'none';
                    mobileFields.style.display = 'block';
                    const name = card.querySelector('span').textContent;
                    payBtn.textContent = 'Pay with ' + name;
                } else {
                    cardFields.style.display = 'none';
                    mobileFields.style.display = 'none';
                    payBtn.textContent = 'Confirm Job & Pay Cash';
                }
            });
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const method = selectedMethodInput.value;
            const phone = document.querySelector('input[name="phone_number"]').value || "your number";
            
            // Show Loader
            loader.style.display = 'flex';
            
            if (['mpesa', 'airtel', 'tpesa', 'halopesa', 'mixx'].includes(method)) {
                const name = document.querySelector(`.method-card[data-method="${method}"] span`).textContent;
                
                // Stage 1: Initiating
                loaderTitle.textContent = "Initiating STK Push...";
                loaderMsg.innerHTML = `Connecting to ${name} gateway for <strong>+255 ${phone}</strong>...`;
                
                setTimeout(() => {
                    // Stage 2: Waiting for PIN
                    loaderTitle.textContent = "Waiting for Handset PIN...";
                    loaderMsg.innerHTML = `<span style="color: #ef4444; font-weight: bold; font-size: 1.1rem;"><i class="fas fa-hand-pointer"></i> ACTION REQUIRED</span><br>Please check your phone for a PIN prompt to authorize payment.`;
                    
                    setTimeout(() => {
                        // Stage 3: Success
                        loaderTitle.textContent = "PIN Verified!";
                        loaderTitle.style.color = "#10b981";
                        loaderMsg.innerHTML = `<i class="fas fa-check-circle" style="font-size: 2rem; color: #10b981; margin-bottom: 0.5rem; display: block;"></i>Payment Authorized. Redirecting to receipt...`;
                        
                        setTimeout(() => {
                            form.submit();
                        }, 1500);
                    }, 4000); // 4 seconds for "Entering PIN"
                }, 2000); // 2 seconds to "Initiate"
            } else if (method === 'card') {
                loaderTitle.textContent = "Verifying Card Details";
                loaderMsg.textContent = "Securely communicating with your bank...";
                setTimeout(() => form.submit(), 3000);
            } else {
                loaderTitle.textContent = "Confirming Selection";
                loaderMsg.textContent = "Finalizing your booking details...";
                setTimeout(() => form.submit(), 2500);
            }
        });
    </script>
</body>
</html>
