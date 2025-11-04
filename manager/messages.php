<?php
require_once '../config/db_connect.php';
session_start();

// Check if user is logged in as manager (matching dashboard auth)
if (!isset($_SESSION['staff_id']) || ($_SESSION['role'] ?? '') !== 'manager') {
    header("Location: ../login/login.php");
    exit();
}

$manager_id = (int) $_SESSION['staff_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'] ?? 'Manager';

// Get manager info for display (matching dashboard approach)
$manager = ['name' => $username, 'profile_picture' => '../assets/pictures/default-profile.png'];

// Handle message deletion only (no status updates since no status column)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    try {
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        
        if ($stmt->execute()) {
            $successMessage = "Message deleted successfully!";
            header("Location: messages.php?success=" . urlencode($successMessage));
            exit();
        } else {
            $errorMessage = "Failed to delete message.";
        }
        $stmt->close();
    } catch (Exception $e) {
        $errorMessage = "Database error: " . $e->getMessage();
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $successMessage = $_GET['success'];
}

// Fetch all messages from database
$messages = [];
try {
    $query = "SELECT * FROM contact_messages ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    }
} catch (Exception $e) {
    $errorMessage = "Failed to fetch messages: " . $e->getMessage();
}

// Count today's messages
$todayCount = 0;
$today = date('Y-m-d');
foreach ($messages as $message) {
    if (date('Y-m-d', strtotime($message['created_at'])) === $today) {
        $todayCount++;
    }
}

