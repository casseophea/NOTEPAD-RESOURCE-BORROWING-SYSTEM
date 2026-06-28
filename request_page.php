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
    $borrow_time = $_POST['borrow_time'] ?? '';
    $return_date = $_POST['return_date'] ?? '';
    $return_time = $_POST['return_time'] ?? '';
    $purpose = trim($_POST['purpose'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($item_name) || $quantity <= 0 || empty($borrow_date) || empty($return_date) || empty($purpose)) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($return_date) < strtotime($borrow_date)) {
        $error = 'Return date cannot be before borrow date.';
    } else {
        // Generate dynamic request code using PDO
        $cnt = $conn->query("SELECT COUNT(*) FROM requests")->fetchColumn();
        $req_code = 'REQ-' . str_pad($cnt + 1, 3, '0', STR_PAD_LEFT);
        
        $user_id = $_SESSION['user_id'];
        $status = 'Pending';
        
        $stmt = $conn->prepare("INSERT INTO requests (request_code, user_id, item_name, quantity, borrow_date, borrow_time, return_date, return_time, purpose, notes, status) VALUES (:code, :uid, :item, :qty, :bdate, :btime, :rdate, :rtime, :purpose, :notes, :status)");
        
        $success = $stmt->execute([
            'code' => $req_code,
            'uid' => $user_id,
            'item' => $item_name,
            'qty' => $quantity,
            'bdate' => $borrow_date,
            'btime' => $borrow_time ?: null,
            'rdate' => $return_date,
            'rtime' => $return_time ?: null,
            'purpose' => $purpose,
            'notes' => $notes ?: null,
            'status' => $status
        ]);
        
        if ($success) {
            header('Location: my_request_page.php');
            exit;
        } else {
            $error = 'Failed to submit request. Please try again.';
        }
    }
}

// Fetch all inventory items for selection dropdown using PDO
$items_res = $conn->query("SELECT name, available FROM inventory ORDER BY name ASC");
$inventory_list = $items_res->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Barangay Tiniguiban – Request Item</title>

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet"/>

  <link rel="stylesheet" href="request_page.css?v=<?php echo time(); ?>"/>
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

<?php include 'navigation.php'; ?>

<!-- MAIN -->
<main>

  <section class="request-container">

    <?php if (!empty($error)): ?>
      <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="request_page.php" id="requestForm">
      <!-- Return Schedule Reminder -->
      <div style="background: #F0F0DB; border: 2px solid #30364F; border-radius: 8px; padding: 12px 15px; margin-bottom: 20px; color: #1a2535; font-size: 13px; line-height: 1.5; display: flex; align-items: flex-start; gap: 10px;">
        <svg style="width: 20px; height: 20px; fill: #7a1a1a; flex-shrink: 0; margin-top: 2px;" viewBox="0 0 24 24">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h-3v2h1v4H8v2h8v-2h-3zm-1-8c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/>
        </svg>
        <div>
          <strong>Return Schedule Reminder:</strong> Under Barangay regulations and civil borrowing guidelines, you must return the requested items strictly by the exact return date and time selected below. This ensures other citizens who have scheduled requests can access the items immediately.
        </div>
      </div>

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

        <!-- BORROW TIME -->
        <div class="form-group">
          <label>Borrow Time:</label>
          <input type="time" name="borrow_time" id="borrowTime">
        </div>

        <!-- RETURN DATE -->
        <div class="form-group">
          <label>Return Date:</label>
          <input type="date" name="return_date" id="returnDate" required>
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
      <div class="bottom-row" style="justify-content: center;">

        <div class="buttons">
          <button type="button" class="btn-cancel" onclick="window.location.href='user_page.php'">Cancel</button>
          <button type="submit" class="btn-submit">Submit</button>
        </div>

      </div>
    </form>

  </section>

</main>

<!-- FOOTER -->
<footer>
  &copy; 2026 Barangay Tiniguiban
</footer>

<script>
  window.addEventListener('DOMContentLoaded', () => {
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('borrowDate').value = today;
    document.getElementById('returnDate').value = today;
    
    // Set default borrow time to current time
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('borrowTime').value = `${hours}:${minutes}`;
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
