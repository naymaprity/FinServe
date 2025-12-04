<?php
session_start();
require '../config/db.php';

// ✅ Teller Login Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 8) {
    header('Location: teller_login.php');
    exit;
}

$teller_name = $_SESSION['user']['full_name'] ?? 'Teller';

// ✅ Fetch Customers
$customers = $pdo->query("SELECT * FROM customers ORDER BY id DESC LIMIT 50")->fetchAll();
$err = '';
$success = '';
$selectedCustomerBalance = 0;

// CSRF token
if (empty($_SESSION['csrf_deposit'])) {
    $_SESSION['csrf_deposit'] = bin2hex(random_bytes(32));
}

// ✅ Handle Deposit POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Search by account number
    if (isset($_POST['search_customer'])) {
        $searchAcc = trim($_POST['account_number'] ?? '');
        if ($searchAcc != '') {
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE account_number = ?");
            $stmt->execute([$searchAcc]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $_POST['customer_id'] = $row['id']; // Automatically select in dropdown
            } else {
                $err = "No customer found with account number $searchAcc";
            }
        }
    }

    // Deposit Logic
    if (isset($_POST['deposit_submit']) && !empty($_POST['customer_id'])) {
        $token = $_POST['csrf_deposit'] ?? '';
        $customer_id = $_POST['customer_id'];
        if (!hash_equals($_SESSION['csrf_deposit'], $token)) {
            $err = 'Invalid form submission.';
        } else {
            $amount_raw = str_replace(',', '', trim($_POST['amount'] ?? ''));
            if ($amount_raw === '' || !is_numeric($amount_raw)) {
                $err = 'Please enter a valid amount.';
            } else {
                $amount = round((float)$amount_raw, 2);
                if ($amount <= 0) {
                    $err = 'Amount must be greater than zero.';
                } elseif ($amount > 1000000) {
                    $err = 'Amount exceeds maximum limit (1,000,000).';
                } else {
                    try {
                        $pdo->beginTransaction();

                        $stmt = $pdo->prepare('SELECT balance FROM customers WHERE id = ? FOR UPDATE');
                        $stmt->execute([$customer_id]);
                        $cust = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!$cust) throw new Exception('Customer not found.');

                        $newBalance = round($cust['balance'] + $amount, 2);

                        $stmt = $pdo->prepare('UPDATE customers SET balance = ? WHERE id = ?');
                        $stmt->execute([$newBalance, $customer_id]);

                        $note = trim($_POST['note'] ?? '');
                        $stmt = $pdo->prepare('INSERT INTO transactions (customer_id, type, amount, description, balance_after, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                        $stmt->execute([$customer_id, 'deposit', $amount, $note ?: 'Deposit via Teller', $newBalance]);

                        $pdo->commit();
                        $success = 'Deposit successful! Amount: ' . number_format($amount, 2) . ' BDT. New balance: ' . number_format($newBalance, 2) . ' BDT.';
                        $_SESSION['csrf_deposit'] = bin2hex(random_bytes(32));
                        $selectedCustomerBalance = $newBalance;
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        error_log('Deposit error for customer ' . $customer_id . ': ' . $e->getMessage());
                        $err = 'Unable to complete deposit. Try again later.';
                    }
                }
            }
        }
    }
}

