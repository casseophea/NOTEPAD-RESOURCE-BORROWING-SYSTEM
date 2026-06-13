<?php
// register.php - Secure User Signup for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

$error = '';
$id_uploaded = (isset($_SESSION['id_front_path']) && isset($_SESSION['id_back_path']));
$upload_text = $id_uploaded ? '✓ ID Uploaded' : 'Upload';
$upload_style = $id_uploaded ? 'background: #006615; color: white; border-color: #004d10;' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $contact_number = trim($_POST['contact_number'] ?? '');
    $valid_id_type = $_POST['id_type'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($contact_number) || empty($valid_id_type)) {
        $error = 'Please fill in all required fields marked with an asterisk (*).';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!$id_uploaded) {
        $error = 'Please upload your Valid ID front and back photos before registering.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res && $res->num_rows > 0) {
            $error = 'Email is already registered. Please login instead.';
        } else {
            // Hash password and insert user into DB
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $role = 'user';
            $id_front = $_SESSION['id_front_path'] ?? null;
            $id_back = $_SESSION['id_back_path'] ?? null;
            
            $insert = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, contact_number, valid_id_type, role, id_front_path, id_back_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->bind_param("sssssssss", $first_name, $last_name, $email, $password_hash, $contact_number, $valid_id_type, $role, $id_front, $id_back);
            
            if ($insert->execute()) {
                // Set session immediately on register
                $_SESSION['user_id'] = $insert->insert_id;
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                
                // Clear photo session paths
                unset($_SESSION['id_front_path']);
                unset($_SESSION['id_back_path']);
                unset($_SESSION['register_source']);
                
                header('Location: user_page.php');
                exit;
            } else {
                $error = 'Failed to register account. Please try again.';
            }
            $insert->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <link rel="stylesheet" href="register.css">
  <style>
    .error-msg {
      background: #7a1a1a;
      color: white;
      padding: 8px 12px;
      border-radius: 8px;
      font-size: 11px;
      margin-bottom: 15px;
      text-align: center;
      font-weight: bold;
    }
  </style>
</head>
<body>
<div class="container">
  <!-- LEFT PANEL -->
  <div class="left">
    <img src="logo.png" class="logo" alt="logo" style="width: 150px; height: 150px; border-radius: 50%;">
    <h2>BARANGAY TINIGUIBAN RESOURCE<br> BORROWING SYSTEM</h2>
    <hr style="border: 2px solid #F0F0DB; width: 110%; margin-left: -5%;">
  </div>
  
  <!-- RIGHT PANEL -->
  <div class="right">
    <h2>CREATE ACCOUNT</h2>
    <p class="subtitle">Create new account to continue with our service.</p>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" id="registerForm">
      <!-- FIRST NAME & LAST NAME -->
      <div class="name-row">
        <div class="name-field">
          <label>First Name:<span class="required">*</span></label>
          <input type="text" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
        </div>
        <div class="name-field">
          <label>Last name:<span class="required">*</span></label>
          <input type="text" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
        </div>
      </div>

      <label>Email:<span class="required">*</span></label>
      <input type="email" name="email" class="full-input" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

      <label>Password:<span class="required">*</span></label>
      <div class="password">
        <input type="password" name="password" id="password" required>
        <span class="eye-icon" onclick="togglePassword('password', this)">&#128065;</span>
      </div>

      <label>Confirm Password:<span class="required">*</span></label>
      <div class="password">
        <input type="password" name="confirm_password" id="confirmPassword" required>
        <span class="eye-icon" onclick="togglePassword('confirmPassword', this)">&#128065;</span>
      </div>

      <label>Contact Number:<span class="required">*</span></label>
      <input type="tel" name="contact_number" class="full-input" required value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>">

      <div class="valid-id-row">
        <label>Valid ID <span class="required">*</span></label>
        <select class="id-type" name="id_type" required>
          <option value="" disabled selected>Choose ID type</option>
          <option value="PhilSys ID" <?php echo (($_POST['id_type'] ?? '') === 'PhilSys ID') ? 'selected' : ''; ?>>PhilSys ID</option>
          <option value="Driver's License" <?php echo (($_POST['id_type'] ?? '') === "Driver's License") ? 'selected' : ''; ?>>Driver's License</option>
          <option value="Passport" <?php echo (($_POST['id_type'] ?? '') === 'Passport') ? 'selected' : ''; ?>>Passport</option>
          <option value="Voter's ID" <?php echo (($_POST['id_type'] ?? '') === "Voter's ID") ? 'selected' : ''; ?>>Voter's ID</option>
          <option value="SSS ID" <?php echo (($_POST['id_type'] ?? '') === 'SSS ID') ? 'selected' : ''; ?>>SSS ID</option>
          <option value="Pag-IBIG ID" <?php echo (($_POST['id_type'] ?? '') === 'Pag-IBIG ID') ? 'selected' : ''; ?>>Pag-IBIG ID</option>
          <option value="PhilHealth ID" <?php echo (($_POST['id_type'] ?? '') === 'PhilHealth ID') ? 'selected' : ''; ?>>PhilHealth ID</option>
          <option value="Postal ID" <?php echo (($_POST['id_type'] ?? '') === 'Postal ID') ? 'selected' : ''; ?>>Postal ID</option>
        </select>
      </div>

      <div class="upload-row">
        <button type="button" class="upload-btn" style="<?php echo $upload_style; ?>" onclick="saveAndUpload()"><?php echo $upload_text; ?></button>
        <p class="login-link">Already have an account? <a href="login.php">Login Now</a></p>
      </div>

      <p class="terms-link">
        By registering, you agree to our
       <a href="terms_conditions.php">Terms and Conditions</a>
      </p>

      <button type="submit" class="button-link">REGISTER</button>
    </form>
  </div>
</div>
<footer>© 2026 Barangay Tiniguiban</footer>

<script>
  // Auto-save form inputs to sessionStorage in real-time
  function autoSaveForm() {
    try {
      const fields = {
        first_name: document.querySelector('input[name="first_name"]').value,
        last_name: document.querySelector('input[name="last_name"]').value,
        email: document.querySelector('input[name="email"]').value,
        password: document.querySelector('input[name="password"]').value,
        confirm_password: document.querySelector('input[name="confirm_password"]').value,
        contact_number: document.querySelector('input[name="contact_number"]').value,
        id_type: document.querySelector('select[name="id_type"]').value
      };
      sessionStorage.setItem('register_form_state', JSON.stringify(fields));
    } catch (e) {
      console.error("Error saving form state:", e);
    }
  }

  window.addEventListener('DOMContentLoaded', () => {
    // Restore inputs from sessionStorage
    try {
      const saved = sessionStorage.getItem('register_form_state');
      if (saved) {
        const fields = JSON.parse(saved);
        if (fields.first_name !== undefined) document.querySelector('input[name="first_name"]').value = fields.first_name;
        if (fields.last_name !== undefined) document.querySelector('input[name="last_name"]').value = fields.last_name;
        if (fields.email !== undefined) document.querySelector('input[name="email"]').value = fields.email;
        if (fields.password !== undefined) document.querySelector('input[name="password"]').value = fields.password;
        if (fields.confirm_password !== undefined) document.querySelector('input[name="confirm_password"]').value = fields.confirm_password;
        if (fields.contact_number !== undefined) document.querySelector('input[name="contact_number"]').value = fields.contact_number;
        if (fields.id_type !== undefined) document.querySelector('select[name="id_type"]').value = fields.id_type;
      }
    } catch (e) {
      console.error("Error restoring form state:", e);
    }

    // Attach listeners to all inputs to auto-save as user types
    document.querySelectorAll('input, select').forEach(el => {
      el.addEventListener('input', autoSaveForm);
      el.addEventListener('change', autoSaveForm);
    });
  });

  document.getElementById('registerForm').addEventListener('submit', () => {
    // Clear state on successful submission
    sessionStorage.removeItem('register_form_state');
  });

  function togglePassword(fieldId, icon) {
    const input = document.getElementById(fieldId);
    if (input.type === 'password') {
      input.type = 'text';
      icon.style.opacity = '1';
    } else {
      input.type = 'password';
      icon.style.opacity = '0.5';
    }
  }

  function saveAndUpload() {
    autoSaveForm();
    window.location.href = 'upload_id.php?from=register';
  }
</script>
</body>
</html>
