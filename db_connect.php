<?php
// db_connect.php - Central Database Configuration for Barangay Tiniguiban Resource Borrowing System

$db_host = '127.0.0.1';
$db_port = 3307;
$db_user = 'root';
$db_pass = '';
$db_name = 'brgy_borrow';

// 1. Establish MySQL Connection
$conn = new mysqli($db_host, $db_user, $db_pass, '', $db_port);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// 2. Create Database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS $db_name");
$conn->select_db($db_name);

// 3. Create Tables
// Users Table
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
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

// Run alters in case table existed without columns
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS id_front_path VARCHAR(255) NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS id_back_path VARCHAR(255) NULL");

// Inventory Table
$conn->query("CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    category VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    available INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Available'
)");

// Requests Table
$conn->query("CREATE TABLE IF NOT EXISTS requests (
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

// 4. Seed Data if empty
// Seed Users
$res = $conn->query("SELECT COUNT(*) as cnt FROM users");
$row = $res->fetch_assoc();
if ($row['cnt'] == 0) {
    $password_hash = password_hash('password', PASSWORD_BCRYPT);
    
    // Seed default User
    $conn->query("INSERT INTO users (first_name, last_name, email, password, contact_number, valid_id_type, role) 
        VALUES ('Jane', 'Doe', 'user@example.com', '$password_hash', '09171234567', 'PhilSys ID', 'user')");
    $user1_id = $conn->insert_id;

    $conn->query("INSERT INTO users (first_name, last_name, email, password, contact_number, valid_id_type, role) 
        VALUES ('John', 'Smith', 'user2@example.com', '$password_hash', '09187654321', 'Driver\'s License', 'user')");
    $user2_id = $conn->insert_id;

    // Seed default Admin
    $conn->query("INSERT INTO users (first_name, last_name, email, password, contact_number, valid_id_type, role, barangay_role) 
        VALUES ('Admin', 'Official', 'admin@example.com', '$password_hash', '09998887777', 'Passport', 'admin', 'Barangay Captain')");
}

// Seed Inventory Items
$res = $conn->query("SELECT COUNT(*) as cnt FROM inventory");
$row = $res->fetch_assoc();
if ($row['cnt'] == 0) {
    $conn->query("INSERT INTO inventory (name, category, quantity, available, status) VALUES
        ('Plastic Chairs', 'Furniture', 150, 120, 'Available'),
        ('Foldable Tables', 'Furniture', 20, 5, 'Limited'),
        ('Sound System', 'Electronics', 5, 0, 'Not Available'),
        ('Projector Screen', 'Electronics', 3, 2, 'Limited'),
        ('Microphone', 'Electronics', 10, 10, 'Available'),
        ('Whiteboard', 'Office', 8, 8, 'Available'),
        ('Extension Cord', 'Electronics', 15, 15, 'Available')");
}

// Seed Requests (tied to user IDs created above)
$res = $conn->query("SELECT COUNT(*) as cnt FROM requests");
$row = $res->fetch_assoc();
if ($row['cnt'] == 0) {
    // Fetch user IDs
    $resUser = $conn->query("SELECT id FROM users WHERE email='user@example.com' LIMIT 1");
    if ($resUser && $resUser->num_rows > 0) {
        $u1 = $resUser->fetch_assoc()['id'];
        
        $resUser2 = $conn->query("SELECT id FROM users WHERE email='user2@example.com' LIMIT 1");
        $u2 = ($resUser2 && $resUser2->num_rows > 0) ? $resUser2->fetch_assoc()['id'] : $u1;

        $conn->query("INSERT INTO requests (request_code, user_id, item_name, quantity, borrow_date, return_date, return_time, purpose, notes, status) VALUES
            ('REQ-001', $u1, 'Plastic Chairs', 50, '2026-06-10', '2026-06-12', '17:00:00', 'Community seminar on waste management and recycling', 'Need chairs set up in the main sports court.', 'Approved'),
            ('REQ-002', $u2, 'Projector Screen', 1, '2026-06-15', '2026-06-17', '13:00:00', 'Barangay general meeting slides presentation', 'Needs HDMI adapter and projector stands.', 'Pending'),
            ('REQ-003', $u1, 'Sound System', 2, '2026-06-08', '2026-06-09', '18:00:00', 'Private garage birthday party celebration', 'Will pick up in the afternoon.', 'Rejected')");
    }
}

// 5. Start Session safely
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