// Fetch selected customer balance for showing in form
if (isset($_POST['customer_id']) && $_POST['customer_id']) {
    $stmt = $pdo->prepare('SELECT balance FROM customers WHERE id = ?');
    $stmt->execute([$_POST['customer_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $selectedCustomerBalance = $row['balance'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Teller Dashboard | Deposit Funds</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
/* 🌈 Dashboard style same as before */
body { font-family:'Segoe UI', sans-serif; margin:0; padding:0; background: linear-gradient(135deg,#5D54A4,#7C78B8); min-height:100vh; color:#fff; }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 40px; background: rgba(0,0,0,0.25); backdrop-filter: blur(5px); box-shadow:0 4px 12px rgba(0,0,0,0.3); }
header .logo-section { display:flex; align-items:center; gap:12px; }
header .logo-section img { height:40px; width:40px; border-radius:50%; border:1px solid #fff; }
header h1 { margin:0; font-size:22px; font-weight:bold; color:#fff; }
header .logout-btn { background:#ef4444; color:#fff; padding:10px 18px; border-radius:25px; text-decoration:none; font-weight:bold; transition:0.3s; }
header .logout-btn:hover { background:#cc0000; }

.container { max-width:900px; margin:50px auto; }
.card { background: rgba(255,255,255,0.1); backdrop-filter: blur(15px); border-radius:20px; padding:30px 25px; box-shadow:0 8px 25px rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.2); transition: transform 0.3s ease; }
.card:hover { transform: translateY(-5px); }
.card h2 { font-size:24px; margin-bottom:20px; display:flex; align-items:center; gap:10px; color:#FFD700; }
.alert { padding:14px 18px; border-radius:12px; font-size:15px; margin-bottom:18px; }
.alert-success {
    background: rgba(255, 223, 0, 0.2);
    color: #FFD700;
    border: 1px solid rgba(255, 223, 0, 0.4);
    font-weight: bold;
}
.alert-error { background: rgba(220,38,38,0.1); color:#dc2626; border:1px solid rgba(220,38,38,0.3); }
.form-group { margin-bottom:18px; }
.form-group label { display:block; font-weight:600; margin-bottom:6px; color:#fff; }
.form-group input, .form-group textarea, .form-group select {
    width:100%; padding:14px; border-radius:12px; border:none; font-size:15px; background: rgba(255,255,255,0.15); color:#fff; outline:none; transition:0.3s;
}
.form-group input:focus, .form-group textarea:focus, .form-group select:focus { background: rgba(255,255,255,0.25); box-shadow:0 4px 15px rgba(0,0,0,0.25); }
.form-actions { display:flex; gap:15px; flex-wrap:wrap; margin-top:12px; }
.btn { padding:14px 22px; border-radius:12px; font-weight:600; cursor:pointer; border:none; transition:0.3s; }
.btn-primary { background:#FFD700; color:#0f172a; font-weight:bold; }
.btn-primary:hover { background:#FFC700; }
.btn-light { background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.3); }
.btn-light:hover { background:rgba(255,255,255,0.35); }
.muted { color:#e0e0e0; font-size:13px; }
@media(max-width:600px){ .card { padding:20px 15px; } .form-actions { flex-direction:column; } }
</style>
</head>
<body>

<header>
    <div class="logo-section">
        <img src="../assets/logo.png" alt="Logo">
        <h1>Finserve Teller</h1>
    </div>
    
</header>

<div class="container">
  <div class="card">
    <h2><i class="ri-bank-line"></i> Deposit Money</h2>

    <?php if($err): ?>
      <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- 🔍 Search Customer -->
    <form method="POST" style="margin-bottom:20px;">
      <div class="form-group">
        <label for="account_number">Search Customer by Account Number</label>
        <input type="text" name="account_number" id="account_number" placeholder="Enter account number" value="<?= htmlspecialchars($_POST['account_number'] ?? '') ?>">
      </div>
      <div class="form-actions">
        <button type="submit" name="search_customer" class="btn btn-primary">Search</button>
      </div>
    </form>

    <!-- Deposit Form -->
    <form method="POST">
      <input type="hidden" name="csrf_deposit" value="<?= htmlspecialchars($_SESSION['csrf_deposit']) ?>">

      <div class="form-group">
        <label for="customer_id">Select Customer</label>
        <select name="customer_id" id="customer_id" required onchange="this.form.submit()">
          <option value="">-- Choose Customer --</option>
          <?php foreach($customers as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (isset($_POST['customer_id']) && $_POST['customer_id']==$c['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['account_number'])." - ".htmlspecialchars($c['account_type'])." (Balance: ".number_format($c['balance'],2)." BDT)" ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if(isset($_POST['customer_id']) && $_POST['customer_id']): ?>
  <p class="muted">Current Balance: <strong><?= number_format($selectedCustomerBalance,2) ?> BDT</strong></p>

  <?php if(!$success): ?>
    <div class="form-group">
      <label for="amount">Amount (BDT)</label>
      <input type="number" step="0.01" min="0.01" name="amount" id="amount" required placeholder="e.g. 1500.00">
      <small class="muted">Maximum single deposit: 1,000,000 BDT</small>
    </div>

    <div class="form-group">
      <label for="note">Note (Optional)</label>
      <textarea name="note" id="note" placeholder="e.g. Salary, Savings, Gift" rows="2" maxlength="255"></textarea>
    </div>

    <div class="form-actions">
      <button type="submit" name="deposit_submit" class="btn btn-primary">Deposit</button>
      <a href="dashboard.php" class="btn btn-light">Cancel</a>
    </div>
  <?php else: ?>
    <div class="form-actions">
      <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
  <?php endif; ?>
<?php endif; ?>
    </form>
  </div>
</div>

</body>
</html>
