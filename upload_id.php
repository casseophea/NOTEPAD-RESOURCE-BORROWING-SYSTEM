<?php

require_once 'db_connect.php';

$message = '';
$error = '';

$from = $_GET['from'] ?? $_SESSION['register_source'] ?? 'register';
$_SESSION['register_source'] = $from;

// Handle File Upload POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_dir = "uploads/";
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $side = $_POST['side'] ?? '';
    $file_input_name = $side . '_file';
    
    if (($side === 'front' || $side === 'back') && isset($_FILES[$file_input_name])) {
        $file = $_FILES[$file_input_name];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                // Generate a unique filename to prevent overwriting
                $new_filename = $target_dir . "id_" . $side . "_" . time() . "_" . uniqid() . "." . $file_extension;
                
                if (move_uploaded_file($file['tmp_name'], $new_filename)) {
                    // Save file path in PHP session
                    $_SESSION['id_' . $side . '_path'] = $new_filename;
                    $message = ucfirst($side) . " ID photo uploaded successfully!";
                } else {
                    $error = "Failed to save the uploaded file on the server.";
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
            }
        } else {
            $error = "Error during file upload: " . $file['error'];
        }
    }
}

// Get return link
$return_link = ($from === 'admin_registration') ? 'admin_registration.php' : 'register.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Valid ID</title>
  <link rel="stylesheet" href="register.css?v=<?php echo time(); ?>">
  <style>
    .upload-box {
      width: 100%;
      height: 140px;
      background: #d9d9d9;
      border: 2px dashed #aaa;
      border-radius: 12px;
      margin: 5px 0 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      color: #555;
      font-size: 13px;
      position: relative;
      overflow: hidden;
      box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);
    }
    .upload-box img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }
    .upload-section {
      margin-bottom: 15px;
      background: #e8e8d8;
      padding: 10px;
      border-radius: 10px;
      border: 1px solid #c8c2b4;
    }
    .upload-section label {
      font-weight: bold;
      color: #333;
      margin-bottom: 5px;
      display: block;
    }
    .action-btn {
      display: inline-block;
      padding: 8px 20px;
      background: #30364F;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 12px;
      font-weight: bold;
      transition: background 0.2s;
    }
    .action-btn:hover {
      background: #000;
    }
    .back-btn-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      border-bottom: 2px solid #30364F;
      padding-bottom: 8px;
    }
    .back-arrow {
      font-size: 24px;
      color: #30364F;
      text-decoration: none;
      font-weight: bold;
      transition: transform 0.2s;
      display: flex;
      align-items: center;
    }
    .back-arrow:hover {
      transform: scale(1.1);
    }
    .alert {
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: bold;
      margin-bottom: 15px;
      text-align: center;
    }
    .alert-success {
      background: #006615;
      color: white;
    }
    .alert-error {
      background: #7a1a1a;
      color: white;
    }
    .confirm-btn {
      width: 100%;
      padding: 12px;
      background: #30364F;
      color: white;
      border: 2px solid #000;
      border-radius: 10px;
      cursor: pointer;
      font-size: 14px;
      font-weight: bold;
      text-align: center;
      transition: background 0.2s;
      margin-top: 10px;
    }
    .confirm-btn:hover {
      background: #000;
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
  <div class="right" style="overflow-y: auto;">
    
    <div class="back-btn-container">
      <a href="<?php echo htmlspecialchars($return_link); ?>" class="back-arrow">&#8617; <span style="font-size: 14px; margin-left: 5px;">Cancel</span></a>
      <h2 style="margin: 0; font-size: 18px; font-weight: bold;">Upload Valid ID photo</h2>
      <div></div>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- FRONT UPLOAD FORM -->
    <div class="upload-section">
      <form method="POST" action="upload_id.php?from=<?php echo htmlspecialchars($from); ?>" enctype="multipart/form-data">
        <input type="hidden" name="side" value="front">
        <input type="hidden" name="action" value="upload">
        
        <label>Front Side:</label>
        <div class="upload-box" onclick="document.getElementById('frontInput').click()">
          <?php if (isset($_SESSION['id_front_path']) && file_exists($_SESSION['id_front_path'])): ?>
            <img src="<?php echo htmlspecialchars($_SESSION['id_front_path']); ?>" alt="Front ID Photo">
          <?php else: ?>
            <span style="color: #666; text-align: center;">Click to select ID Front side<br><small>(JPG, PNG, GIF)</small></span>
          <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 5px;">
          <input type="file" name="front_file" id="frontInput" accept="image/*" style="display: none;" onchange="this.form.submit()">
          <button type="button" class="action-btn" onclick="document.getElementById('frontInput').click()">Select & Upload Front</button>
        </div>
      </form>
    </div>

    <!-- BACK UPLOAD FORM -->
    <div class="upload-section">
      <form method="POST" action="upload_id.php?from=<?php echo htmlspecialchars($from); ?>" enctype="multipart/form-data">
        <input type="hidden" name="side" value="back">
        <input type="hidden" name="action" value="upload">
        
        <label>Back Side:</label>
        <div class="upload-box" onclick="document.getElementById('backInput').click()">
          <?php if (isset($_SESSION['id_back_path']) && file_exists($_SESSION['id_back_path'])): ?>
            <img src="<?php echo htmlspecialchars($_SESSION['id_back_path']); ?>" alt="Back ID Photo">
          <?php else: ?>
            <span style="color: #666; text-align: center;">Click to select ID Back side<br><small>(JPG, PNG, GIF)</small></span>
          <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 5px;">
          <input type="file" name="back_file" id="backInput" accept="image/*" style="display: none;" onchange="this.form.submit()">
          <button type="button" class="action-btn" onclick="document.getElementById('backInput').click()">Select & Upload Back</button>
        </div>
      </form>
    </div>

    <!-- CONFIRM AND RETURN -->
    <button class="confirm-btn" onclick="window.location.href='<?php echo htmlspecialchars($return_link); ?>'">Confirm and Return</button>

  </div>
</div>

<footer>© 2026 Barangay Tiniguiban</footer>

</body>
</html>
