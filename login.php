<?php
// login.php - Secure PHP/MySQL Login for Barangay Tiniguiban Resource Borrowing System
require_once 'db_connect.php';

$error = '';
$message = '';

if (isset($_GET['timeout'])) {
    $error = 'You have been logged out due to 15 minutes of inactivity.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($login_input) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        // Query user from MySQL database using PDO
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, username, password, role, barangay_role FROM users WHERE email = :login OR username = :login LIMIT 1");
        $stmt->execute(['login' => $login_input]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Verify password using bcrypt hash
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'] . ($user['barangay_role'] ? ' (' . $user['barangay_role'] . ')' : '');
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                
                // Track session in database and browser cookies
                $session_id = session_id();
                
                if ($remember_me) {
                    // Generate secure token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days
                    
                    $sess_stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, token, expires_at) VALUES (:uid, :sid, :token, :expires)");
                    $sess_stmt->execute([
                        'uid' => $user['id'],
                        'sid' => $session_id,
                        'token' => $token,
                        'expires' => $expires
                    ]);
                    
                    // Set cookie in browser
                    setcookie('remember_token', $token, time() + (86400 * 30), '/');
                } else {
                    // Regular session logging
                    $sess_stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id) VALUES (:uid, :sid)");
                    $sess_stmt->execute([
                        'uid' => $user['id'],
                        'sid' => $session_id
                    ]);
                }
                
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
            $error = 'Account not found. Please register first.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="login.css?v=<?php echo time(); ?>">
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
    .info-msg {
      background: #2a6e2a;
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
    <?php if (!empty($message)): ?>
        <div class="info-msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm">
      <label>Username or Email:</label>
      <input type="text" name="login_input" required placeholder="Enter your username or email" value="<?php echo htmlspecialchars($_POST['login_input'] ?? ''); ?>">

      <label>Password:</label>
      <div class="password">
        <input type="password" name="password" id="password" required placeholder="Enter your password">
        <span class="eye-icon" onclick="togglePassword()">&#128065;</span>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <label style="display: flex; align-items: center; font-size: 13px; font-weight: normal; cursor: pointer; color: #333;">
          <input type="checkbox" name="remember_me" style="margin-right: 5px; width: auto; height: auto;"> Remember Me
        </label>
        <a href="#" class="forgot" style="margin-bottom: 0;">Forgot Password?</a>
      </div>

      <p class="register">
        Don't have an account?<br>
        Register as <a href="register.php" style="font-weight: bold; text-decoration: none; color: #30364F;">Citizen</a> or <a href="admin_registration.php" style="font-weight: bold; text-decoration: none; color: #30364F;">Barangay Official</a>
      </p>

      <button type="submit" class="login-btn">LOGIN</button>
    </form>
  </div>

</div>

<!-- Public About Our System & Services -->
<section class="about-services-section">
    <h2>About Our System & Services</h2>
    <p class="about-desc">
        The <strong>Barangay Tiniguiban Resource Borrowing System</strong> is an automated platform created to streamline the scheduling, booking, and management of public assets. It ensures fair access and transparency, enabling residents to secure municipal resources for community and family events under the provisions of the local government codes.
    </p>
    <div class="services-grid">
        <div class="service-card">
            <h3>Available Resources</h3>
            <ul>
                <li><strong>Event Furniture</strong>: Durable plastic chairs and foldable tables for private gatherings, assemblies, or wakes.</li>
                <li><strong>Audio/Video Gear</strong>: Professional sound systems, wireless microphones, projectors, and projection screens.</li>
                <li><strong>Office & Event Accs</strong>: Presentation boards, high-capacity extension cords, and accessory kits.</li>
            </ul>
        </div>
        <div class="service-card">
            <h3>Borrowing Guidelines</h3>
            <ul>
                <li><strong>Live Inventory Checking</strong>: Always verify item counts in the live inventory database before booking.</li>
                <li><strong>Adhere to Timelines</strong>: Items must be returned strictly by the exact return date and time to allow other residents to borrow them.</li>
                <li><strong>Commodatum Rules</strong>: Under the Civil Code of the Philippines, borrowers must exercise high care and diligence over public assets.</li>
            </ul>
        </div>
    </div>
</section>

<footer>© 2026 Barangay Tiniguiban</footer>

<script>
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
