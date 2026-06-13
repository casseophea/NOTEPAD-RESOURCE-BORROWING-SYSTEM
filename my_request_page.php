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
        // Verify request belongs to user and is Approved
        $stmt = $conn->prepare("SELECT id, item_name, quantity, status FROM requests WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->bind_param("ii", $req_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res && $res->num_rows > 0) {
            $req = $res->fetch_assoc();
            
            if ($req['status'] === 'Approved') {
                // Begin transaction
                $conn->begin_transaction();
                
                // Update request status to 'Returned'
                $update = $conn->prepare("UPDATE requests SET status = 'Returned' WHERE id = ?");
                $update->bind_param("i", $req_id);
                
                // Restore inventory availability count
                $restore = $conn->prepare("UPDATE inventory SET available = available + ? WHERE name = ?");
                $restore->bind_param("is", $req['quantity'], $req['item_name']);
                
                if ($update->execute() && $restore->execute()) {
                    // Update status for inventory item if available count exceeds limit
                    $sync = $conn->prepare("UPDATE inventory SET status = CASE WHEN available = 0 THEN 'Not Available' WHEN available <= 3 THEN 'Limited' ELSE 'Available' END WHERE name = ?");
                    $sync->bind_param("s", $req['item_name']);
                    $sync->execute();
                    $sync->close();

                    $conn->commit();
                    $message = 'Item successfully returned! Thank you.';
                } else {
                    $conn->rollback();
                    $error = 'Failed to process item return. Please try again.';
                }
                $update->close();
                $restore->close();
            } else {
                $error = 'You can only return items that are currently Approved (borrowed).';
            }
        } else {
            $error = 'Request not found or unauthorized.';
        }
        $stmt->close();
    }
}

// Fetch requests belonging to this user
$user_id = $_SESSION['user_id'];
$requests_res = $conn->query("SELECT * FROM requests WHERE user_id = $user_id ORDER BY id DESC");
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Requests - Barangay Tiniguiban</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="my_request_page.css"/>
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
  </style>
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

    <!-- NAVIGATION -->
    <nav>
      <a href="user_page.php">Home</a>
      <a href="user_inventory.php">Inventory</a>
      <a href="request_page.php">Request</a>
      <a href="my_request_page.php" class="active">My Request</a>
    </nav>

    <!-- RIGHT SIDE -->
    <div class="header-right">
      <span class="welcome-text">
        Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?>!</strong>
      </span>
      <a href="profile.php" class="profile-wrap" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center;">
        <div class="avatar-btn">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
          </svg>
        </div>
        <span class="profile-label">Profile</span>
      </a>
      <button class="btn-logout" onclick="window.location.href='logout.php'">Logout</button>
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
              <td>
                <span class="status-pill <?php echo strtolower($req['status']); ?>">
                  <?php echo htmlspecialchars($req['status']); ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center; color: #666; padding: 2rem;">You have not made any borrow requests yet.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

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
      if (row.cells.length < 6) return;
      
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
