<?php
// admin_page.php - Admin Home Dashboard for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

// Verify Admin session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 1. Fetch live metrics from MySQL
// Total Items & Available Items
$inv_res = $conn->query("SELECT SUM(quantity) as total_qty, SUM(available) as avail_qty FROM inventory");
$inv_data = $inv_res->fetch_assoc();
$total_items = $inv_data['total_qty'] ?? 0;
$available_items = $inv_data['avail_qty'] ?? 0;

// Currently Borrowed (Approved requests)
$borrowed_res = $conn->query("SELECT SUM(quantity) as borrowed_qty FROM requests WHERE status = 'Approved'");
$borrowed_data = $borrowed_res->fetch_assoc();
$currently_borrowed = $borrowed_data['borrowed_qty'] ?? 0;

// Pending Approval (Pending requests)
$pending_res = $conn->query("SELECT SUM(quantity) as pending_qty FROM requests WHERE status = 'Pending'");
$pending_data = $pending_res->fetch_assoc();
$pending_approval = $pending_data['pending_qty'] ?? 0;

// 2. Fetch Recent Activities (System-wide requests)
$activity_res = $conn->query("
    SELECT r.item_name, r.borrow_date, r.return_date, r.status, u.first_name, u.last_name 
    FROM requests r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.id DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Barangay Tiniguiban</title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="user_page.css">
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
                <a href="admin_page.php" class="active">Home</a>
                <a href="admin_inventory.php">Inventory</a>
                <a href="manage_request.php">Manage Request</a>
            </nav>

            <!-- User Section -->
            <div class="header-right">

                <span class="welcome-text">
                    Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?>!</strong>
                </span>

                <a href="profile.php" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center;">
                    <div class="avatar-btn">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                        </svg>
                    </div>
                    <span class="profile-label">Profile</span>
                </a>

                <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')" class="btn-logout">Logout</a>

            </div>

        </div>
    </header>

    <!-- Main Content -->
    <main>

        <!-- Statistics and Buttons -->
        <div class="top-row">

            <div class="stat-cards">
                <div class="stat-card">
                    <div class="label">Total Items</div>
                    <div class="number"><?php echo number_format($total_items); ?></div>
                </div>

                <div class="stat-card">
                    <div class="label">Available</div>
                    <div class="number"><?php echo number_format($available_items); ?></div>
                </div>

                <div class="stat-card">
                    <div class="label">Currently<br>Borrowed</div>
                    <div class="number"><?php echo number_format($currently_borrowed); ?></div>
                </div>

                <div class="stat-card">
                    <div class="label">Pending<br>Approval</div>
                    <div class="number"><?php echo number_format($pending_approval); ?></div>
                </div>
            </div>

            <div class="action-btns">
                <button class="btn-action" onclick="window.location.href='admin_inventory.php'">View Inventory</button>
                <button class="btn-action" onclick="window.location.href='request_page.php'">Request Item</button>
            </div>

        </div>

        <!-- Recent Borrowing Activity -->
        <section class="activity-section">

            <h2>
                Recent Borrowing Activity (System-wide)
                <span></span>
            </h2>

            <table>

                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Item Borrowed</th>
                        <th>Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($activity_res && $activity_res->num_rows > 0): ?>
                        <?php while ($row = $activity_res->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['borrow_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['return_date']); ?></td>
                                <td>
                                    <span class="status-pill <?php echo strtolower($row['status']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">No recent activity found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>

        </section>

    </main>

    <!-- Footer -->
    <footer>
        &copy; 2026 Barangay Tiniguiban
    </footer>

</body>
</html>
