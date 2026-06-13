<?php
// admin_inventory.php - Admin Inventory Management for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

// Verify Admin session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle DB Mutations (Add/Edit/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? 'Furniture';
        $quantity = intval($_POST['quantity'] ?? 0);
        $available = intval($_POST['available'] ?? 0);
        
        // Compute Status
        $status = 'Available';
        if ($available === 0) {
            $status = 'Not Available';
        } elseif ($available <= 3) {
            $status = 'Limited';
        }
        
        if (!empty($name)) {
            $stmt = $conn->prepare("INSERT INTO inventory (name, category, quantity, available, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiis", $name, $category, $quantity, $available, $status);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: admin_inventory.php');
        exit;
    }
    
    elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? 'Furniture';
        $quantity = intval($_POST['quantity'] ?? 0);
        $available = intval($_POST['available'] ?? 0);
        
        $status = 'Available';
        if ($available === 0) {
            $status = 'Not Available';
        } elseif ($available <= 3) {
            $status = 'Limited';
        }
        
        if ($id > 0 && !empty($name)) {
            $stmt = $conn->prepare("UPDATE inventory SET name = ?, category = ?, quantity = ?, available = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssiisi", $name, $category, $quantity, $available, $status, $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: admin_inventory.php');
        exit;
    }
    
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: admin_inventory.php');
        exit;
    }
}

