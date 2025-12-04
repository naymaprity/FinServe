<?php
session_start();
require '../config/db.php';

// ✅ Allow only logged-in customers
if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    header('Location: ../login.php');
    exit;
}

$cid = $_SESSION['customer']['id'];
$customer_name = $_SESSION['customer']['full_name'] ?? 'Customer';
$err = '';
$success = '';

// CSRF token
if (empty($_SESSION['csrf_loan'])) {
    $_SESSION['csrf_loan'] = bin2hex(random_bytes(32));
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_loan'] ?? '';
    if (!hash_equals($_SESSION['csrf_loan'], $token)) {
        $err = 'Invalid form submission.';
    } else {
        $loan_type = trim($_POST['loan_type'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $term_months = intval($_POST['term_months'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if ($loan_type === '' || $amount <= 0 || $term_months <= 0) {
            $err = 'Please fill all required fields correctly.';
        } elseif (strlen($reason) < 5) {
            $err = 'Please enter a valid reason for your loan request.';
        } else {
            try {
                $status = 'pending'; // default status
                $stmt = $pdo->prepare('INSERT INTO loans (customer_id, loan_type, amount, reason, term_months, status, applied_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$cid, $loan_type, $amount, $reason, $term_months, $status]);

                $success = 'Your loan request has been submitted successfully!';
                $_SESSION['csrf_loan'] = bin2hex(random_bytes(32)); // regenerate CSRF
            } catch (Exception $e) {
                error_log('Loan request error: ' . $e->getMessage());
                $err = 'An unexpected error occurred. Please try again later.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Loan Request</title>

<style>
:root {
  --bg:#f6f9fc;
  --card:#ffffff;
  --border:#e4e9f2;
  --accent:#0b6ef6;
  --accent-hover:#2563eb;
  --success:#16a34a;
  --danger:#ef4444;
  --radius:14px;
  --shadow:0 10px 25px rgba(0,0,0,0.08);
  font-family:"Inter","Segoe UI",Roboto,sans-serif;
}

body {
  background: linear-gradient(135deg, #eef3ff, #ffffff);
  padding: 30px;
  color:#111827;
}

.container {
  max-width:720px;
  margin:0 auto;
}

/* ✅ Header Styling */
header {
    background: rgba(0, 51, 102, 0.9);
    color: #fff;
    padding: 15px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
header .logo-section {
    display: flex;
    align-items: center;
    gap: 12px;
}
header img {
    height: 40px;
    width: 40px;
    border-radius: 50%;
}
header h1 {
    font-size: 22px;
    font-weight: bold;
    letter-spacing: 1px;
}
header .logout-btn {
    background: #ff4d4d;
    color: #fff;
    padding: 10px 18px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: bold;
    transition: background 0.3s;
}
header .logout-btn:hover {
    background: #cc0000;
}

.card {
  background: var(--card);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow:hidden;
  animation:fadeIn .6s ease;
}

@keyframes fadeIn { from {opacity:0;transform:translateY(10px);} to {opacity:1;transform:translateY(0);} }

.card-header {
  padding: 24px;
  background: linear-gradient(135deg, var(--accent), var(--accent-hover));
  color: #fff;
  font-size: 22px;
  font-weight: 600;
  text-align:center;
}

.card-body {
  padding: 24px;
}

.alert {
  margin: 20px;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 15px;
  line-height: 1.4;
}

.alert-error {
  background: rgba(239,68,68,0.08);
  color: var(--danger);
  border:1px solid rgba(239,68,68,0.15);
}

.alert-success {
  background: rgba(22,163,74,0.06);
  color: var(--success);
  border:1px solid rgba(16,185,129,0.08);
}

.form-group { margin-bottom:18px; }
.form-group label {
  display:block;
  font-weight:600;
  margin-bottom:6px;
  font-size:15px;
}
.form-group input, .form-group select, .form-group textarea {
  width:100%;
  padding:10px 14px;
  border:1px solid var(--border);
  border-radius:10px;
  font-size:15px;
  box-sizing:border-box;
  transition:all 0.2s ease;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
  border-color: var(--accent);
  box-shadow:0 0 0 3px rgba(11,110,246,0.15);
  outline:none;
}

textarea { resize:vertical; min-height:80px; }

.form-actions {
  display:flex;
  gap:10px;
  justify-content:flex-end;
  margin-top:16px;
}

.btn {
  border:none;
  cursor:pointer;
  font-weight:600;
  border-radius:10px;
  padding:10px 18px;
  font-size:15px;
  transition:all 0.2s ease;
}
.btn-primary {
  background: linear-gradient(90deg, var(--accent), var(--accent-hover));
  color:#fff;
}
.btn-primary:hover {
  box-shadow:0 6px 18px rgba(11,110,246,0.25);
  transform:translateY(-2px);
}
.btn-light {
  background:#f3f4f6;
  color:#111827;
}
.btn-light:hover {
  background:#e5e7eb;
}

@media (max-width:600px){
  .form-actions { flex-direction:column; align-items:stretch; }
}
</style>
</head>
<body>
<!-- ✅ Header Section -->
<header>
    <div class="logo-section">
        <img src="../assets/logo.png" alt="Logo">
        <h1>Finserve</h1>
    </div>
    <a href="../index.php" class="logout-btn">Logout</a>
</header>
<div class="container">
  <div class="header">
    <h1>Hello, <?= htmlspecialchars($customer_name) ?>! Submit Your Loan Request</h1>
  </div>

  <div class="card">
    <div class="card-header">Loan Request Form</div>

    <?php if ($err): ?>
      <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card-body">
      <form method="post">
        <input type="hidden" name="csrf_loan" value="<?= htmlspecialchars($_SESSION['csrf_loan']) ?>">

        <div class="form-group">
          <label for="loan_type">Loan Type</label>
          <select id="loan_type" name="loan_type" required>
            <option value="">-- Select Type --</option>
            <option value="Personal Loan">Personal Loan</option>
            <option value="Business Loan">Business Loan</option>
            <option value="Education Loan">Education Loan</option>
            <option value="Home Loan">Home Loan</option>
          </select>
        </div>

        <div class="form-group">
          <label for="amount">Loan Amount (BDT)</label>
          <input id="amount" name="amount" type="number" step="0.01" min="1000" placeholder="e.g. 50000" required>
        </div>

        <div class="form-group">
          <label for="term_months">Term (Months)</label>
          <input id="term_months" name="term_months" type="number" min="6" max="60" placeholder="e.g. 12" required>
        </div>

        <div class="form-group">
          <label for="reason">Reason for Loan</label>
          <textarea id="reason" name="reason" maxlength="255" placeholder="Explain why you need this loan..." required></textarea>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Submit Request</button>
          <?php if (!$success): ?>
            <a href="dashboard.php" class="btn btn-light">Cancel</a>
          <?php else: ?>
            <a href="dashboard.php" class="btn btn-light">Back to Dashboard</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

</body>
</html>
