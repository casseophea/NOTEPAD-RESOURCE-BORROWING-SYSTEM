<?php
// manage_users.php - Admin User Management board for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

// Verify Admin session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Process DB Mutations (Edit / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $barangay_role = trim($_POST['barangay_role'] ?? '');
        
        if ($id > 0 && !empty($first_name) && !empty($last_name) && !empty($username) && !empty($email)) {
            try {
                // Check duplicate email
                $chk_email = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :id");
                $chk_email->execute(['email' => $email, 'id' => $id]);
                
                // Check duplicate username
                $chk_un = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :un AND id != :id");
                $chk_un->execute(['un' => $username, 'id' => $id]);
                
                if ($chk_email->fetchColumn() > 0) {
                    $error = 'Email is already registered by another user.';
                } elseif ($chk_un->fetchColumn() > 0) {
                    $error = 'Username is already taken by another user.';
                } else {
                    $stmt = $conn->prepare("UPDATE users SET first_name = :fn, last_name = :ln, username = :un, email = :email, contact_number = :contact, role = :role, barangay_role = :brgy_role WHERE id = :id");
                    $stmt->execute([
                        'fn' => $first_name,
                        'ln' => $last_name,
                        'un' => $username,
                        'email' => $email,
                        'contact' => $contact_number,
                        'role' => $role,
                        'brgy_role' => ($role === 'admin') ? $barangay_role : null,
                        'id' => $id
                    ]);
                    $message = 'User account updated successfully!';
                }
            } catch (Exception $e) {
                $error = 'Failed to update user: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
    
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        // Prevent deleting oneself
        if ($id === intval($_SESSION['user_id'])) {
            $error = 'You cannot delete your own logged-in administrator account!';
        } elseif ($id > 0) {
            try {
                $conn->beginTransaction();
                
                // Delete user (associated sessions and requests delete cascadingly in DB schema)
                $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute(['id' => $id]);
                
                $conn->commit();
                $message = 'User account deleted successfully!';
            } catch (Exception $e) {
                $conn->rollBack();
                $error = 'Failed to delete user: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all users using PDO
$users_res = $conn->query("SELECT * FROM users ORDER BY role ASC, last_name ASC");
$users_list = $users_res->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Barangay Tiniguiban</title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="user_inventory.css?v=<?php echo time(); ?>">
    
    <style>
        /* Table / Page specific styles */
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
            transition: background 0.2s;
        }
        .btn-add:hover {
            background: black;
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
        .btn-view-id {
                background: #17a2b8;
                border-color: #138496;
            }
        .btn-delete {
            background: #7a1a1a;
        }
        .alert {
            width: 95%;
            max-width: 1300px;
            margin: 15px auto;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            font-size: 13px;
        }
        .alert-success {
            background: #2a6e2a;
            color: white;
        }
        .alert-error {
            background: #7a1a1a;
            color: white;
        }
        
        /* Modal Setup */
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
            width: 90%;
            max-width: 450px;
            box-sizing: border-box;
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
        } /* End of @media (max-width: 768px) */

        .btn-view-id {
            background: #17a2b8;
            border-color: #138496;
        }
        .id-images-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 15px;
        }
        .id-img-box {
            flex: 1;
            text-align: center;
        }
        .id-img-box h4 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #5a6a76;
        }
        .id-img-wrap {
            border: 2px solid #c8c2b4;
            border-radius: 12px;
            overflow: hidden;
            background: #eee;
            height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            box-sizing: border-box;
        }
        .id-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 6px;
        }
        @media (max-width: 600px) {
            .id-img-wrap {
                height: 180px !important;
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
      <a href="admin_page.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'admin_page.php') ? 'active' : ''; ?>">Home</a>
      <a href="admin_inventory.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'admin_inventory.php') ? 'active' : ''; ?>">Inventory</a>
      <a href="manage_request.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'manage_request.php') ? 'active' : ''; ?>">Manage Request</a>
      <a href="manage_users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'manage_users.php') ? 'active' : ''; ?>">Manage Users</a>
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

  <?php if (!empty($message)): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <!-- MAIN -->
  <main>

    <!-- Search Row -->
    <div class="search-row" style="position: relative;">
      <span class="search-label">Search Users:</span>
      <div class="search-input-wrap">
        <input type="text" id="searchInput" oninput="filterUsers()" placeholder="Search by name, username, email..." />
        <svg class="search-icon" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="7" />
          <line x1="16.5" y1="16.5" x2="22" y2="22" />
        </svg>
      </div>
      
      <select class="filter-select" id="filterRole" onchange="filterUsers()">
        <option value="All">All Roles</option>
        <option value="user">User</option>
        <option value="admin">Admin</option>
      </select>
    </div>

    <!-- Users Table -->
    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Role</th>
            <th>Barangay Position</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="usersTableBody">
          <?php if (!empty($users_list)): ?>
            <?php foreach ($users_list as $usr): ?>
              <tr data-role="<?php echo htmlspecialchars($usr['role']); ?>">
                <td><?php echo htmlspecialchars($usr['first_name'] . ' ' . $usr['last_name']); ?></td>
                <td><?php echo htmlspecialchars($usr['username']); ?></td>
                <td><?php echo htmlspecialchars($usr['email']); ?></td>
                <td><?php echo htmlspecialchars($usr['contact_number'] ?: 'N/A'); ?></td>
                <td>
                    <span style="font-weight: bold; text-transform: uppercase; color: <?php echo ($usr['role'] === 'admin') ? '#7a1a1a' : '#1a2535'; ?>">
                        <?php echo htmlspecialchars($usr['role']); ?>
                    </span>
                </td>
                <td><?php echo htmlspecialchars($usr['barangay_role'] ?: 'Citizen'); ?></td>
                <td>
                  <div class="action-btn-group">
                    <button class="btn-table-action btn-edit" onclick="openEditModal(<?php echo $usr['id']; ?>)">Edit</button>
                    <?php if (!empty($usr['id_front_path']) || !empty($usr['id_back_path'])): ?>
                        <button class="btn-table-action btn-view-id" onclick="viewID('<?php echo htmlspecialchars($usr['id_front_path'] ?: ''); ?>', '<?php echo htmlspecialchars($usr['id_back_path'] ?: ''); ?>', '<?php echo addslashes($usr['first_name'] . ' ' . $usr['last_name']); ?>', '<?php echo addslashes($usr['valid_id_type'] ?: 'N/A'); ?>')">View ID</button>
                    <?php endif; ?>
                    <?php if ($usr['id'] !== intval($_SESSION['user_id'])): ?>
                        <button class="btn-table-action btn-delete" onclick="deleteUser(<?php echo $usr['id']; ?>, '<?php echo addslashes($usr['first_name'] . ' ' . $usr['last_name']); ?>')">Delete</button>
                    <?php else: ?>
                        <button class="btn-table-action" style="background:#ccc; cursor:not-allowed; border-color:#aaa;" disabled>Self</button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; color: #666;">No users registered yet.</td>
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

  <!-- EDIT USER MODAL -->
  <div class="modal" id="userModal">
    <div class="modal-content">
      <h3 id="modalTitle">Edit User Details</h3>
      
      <form method="POST" action="manage_users.php" id="modalForm">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="userId" value="">
        
        <div class="modal-group">
          <label>First Name:</label>
          <input type="text" name="first_name" id="userFirstName" required>
        </div>
        
        <div class="modal-group">
          <label>Last Name:</label>
          <input type="text" name="last_name" id="userLastName" required>
        </div>

        <div class="modal-group">
          <label>Username:</label>
          <input type="text" name="username" id="userUsername" required>
        </div>

        <div class="modal-group">
          <label>Email Address:</label>
          <input type="email" name="email" id="userEmail" required>
        </div>

        <div class="modal-group">
          <label>Contact Number:</label>
          <input type="tel" name="contact_number" id="userContact" required>
        </div>
        
        <div class="modal-group">
          <label>System Access Role:</label>
          <select name="role" id="userRole" onchange="toggleBrgyRoleField()">
            <option value="user">User (Citizen)</option>
            <option value="admin">Admin (Official)</option>
          </select>
        </div>

        <div class="modal-group" id="brgyRoleGroup">
          <label>Barangay Position (Admin only):</label>
          <select name="barangay_role" id="userBrgyRole">
            <option value="Barangay Captain">Barangay Captain</option>
            <option value="Barangay Councilor">Barangay Councilor</option>
            <option value="Barangay Tanod">Barangay Tanod</option>
            <option value="Staff">Staff</option>
          </select>
        </div>
        
        <div class="modal-actions">
          <button type="button" class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn-modal-save">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- VIEW ID MODAL -->
  <div class="modal" id="idModal">
    <div class="modal-content" style="width: 700px; max-width: 95%;">
      <h3 style="margin-top: 0; color: #1a2535;">Citizen Identification Verification</h3>
      <p style="font-size: 14px; margin-bottom: 20px; color: #333; line-height: 1.5;">
        Citizen Name: <strong id="idModalName">...</strong><br>
        Submitted ID Type: <strong id="idModalType">...</strong>
      </p>
      
      <div class="id-images-container">
        <div class="id-img-box">
          <h4>FRONT OF ID</h4>
          <div class="id-img-wrap">
            <img id="idFrontImg" src="" alt="Front ID Image">
          </div>
        </div>
        <div class="id-img-box" id="idBackBox">
          <h4>BACK OF ID</h4>
          <div class="id-img-wrap">
            <img id="idBackImg" src="" alt="Back ID Image">
          </div>
        </div>
      </div>
      
      <div class="modal-actions" style="margin-top: 25px;">
        <button type="button" class="btn-modal-cancel" onclick="closeIdModal()" style="padding: 10px 25px;">Close ID Viewer</button>
      </div>
    </div>
  </div>

  <script>
    // Embed user list as JSON for client side editing lookup
    const usersData = <?php echo json_encode($users_list); ?>;

    function filterUsers() {
      const searchVal = document.getElementById('searchInput').value.toLowerCase();
      const roleVal = document.getElementById('filterRole').value;
      
      const rows = document.querySelectorAll('#usersTableBody tr');
      rows.forEach(row => {
        if (row.cells.length < 7) return;
        
        const name = row.cells[0].textContent.toLowerCase();
        const username = row.cells[1].textContent.toLowerCase();
        const email = row.cells[2].textContent.toLowerCase();
        const contact = row.cells[3].textContent.toLowerCase();
        const role = row.dataset.role;
        
        const matchesSearch = name.includes(searchVal) || username.includes(searchVal) || email.includes(searchVal) || contact.includes(searchVal);
        const matchesRole = roleVal === 'All' || role === roleVal;
        
        if (matchesSearch && matchesRole) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    function toggleBrgyRoleField() {
      const role = document.getElementById('userRole').value;
      const grp = document.getElementById('brgyRoleGroup');
      if (role === 'admin') {
        grp.style.display = 'flex';
      } else {
        grp.style.display = 'none';
      }
    }

    function openEditModal(id) {
      const usr = usersData.find(u => u.id == id);
      if (usr) {
        document.getElementById('userId').value = usr.id;
        document.getElementById('userFirstName').value = usr.first_name;
        document.getElementById('userLastName').value = usr.last_name;
        document.getElementById('userUsername').value = usr.username;
        document.getElementById('userEmail').value = usr.email;
        document.getElementById('userContact').value = usr.contact_number || '';
        document.getElementById('userRole').value = usr.role;
        
        document.getElementById('userBrgyRole').value = usr.barangay_role || 'Staff';
        
        toggleBrgyRoleField();
        document.getElementById('userModal').style.display = 'flex';
      }
    }

    function closeModal() {
      document.getElementById('userModal').style.display = 'none';
    }

    function viewID(frontPath, backPath, userName, idType) {
      document.getElementById('idModalName').innerText = userName;
      document.getElementById('idModalType').innerText = idType;
      
      const frontImg = document.getElementById('idFrontImg');
      const backImg = document.getElementById('idBackImg');
      const backBox = document.getElementById('idBackBox');
      
      frontImg.src = frontPath ? frontPath : '';
      frontImg.alt = frontPath ? 'Front of ID' : 'Front of ID not uploaded';
      
      if (backPath && backPath.trim() !== '') {
        backImg.src = backPath;
        backImg.alt = 'Back of ID';
        backBox.style.display = 'block';
      } else {
        backImg.src = '';
        backImg.alt = '';
        backBox.style.display = 'none';
      }
      
      document.getElementById('idModal').style.display = 'flex';
    }

    function closeIdModal() {
      document.getElementById('idModal').style.display = 'none';
    }

    function deleteUser(id, fullname) {
      if (confirm(`ARE YOU ABSOLUTELY SURE you want to delete the user "${fullname}"?\n\nDeleting this user will permanently remove all their associated borrow requests, upload references, and database sessions. This operation CANNOT be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'manage_users.php';
        
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
  </script>

</body>
</html>
