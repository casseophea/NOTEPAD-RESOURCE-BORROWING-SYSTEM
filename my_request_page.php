<?php
// my_request_page.php - User Requests Dashboard for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

// Verify User session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle Item Return POST Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return') {
    $req_id = intval($_POST['request_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($req_id > 0) {
        // Verify request belongs to user and is Approved using PDO
        $stmt = $conn->prepare("SELECT id, item_name, quantity, status, return_date, return_time FROM requests WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute(['id' => $req_id, 'uid' => $user_id]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($req) {
            if ($req['status'] === 'Approved') {
                try {
                    // Begin transaction
                    $conn->beginTransaction();
                    
                    // Calculate Penalty
                    $now = new DateTime();
                    $actual_return_time_str = $now->format('Y-m-d H:i:s');
                    
                    $deadline_str = $req['return_date'];
                    if (!empty($req['return_time'])) {
                        $deadline_str .= ' ' . $req['return_time'];
                    } else {
                        $deadline_str .= ' 23:59:59';
                    }
                    $deadline = new DateTime($deadline_str);
                    
                    $penalty_amount = 0.00;
                    $penalty_status = 'No Penalty';
                    
                    if ($now > $deadline) {
                        $seconds_late = $now->getTimestamp() - $deadline->getTimestamp();
                        if ($seconds_late > 0) {
                            $hours_late = ceil($seconds_late / 3600);
                            $penalty_amount = $hours_late * 100;
                            $penalty_status = 'Unpaid';
                        }
                    }
                    
                    // Update request status to 'Returned'
                    $update = $conn->prepare("UPDATE requests SET status = 'Returned', returned_at = :returned_at, penalty_amount = :penalty_amount, penalty_status = :penalty_status WHERE id = :id");
                    $update->execute([
                        'returned_at' => $actual_return_time_str,
                        'penalty_amount' => $penalty_amount,
                        'penalty_status' => $penalty_status,
                        'id' => $req_id
                    ]);
                    
                    // Restore inventory availability count
                    $restore = $conn->prepare("UPDATE inventory SET available = available + :qty WHERE name = :name");
                    $restore->execute(['qty' => $req['quantity'], 'name' => $req['item_name']]);
                    
                    // Update status for inventory item
                    $sync = $conn->prepare("UPDATE inventory SET status = CASE WHEN available = 0 THEN 'Not Available' WHEN available <= 3 THEN 'Limited' ELSE 'Available' END WHERE name = :name");
                    $sync->execute(['name' => $req['item_name']]);

                    $conn->commit();
                    if ($penalty_amount > 0) {
                        $message = 'Item successfully returned! A penalty of ₱' . number_format($penalty_amount, 2) . ' has been charged because the item was returned late. Please pay this at the Barangay hall to settle your account.';
                    } else {
                        $message = 'Item successfully returned on time! Thank you.';
                    }
                } catch (Exception $e) {
                    $conn->rollBack();
                    $error = 'Failed to process item return: ' . $e->getMessage();
                }
            } else {
                $error = 'You can only return items that are currently Approved (borrowed).';
            }
        } else {
            $error = 'Request not found or unauthorized.';
        }
    }
}

// Fetch requests belonging to this user using PDO prepared statement
$user_id = $_SESSION['user_id'];
$requests_stmt = $conn->prepare("SELECT * FROM requests WHERE user_id = :uid ORDER BY id DESC");
$requests_stmt->execute(['uid' => $user_id]);
$requests_data = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Requests - Barangay Tiniguiban</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="my_request_page.css?v=<?php echo time(); ?>"/>
  <style>
    /* Row Selection and custom styles */
    tbody tr {
      cursor: pointer;
    }
    tbody tr.selected {
      background: rgba(26, 37, 53, 0.15) !important;
    }
    tbody tr.selected td {
      font-weight: bold;
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
      font-size: 13px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 20px;
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

<!-- ═════════════════ HEADER ═════════════════ -->
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
      <a href="user_page.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'user_page.php') ? 'active' : ''; ?>">Home</a>
      <a href="user_inventory.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'user_inventory.php') ? 'active' : ''; ?>">Inventory</a>
      <a href="request_page.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'request_page.php') ? 'active' : ''; ?>">Request</a>
      <a href="my_request_page.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'my_request_page.php') ? 'active' : ''; ?>">My Request</a>
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

<!-- ═════════════════ MAIN ═════════════════ -->
<main>
  <h2 class="section-title">My requests.</h2>

  <?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <!-- TABLE CARD -->
  <section class="table-card">
    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th>Quantity</th>
          <th>Borrowed Date</th>
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
            <tr data-id="<?php echo $req['id']; ?>" data-status="<?php echo htmlspecialchars($req['status']); ?>">
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
              <td>
                <span class="status-pill <?php echo strtolower($req['status']); ?>">
                  <?php echo htmlspecialchars($req['status']); ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" style="text-align: center; color: #666; padding: 2rem;">You have not made any borrow requests yet.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <!-- Return Notice -->
  <div style="background: #F0F0DB; border: 1.5px solid #c8c2b4; border-radius: 10px; padding: 12px 15px; max-width: 1300px; margin: 15px auto; font-size: 13px; color: #1a2535; display: flex; align-items: center; gap: 10px;">
    <svg style="width: 18px; height: 18px; fill: #7a1a1a; flex-shrink: 0;" viewBox="0 0 24 24">
      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h-3v2h1v4H8v2h8v-2h-3zm-1-8c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/>
    </svg>
    <span>
      <strong>Important Notice:</strong> Please return your borrowed items strictly on or before the scheduled return date and time. Late returns will be subject to a late penalty fee of <strong>₱100.00 per hour</strong>. This policy is strictly enforced to ensure that other residents will also be able to borrow the items on time for their scheduled bookings.
    </span>
  </div>

  <!-- BUTTON -->
  <div class="button-wrap">
    <button class="btn-return" onclick="handleReturn()">
      Return Item
    </button>
  </div>
</main>

<!-- ═════════════════ FOOTER ═════════════════ -->
<footer>
  &copy; 2026 Barangay Tiniguiban
</footer>

<script>
  let selectedRequestId = null;
  let selectedRequestStatus = null;

  window.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('#requestsTableBody tr');
    rows.forEach(row => {
      // Skip empty row
      if (row.cells.length < 8) return;
      
      row.addEventListener('click', () => {
        rows.forEach(r => r.classList.remove('selected'));
        row.classList.add('selected');
        
        selectedRequestId = row.dataset.id;
        selectedRequestStatus = row.dataset.status;
      });
    });
  });

  function handleReturn() {
    if (!selectedRequestId) {
      alert('Please click on a request in the table to select it first.');
      return;
    }
    
    if (selectedRequestStatus !== 'Approved') {
      alert(`You can only return items that are currently "Approved" (borrowed). Current status is: "${selectedRequestStatus}".`);
      return;
    }
    
    if (confirm('Are you sure you want to return this item?')) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'my_request_page.php';
      
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'return';
      form.appendChild(actionInput);
      
      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'request_id';
      idInput.value = selectedRequestId;
      form.appendChild(idInput);
      
      document.body.appendChild(form);
      form.submit();
    }
  }
</script>
</body>
</html>
