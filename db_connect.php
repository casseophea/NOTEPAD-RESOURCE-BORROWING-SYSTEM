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
    $conn = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
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