// Close connection after all database operations are done
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Flakies | Customer Messages</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            height: 100vh;
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: #f7f8fa;
            color: #222;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background: linear-gradient(180deg, #d9ed42 0%, #d39e2a 60%, #e0d979ff 100%);
            color: #000;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            padding: 25px 20px;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            border-top-right-radius: 20px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .main-content {
            margin-left: 260px !important;
            padding: 40px 50px;
            background: #fafafa;
            min-height: 100vh;
            box-sizing: border-box;
            width: calc(100% - 260px);
        }

        .sidebar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .sidebar .logo img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
        }

        .sidebar .welcome {
            font-size: 14px;
            color: rgba(0, 0, 0, 0.7);
            margin-bottom: 25px;
            font-weight: 500;
        }

        /* MENU LINKS */
        .menu {
            list-style: none;
            width: 100%;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .menu li {
            margin: 0;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #000;
            font-weight: 600;
            padding: 12px 18px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(0, 0, 0, 0.1);
            color: #000;
            transform: translateX(4px);
        }

        /* LOGOUT BUTTON */
        .btn-logout {
            margin-top: auto;
            width: 100%;
            background: linear-gradient(135deg, #000 0%, #222 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            padding: 12px 0;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
        }

        .btn-logout::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0));
            transition: all 0.5s ease;
        }

        .btn-logout:hover::before {
            left: 100%;
        }

        .btn-logout:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, #111 0%, #000 100%);
        }

        /* MAIN CONTENT */
        .main-content {
            flex-grow: 1;
            margin-left: 260px;
            padding: 40px 50px;
            background: #fafafa;
            overflow-y: auto;
        }

        /* === GLOBAL HEADER STYLING === */
        h1, h2, h3 {
            font-family: "Poppins", sans-serif;
            font-weight: 700;
            color: #000;
            letter-spacing: 0.5px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }

        h1::after, h2::after, h3::after {
            content: "";
            flex-grow: 1;
            height: 3px;
            border-radius: 10px;
            background: linear-gradient(90deg, #d9ed42, #d39e2a);
            margin-left: 12px;
            opacity: 0.4;
        }

        h1 {
            font-size: 28px;
        }

        h2 {
            font-size: 22px;
        }

        h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        /* PAGE HEADER */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .user-info {
            text-align: right;
        }

        .user-info .name {
            font-weight: 700;
            font-size: 16px;
        }

        .user-info .date {
            color: #666;
            font-size: 14px;
        }

        /* DASHBOARD CARDS */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
        }

        .card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .card p {
            font-size: 26px;
            font-weight: 700;
            color: #000;
        }

        .card small {
            color: gray;
        }

        .unread-count {
            color: #e74c3c;
        }

        /* MESSAGES CONTAINER */
        .messages-container {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .messages-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f4e04d;
        }

        .filter-section {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: inherit;
            background: white;
            cursor: pointer;
        }

        /* MESSAGES TABLE */
        .messages-table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .messages-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .messages-table th {
            background: #f8f9fa;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #2d2d2d;
            border-bottom: 2px solid #e9ecef;
            position: sticky;
            top: 0;
        }

        .messages-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
        }

        .messages-table tr:hover {
            background-color: #f8f9fa;
        }

        .messages-table tr:last-child td {
            border-bottom: none;
        }

        /* STATUS BADGES */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-unread {
            background-color: #ffe6e6;
            color: #e74c3c;
        }

        .status-read {
            background-color: #e6ffe6;
            color: #27ae60;
        }

        .status-replied {
            background-color: #e6f3ff;
            color: #3498db;
        }

        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-view {
            background-color: #3498db;
            color: white;
        }

        .btn-view:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
        }

        .btn-mark-read {
            background-color: #27ae60;
            color: white;
        }

        .btn-mark-read:hover {
            background-color: #219653;
            transform: translateY(-1px);
        }

        .btn-mark-replied {
            background-color: #9b59b6;
            color: white;
        }

        .btn-mark-replied:hover {
            background-color: #8e44ad;
            transform: translateY(-1px);
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c0392b;
            transform: translateY(-1px);
        }

        .btn-reply {
            background-color: #9b59b6;
            color: white;
        }

        .btn-reply:hover {
            background-color: #8e44ad;
            transform: translateY(-1px);
        }

        /* MESSAGES */
        .no-messages {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-messages h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2d2d2d;
        }

        .success-message {
            background: #e6ffe6;
            border-left: 4px solid #48bb78;
            color: #22543d;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-message {
            background: #ffe6e6;
            border-left: 4px solid #f56565;
            color: #742a2a;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* MODAL STYLES */
        .message-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: modalAppear 0.3s ease-out;
        }

        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 2px solid #f4e04d;
            background: #f8f9fa;
            border-radius: 15px 15px 0 0;
        }

        .modal-header h2 {
            color: #2d2d2d;
            font-size: 1.5rem;
            margin: 0;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: #666;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }

        .close-modal:hover {
            background: #e9ecef;
        }

        .modal-body {
            padding: 2rem;
        }

        .message-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .message-field {
            margin-bottom: 1rem;
        }

        .message-field.full-width {
            grid-column: 1 / -1;
        }

        .message-field label {
            font-weight: 600;
            color: #2d2d2d;
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .message-field p {
            color: #495057;
            padding: 0.8rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #f4e04d;
            margin: 0;
        }

        .message-body {
            white-space: pre-wrap;
            line-height: 1.6;
            min-height: 150px;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .muted {
            color: #666;
            font-size: 13px;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .sidebar {
                position: static;
                width: 100%;
                display: flex;
                overflow: auto;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .messages-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .filter-section {
                width: 100%;
            }
            
            .filter-select {
                flex: 1;
            }
            
            .message-details {
                grid-template-columns: 1fr;
            }
            
            .modal-actions {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
            <span>Flakies</span>
        </div>
        <div class="welcome">Manager Panel</div>
        <ul class="menu">
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="../manager/inventory.php">📦 Inventory</a></li>
            <li><a href="../manager/reports.php">📊 Reports</a></li>
            <li><a href="../manager/messages.php" class="active">💬 Messages</a></li>
        </ul>
        <a href="../login/logout.php" class="btn-logout">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>💬 Customer Messages</h1>
            <div class="user-info">
                <div class="name"><?= htmlspecialchars($manager['name']); ?></div>
                <div class="date"><?= date('M j, Y H:i') ?></div>
            </div>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="success-message">
                <span>✓</span>
                <span><?php echo htmlspecialchars($successMessage); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="error-message">
                <span>✗</span>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
        <?php endif; ?>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Total Messages</h3>
                <p><?php echo number_format(count($messages)); ?></p>
                <small>All customer messages</small>
            </div>
            <div class="card">
                <h3>Unread Messages</h3>
                <p class="unread-count" id="unreadCount"><?php echo number_format(count($messages)); ?></p>
                <small>Requires attention</small>
            </div>
            <div class="card">
                <h3>Today's Messages</h3>
                <p><?php echo number_format($todayCount); ?></p>
                <small>Messages from today</small>
            </div>
        </div>

        <div class="messages-container">
            <div class="messages-header">
                <h2>All Messages</h2>
                <div class="filter-section">
                    <label for="status-filter">Filter by Status:</label>
                    <select id="status-filter" class="filter-select">
                        <option value="all">All Messages</option>
                        <option value="unread">Unread Only</option>
                        <option value="read">Read Only</option>
                        <option value="replied">Replied Only</option>
                    </select>
                </div>
            </div>

            <?php if (empty($messages)): ?>
                <div class="no-messages">
                    <h3>No messages yet</h3>
                    <p>Customer messages will appear here once they contact you through the contact form.</p>
                </div>
            <?php else: ?>
                <div class="messages-table-container">
                    <table class="messages-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Subject</th>
                                <th>Message Preview</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="messagesTableBody">
                            <?php foreach ($messages as $message): ?>
                                <tr class="message-row" data-id="<?php echo $message['id']; ?>" data-status="unread">
                                    <td><?php echo $message['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($message['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($message['email']); ?></td>
                                    <td><?php echo htmlspecialchars($message['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                    <td>
                                        <?php 
                                            $messagePreview = $message['message'];
                                            if (strlen($messagePreview) > 50) {
                                                $messagePreview = substr($messagePreview, 0, 50) . '...';
                                            }
                                            echo htmlspecialchars($messagePreview);
                                        ?>
                                    </td>
                                    <td class="muted"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge status-unread">
                                            Unread
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <button class="btn btn-view" onclick="viewMessage(<?php echo $message['id']; ?>)">
                                            <span>👁️</span> View
                                        </button>
                                        <button class="btn btn-mark-read" onclick="markAsRead(<?php echo $message['id']; ?>)">
                                            <span>✓</span> Read
                                        </button>
                                        <button class="btn btn-mark-replied" onclick="markAsReplied(<?php echo $message['id']; ?>)">
                                            <span>📧</span> Replied
                                        </button>
                                        <a href="?delete_id=<?php echo $message['id']; ?>" 
                                           class="btn btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this message?')">
                                            <span>🗑️</span> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Message Detail Modal -->
    <div class="message-modal" id="messageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalSubject">Message Details</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Message details will be loaded here via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Store message status in memory
        let messageStatus = {};
        
        // Initialize all messages as unread
        document.querySelectorAll('.message-row').forEach(row => {
            const messageId = row.getAttribute('data-id');
            messageStatus[messageId] = 'unread';
        });

        // Update unread count
        function updateUnreadCount() {
            const unreadCount = Object.values(messageStatus).filter(status => status === 'unread').length;
            document.getElementById('unreadCount').textContent = unreadCount;
        }

        // Mark message as read
        function markAsRead(messageId) {
            const row = document.querySelector(`.message-row[data-id="${messageId}"]`);
            if (row) {
                messageStatus[messageId] = 'read';
                row.setAttribute('data-status', 'read');
                
                // Update status badge
                const statusBadge = row.querySelector('.status-badge');
                statusBadge.className = 'status-badge status-read';
                statusBadge.textContent = 'Read';
                
                // Update buttons - hide Read button, show Replied button
                const readBtn = row.querySelector('.btn-mark-read');
                const repliedBtn = row.querySelector('.btn-mark-replied');
                readBtn.style.display = 'none';
                repliedBtn.style.display = 'inline-flex';
                
                updateUnreadCount();
                showNotification('Message marked as read!');
            }
        }

        // Mark message as replied
        function markAsReplied(messageId) {
            const row = document.querySelector(`.message-row[data-id="${messageId}"]`);
            if (row) {
                messageStatus[messageId] = 'replied';
                row.setAttribute('data-status', 'replied');
                
                // Update status badge
                const statusBadge = row.querySelector('.status-badge');
                statusBadge.className = 'status-badge status-replied';
                statusBadge.textContent = 'Replied';
                
                // Hide both Read and Replied buttons
                const readBtn = row.querySelector('.btn-mark-read');
                const repliedBtn = row.querySelector('.btn-mark-replied');
                readBtn.style.display = 'none';
                repliedBtn.style.display = 'none';
                
                updateUnreadCount();
                showNotification('Message marked as replied!');
            }
        }

        // Filter messages by status
        document.getElementById('status-filter').addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('.message-row');
            
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                if (status === 'all' || rowStatus === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // View message details
        function viewMessage(messageId) {
            // Find the row with this message ID
            const row = document.querySelector(`.message-row[data-id="${messageId}"]`);
            if (!row) {
                alert('Message not found!');
                return;
            }
            
            // Extract data from the row
            const name = row.cells[1].textContent;
            const email = row.cells[2].textContent;
            const phone = row.cells[3].textContent;
            const subject = row.cells[4].textContent;
            const date = row.cells[6].textContent;
            const status = row.getAttribute('data-status');
            const messagePreview = row.cells[5].textContent;
            
            // Populate the modal
            document.getElementById('modalSubject').textContent = subject;
            document.getElementById('modalContent').innerHTML = `
                <div class="message-details">
                    <div class="message-field">
                        <label>Name:</label>
                        <p>${name}</p>
                    </div>
                    <div class="message-field">
                        <label>Email:</label>
                        <p>${email}</p>
                    </div>
                    <div class="message-field">
                        <label>Phone:</label>
                        <p>${phone}</p>
                    </div>
                    <div class="message-field">
                        <label>Date Sent:</label>
                        <p>${date}</p>
                    </div>
                    <div class="message-field">
                        <label>Status:</label>
                        <p><span class="status-badge status-${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span></p>
                    </div>
                    <div class="message-field full-width">
                        <label>Subject:</label>
                        <p>${subject}</p>
                    </div>
                    <div class="message-field full-width">
                        <label>Message:</label>
                        <p class="message-body">${messagePreview}</p>
                    </div>
                </div>
                <div class="modal-actions">
                    <a href="mailto:${email}?subject=Re: ${encodeURIComponent(subject)}" class="btn btn-reply">
                        <span>📧</span> Reply via Email
                    </a>
                    ${status === 'unread' ? `
                        <button class="btn btn-mark-read" onclick="markAsRead(${messageId}); closeModal();">
                            <span>✓</span> Mark as Read
                        </button>
                    ` : ''}
                    ${status === 'read' ? `
                        <button class="btn btn-mark-replied" onclick="markAsReplied(${messageId}); closeModal();">
                            <span>📧</span> Mark as Replied
                        </button>
                    ` : ''}
                    <button class="btn btn-view" onclick="closeModal()">
                        <span>←</span> Close
                    </button>
                </div>
            `;
            
            // Show the modal
            document.getElementById('messageModal').style.display = 'flex';
        }

        // Show notification
        function showNotification(message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #48bb78;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1001;
                font-weight: 500;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Close modal
        function closeModal() {
            document.getElementById('messageModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('messageModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            updateUnreadCount();
        });
    </script>
</body>
</html>