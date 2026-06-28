<?php
// manage_request.php - Admin Request Manager for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

// Verify Admin session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle Admin Decisions (Approve / Reject) using PDO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    $req_id = intval($_POST['request_id']);
    $decision = $_POST['action']; // 'Approved', 'Rejected', 'pay_penalty', or 'waive_penalty'
    
    if ($req_id > 0) {
        if ($decision === 'Approved' || $decision === 'Rejected') {
            // Fetch request details
            $stmt = $conn->prepare("SELECT item_name, quantity, status FROM requests WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $req_id]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($req) {
                if ($req['status'] === 'Pending') {
                    try {
                        $conn->beginTransaction();
                        
                        // Update request status
                        $update = $conn->prepare("UPDATE requests SET status = :status WHERE id = :id");
                        $update_success = $update->execute(['status' => $decision, 'id' => $req_id]);
                        
                        $inventory_success = true;
                        
                        if ($decision === 'Approved') {
                            // Check if inventory has enough available
                            $inv_check = $conn->prepare("SELECT available FROM inventory WHERE name = :name LIMIT 1");
                            $inv_check->execute(['name' => $req['item_name']]);
                            $inv_res = $inv_check->fetch(PDO::FETCH_ASSOC);
                            
                            if ($inv_res && $inv_res['available'] >= $req['quantity']) {
                                // Deduct available count
                                $deduct = $conn->prepare("UPDATE inventory SET available = available - :qty WHERE name = :name");
                                $inventory_success = $deduct->execute(['qty' => $req['quantity'], 'name' => $req['item_name']]);
                                
                                // Sync status
                                $sync = $conn->prepare("UPDATE inventory SET status = CASE WHEN available = 0 THEN 'Not Available' WHEN available <= 3 THEN 'Limited' ELSE 'Available' END WHERE name = :name");
                                $sync->execute(['name' => $req['item_name']]);
                            } else {
                                $inventory_success = false;
                                $error = 'Insufficient inventory availability to approve this request.';
                            }
                        }
                        
                        if ($update_success && $inventory_success) {
                            $conn->commit();
                            $message = "Request REQ-" . str_pad($req_id, 3, '0', STR_PAD_LEFT) . " has been successfully " . strtolower($decision) . "!";
                        } else {
                            $conn->rollBack();
                            if (empty($error)) {
                                $error = 'Failed to update request. Database connection error.';
                            }
                        }
                    } catch (Exception $e) {
                        $conn->rollBack();
                        $error = 'Failed to process request decision: ' . $e->getMessage();
                    }
                } else {
                    $error = 'This request has already been processed.';
                }
            } else {
                $error = 'Request not found.';
            }
        } elseif ($decision === 'pay_penalty' || $decision === 'waive_penalty') {
            // Fetch request details
            $stmt = $conn->prepare("SELECT status, penalty_status FROM requests WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $req_id]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($req && $req['status'] === 'Returned' && $req['penalty_status'] === 'Unpaid') {
                try {
                    $new_status = ($decision === 'pay_penalty') ? 'Paid' : 'Waived';
                    $update = $conn->prepare("UPDATE requests SET penalty_status = :pstatus WHERE id = :id");
                    $update->execute(['pstatus' => $new_status, 'id' => $req_id]);
                    
                    $message = "Penalty for request REQ-" . str_pad($req_id, 3, '0', STR_PAD_LEFT) . " has been successfully " . strtolower($new_status) . "!";
                } catch (Exception $e) {
                    $error = 'Failed to update penalty status: ' . $e->getMessage();
                }
            } else {
                $error = 'This request does not have an unpaid penalty.';
            }
        }
    }
}

// 1. Calculate live counts for cards using PDO
$stat_total = $conn->query("SELECT COUNT(*) FROM requests")->fetchColumn();
$stat_pending = $conn->query("SELECT COUNT(*) FROM requests WHERE status = 'Pending'")->fetchColumn();
$stat_approved = $conn->query("SELECT COUNT(*) FROM requests WHERE status = 'Approved'")->fetchColumn();
$stat_rejected = $conn->query("SELECT COUNT(*) FROM requests WHERE status = 'Rejected'")->fetchColumn();

