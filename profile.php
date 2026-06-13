<?php
// profile.php - User Profile settings for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

// Verify User or Admin session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Process Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_number = trim($_POST['contact_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($contact_number)) {
        $error = 'Contact number cannot be empty.';
    } else {
        $conn->begin_transaction();
        $update_success = true;
        
        // Update contact number
        $stmt = $conn->prepare("UPDATE users SET contact_number = ? WHERE id = ?");
        $stmt->bind_param("si", $contact_number, $user_id);
        if (!$stmt->execute()) {
            $update_success = false;
        }
        $stmt->close();
        
        // If password change is requested
        if ($update_success && !empty($password)) {
            if ($password !== $confirm_password) {
                $error = 'New passwords do not match.';
                $update_success = false;
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long.';
                $update_success = false;
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt_pwd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_pwd->bind_param("si", $password_hash, $user_id);
                if (!$stmt_pwd->execute()) {
                    $update_success = false;
                    $error = 'Failed to update password.';
                }
                $stmt_pwd->close();
            }
        }
        
        if ($update_success) {
            $conn->commit();
            $message = 'Profile updated successfully!';
        } else {
            $conn->rollback();
            if (empty($error)) {
                $error = 'Failed to save updates. Connection error.';
            }
        }
    }
}

// Fetch current user details from DB
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

if (!$user) {
    die("User not found.");
}

// Determine Home Link depending on role
$home_link = ($user['role'] === 'admin') ? 'admin_page.php' : 'user_page.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Barangay Tiniguiban</title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="user_page.css">
    <style>
        .profile-container {
            background: var(--card-bg);
            border: 1.5px solid var(--card-border);
            border-radius: var(--radius-large);
            padding: 30px;
            max-width: 600px;
            margin: 20px auto;
            box-shadow: var(--shadow-medium);
        }
        .profile-container h2 {
            margin-top: 0;
            color: var(--navy);
            border-bottom: 2px solid var(--navy);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 22px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }
        .form-group label {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #aaa;
            background: #efefef;
            font-size: 14px;
            outline: none;
        }
        .form-group input:focus {
            border-color: var(--navy);
        }
        .form-group input:disabled {
            background: #dcdcd0;
            color: #666;
            cursor: not-allowed;
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
        }
        .btn-save {
            background: #30364F;
            color: white;
            border: 2px solid #000;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        .btn-save:hover {
            background: black;
        }
        .btn-back {
            background: #888;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        .btn-back:hover {
            background: #666;
        }
        .alert {
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            font-size: 13px;
            margin-bottom: 15px;
        }
        .alert-success {
            background: #2a6e2a;
            color: white;
        }
        .alert-error {
            background: #7a1a1a;
            color: white;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="header-inner">

            <div class="logo-wrap">
                <img src="logo.png" alt="Barangay Logo" class="logo">

                <div class="brand-text">
                    <h1>BARANGAY TINIGUIBAN</h1>
                    <p>Resource Borrowing System</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav>
                <a href="<?php echo htmlspecialchars($home_link); ?>">Home</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="admin_inventory.php">Inventory</a>
                    <a href="manage_request.php">Manage Request</a>
                <?php else: ?>
                    <a href="user_inventory.php">Inventory</a>
                    <a href="request_page.php">Request</a>
                    <a href="my_request_page.php">My Request</a>
                <?php endif; ?>
            </nav>

            <!-- User Section -->
            <div class="header-right">
                <span class="welcome-text">
                    Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?>!</strong>
                </span>
                
                <a href="profile.php" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center;">
                    <div class="avatar-btn" style="border-color: #000; background: #30364F;">
                        <svg viewBox="0 0 24 24" style="fill: #F0F0DB;">
                            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                        </svg>
                    </div>
                    <span class="profile-label" style="font-weight: bold;">Profile</span>
                </a>

                <button class="btn-logout" onclick="window.location.href='logout.php'">Logout</button>
            </div>

        </div>
    </header>

    <!-- Main Content -->
    <main>

        <div class="profile-container">
            <h2>Account Profile Settings</h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="profile.php">
                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['first_name']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['last_name']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Email Address:</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label>System Role:</label>
                    <input type="text" value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>" disabled>
                </div>

                <?php if ($user['role'] === 'admin'): ?>
                    <div class="form-group">
                        <label>Barangay Position:</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['barangay_role'] ?? 'Official'); ?>" disabled>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Contact Number:</label>
                    <input type="tel" name="contact_number" required value="<?php echo htmlspecialchars($user['contact_number']); ?>">
                </div>

                <div class="form-group" style="margin-top: 25px;">
                    <label>New Password (leave blank to keep current):</label>
                    <input type="password" name="password" placeholder="Enter new password">
                </div>

                <div class="form-group">
                    <label>Confirm New Password:</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password">
                </div>

                <div class="btn-group">
                    <button type="button" class="btn-back" onclick="window.location.href='<?php echo htmlspecialchars($home_link); ?>'">Back to Home</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>

    </main>

    <!-- Footer -->
    <footer>
        &copy; 2026 Barangay Tiniguiban
    </footer>

</body>
</html>
