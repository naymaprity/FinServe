<?php
session_start();
require '../config/db.php';

// ✅ Ensure only logged-in customers can access
if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    header('Location: ../login.php');
    exit;
}

$cid = $_SESSION['customer']['id'];
$err = '';
$success = '';
$currentBalance = 0;

// ✅ Create CSRF token
if (empty($_SESSION['csrf_transfer'])) {
    $_SESSION['csrf_transfer'] = bin2hex(random_bytes(32));
}

try {
    $stmt = $pdo->prepare('SELECT account_number, balance, transaction_pin FROM customers WHERE id = ?');
    $stmt->execute([$cid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $currentBalance = (float)$row['balance'];
        $savedPin = $row['transaction_pin'];
        $senderAcc = $row['account_number'];
    } else {
        throw new Exception('Customer record not found.');
    }
} catch (Exception $e) {
    $err = 'Error loading your account. Please try again.';
}

// ✅ Handle transfer request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_transfer'] ?? '';
    $enteredPin = trim($_POST['transaction_pin'] ?? '');
    $account_no = strtoupper(trim($_POST['account_no'] ?? ''));
    $amount_raw = str_replace(',', '', trim($_POST['amount'] ?? ''));
    $note = trim($_POST['note'] ?? '');

    if (!hash_equals($_SESSION['csrf_transfer'], $token)) {
        $err = 'Invalid request detected.';
    } elseif ($enteredPin === '' || $enteredPin != $savedPin) {
        $err = 'Incorrect transaction PIN.';
    } elseif ($account_no === '' || !preg_match('/^[A-Za-z0-9]+$/', $account_no)) {
        $err = 'Please enter a valid recipient account number.';
    } elseif ($amount_raw === '' || !is_numeric($amount_raw)) {
        $err = 'Please enter a valid amount.';
    } else {
        $amount = round((float)$amount_raw, 2);

        if ($amount <= 0) {
            $err = 'Amount must be greater than zero.';
        } elseif ($amount > $currentBalance) {
            $err = 'Insufficient balance.';
        } elseif ($account_no == $senderAcc) {
            $err = 'Cannot transfer to your own account.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare('SELECT balance FROM customers WHERE id = ? FOR UPDATE');
                $stmt->execute([$cid]);
                $sender = $stmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $pdo->prepare('SELECT id, balance FROM customers WHERE account_number = ? FOR UPDATE');
                $stmt->execute([$account_no]);
                $receiver = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$receiver) throw new Exception('Recipient not found.');

                $newSenderBalance = $sender['balance'] - $amount;
                $newReceiverBalance = $receiver['balance'] + $amount;

                $stmt = $pdo->prepare('UPDATE customers SET balance = ? WHERE id = ?');
                $stmt->execute([$newSenderBalance, $cid]);

                $stmt = $pdo->prepare('UPDATE customers SET balance = ? WHERE id = ?');
                $stmt->execute([$newReceiverBalance, $receiver['id']]);

                $stmt = $pdo->prepare('INSERT INTO transactions (customer_id, type, amount, description, balance_after, created_at) 
                                       VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$cid, 'transfer_out', $amount, $note ?: "Transfer to $account_no", $newSenderBalance]);
                $stmt->execute([$receiver['id'], 'transfer_in', $amount, $note ?: "Received from $senderAcc", $newReceiverBalance]);

                $pdo->commit();
                $success = "৳" . number_format($amount, 2) . " sent successfully to $account_no.";
                $_SESSION['csrf_transfer'] = bin2hex(random_bytes(32));
                $currentBalance = $newSenderBalance;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $err = 'Transfer failed. Please try again.';
            }
        }
    }
}
?>

