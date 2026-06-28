<?php

// Set global timezone
date_default_timezone_set('Asia/Manila');

// Secret administrative key for registration authorization
define('ADMIN_REGISTRATION_KEY', 'TINIGUIBAN_ADMIN');

$db_host = '127.0.0.1';
$db_port = 3307;
$db_user = 'root';
$db_pass = '';
$db_name = 'brgy_borrow';

//Establish MySQL Connection using PDO
try {
    $conn = new PDO("mysql:host=$db_host;port=$db_port", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("USE `$db_name`");
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

//Create Tables
// Users Table
$conn->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20),
    valid_id_type VARCHAR(50),
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    barangay_role VARCHAR(50) NULL,
    id_front_path VARCHAR(255) NULL,
    id_back_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

try {
    $check_col = $conn->query("SHOW COLUMNS FROM users LIKE 'username'");
    if ($check_col->rowCount() === 0) {

        $conn->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL");
        
        $users_res = $conn->query("SELECT id, email FROM users");
        $update_stmt = $conn->prepare("UPDATE users SET username = :username WHERE id = :id");
        while ($user_row = $users_res->fetch(PDO::FETCH_ASSOC)) {
            $email_prefix = explode('@', $user_row['email'])[0];

            $username = $email_prefix;
            $counter = 1;
            while (true) {
                $check_username = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :un AND id != :id");
                $check_username->execute(['un' => $username, 'id' => $user_row['id']]);
                if ($check_username->fetchColumn() == 0) {
                    break;
                }
                $username = $email_prefix . $counter;
                $counter++;
            }
            $update_stmt->execute(['username' => $username, 'id' => $user_row['id']]);
        }
        
        $conn->exec("ALTER TABLE users MODIFY COLUMN username VARCHAR(50) NOT NULL");
        $conn->exec("ALTER TABLE users ADD UNIQUE INDEX (username)");
    }
} catch (PDOException $ex) {

}

try {
    $conn->exec("ALTER TABLE users ADD COLUMN id_front_path VARCHAR(255) NULL");
} catch (PDOException $e) {}
try {
    $conn->exec("ALTER TABLE users ADD COLUMN id_back_path VARCHAR(255) NULL");
} catch (PDOException $e) {}

// Inventory Table
$conn->exec("CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    category VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    available INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Available'
)");

// Requests Table
$conn->exec("CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_code VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    borrow_date DATE NOT NULL,
    return_date DATE NOT NULL,
    return_time TIME,
    purpose TEXT,
    notes TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

try {
    $check_returned_at = $conn->query("SHOW COLUMNS FROM requests LIKE 'returned_at'");
    if ($check_returned_at->rowCount() === 0) {
        $conn->exec("ALTER TABLE requests ADD COLUMN returned_at DATETIME NULL");
    }
} catch (PDOException $ex) {}

try {
    $check_penalty_amount = $conn->query("SHOW COLUMNS FROM requests LIKE 'penalty_amount'");
    if ($check_penalty_amount->rowCount() === 0) {
        $conn->exec("ALTER TABLE requests ADD COLUMN penalty_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00");
    }
} catch (PDOException $ex) {}

try {
    $check_penalty_status = $conn->query("SHOW COLUMNS FROM requests LIKE 'penalty_status'");
    if ($check_penalty_status->rowCount() === 0) {
        $conn->exec("ALTER TABLE requests ADD COLUMN penalty_status VARCHAR(20) NOT NULL DEFAULT 'No Penalty'");
    }
} catch (PDOException $ex) {}


// User Sessions Table for session/cookie log database tracking
$conn->exec("CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    token VARCHAR(255) UNIQUE NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

//Seed Data if empty
// Seed Users
$user_count = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($user_count == 0) {
    $password_hash = password_hash('password', PASSWORD_BCRYPT);
    
    // Seed default User 1
    $insert_user = $conn->prepare("INSERT INTO users (username, first_name, last_name, email, password, contact_number, valid_id_type, role) 
        VALUES (:un, :fn, :ln, :email, :pwd, :contact, :id_type, :role)");
    
    $insert_user->execute([
        'un' => 'jane_doe',
        'fn' => 'Jane',
        'ln' => 'Doe',
        'email' => 'user@example.com',
        'pwd' => $password_hash,
        'contact' => '09171234567',
        'id_type' => 'PhilSys ID',
        'role' => 'user'
    ]);
    
    // Seed default User 2
    $insert_user->execute([
        'un' => 'john_smith',
        'fn' => 'John',
        'ln' => 'Smith',
        'email' => 'user2@example.com',
        'pwd' => $password_hash,
        'contact' => '09187654321',
        'id_type' => "Driver's License",
        'role' => 'user'
    ]);

    // Seed default Admin
    $insert_admin = $conn->prepare("INSERT INTO users (username, first_name, last_name, email, password, contact_number, valid_id_type, role, barangay_role) 
        VALUES (:un, :fn, :ln, :email, :pwd, :contact, :id_type, :role, :brgy_role)");
    $insert_admin->execute([
        'un' => 'admin_official',
        'fn' => 'Admin',
        'ln' => 'Official',
        'email' => 'admin@example.com',
        'pwd' => $password_hash,
        'contact' => '09998887777',
        'id_type' => 'Passport',
        'role' => 'admin',
        'brgy_role' => 'Barangay Captain'
    ]);
}

// Seed Inventory Items
$inventory_count = $conn->query("SELECT COUNT(*) FROM inventory")->fetchColumn();
if ($inventory_count == 0) {
    $conn->exec("INSERT INTO inventory (name, category, quantity, available, status) VALUES
        ('Plastic Chairs', 'Furniture', 150, 120, 'Available'),
        ('Foldable Tables', 'Furniture', 20, 5, 'Limited'),
        ('Sound System', 'Electronics', 5, 0, 'Not Available'),
        ('Projector Screen', 'Electronics', 3, 2, 'Limited'),
        ('Microphone', 'Electronics', 10, 10, 'Available'),
        ('Whiteboard', 'Office', 8, 8, 'Available'),
        ('Extension Cord', 'Electronics', 15, 15, 'Available')");
}

// Seed Requests
$requests_count = $conn->query("SELECT COUNT(*) FROM requests")->fetchColumn();
if ($requests_count == 0) {

    $u1_stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $u1_stmt->execute(['email' => 'user@example.com']);
    $u1 = $u1_stmt->fetchColumn();
    
    $u2_stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $u2_stmt->execute(['email' => 'user2@example.com']);
    $u2 = $u2_stmt->fetchColumn();
    if (!$u2) { $u2 = $u1; }

    if ($u1) {
        $insert_req = $conn->prepare("INSERT INTO requests (request_code, user_id, item_name, quantity, borrow_date, return_date, return_time, purpose, notes, status) VALUES
            (:code, :uid, :item, :qty, :bdate, :rdate, :rtime, :purpose, :notes, :status)");
            
        $insert_req->execute([
            'code' => 'REQ-001',
            'uid' => $u1,
            'item' => 'Plastic Chairs',
            'qty' => 50,
            'bdate' => '2026-06-10',
            'rdate' => '2026-06-12',
            'rtime' => '17:00:00',
            'purpose' => 'Community seminar on waste management and recycling',
            'notes' => 'Need chairs set up in the main sports court.',
            'status' => 'Approved'
        ]);
        
        $insert_req->execute([
            'code' => 'REQ-002',
            'uid' => $u2,
            'item' => 'Projector Screen',
            'qty' => 1,
            'bdate' => '2026-06-15',
            'rdate' => '2026-06-17',
            'rtime' => '13:00:00',
            'purpose' => 'Barangay general meeting slides presentation',
            'notes' => 'Needs HDMI adapter and projector stands.',
            'status' => 'Pending'
        ]);

        $insert_req->execute([
            'code' => 'REQ-003',
            'uid' => $u1,
            'item' => 'Sound System',
            'qty' => 2,
            'bdate' => '2026-06-08',
            'rdate' => '2026-06-09',
            'rtime' => '18:00:00',
            'purpose' => 'Private garage birthday party celebration',
            'notes' => 'Will pick up in the afternoon.',
            'status' => 'Rejected'
        ]);
    }
}

//Start Session safely
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//Global Inactivity Timeout Check
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {

        $destroy_sess = $conn->prepare("DELETE FROM user_sessions WHERE session_id = :sid");
        $destroy_sess->execute(['sid' => session_id()]);
        
        // Clear remember cookie if present
        if (isset($_COOKIE['remember_token'])) {
            $clear_token = $conn->prepare("DELETE FROM user_sessions WHERE token = :token");
            $clear_token->execute(['token' => $_COOKIE['remember_token']]);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Unset and destroy session
        session_unset();
        session_destroy();
        
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

//Remember Me Cookie Autologin Logic
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Query database for a valid, non-expired token
    $token_stmt = $conn->prepare("
        SELECT s.user_id, u.first_name, u.last_name, u.email, u.role, u.barangay_role, u.username
        FROM user_sessions s
        JOIN users u ON s.user_id = u.id
        WHERE s.token = :token AND (s.expires_at > NOW() OR s.expires_at IS NULL)
        LIMIT 1
    ");
    $token_stmt->execute(['token' => $token]);
    $session_data = $token_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($session_data) {
        // Recreate user session
        $_SESSION['user_id'] = $session_data['user_id'];
        $_SESSION['user_name'] = $session_data['first_name'] . ' ' . $session_data['last_name'] . ($session_data['barangay_role'] ? ' (' . $session_data['barangay_role'] . ')' : '');
        $_SESSION['email'] = $session_data['email'];
        $_SESSION['role'] = $session_data['role'];
        $_SESSION['username'] = $session_data['username'];
        $_SESSION['last_activity'] = time();
        
        // Re-register new session ID in table
        $update_sess = $conn->prepare("UPDATE user_sessions SET session_id = :sid WHERE token = :token");
        $update_sess->execute(['sid' => session_id(), 'token' => $token]);
    } else {
        // Invalid or expired token cookie, clear it
        setcookie('remember_token', '', time() - 3600, '/');
    }
}
