<?php
// request_page.php - User Request Form for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

// Verify User session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$error = '';

// Process Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = $_POST['item_name'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $borrow_date = $_POST['borrow_date'] ?? '';
    $return_date = $_POST['return_date'] ?? '';
    $return_time = $_POST['return_time'] ?? '';
    $purpose = trim($_POST['purpose'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($item_name) || $quantity <= 0 || empty($borrow_date) || empty($return_date) || empty($purpose)) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($return_date) < strtotime($borrow_date)) {
        $error = 'Return date cannot be before borrow date.';
    } else {
        // Generate dynamic request code: REQ-001, etc.
        $resCount = $conn->query("SELECT COUNT(*) as cnt FROM requests");
        $cntRow = $resCount->fetch_assoc();
        $req_code = 'REQ-' . str_pad($cntRow['cnt'] + 1, 3, '0', STR_PAD_LEFT);
        
        $user_id = $_SESSION['user_id'];
        $status = 'Pending';
        
        $stmt = $conn->prepare("INSERT INTO requests (request_code, user_id, item_name, quantity, borrow_date, return_date, return_time, purpose, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissssssss", $req_code, $user_id, $item_name, $quantity, $borrow_date, $return_date, $return_time, $purpose, $notes, $status);
        
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: my_request_page.php');
            exit;
        } else {
            $error = 'Failed to submit request. Please try again.';
        }
        $stmt->close();
    }
}

// Fetch all inventory items for selection dropdown
$items_res = $conn->query("SELECT name, available FROM inventory ORDER BY name ASC");
$inventory_list = [];
if ($items_res) {
    while ($row = $items_res->fetch_assoc()) {
        $inventory_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Barangay Tiniguiban – Request Item</title>

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet"/>

  <link rel="stylesheet" href="request_page.css"/>
  <style>
    .error-msg {
      background: #7a1a1a;
      color: white;
      padding: 10px 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: bold;
      font-size: 13px;
      text-align: center;
    }
  </style>
</head>
<body>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<header>
  <div class="header-inner">

    <!-- LOGO -->
    <div class="logo-wrap">
      <div class="logo">
        <img src="logo.png"
             alt="logo"
             style="width: 50px; height: 50px; border-radius: 50%;">
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
      <a href="request_page.php" class="active">Request</a>
      <a href="my_request_page.php">My Request</a>
    </nav>

    <!-- RIGHT SECTION -->
    <div class="header-right">

      <span class="welcome-text">
        Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?>!</strong>
      </span>

      <a href="profile.php" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; margin-right: 5px;">
        <div class="avatar-btn" title="Profile">
          <svg viewBox="0 0 24 24"
               xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
          </svg>
        </div>

        <span class="profile-label">Profile</span>
      </a>

      <button class="btn-logout" onclick="window.location.href='logout.php'">Logout</button>

    </div>
  </div>
</header>

<!-- ═══════════════════ MAIN ═══════════════════ -->
<main>

  <section class="request-container">

    <?php if (!empty($error)): ?>
      <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="request_page.php" id="requestForm">
      <!-- TOP ROW -->
      <div class="form-grid">

        <!-- ITEM -->
        <div class="form-group">
          <label>Item:</label>
          <select name="item_name" id="itemSelect" onchange="updateQuantities()" required>
            <option value="">Select Item</option>
            <?php foreach ($inventory_list as $item): ?>
              <option value="<?php echo htmlspecialchars($item['name']); ?>" data-available="<?php echo $item['available']; ?>">
                <?php echo htmlspecialchars($item['name'] . ' (' . $item['available'] . ' available)'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- QUANTITY -->
        <div class="form-group">
          <label>Quantity:</label>
          <select name="quantity" id="quantitySelect" required>
            <option value="">Select Item first</option>
          </select>
        </div>

        <!-- BORROW DATE -->
        <div class="form-group">
          <label>Borrow Date:</label>
          <input type="date" name="borrow_date" id="borrowDate" required>
        </div>

        <!-- RETURN TIME -->
        <div class="form-group">
          <label>Exact return time:</label>
          <input type="time" name="return_time" id="returnTime">
        </div>

      </div>

      <!-- PURPOSE -->
      <div class="form-group full-width">
        <label>Purpose of borrowing:</label>
        <textarea name="purpose" id="purpose" rows="2" placeholder="Describe the purpose of your request..." required><?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?></textarea>
      </div>

      <!-- NOTES -->
      <div class="form-group full-width">
        <label>Additional Notes:</label>
        <textarea name="notes" id="notes" rows="2" placeholder="Any additional notes (optional)..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
      </div>

      <!-- BOTTOM SECTION -->
      <div class="bottom-row">

        <div class="buttons">
          <button type="button" class="btn-cancel" onclick="window.location.href='user_page.php'">Cancel</button>
          <button type="submit" class="btn-submit">Submit</button>
        </div>

        <div class="form-group return-date">
          <label>Return Date:</label>
          <input type="date" name="return_date" id="returnDate" required>
        </div>

      </div>
    </form>

  </section>

</main>

<!-- ═══════════════════ FOOTER ═══════════════════ -->
<footer>
  &copy; 2026 Barangay Tiniguiban
</footer>

<script>
  window.addEventListener('DOMContentLoaded', () => {
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('borrowDate').value = today;
    document.getElementById('returnDate').value = today;
  });

  function updateQuantities() {
    const itemSelect = document.getElementById('itemSelect');
    const quantitySelect = document.getElementById('quantitySelect');
    const selectedOpt = itemSelect.options[itemSelect.selectedIndex];
    
    if (!selectedOpt || selectedOpt.value === '') {
      quantitySelect.innerHTML = '<option value="">Select Item first</option>';
      return;
    }
    
    const available = parseInt(selectedOpt.dataset.available) || 0;
    quantitySelect.innerHTML = '';
    
    if (available <= 0) {
      quantitySelect.innerHTML = '<option value="0">Out of Stock</option>';
      return;
    }
    
    // Fill quantities up to available count (capped at 50)
    const limit = Math.min(available, 50);
    for (let i = 1; i <= limit; i++) {
      const opt = document.createElement('option');
      opt.value = i;
      opt.textContent = i;
      quantitySelect.appendChild(opt);
    }
  }
</script>

</body>
</html>
