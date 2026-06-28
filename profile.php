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

// Fetch current user details from DB using PDO
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$user_stmt->execute(['id' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Process Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_number = trim($_POST['contact_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($contact_number)) {
        $error = 'Contact number cannot be empty.';
    } else {
        try {
            $conn->beginTransaction();
            $update_success = true;
            
            // Update contact number
            $stmt = $conn->prepare("UPDATE users SET contact_number = :contact WHERE id = :id");
            $stmt->execute(['contact' => $contact_number, 'id' => $user_id]);
            
            // If password change is requested
            if (!empty($password)) {
                $has_uppercase = preg_match('@[A-Z]@', $password);
                $has_lowercase = preg_match('@[a-z]@', $password);
                $has_number    = preg_match('@[0-9]@', $password);
                $has_special   = preg_match('@[^\w]@', $password);

                if ($password !== $confirm_password) {
                    $error = 'New passwords do not match.';
                    $update_success = false;
                } elseif (strlen($password) < 8 || !$has_uppercase || !$has_lowercase || !$has_number || !$has_special) {
                    $error = 'New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
                    $update_success = false;
                } else {
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt_pwd = $conn->prepare("UPDATE users SET password = :pwd WHERE id = :id");
                    $stmt_pwd->execute(['pwd' => $password_hash, 'id' => $user_id]);
                }
            }
            
            if ($update_success) {
                $conn->commit();
                $message = 'Profile updated successfully!';
                
                // Fetch refreshed user details
                $user_stmt->execute(['id' => $user_id]);
                $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $conn->rollBack();
            }
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = 'Failed to save updates: ' . $e->getMessage();
        }
    }
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
    <link rel="stylesheet" href="user_page.css?v=<?php echo time(); ?>">
    <style>
        .profile-container {
            background: #d9d9d9;
            border: 1.5px solid #c8c2b4;
            border-radius: 22px;
            padding: 30px;
            max-width: 600px;
            margin: 20px auto;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.16);
        }
        .profile-container h2 {
            margin-top: 0;
            color: #1a2535;
            border-bottom: 2px solid #1a2535;
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
            border-color: #1a2535;
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
    <?php include 'navigation.php'; ?>

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
                    <label>Username:</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                </div>

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
                    <input type="password" name="password" placeholder="Min 8 chars, 1 upper, 1 lower, 1 num, 1 special">
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
