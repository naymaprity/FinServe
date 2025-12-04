<?php
session_start();
require '../config/db.php';

// ✅ Teller Login Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 8) {
    header('Location: teller_login.php');
    exit;
}

$teller_name = $_SESSION['user']['full_name'] ?? 'Teller';
$customers = $pdo->query("SELECT * FROM customers ORDER BY id DESC LIMIT 50")->fetchAll();
$err = '';
$success = '';
$selectedCustomerBalance = 0;

// CSRF token
if (empty($_SESSION['csrf_withdraw'])) {
    $_SESSION['csrf_withdraw'] = bin2hex(random_bytes(32));
}

// ✅ Withdraw request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Search Customer
    if (isset($_POST['search_customer'])) {
        $searchAcc = trim($_POST['account_number'] ?? '');
        if ($searchAcc != '') {
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE account_number = ?");
            $stmt->execute([$searchAcc]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $_POST['customer_id'] = $row['id'];
            else $err = "No customer found with account number $searchAcc";
        }
    }

    // Confirm Withdraw after OTP verified
    if (isset($_POST['confirm_withdraw'])) {
        $customer_id = $_POST['customer_id'];
        $entered_code = trim($_POST['security_code'] ?? '');
        $amount = $_POST['amount'] ?? 0;
        $note = $_POST['note'] ?? '';

        // ✅ Verify Security Code
        $stmt = $pdo->prepare("SELECT * FROM security_codes WHERE customer_id=? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$customer_id]);
        $otpRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otpRow || $otpRow['code'] !== $entered_code) {
            $err = "Invalid Security Code!";
        } else {
            try {
                $pdo->beginTransaction();

                // Fetch current balance
                $stmt = $pdo->prepare("SELECT balance FROM customers WHERE id=? FOR UPDATE");
                $stmt->execute([$customer_id]);
                $cust = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$cust) throw new Exception("Customer not found.");

                if ($amount > $cust['balance']) throw new Exception("Insufficient balance.");

                $newBalance = $cust['balance'] - $amount;

                // Update balance
                $stmt = $pdo->prepare("UPDATE customers SET balance=? WHERE id=?");
                $stmt->execute([$newBalance, $customer_id]);

                // Record transaction
                $stmt = $pdo->prepare("INSERT INTO transactions (customer_id, type, amount, description, balance_after, created_at)
                                       VALUES (?, 'withdraw', ?, ?, ?, NOW())");
                $stmt->execute([$customer_id, $amount, $note ?: 'Withdraw via Teller', $newBalance]);

                // Mark code as used
                $pdo->prepare("UPDATE security_codes SET used=1 WHERE id=?")->execute([$otpRow['id']]);

                $pdo->commit();
                $success = "Withdraw successful! Amount: " . number_format($amount, 2) . " BDT.";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $err = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Secure Withdraw | FinServe Teller</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
body { font-family:'Segoe UI',sans-serif; background:linear-gradient(135deg,#5D54A4,#7C78B8); margin:0; color:#fff; min-height:100vh; }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 40px; background:rgba(0,0,0,0.25); backdrop-filter:blur(5px); box-shadow:0 4px 12px rgba(0,0,0,0.3); }
header h1 { margin:0; font-size:22px; font-weight:bold; }
.container { max-width:900px; margin:50px auto; }
.card { background:rgba(255,255,255,0.1); padding:30px; border-radius:20px; box-shadow:0 6px 25px rgba(0,0,0,0.3); }
.card h2 { color:#FFD700; display:flex; align-items:center; gap:10px; margin-bottom:15px; }
.alert { padding:14px 18px; border-radius:12px; margin-bottom:15px; }
.alert-error { background:rgba(220,38,38,0.2); color:#ff9999; }
.alert-success { background:rgba(255,223,0,0.2); color:#FFD700; }
.form-group { margin-bottom:15px; }
input, select, textarea { width:100%; padding:12px; border:none; border-radius:10px; background:rgba(255,255,255,0.15); color:#fff; font-size:15px; }
button { padding:12px 18px; border-radius:10px; cursor:pointer; font-weight:bold; border:none; }
.btn-primary { background:#FFD700; color:#000; }
.btn-primary:hover { background:#FFC700; }
.btn-light { background:rgba(255,255,255,0.2); color:#fff; }
.popup { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:1000; }
.popup-content { background:#fff; color:#000; padding:30px; border-radius:15px; text-align:center; width:350px; position:relative; }
.popup-content input { width:80%; margin-top:10px; padding:10px; border:1px solid #ccc; border-radius:8px; }
.success-check { font-size:80px; color:green; animation:pop 0.6s ease forwards; }
@keyframes pop { 0% {transform:scale(0);} 70% {transform:scale(1.2);} 100% {transform:scale(1);} }
</style>
</head>
<body>
<header>
  <h1><i class="ri-bank-line"></i> FinServe Teller Withdraw</h1>
</header>

<div class="container">
  <div class="card">
    <h2><i class="ri-money-dollar-circle-line"></i> Withdraw Money</h2>

    <?php if($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_withdraw" value="<?= htmlspecialchars($_SESSION['csrf_withdraw']) ?>">
      <div class="form-group">
        <label>Customer</label>
        <select name="customer_id" id="customer_id" required>
          <option value="">-- Select Customer --</option>
          <?php foreach($customers as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['account_number']) ?> - <?= htmlspecialchars($c['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Amount (BDT)</label>
        <input type="number" name="amount" id="amount" required placeholder="Enter amount">
      </div>
      <div class="form-group">
        <label>Note</label>
        <textarea name="note" placeholder="Purpose"></textarea>
      </div>

      <div class="form-actions">
        <button type="button" class="btn-primary" onclick="showPopup()">Withdraw</button>
        <a href="dashboard.php" class="btn-light">Cancel</a>
      </div>
    </form>
  </div>
</div>

<!-- 🔒 Security Code Popup -->
<div class="popup" id="popupBox">
  <div class="popup-content" id="popupContent">
    <h3>Enter Security Code</h3>
    <form method="POST" id="confirmForm">
      <input type="hidden" name="customer_id" id="popupCustomerId">
      <input type="hidden" name="amount" id="popupAmount">
      <input type="hidden" name="note" id="popupNote">
      <input type="text" name="security_code" placeholder="Enter Code" required>
      <div style="margin-top:15px;">
        <button type="submit" name="confirm_withdraw" class="btn-primary">Confirm Withdraw</button>
        <button type="button" class="btn-light" onclick="closePopup()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function showPopup(){
  const customer = document.getElementById('customer_id').value;
  const amount = document.getElementById('amount').value;
  const note = document.querySelector('textarea[name=note]').value;
  if(!customer || !amount){
    alert("Please select customer and enter amount.");
    return;
  }
  document.getElementById('popupCustomerId').value = customer;
  document.getElementById('popupAmount').value = amount;
  document.getElementById('popupNote').value = note;
  document.getElementById('popupBox').style.display='flex';
}
function closePopup(){ document.getElementById('popupBox').style.display='none'; }

// ✅ Success animation on confirm
<?php if($success): ?>
document.getElementById('popupContent').innerHTML = '<div class="success-check">✔</div><h3>Withdraw Successful!</h3>';
setTimeout(()=>{ window.location.href='dashboard.php'; },3000);
document.getElementById('popupBox').style.display='flex';
<?php endif; ?>
</script>
</body>
</html>
