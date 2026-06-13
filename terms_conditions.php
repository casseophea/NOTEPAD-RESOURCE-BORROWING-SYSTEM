<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Terms and Conditions | Barangay Tiniguiban</title>
  <link rel="stylesheet" href="terms_conditions.css">
</head>
<body>

<div class="container">
  <!-- LEFT PANEL -->
  <div class="left">
    <img src="logo.png" class="logo" alt="logo" style="width: 150px; height: 150px; border-radius: 50%;">
    <h2>BARANGAY TINIGUIBAN RESOURCE<br> BORROWING SYSTEM</h2>
    <hr style="border: 1px solid #F0F0DB; width: 100%; margin-top: 20px;">
    <p class="tagline">Providing efficient resource sharing for our barangay community.</p>
  </div>

  <!-- RIGHT PANEL -->
  <div class="right">
    <h2>Terms & Conditions</h2>
    <p class="subtitle">Please read and accept the agreement to continue.</p>

    <!-- SCROLLABLE TERMS BOX -->
    <div class="content" style="max-height: 320px; overflow-y: auto; border: 1px solid #aaa; padding: 15px; border-radius: 10px; background: #e8e8d8; margin-bottom: 15px; font-size: 13px; text-align: justify; line-height: 1.5;">
      <p style="margin-top: 0;"><strong>Welcome to the Barangay Tiniguiban Resource Borrowing System.</strong> By using this system, you agree to comply with the following terms and conditions in accordance with applicable laws and regulations of the Republic of the Philippines.</p>
      
      <h3 style="font-size: 14px; margin-top: 15px; color: #30364F;">1. Eligibility & Asset Management (R.A. 7160)</h3>
      <p>Only verified residents, barangay officials, and authorized users of Barangay Tiniguiban are allowed to borrow resources. In accordance with <strong>Republic Act No. 7160 (Local Government Code of 1991)</strong>, all municipal and barangay equipment is public property and must be managed and used solely for public service, community-related, educational, health, or officially approved activities.</p>

      <h3 style="font-size: 14px; margin-top: 15px; color: #30364F;">2. Proper Use & Prohibitions (R.A. 3019)</h3>
      <p>Borrowed resources must only be used for the declared and officially sanctioned purpose. Pursuant to <strong>Republic Act No. 3019 (Anti-Graft and Corrupt Practices Act)</strong>, the commercial exploitation, subleasing, or use of public barangay equipment for private commercial gain, personal enrichment, or unauthorized political activities is strictly prohibited and punishable by law.</p>

      <h3 style="font-size: 14px; margin-top: 15px; color: #30364F;">3. Legal Nature of Borrowing & Care (R.A. 386 / Commodatum)</h3>
      <p>The borrowing of resources through this system constitutes a contract of <strong>Commodatum</strong> under <strong>Articles 1933 to 1952 of the Civil Code of the Philippines (Republic Act No. 386)</strong>. Under this legal framework, the following rules apply:</p>
      <ul style="padding-left: 20px; margin: 5px 0;">
        <li>The borrower (bailee) acquires only the temporary use of the borrowed equipment and is obliged to return the exact same item. Ownership remains with Barangay Tiniguiban.</li>
        <li>The borrower is required to take care of the borrowed resources with the diligence of a good father of a family (Article 1163).</li>
        <li>The borrower is liable for ordinary expenses required for the use and preservation of the borrowed property (Article 1941).</li>
        <li>Pursuant to Article 1942, the borrower shall be liable for any loss or damage to the resources, even if it should be through a fortuitous event (calamity, accident), if:
          <ul style="padding-left: 20px; margin: 3px 0; list-style-type: circle;">
            <li>The borrower keeps the equipment longer than the period agreed upon;</li>
            <li>The borrower uses the equipment for a purpose other than that for which it was lent;</li>
            <li>The equipment was delivered under appraisal of its value;</li>
            <li>The borrower lends the equipment to a third person who is not authorized.</li>
          </ul>
        </li>
      </ul>

      <h3 style="font-size: 14px; margin-top: 15px; color: #30364F;">4. Privacy & Data Protection (R.A. 10173)</h3>
      <p>Personal information and valid IDs collected through this system shall be processed in strict compliance with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>. Your data will only be utilized for transaction monitoring, security tracking, and administrative monitoring.</p>

      <h3 style="font-size: 14px; margin-top: 15px; color: #30364F;">5. Violations & Liability</h3>
      <p>Barangay Tiniguiban reserves the right to deny, suspend, or permanently terminate borrowing privileges for any user found violating these terms. The barangay administration may prosecute individuals under civil and criminal statutes for deliberate damage, theft, or graft involving public resources.</p>

      <h3 style="font-size: 14px; margin-top: 15px; color: #30364F;">6. Amendments</h3>
      <p style="margin-bottom: 0;">The barangay administration may update or revise these terms and conditions at any time to align with new local ordinances or national laws. Continued use of the system signifies acceptance of any updated policies.</p>
    </div>

    <!-- AGREEMENT CHECKBOX -->
    <div class="checkbox" style="display: flex; align-items: flex-start; gap: 8px; font-size: 12px; line-height: 1.4; color: #333; margin-bottom: 15px;">
      <input type="checkbox" id="agree" style="margin-top: 2px; cursor: pointer;">
      <label for="agree" style="display: inline; cursor: pointer;">I have read and understood the Terms and Conditions of the Barangay Tiniguiban Resource Borrowing System in compliance with Philippine law.</label>
    </div>

    <!-- BUTTONS -->
    <div>
      <button onclick="acceptTerms()" style="padding: 10px 20px; background: #30364F; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: bold;">Accept Terms</button>
      <button onclick="goBack()" style="padding: 10px 20px; background: #999; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: bold; margin-left: 10px;">Back</button>
    </div>

  </div>
</div>

<footer>© 2026 Barangay Tiniguiban</footer>

<script>
  function acceptTerms() {
    const checkbox = document.getElementById('agree');
    if (!checkbox.checked) {
      alert('Please agree to the Terms and Conditions to proceed.');
      return;
    }
    
    alert('Terms accepted successfully!');
    goBack();
  }

  function goBack() {
    const urlParams = new URLSearchParams(window.location.search);
    const from = urlParams.get('from');
    if (from === 'admin_registration' || (document.referrer && document.referrer.includes('admin_registration'))) {
      window.location.href = 'admin_registration.php';
    } else if (from === 'register' || (document.referrer && document.referrer.includes('register'))) {
      window.location.href = 'register.php';
    } else {
      window.location.href = 'register.php';
    }
  }
</script>

</body>
</html>
