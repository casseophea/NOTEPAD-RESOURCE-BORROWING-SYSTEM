<?php
// admin_page.php - Admin Home Dashboard for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

// Verify Admin session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

//Fetch live metrics from MySQL using PDO
// Total Items & Available Items
$inv_res = $conn->query("SELECT SUM(quantity) as total_qty, SUM(available) as avail_qty FROM inventory");
$inv_data = $inv_res->fetch(PDO::FETCH_ASSOC);
$total_items = $inv_data['total_qty'] ?? 0;
$available_items = $inv_data['avail_qty'] ?? 0;

// Currently Borrowed (Approved requests)
$borrowed_res = $conn->query("SELECT SUM(quantity) as borrowed_qty FROM requests WHERE status = 'Approved'");
$borrowed_data = $borrowed_res->fetch(PDO::FETCH_ASSOC);
$currently_borrowed = $borrowed_data['borrowed_qty'] ?? 0;

// Pending Approval (Pending requests)
$pending_res = $conn->query("SELECT SUM(quantity) as pending_qty FROM requests WHERE status = 'Pending'");
$pending_data = $pending_res->fetch(PDO::FETCH_ASSOC);
$pending_approval = $pending_data['pending_qty'] ?? 0;

//Fetch Recent Activities (System-wide requests)
$activity_res = $conn->query("
    SELECT r.item_name, r.borrow_date, r.return_date, r.status, u.first_name, u.last_name 
    FROM requests r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.id DESC 
    LIMIT 5
");
$activities = $activity_res->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Barangay Tiniguiban</title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="user_page.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include 'navigation.php'; ?>

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
                <button class="btn-action" onclick="window.location.href='admin_inventory.php'">Manage Inventory</button>
                <button class="btn-action" onclick="window.location.href='manage_request.php'">Manage Requests</button>
                <button class="btn-action" onclick="window.location.href='manage_users.php'">Manage Users</button>
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
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $row): ?>
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
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">No recent activity found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>

        </section>

        <!-- About System & Services -->
        <section class="about-services-section" style="background: #f5f0e8; border-radius: 20px; border: 1.5px solid #ede6d6; padding: 30px; margin-top: 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12); text-align: left;">
            <h2 style="font-family: 'Playfair Display', serif; color: #1a2535; border-bottom: 2px solid #1a2535; padding-bottom: 10px; margin-bottom: 20px; font-size: 22px;">
                About Our System & Services
            </h2>
            
            <p style="font-size: 14px; line-height: 1.6; color: #1e2830; margin-bottom: 20px;">
                The <strong>Barangay Tiniguiban Resource Borrowing System</strong> is an automated platform created to streamline the scheduling, booking, and management of public assets. It ensures fair access and transparency, enabling residents to secure municipal resources for community and family events under the provisions of the local government codes.
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                <div style="background: #ffffff; border: 1px solid #c8c2b4; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);">
                    <h3 style="color: #1a2535; font-size: 16px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        Available Resources
                    </h3>
                    <ul style="font-size: 13px; line-height: 1.6; padding-left: 20px; color: #5a6a76;">
                        <li><strong>Event Furniture</strong>: Durable plastic chairs and foldable tables for private gatherings, assemblies, or wakes.</li>
                        <li><strong>Audio/Video Gear</strong>: Professional sound systems, wireless microphones, projectors, and projection screens.</li>
                        <li><strong>Office & Event Accs</strong>: Presentation boards, high-capacity extension cords, and accessory kits.</li>
                    </ul>
                </div>
                
                <div style="background: #ffffff; border: 1px solid #c8c2b4; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);">
                    <h3 style="color: #1a2535; font-size: 16px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        Borrowing Guidelines
                    </h3>
                    <ul style="font-size: 13px; line-height: 1.6; padding-left: 20px; color: #5a6a76;">
                        <li><strong>Live Inventory Checking</strong>: Always verify item counts in the live inventory database before booking.</li>
                        <li><strong>Adhere to Timelines</strong>: Items must be returned strictly by the exact return date and time to allow other residents to borrow them.</li>
                        <li><strong>Commodatum Rules</strong>: Under the Civil Code of the Philippines, borrowers must exercise high care and diligence over public assets.</li>
                    </ul>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer>
        &copy; 2026 Barangay Tiniguiban
    </footer>

</body>
</html>