// 2. Fetch all system requests
$requests_res = $conn->query("
    SELECT r.*, u.first_name, u.last_name, u.contact_number 
    FROM requests r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.id DESC
");
$requests_data = $requests_res->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Borrow Requests - Barangay Tiniguiban</title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="user_page.css?v=<?php echo time(); ?>">
    <style>
        /* Manage Requests Custom Styles */
        .search-filter-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            background: #d9d9d9;
            padding: 12px 20px;
            border-radius: 16px;
            border: 1.5px solid #c8c2b4;
        }
        .search-filter-row input {
            padding: 8px 12px;
            border-radius: 20px;
            border: 1px solid #aaa;
            background: #efefef;
            outline: none;
            width: 250px;
        }
        .search-filter-row select {
            padding: 8px 12px;
            border-radius: 20px;
            border: 1px solid #aaa;
            background: #efefef;
            outline: none;
            cursor: pointer;
        }
        
        tbody tr {
            cursor: pointer;
        }
        tbody tr.selected {
            background: rgba(26, 37, 53, 0.15) !important;
        }
        tbody tr.selected td {
            font-weight: bold;
        }

        .bottom-panel {
            display: grid;
            grid-template-columns: 1fr 140px 1fr;
            gap: 20px;
            margin-top: 25px;
            background: #d9d9d9;
            border: 1.5px solid #c8c2b4;
            border-radius: 22px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }
        .bottom-panel .box {
            display: flex;
            flex-direction: column;
        }
        .bottom-panel label {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .bottom-panel textarea {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1.5px solid #aaa;
            background: #e8e8d8;
            font-size: 13px;
            resize: none;
            height: 90px;
            color: #333;
            outline: none;
        }
        .bottom-panel .actions {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 10px;
            height: 100%;
            padding-top: 15px;
        }
        .btn-decision {
            width: 100%;
            padding: 10px;
            border: 1px solid #111;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            color: white;
            font-size: 13px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            transition: opacity 0.2s;
        }
        .btn-decision:hover {
            opacity: 0.8;
        }
        .btn-decision:disabled {
            background: #aaa !important;
            border-color: #999;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .btn-approve {
            background: #006615;
        }
        .btn-reject {
            background: #7a1a1a;
        }
        
        .status-pill.returned {
            background: #888;
        }
        .penalty-tag {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }
        .penalty-tag.unpaid {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        .penalty-tag.paid {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
            border: 1px solid #28a745;
        }
        .penalty-tag.waived {
            background: rgba(108, 117, 125, 0.15);
            color: #6c757d;
            border: 1px solid #6c757d;
        }
        .penalty-tag.none {
            color: #888;
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
        .hamburger-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            width: 35px;
            height: 35px;
            margin-left: auto;
        }
        .hamburger-toggle svg {
            width: 100%;
            height: 100%;
            fill: #1a2535;
        }
        
        @media (max-width: 768px) {
            .header-inner {
                justify-content: space-between;
                position: relative;
            }
            .hamburger-toggle {
                display: block;
            }
            header nav, header .header-right {
                display: none !important;
                width: 100%;
            }
            .header-inner.menu-open nav {
                display: flex !important;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                margin-top: 15px;
                width: 100%;
                margin-left: 0;
            }
            .header-inner.menu-open .header-right {
                display: flex !important;
                flex-direction: column;
                align-items: center;
                gap: 15px;
                margin-top: 15px;
                width: 100%;
                border-top: 1px solid #ede6d6;
                padding-top: 15px;
                margin-left: 0;
            }
            .header-inner.menu-open nav a {
                width: 100%;
                text-align: center;
                padding: 8px;
            }
            .header-inner.menu-open nav a::after {
                display: none !important;
            }
            
            /* Responsive Search Row */
            .search-filter-row {
                flex-direction: column !important;
                align-items: stretch !important;
            }
            .search-filter-row input {
                width: 100% !important;
                box-sizing: border-box;
            }
            .search-filter-row select {
                width: 100% !important;
            }

            /* Responsive Bottom Panel */
            .bottom-panel {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
                padding: 15px !important;
            }
            .bottom-panel .actions {
                flex-direction: row !important;
                flex-wrap: wrap !important;
                justify-content: center !important;
                gap: 10px !important;
            }
            .bottom-panel button {
                flex: 1 1 120px !important;
                padding: 10px !important;
            }
        }
    </style>
    <script>
        function toggleMenu() {
            const inner = document.querySelector('.header-inner');
            inner.classList.toggle('menu-open');
        }
    </script>
</head>
<body>

    <header>
  <div class="header-inner">

    <!-- LOGO -->
    <div class="logo-wrap">
      <div class="logo-circle">
        <img src="logo.png" alt="Barangay Logo">
      </div>
      <div class="brand-text">
        <h1>BARANGAY TINIGUIBAN</h1>
        <p>Resource Borrowing System</p>
      </div>
    </div>

    <!-- Hamburger Toggle Button -->
    <button class="hamburger-toggle" onclick="toggleMenu()" aria-label="Toggle Menu">
        <svg viewBox="0 0 24 24">
            <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
        </svg>
    </button>

    <!-- NAVIGATION -->
    <nav>
      <a href="admin_page.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'admin_page.php') ? 'active' : ''; ?>">Home</a>
      <a href="admin_inventory.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'admin_inventory.php') ? 'active' : ''; ?>">Inventory</a>
      <a href="manage_request.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'manage_request.php') ? 'active' : ''; ?>">Manage Request</a>
      <a href="manage_users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'manage_users.php') ? 'active' : ''; ?>">Manage Users</a>
    </nav>

    <!-- RIGHT SIDE -->
    <div class="header-right">
      <span class="welcome-text">
        Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?>!</strong>
      </span>
      <a href="profile.php" class="profile-wrap" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center;">
        <div class="avatar-btn">
          <svg viewBox="0 0 24 24">
            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
          </svg>
        </div>
        <span class="profile-label">Profile</span>
      </a>
      <button class="btn-logout" onclick="if(confirm('Are you sure you want to logout?')) window.location.href='logout.php';">Logout</button>
    </div>

  </div>
</header>

    <!-- Main Content -->
    <main>

        <!-- Statistics Cards -->
        <div class="top-row" style="margin-bottom: 1.5rem;">
            <div class="stat-cards" style="width: 100%; justify-content: flex-start; gap: 1rem;">
                <div class="stat-card" style="flex: 1; min-width: 120px;">
                    <div class="label">Total</div>
                    <div class="number" id="statTotal"><?php echo number_format($stat_total); ?></div>
                </div>

                <div class="stat-card" style="flex: 1; min-width: 120px;">
                    <div class="label">Pending</div>
                    <div class="number" id="statPending"><?php echo number_format($stat_pending); ?></div>
                </div>

                <div class="stat-card" style="flex: 1; min-width: 120px;">
                    <div class="label">Approved</div>
                    <div class="number" id="statApproved"><?php echo number_format($stat_approved); ?></div>
                </div>

                <div class="stat-card" style="flex: 1; min-width: 120px;">
                    <div class="label">Rejected</div>
                    <div class="number" id="statRejected"><?php echo number_format($stat_rejected); ?></div>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Search & Filter Controls -->
        <div class="search-filter-row">
            <span style="font-weight: bold; font-size: 13px; color: #333;">Search:</span>
            <input type="text" id="searchInput" oninput="filterRequests()" placeholder="Search by requester or item...">
            
            <select id="filterStatus" onchange="filterRequests()">
                <option value="All">Status: All</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="Returned">Returned</option>
            </select>
            
            <select id="filterDate" onchange="filterRequests()">
                <option value="All">Date: All</option>
                <option value="Today">Today</option>
                <option value="Future">Future</option>
            </select>
        </div>

        <!-- Recent Borrowing Activity -->
        <section class="activity-section" style="max-height: 280px; overflow: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Request Code</th>
                        <th>Requester</th>
                        <th>Contact Number</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Return Time</th>
                        <th>Returned At</th>
                        <th>Penalty</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody id="requestsTableBody">
                    <?php if (count($requests_data) > 0): ?>
                        <?php foreach ($requests_data as $req): ?>
                            <tr data-id="<?php echo $req['id']; ?>" data-status="<?php echo htmlspecialchars($req['status']); ?>" data-date="<?php echo htmlspecialchars($req['borrow_date']); ?>">
                                <td><?php echo htmlspecialchars($req['request_code']); ?></td>
                                <td><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['contact_number']); ?></td>
                                <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($req['borrow_date']); ?></td>
                                <td><?php echo htmlspecialchars($req['return_date']); ?></td>
                                <td><?php echo htmlspecialchars(substr($req['return_time'], 0, 5)); ?></td>
                                <td><?php echo $req['returned_at'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($req['returned_at']))) : '—'; ?></td>
                                <td>
                                    <?php if ($req['penalty_amount'] > 0): ?>
                                        <span class="penalty-tag <?php echo strtolower($req['penalty_status']); ?>">
                                            ₱<?php echo number_format($req['penalty_amount'], 2); ?> (<?php echo htmlspecialchars($req['penalty_status']); ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="penalty-tag none">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-pill <?php echo strtolower($req['status']); ?>"><?php echo htmlspecialchars($req['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" style="text-align: center; color: #666; padding: 1.5rem;">No requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Decision Box Panel -->
        <div class="bottom-panel">
            <!-- Left: Purpose -->
            <div class="box">
                <label>Purpose of borrowing:</label>
                <textarea id="detailPurpose" readonly placeholder="Select a request to view its purpose..."></textarea>
            </div>
            
            <!-- Center: Actions -->
            <div class="actions">
                <button class="btn-decision btn-approve" id="approveBtn" disabled onclick="decideRequest('Approved')">Approve</button>
                <button class="btn-decision btn-reject" id="rejectBtn" disabled onclick="decideRequest('Rejected')">Reject</button>
                <button class="btn-decision btn-pay" id="payBtn" style="display: none; background: #006615;" onclick="handlePenaltyAction('pay_penalty')">Mark Paid</button>
                <button class="btn-decision btn-waive" id="waiveBtn" style="display: none; background: #6c757d;" onclick="handlePenaltyAction('waive_penalty')">Waive Penalty</button>
            </div>
            
            <!-- Right: Notes -->
            <div class="box">
                <label>Additional Notes:</label>
                <textarea id="detailNotes" readonly placeholder="Select a request to view additional notes..."></textarea>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer style="margin-top: 40px;">
        &copy; 2026 Barangay Tiniguiban
    </footer>

    <script>
        // Embed requests data for JS detail viewing
        const requestsData = <?php echo json_encode($requests_data); ?>;
        let selectedRowId = null;

        window.addEventListener('DOMContentLoaded', () => {
            const rows = document.querySelectorAll('#requestsTableBody tr');
            rows.forEach(row => {
                if (row.cells.length < 11) return;
                
                row.addEventListener('click', () => {
                    rows.forEach(r => r.classList.remove('selected'));
                    row.classList.add('selected');
                    
                    selectedRowId = row.dataset.id;
                    const req = requestsData.find(r => r.id == selectedRowId);
                    if (req) {
                        loadRequestDetail(req);
                    }
                });
            });
        });

        function loadRequestDetail(req) {
            document.getElementById('detailPurpose').value = req.purpose || 'No purpose stated.';
            document.getElementById('detailNotes').value = req.notes || 'No additional notes provided.';

            const approveBtn = document.getElementById('approveBtn');
            const rejectBtn = document.getElementById('rejectBtn');
            const payBtn = document.getElementById('payBtn');
            const waiveBtn = document.getElementById('waiveBtn');

            // Reset all
            approveBtn.style.display = 'inline-block';
            rejectBtn.style.display = 'inline-block';
            approveBtn.disabled = true;
            rejectBtn.disabled = true;
            if (payBtn) payBtn.style.display = 'none';
            if (waiveBtn) waiveBtn.style.display = 'none';

            if (req.status === 'Pending') {
                approveBtn.disabled = false;
                rejectBtn.disabled = false;
            } else if (req.status === 'Returned' && req.penalty_status === 'Unpaid') {
                approveBtn.style.display = 'none';
                rejectBtn.style.display = 'none';
                if (payBtn) payBtn.style.display = 'inline-block';
                if (waiveBtn) waiveBtn.style.display = 'inline-block';
            }
        }

        function decideRequest(decision) {
            if (!selectedRowId) return;

            const actionVerb = decision === 'Approved' ? 'approve' : 'reject';
            if (confirm(`Are you sure you want to ${actionVerb} request REQ-${selectedRowId}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'manage_request.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = decision;
                form.appendChild(actionInput);
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'request_id';
                idInput.value = selectedRowId;
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        function handlePenaltyAction(action) {
            if (!selectedRowId) return;

            const actionVerb = action === 'pay_penalty' ? 'mark this penalty as PAID' : 'WAIVE this penalty';
            if (confirm(`Are you sure you want to ${actionVerb} for request REQ-${selectedRowId}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'manage_request.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = action;
                form.appendChild(actionInput);
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'request_id';
                idInput.value = selectedRowId;
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        function filterRequests() {
            const searchVal = document.getElementById('searchInput').value.toLowerCase();
            const statusVal = document.getElementById('filterStatus').value;
            const dateVal = document.getElementById('filterDate').value;

            const today = new Date().toISOString().split('T')[0];
            const rows = document.querySelectorAll('#requestsTableBody tr');
            
            rows.forEach(row => {
                if (row.cells.length < 11) return;

                const code = row.cells[0].textContent.toLowerCase();
                const requester = row.cells[1].textContent.toLowerCase();
                const item = row.cells[3].textContent.toLowerCase();
                const status = row.dataset.status;
                const borrowDate = row.dataset.date;

                const matchesSearch = code.includes(searchVal) || requester.includes(searchVal) || item.includes(searchVal);
                const matchesStatus = statusVal === 'All' || status === statusVal;
                
                let matchesDate = true;
                if (dateVal === 'Today') {
                    matchesDate = (borrowDate === today);
                } else if (dateVal === 'Future') {
                    matchesDate = (borrowDate > today);
                }

                if (matchesSearch && matchesStatus && matchesDate) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
