<?php
// logout.php - Session Destroy for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

// Delete session from database log
if (isset($conn)) {
    $sess_id = session_id();
    $del_sess = $conn->prepare("DELETE FROM user_sessions WHERE session_id = :sid");
    $del_sess->execute(['sid' => $sess_id]);
    
    // Clear remember token if cookie exists
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $del_token = $conn->prepare("DELETE FROM user_sessions WHERE token = :token");
        $del_token->execute(['token' => $token]);
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

//Clear PHP Session variables
session_unset();
session_destroy();

// Redirect back to login
if (isset($_GET['timeout'])) {
    header('Location: login.php?timeout=1');
} else {
    header('Location: login.php');
}
exit;
