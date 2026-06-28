<?php

require_once 'db_connect.php';

// Verify User session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Fetch all inventory items from MySQL using PDO
$inv_res = $conn->query("SELECT * FROM inventory ORDER BY name ASC");
$items = $inv_res->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Inventory - Barangay Tiniguiban</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="user_inventory.css?v=<?php echo time(); ?>" />
</head>
<body>

  <?php include 'navigation.php'; ?>

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
          <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
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
            <?php endforeach; ?>
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
