<?php
// index.php - Server-side Session Redirection for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin_page.php');
    } else {
        header('Location: user_page.php');
    }
} else {
    header('Location: login.php');
}
exit;
