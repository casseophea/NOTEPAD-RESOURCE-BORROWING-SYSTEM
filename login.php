<?php
// login.php - Secure PHP/MySQL Login for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Query user from MySQL database
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
            
            // Verify password using bcrypt hash
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect depending on role
                if ($user['role'] === 'admin') {
                    header('Location: admin_page.php');
                } else {
                    header('Location: user_page.php');
                }
                exit;
            } else {
                $error = 'Incorrect password. Please try again.';
            }
        } else {
            $error = 'Email not found. Please register an account first.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="login.css">
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
    <h2>LOGIN ACCOUNT</h2>
    <p class="subtitle">Continue with your account</p>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm">
      <div class="role">
        <button type="button" class="user active" id="userBtn" onclick="setRole('user')">USER</button>
        <button type="button" class="admin" id="adminBtn" onclick="setRole('admin')">ADMIN</button>
      </div>
      <input type="hidden" name="selected_role" id="selectedRole" value="user">

      <label>Email:</label>
      <input type="email" name="email" required placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

      <label>Password:</label>
      <div class="password">
        <input type="password" name="password" id="password" required placeholder="Enter your password">
        <span class="eye-icon" onclick="togglePassword()">&#128065;</span>
      </div>

      <a href="#" class="forgot">Forgot Password?</a>

      <p class="register">Don't have an account? <a href="register.php" id="registerLink">Create Now</a></p>

      <button type="submit" class="login-btn">LOGIN</button>
    </form>
  </div>

</div>

<footer>© 2026 Barangay Tiniguiban</footer>

<script>
  let currentRole = 'user';

  function setRole(role) {
    currentRole = role;
    document.getElementById('selectedRole').value = role;
    const userBtn = document.getElementById('userBtn');
    const adminBtn = document.getElementById('adminBtn');
    const registerLink = document.getElementById('registerLink');

    if (role === 'user') {
      userBtn.classList.add('active');
      adminBtn.classList.remove('active');
      registerLink.href = 'register.php';
    } else {
      adminBtn.classList.add('active');
      userBtn.classList.remove('active');
      registerLink.href = 'admin_registration.php';
    }
  }

  function togglePassword() {
    const pwdInput = document.getElementById('password');
    const eyeIcon = document.querySelector('.eye-icon');
    if (pwdInput.type === 'password') {
      pwdInput.type = 'text';
      eyeIcon.style.opacity = '1';
    } else {
      pwdInput.type = 'password';
      eyeIcon.style.opacity = '0.5';
    }
  }
</script>

</body>
</html>