// Fetch all inventory items from MySQL
$inv_res = $conn->query("SELECT * FROM inventory ORDER BY name ASC");
$items_data = [];
if ($inv_res) {
    while ($row = $inv_res->fetch_assoc()) {
        $items_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Inventory - Barangay Tiniguiban</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="user_inventory.css" />
  <style>
    /* Admin Specific Styles */
    .btn-add {
      background: #30364F;
      color: white;
      border: 2px solid #000;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: bold;
      cursor: pointer;
      box-shadow: 0 2px 4px rgba(0,0,0,0.15);
      transition: background 0.2s, transform 0.1s;
    }
    .btn-add:hover {
      background: black;
      transform: translateY(-1px);
    }
    .action-btn-group {
      display: flex;
      gap: 5px;
      justify-content: center;
    }
    .btn-table-action {
      padding: 4px 10px;
      font-size: 11px;
      font-weight: bold;
      border-radius: 4px;
      cursor: pointer;
      color: white;
      border: 1px solid #111;
      transition: opacity 0.2s;
    }
    .btn-table-action:hover {
      opacity: 0.8;
    }
    .btn-edit {
      background: #30364F;
    }
    .btn-delete {
      background: #7a1a1a;
    }
    
    /* Simple CSS Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      align-items: center;
      justify-content: center;
    }
    .modal-content {
      background: #F0F0DB;
      padding: 25px;
      border-radius: 12px;
      width: 400px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      border: 2px solid #30364F;
    }
    .modal h3 {
      margin-top: 0;
      margin-bottom: 15px;
      color: #30364F;
      border-bottom: 2px solid #30364F;
      padding-bottom: 5px;
    }
    .modal-group {
      display: flex;
      flex-direction: column;
      margin-bottom: 12px;
    }
    .modal-group label {
      font-size: 12px;
      font-weight: bold;
      margin-bottom: 3px;
      color: #333;
    }
    .modal-group input, .modal-group select {
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #aaa;
      background: #e8e8d8;
      font-size: 13px;
      outline: none;
    }
    .modal-group input:focus {
      border-color: #30364F;
    }
    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }
    .btn-modal-save {
      background: #30364F;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }
    .btn-modal-cancel {
      background: #888;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <header>
    <div class="header-inner">

      <div class="logo-area">
        <div class="logo-circle">
          <img src="logo.png" alt="Barangay Logo" />
        </div>
        <div class="brand-text">
          <h1>BARANGAY TINIGUIBAN</h1>
          <p>Resource Borrowing System</p>
        </div>
      </div>

      <nav>
        <a href="admin_page.php">Home</a>
        <a href="admin_inventory.php" class="active">Inventory</a>
        <a href="manage_request.php">Manage Request</a>
      </nav>

      <div class="header-right">
        <span class="welcome-text">Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?>!</strong></span>
        <a href="profile.php" class="avatar-wrap" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center;">
          <div class="avatar-circle">
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

  <!-- MAIN -->
  <main>

    <!-- Search Row -->
    <div class="search-row" style="position: relative;">
      <span class="search-label">Search:</span>
      <div class="search-input-wrap">
        <input type="text" id="searchInput" oninput="filterInventory()" placeholder="Search by name or category..." />
        <svg class="search-icon" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="7" />
          <line x1="16.5" y1="16.5" x2="22" y2="22" />
        </svg>
      </div>
      <select class="filter-select" id="filterSelect" onchange="filterInventory()">
        <option value="All">All</option>
        <option value="Available">Available</option>
        <option value="Limited">Limited</option>
        <option value="Not Available">Not Available</option>
      </select>
      
      <!-- ADD ITEM BUTTON -->
      <div style="margin-left: auto;">
        <button class="btn-add" onclick="openAddModal()">+ Add Item</button>
      </div>
    </div>

    <!-- Inventory Table -->
    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th>Item Name</th>
            <th>Category</th>
            <th>Quantity</th>
            <th>Available</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="inventoryTableBody">
          <?php if (count($items_data) > 0): ?>
            <?php foreach ($items_data as $item): ?>
              <?php
                $pillClass = 'pill-available';
                if ($item['status'] === 'Limited') $pillClass = 'pill-limited';
                if ($item['status'] === 'Not Available') $pillClass = 'pill-not-available';
              ?>
              <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo htmlspecialchars($item['category']); ?></td>
                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td><?php echo htmlspecialchars($item['available']); ?></td>
                <td><span class="status-pill <?php echo $pillClass; ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                <td>
                  <div class="action-btn-group">
                    <button class="btn-table-action btn-edit" onclick="openEditModal(<?php echo $item['id']; ?>)">Edit</button>
                    <button class="btn-table-action btn-delete" onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>')">Delete</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; color: #666;">No items found in inventory.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>

  <!-- FOOTER -->
  <footer>
    &copy; 2026 Barangay Tiniguiban
  </footer>

  <!-- ADD / EDIT MODAL -->
  <div class="modal" id="itemModal">
    <div class="modal-content">
      <h3 id="modalTitle">Add New Item</h3>
      
      <form method="POST" action="admin_inventory.php" id="modalForm">
        <input type="hidden" name="action" id="modalAction" value="add">
        <input type="hidden" name="id" id="itemId" value="">
        
        <div class="modal-group">
          <label>Item Name:</label>
          <input type="text" name="name" id="itemName" required>
        </div>
        
        <div class="modal-group">
          <label>Category:</label>
          <select name="category" id="itemCategory">
            <option value="Furniture">Furniture</option>
            <option value="Electronics">Electronics</option>
            <option value="Office">Office</option>
            <option value="Other">Other</option>
          </select>
        </div>
        
        <div class="modal-group">
          <label>Total Quantity:</label>
          <input type="number" name="quantity" id="itemQuantity" min="0" oninput="syncAvailable()" required>
        </div>
        
        <div class="modal-group">
          <label>Available Quantity:</label>
          <input type="number" name="available" id="itemAvailable" min="0" required>
        </div>
        
        <div class="modal-actions">
          <button type="button" class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn-modal-save">Save Item</button>
        </div>
      </form>
    </div>
  </div>

  <!-- JavaScript Inventory Helper -->
  <script>
    // Embed PHP data as JSON for local Edit operations
    const inventoryData = <?php echo json_encode($items_data); ?>;

    function filterInventory() {
      const searchVal = document.getElementById('searchInput').value.toLowerCase();
      const filterVal = document.getElementById('filterSelect').value;
      
      const rows = document.querySelectorAll('#inventoryTableBody tr');
      rows.forEach(row => {
        if (row.cells.length < 6) return;
        
        const name = row.cells[0].textContent.toLowerCase();
        const category = row.cells[1].textContent.toLowerCase();
        const status = row.cells[4].textContent;
        
        const matchesSearch = name.includes(searchVal) || category.includes(searchVal);
        const matchesFilter = filterVal === 'All' || status === filterVal;
        
        if (matchesSearch && matchesFilter) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    // Modal Controls
    function openAddModal() {
      document.getElementById('modalTitle').textContent = 'Add New Item';
      document.getElementById('modalAction').value = 'add';
      document.getElementById('itemId').value = '';
      document.getElementById('itemName').value = '';
      document.getElementById('itemCategory').value = 'Furniture';
      document.getElementById('itemQuantity').value = '';
      document.getElementById('itemAvailable').value = '';
      
      document.getElementById('itemModal').style.display = 'flex';
    }

    function openEditModal(id) {
      const item = inventoryData.find(i => i.id == id);
      if (item) {
        document.getElementById('modalTitle').textContent = 'Edit Item';
        document.getElementById('modalAction').value = 'edit';
        document.getElementById('itemId').value = item.id;
        document.getElementById('itemName').value = item.name;
        document.getElementById('itemCategory').value = item.category;
        document.getElementById('itemQuantity').value = item.quantity;
        document.getElementById('itemAvailable').value = item.available;
        
        document.getElementById('itemModal').style.display = 'flex';
      }
    }

    function syncAvailable() {
      const isAdd = document.getElementById('modalAction').value === 'add';
      if (isAdd) {
        document.getElementById('itemAvailable').value = document.getElementById('itemQuantity').value;
      }
    }

    function closeModal() {
      document.getElementById('itemModal').style.display = 'none';
    }

    function deleteItem(id, name) {
      if (confirm(`Are you sure you want to delete "${name}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'admin_inventory.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
      }
    }
    
    // Validate form on submit
    document.getElementById('modalForm').addEventListener('submit', (e) => {
      const qty = parseInt(document.getElementById('itemQuantity').value) || 0;
      const avail = parseInt(document.getElementById('itemAvailable').value) || 0;
      if (avail > qty) {
        e.preventDefault();
        alert('Available quantity cannot exceed total quantity.');
      }
    });
  </script>

</body>
</html>
