<?php
// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
?>
<!-- Navigation responsive hamburger styling & behavior -->
<style>
    /* Header */
    header {
      width: 95%;
      max-width: 1300px;
      margin: 25px auto;
      padding: 10px 30px;
      background: #f5f0e8;
      border-radius: 20px;
      border-bottom: 2px solid #ede6d6;
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.16);
    }

    .header-inner {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }

    /* Logo */
    .logo-wrap {
      display: flex;
      align-items: center;
      gap: 0.85rem;
    }

    .logo-circle {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      overflow: hidden;
      background: #2c3e55;
      flex-shrink: 0;
    }

    .logo-circle img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .brand-text h1 {
      font-family: "Playfair Display", serif;
      font-size: 1.05rem;
      font-weight: 700;
      color: #1a2535;
    }

    .brand-text p {
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.12em;
      color: #5a6a76;
      text-transform: uppercase;
    }

    /* Navigation */
    header nav {
      display: flex;
      align-items: center;
      gap: 0.25rem;
      margin-left: 1.5rem;
    }

    header nav a {
      text-decoration: none;
      color: #5a6a76;
      font-size: 0.88rem;
      font-weight: 500;
      padding: 0.35rem 0.75rem;
      border-radius: 10px;
      transition: 0.2s;
      position: relative;
    }

    header nav a:hover {
      background: #ede6d6;
      color: #1a2535;
    }

    header nav a.active {
      color: #1a2535;
      font-weight: 700;
    }

    header nav a.active::after {
      content: "";
      position: absolute;
      left: 0.75rem;
      right: 0.75rem;
      bottom: -2px;
      height: 2px;
      background: #1a2535;
    }

    /* User Section */
    .header-right {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 0.85rem;
    }

    .welcome-text {
      font-size: 0.85rem;
      color: #5a6a76;
    }

    .welcome-text strong {
      color: #1a2535;
    }

    .profile-wrap {
      text-decoration: none;
      color: inherit;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .avatar-btn {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: #2c3e55;
      border: 2px solid #1a2535;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: 0.2s;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }

    .avatar-btn:hover {
      transform: scale(1.05);
    }

    .avatar-btn svg {
      width: 22px;
      height: 22px;
      fill: #f5f0e8;
    }

    .profile-label {
      display: block;
      margin-top: 4px;
      font-size: 0.72rem;
      font-weight: 600;
      color: #5a6a76;
    }

    .btn-logout {
      border: none;
      cursor: pointer;
      background: #7a1a1a;
      color: white;
      padding: 0.42rem 1.1rem;
      border-radius: 20px;
      text-decoration: none;
      font-size: 0.82rem;
      font-weight: 600;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
      transition: 0.2s;
      display: inline-block;
    }

    .btn-logout:hover {
      background: #9e2020;
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
        .logo-circle {
            width: 40px !important;
            height: 40px !important;
        }
        .brand-text h1 {
            font-size: 1.05rem !important;
        }
        .brand-text p {
            font-size: 0.65rem !important;
        }
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
            margin-left: 0 !important;
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
      <?php if ($role === 'admin'): ?>
        <a href="admin_page.php" class="<?php echo ($current_page === 'admin_page.php') ? 'active' : ''; ?>">Home</a>
        <a href="admin_inventory.php" class="<?php echo ($current_page === 'admin_inventory.php') ? 'active' : ''; ?>">Inventory</a>
        <a href="manage_request.php" class="<?php echo ($current_page === 'manage_request.php') ? 'active' : ''; ?>">Manage Request</a>
        <a href="manage_users.php" class="<?php echo ($current_page === 'manage_users.php') ? 'active' : ''; ?>">Manage Users</a>
      <?php else: ?>
        <a href="user_page.php" class="<?php echo ($current_page === 'user_page.php') ? 'active' : ''; ?>">Home</a>
        <a href="user_inventory.php" class="<?php echo ($current_page === 'user_inventory.php') ? 'active' : ''; ?>">Inventory</a>
        <a href="request_page.php" class="<?php echo ($current_page === 'request_page.php') ? 'active' : ''; ?>">Request</a>
        <a href="my_request_page.php" class="<?php echo ($current_page === 'my_request_page.php') ? 'active' : ''; ?>">My Request</a>
      <?php endif; ?>
    </nav>

    <!-- RIGHT SIDE -->
    <div class="header-right">
      <span class="welcome-text">
        Welcome, <strong><?php echo htmlspecialchars($user_name); ?>!</strong>
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
