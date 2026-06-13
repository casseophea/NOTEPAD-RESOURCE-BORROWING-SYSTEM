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

// Handle Admin Decisions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    $req_id = intval($_POST['request_id']);
    $decision = $_POST['action']; // 'Approved' or 'Rejected'
    
    if ($req_id > 0 && ($decision === 'Approved' || $decision === 'Rejected')) {
        // Fetch request details
        $stmt = $conn->prepare("SELECT item_name, quantity, status FROM requests WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $req_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res && $res->num_rows > 0) {
            $req = $res->fetch_assoc();
            
            if ($req['status'] === 'Pending') {
                $conn->begin_transaction();
                
                // Update request status
                $update = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
                $update->bind_param("si", $decision, $req_id);
                $update_success = $update->execute();
                $update->close();
                
                $inventory_success = true;
                
                if ($decision === 'Approved') {
                    // Check if inventory has enough available
                    $inv_check = $conn->prepare("SELECT available FROM inventory WHERE name = ? LIMIT 1");
                    $inv_check->bind_param("s", $req['item_name']);
                    $inv_check->execute();
                    $inv_res = $inv_check->get_result()->fetch_assoc();
                    $inv_check->close();
                    
                    if ($inv_res && $inv_res['available'] >= $req['quantity']) {
                        // Deduct available count
                        $deduct = $conn->prepare("UPDATE inventory SET available = available - ? WHERE name = ?");
                        $deduct->bind_param("is", $req['quantity'], $req['item_name']);
                        $inventory_success = $deduct->execute();
                        $deduct->close();
                        
                        // Sync status
                        $sync = $conn->prepare("UPDATE inventory SET status = CASE WHEN available = 0 THEN 'Not Available' WHEN available <= 3 THEN 'Limited' ELSE 'Available' END WHERE name = ?");
                        $sync->bind_param("s", $req['item_name']);
                        $sync->execute();
                        $sync->close();
                    } else {
                        $inventory_success = false;
                        $error = 'Insufficient inventory availability to approve this request.';
                    }
                }
                
                if ($update_success && $inventory_success) {
                    $conn->commit();
                    $message = "Request REQ-" . str_pad($req_id, 3, '0', STR_PAD_LEFT) . " has been successfully " . strtolower($decision) . "!";
                } else {
                    $conn->rollback();
                    if (empty($error)) {
                        $error = 'Failed to update request. Database connection error.';
                    }
                }
            } else {
                $error = 'This request has already been processed.';
            }
        } else {
            $error = 'Request not found.';
        }
        $stmt->close();
    }
}

// 1. Calculate live counts for cards
$stat_total = $conn->query("SELECT COUNT(*) FROM requests")->fetch_row()[0];
$stat_pending = $conn->query("SELECT COUNT(*) FROM requests WHERE status = 'Pending'")->fetch_row()[0];
$stat_approved = $conn->query("SELECT COUNT(*) FROM requests WHERE status = 'Approved'")->fetch_row()[0];
$stat_rejected = $conn->query("SELECT COUNT(*) FROM requests WHERE status = 'Rejected'")->fetch_row()[0];

// 2. Fetch all system requests
$requests_res = $conn->query("
    SELECT r.*, u.first_name, u.last_name, u.contact_number 
    FROM requests r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.id DESC
");
$requests_data = [];
if ($requests_res) {
    while ($row = $requests_res->fetch_assoc()) {
        $requests_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Borrow Requests - Barangay Tiniguiban</title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="user_page.css">
    <style>
        /* Manage Requests Custom Styles */
        .search-filter-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            background: var(--card-bg);
            padding: 12px 20px;
            border-radius: var(--radius-medium);
            border: 1.5px solid var(--card-border);
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
            background: var(--card-bg);
            border: 1.5px solid var(--card-border);
            border-radius: var(--radius-large);
            padding: 20px;
            box-shadow: var(--shadow-small);
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
            box-shadow: var(--shadow-sm);
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
                <a href="admin_page.php">Home</a>
                <a href="admin_inventory.php">Inventory</a>
                <a href="manage_request.php" class="active">Manage Request</a>
            </nav>

            <!-- User Section -->
            <div class="header-right">

                <span class="welcome-text">
                    Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?>!</strong>
                </span>

                <a href="profile.php" class="profile-section" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center;">
                    <div class="avatar-btn">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                        </svg>
                    </div>

                    <span class="profile-label">Profile</span>
                </a>

                <button class="btn-logout" onclick="window.location.href='logout.php'">Logout</button>

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
        <section class="activity-section" style="max-height: 280px; overflow-y: auto;">
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
                                <td><span class="status-pill <?php echo strtolower($req['status']); ?>"><?php echo htmlspecialchars($req['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: #666; padding: 1.5rem;">No requests found.</td>
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
                if (row.cells.length < 9) return;
                
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

            if (req.status === 'Pending') {
                approveBtn.disabled = false;
                rejectBtn.disabled = false;
            } else {
                approveBtn.disabled = true;
                rejectBtn.disabled = true;
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

        function filterRequests() {
            const searchVal = document.getElementById('searchInput').value.toLowerCase();
            const statusVal = document.getElementById('filterStatus').value;
            const dateVal = document.getElementById('filterDate').value;

            const today = new Date().toISOString().split('T')[0];
            const rows = document.querySelectorAll('#requestsTableBody tr');
            
            rows.forEach(row => {
                if (row.cells.length < 9) return;

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
