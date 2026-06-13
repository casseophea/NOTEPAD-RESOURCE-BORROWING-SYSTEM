<?php
// logout.php - Session Destroy for Barangay Tiniguiban Resource Borrowing System
session_start();
session_unset();
session_destroy();
header('Location: login.php');
exit;
