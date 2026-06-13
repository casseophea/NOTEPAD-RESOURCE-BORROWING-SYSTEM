<?php
// user_inventory.php - User Inventory View for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

// Verify User session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Fetch all inventory items from MySQL
$inv_res = $conn->query("SELECT * FROM inventory ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Inventory - Barangay Tiniguiban</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="user_inventory.css" />
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
        <a href="user_page.php">Home</a>
        <a href="user_inventory.php" class="active">Inventory</a>
        <a href="request_page.php">Request</a>
        <a href="my_request_page.php">My Request</a>
      </nav>

      <div class="header-right">
        <span class="welcome-text">Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?>!</strong></span>
        <a href="profile.php" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; margin-right: 5px;">
          <div class="avatar-circle">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
          </div>
          <span class="profile-label" style="display: block; text-align: center; font-size: 0.72rem; margin-top: 4px; font-weight: 600; color: var(--text-muted);">Profile</span>
        </a>
        <button class="btn-logout" onclick="window.location.href='logout.php'">Logout</button>
      </div>

    </div>
  </header>

  <!-- MAIN -->
  <main>

    <!-- Search Row -->
    <div class="search-row">
      <span class="search-label">Search:</span>
      <div class="search-input-wrap">
        <input type="text" id="searchInput" oninput="filterInventory()" placeholder="Search by name or category..." />
        <svg class="search-icon" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="7" />
          <line x1="16.5" y1="16.5" x2="22" y2="22" />
        </svg>
      </div>
      <select class="filter-select" id="filterSelect" onchange="filterInventory()">
        <option value="All">All Status</option>
        <option value="Available">Available</option>
        <option value="Limited">Limited</option>
        <option value="Not Available">Not Available</option>
      </select>
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
          </tr>
        </thead>
        <tbody id="inventoryTableBody">
          <?php if ($inv_res && $inv_res->num_rows > 0): ?>
            <?php while ($item = $inv_res->fetch_assoc()): ?>
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
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align: center; color: #666;">No items found in inventory.</td>
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

  <script>
    function filterInventory() {
      const searchVal = document.getElementById('searchInput').value.toLowerCase();
      const filterVal = document.getElementById('filterSelect').value;
      
      const rows = document.querySelectorAll('#inventoryTableBody tr');
      rows.forEach(row => {
        if (row.cells.length < 5) return;
        
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
  </script>

</body>
</html>
