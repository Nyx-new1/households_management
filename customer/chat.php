<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}
require_once '../includes/language.php';
require_once '../config/db_connect.php';

$booking_id = $_GET['booking_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$booking_id) {
    header("Location: dashboard.php");
    exit;
}

// Verify booking belongs to customer and get provider name
$stmt = $pdo->prepare("
    SELECT b.*, u.name as provider_name 
    FROM bookings b 
    JOIN users u ON b.provider_id = u.id 
    WHERE b.id = ? AND b.customer_id = ?
");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Unauthorized access to chat.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat with <?php echo htmlspecialchars($booking['provider_name']); ?> - HomeServe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chat-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            display: flex;
            flex-direction: column;
            height: 600px;
        }
        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: #f9fafb;
        }
        .message {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.95rem;
            line-height: 1.4;
            position: relative;
        }
        .message-sent {
            align-self: flex-end;
            background: var(--primary-color);
            color: white;
            border-bottom-right-radius: 2px;
        }
        .message-received {
            align-self: flex-start;
            background: #e5e7eb;
            color: var(--text-dark);
            border-bottom-left-radius: 2px;
        }
        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 0.25rem;
            display: block;
            text-align: right;
        }
        .message-row {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .message-row.sent {
            flex-direction: row-reverse;
        }
        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e5e7eb;
            flex-shrink: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: #6b7280;
        }
        .chat-input {
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 1rem;
        }
        .chat-input input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            outline: none;
        }
        .chat-input input:focus {
            border-color: var(--primary-color);
        }
        .date-separator {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        .date-separator::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e5e7eb;
            z-index: 1;
        }
        .date-text {
            background: #f9fafb;
            padding: 0 1rem;
            font-size: 0.75rem;
            color: var(--text-light);
            font-weight: 600;
            position: relative;
            z-index: 2;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container nav-content">
            <a href="dashboard.php" class="logo">HomeServe</a>
            <div class="nav-links">
                <a href="booking_details.php?id=<?php echo $booking_id; ?>"><i class="fas fa-arrow-left"></i> Back to Booking</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="chat-container">
            <div class="chat-header">
                <div id="participantPhoto" style="width: 40px; height: 40px; background: #e5e7eb; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user" style="color: #6b7280;"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($booking['provider_name']); ?></h3>
                    <p style="margin: 0; font-size: 0.8rem; color: var(--text-light);">Provider &bull; Booking #<?php echo $booking_id; ?></p>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <!-- Messages will be loaded here -->
            </div>

            <form class="chat-input" id="chatForm">
                <input type="text" id="messageInput" placeholder="<?php echo $lang['type_message']; ?>" autocomplete="off">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const bookingId = <?php echo $booking_id; ?>;
        const currentUserId = <?php echo $user_id; ?>;
        let lastMessageId = 0;

        async function fetchMessages() {
            try {
                const response = await fetch(`../api/chat_handler.php?action=fetch&booking_id=${bookingId}&last_id=${lastMessageId}`);
                const data = await response.json();
                
                // Update participant photo in header
                if (data.participants) {
                    const other = data.participants.find(p => p.id != currentUserId);
                    if (other && other.profile_picture) {
                        const photoDiv = document.getElementById('participantPhoto');
                        photoDiv.innerHTML = `<img src="../assets/uploads/profiles/${other.profile_picture}" style="width:100%; height:100%; object-fit:cover;">`;
                    }
                }

                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        const msgDate = new Date(msg.created_at).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
                        const today = new Date().toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
                        const displayDate = msgDate === today ? 'Today' : msgDate;

                        // Add date separator if date changed
                        if (typeof lastRenderedDate === 'undefined' || lastRenderedDate !== msgDate) {
                            const dateDiv = document.createElement('div');
                            dateDiv.className = 'date-separator';
                            dateDiv.innerHTML = `<span class="date-text">${displayDate}</span>`;
                            chatMessages.appendChild(dateDiv);
                            window.lastRenderedDate = msgDate;
                        }

                        const isSent = msg.sender_id == currentUserId;
                        const row = document.createElement('div');
                        row.className = `message-row ${isSent ? 'sent' : 'received'}`;
                        
                        // Find participant for avatar
                        const sender = data.participants.find(p => p.id == msg.sender_id);
                        const avatarHtml = sender && sender.profile_picture 
                            ? `<img src="../assets/uploads/profiles/${sender.profile_picture}" style="width:100%; height:100%; object-fit:cover;">`
                            : `<i class="fas fa-user"></i>`;

                        const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        
                        row.innerHTML = `
                            <div class="message-avatar">${avatarHtml}</div>
                            <div class="message ${isSent ? 'message-sent' : 'message-received'}">
                                ${msg.message}
                                <span class="message-time">${time}</span>
                            </div>
                        `;
                        
                        chatMessages.appendChild(row);
                        lastMessageId = msg.id;
                    });
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            } catch (error) {
                console.error('Error fetching messages:', error);
            }
        }

        chatForm.onsubmit = async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            messageInput.value = '';
            
            const formData = new FormData();
            formData.append('booking_id', bookingId);
            formData.append('message', message);

            try {
                const response = await fetch('../api/chat_handler.php?action=send', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    fetchMessages();
                }
            } catch (error) {
                console.error('Error sending message:', error);
            }
        };

        // Initial fetch and poll
        fetchMessages();
        setInterval(fetchMessages, 3000);
    </script>
</body>
</html>
