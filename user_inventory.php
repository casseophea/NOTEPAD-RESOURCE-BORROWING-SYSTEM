<?php
// user_inventory.php - User Inventory View for Barangay Tiniguiban Resource Borrowing System
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
  <style>
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