<style>
:root {
  --bg:#f0f2f5; --card:#fff; --primary:#1d4ed8; --primary-hover:#2563eb;
  --success:#16a34a; --danger:#dc2626; --text:#1f2937;
  --border:#e5e7eb; --radius:16px; font-family:'Inter',sans-serif;
}
html,body{margin:0;padding:0;background:var(--bg);color:var(--text);}
body{display:flex;flex-direction:column;min-height:100vh;}
header{background:linear-gradient(90deg,#1d4ed8,#2563eb);color:#fff;
  padding:15px 40px;display:flex;align-items:center;justify-content:space-between;
  box-shadow:0 6px 18px rgba(0,0,0,0.15);}
header .logo-section{display:flex;align-items:center;gap:12px;}
header img{height:42px;width:42px;border-radius:50%;}
header h1{font-size:24px;font-weight:700;}
header .logout-btn{background:#ff4d4d;padding:10px 20px;border-radius:25px;
  color:#fff;text-decoration:none;font-weight:600;transition:0.3s;}
header .logout-btn:hover{background:#cc0000;}
.container{max-width:700px;width:90%;margin:auto;flex:1;display:flex;
  align-items:center;justify-content:center;}
.card{background:var(--card);padding:30px;border-radius:var(--radius);
  box-shadow:0 10px 25px rgba(0,0,0,0.1);width:100%;}
.card-header h2{margin:0;font-size:22px;color:var(--primary);}
.alert{padding:14px 18px;border-radius:10px;font-size:14px;margin-bottom:18px;font-weight:500;}
.alert-error{background:rgba(220,38,38,0.1);color:var(--danger);border-left:4px solid var(--danger);}
.form-group{margin-bottom:18px;}
.form-group label{font-weight:600;margin-bottom:6px;display:block;}
.form-group input{width:100%;padding:14px;border-radius:10px;border:1px solid var(--border);}
.form-group input:focus{border-color:var(--primary);outline:none;box-shadow:0 4px 10px rgba(29,78,216,0.1);}
.btn{padding:12px 20px;border-radius:10px;font-weight:600;cursor:pointer;border:none;}
.btn-primary{background:var(--primary);color:#fff;}
.btn-primary:hover{background:var(--primary-hover);}
.btn-light{background:#fff;border:1px solid var(--border);}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;
  background:rgba(0,0,0,0.6);justify-content:center;align-items:center;z-index:999;}
.modal-content{background:var(--card);padding:30px;border-radius:20px;width:380px;text-align:center;}
.modal-content h3{margin-bottom:20px;color:var(--primary);}
.modal-content input{padding:14px;border-radius:10px;border:1px solid var(--border);width:100%;margin-bottom:20px;}
.modal-content button{width:48%;margin:2%;}
/* ✅ Success Popup */
.success-popup{
  display:none;position:fixed;top:0;left:0;width:100%;height:100%;
  background:rgba(0,0,0,0.5);justify-content:center;align-items:center;z-index:1000;
}
.success-box{
  background:#fff;padding:40px 50px;border-radius:20px;text-align:center;
  box-shadow:0 10px 25px rgba(0,0,0,0.2);animation:fadeIn 0.3s ease;
}
.success-tick{
  font-size:60px;color:var(--success);animation:pop 0.4s ease;
}
@keyframes pop{0%{transform:scale(0);}100%{transform:scale(1);}}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
</style>

<header>
  <div class="logo-section">
    <img src="../assets/logo.png" alt="Logo">
    <h1>Finserve</h1>
  </div>
  <a href="dashboard.php" class="logout-btn">Dashboard</a>
</header>

<div class="container">
  <div class="card">
    <div class="card-header"><h2>Transfer Funds</h2></div>
    <div class="card-body">

      <p>Current Balance: <strong><?= number_format($currentBalance,2) ?> BDT</strong></p>

      <?php if($err): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <form id="transferForm" method="POST">
        <input type="hidden" name="csrf_transfer" value="<?= htmlspecialchars($_SESSION['csrf_transfer']) ?>">
        <div class="form-group">
          <label>Recipient Account Number</label>
          <input type="text" name="account_no" required placeholder="e.g. FIN12345" pattern="[A-Za-z0-9]+" title="Only letters and digits allowed">
        </div>
        <div class="form-group">
          <label>Amount (BDT)</label>
          <input type="number" step="0.01" min="0.01" name="amount" required placeholder="e.g. 1500.00">
        </div>
        <div class="form-group">
          <label>Note (Optional)</label>
          <input type="text" name="note" placeholder="e.g. Rent, Loan">
        </div>
        <button type="button" class="btn btn-primary" onclick="openPinModal()">Transfer</button>
        <a href="dashboard.php" class="btn btn-light">Cancel</a>
      </form>
    </div>
  </div>
</div>

<!-- PIN Modal -->
<div class="modal" id="pinModal">
  <div class="modal-content">
    <h3>Enter Transaction PIN</h3>
    <input type="password" id="pinInput" placeholder="Your PIN">
    <button type="button" class="btn btn-primary" onclick="submitTransfer()">Confirm</button>
    <button type="button" class="btn btn-light" onclick="closePinModal()">Cancel</button>
  </div>
</div>

<!-- ✅ Success Popup -->
<div class="success-popup" id="successPopup">
  <div class="success-box">
    <div class="success-tick">✔️</div>
    <h3>Transaction Successful!</h3>
    <p>Your transfer has been completed.</p>
  </div>
</div>

<script>
function openPinModal(){document.getElementById('pinModal').style.display='flex';}
function closePinModal(){document.getElementById('pinModal').style.display='none';}
function submitTransfer(){
  const pin=document.getElementById('pinInput').value.trim();
  if(pin===''){alert('Please enter your PIN.');return;}
  const form=document.getElementById('transferForm');
  const input=document.createElement('input');
  input.type='hidden';input.name='transaction_pin';input.value=pin;
  form.appendChild(input);
  form.submit();
}

// ✅ Show success popup if transfer completed
<?php if($success): ?>
document.addEventListener("DOMContentLoaded",()=>{
  const popup=document.getElementById('successPopup');
  popup.style.display='flex';
  setTimeout(()=>{window.location='dashboard.php';},3000);
});
<?php endif; ?>
</script>
