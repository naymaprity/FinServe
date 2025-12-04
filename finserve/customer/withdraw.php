<?php
session_start();
require '../config/db.php';

// ✅ Only allow logged-in customers
if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    header('Location: ../login.php');
    exit;
}

$cid = $_SESSION['customer']['id'];

// ✅ CSRF token
if (empty($_SESSION['csrf_withdraw'])) {
    $_SESSION['csrf_withdraw'] = bin2hex(random_bytes(32));
}

$err = '';
$success = '';
$currentBalance = 0;

// ✅ Fetch current balance and password hash
$stmt = $pdo->prepare('SELECT balance, password FROM customers WHERE id = ?');
$stmt->execute([$cid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $currentBalance = (float)$row['balance'];
    $password_hash = $row['password']; // hashed password from DB
} else {
    $err = 'Customer record not found.';
}

// ✅ Handle withdraw POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $amount = round((float)$_POST['amount'], 2);
    $note = trim($_POST['note'] ?? '');
    $password = $_POST['password'];

    // ✅ Verify password
    if (!password_verify($password, $password_hash)) {
        $err = '❌ Incorrect password!';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT balance FROM customers WHERE id = ? FOR UPDATE');
            $stmt->execute([$cid]);
            $cust = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cust) throw new Exception('Customer not found.');

            if ($amount > $cust['balance']) throw new Exception('Insufficient balance.');

            $newBalance = round($cust['balance'] - $amount, 2);
            $stmt = $pdo->prepare('UPDATE customers SET balance = ? WHERE id = ?');
            $stmt->execute([$newBalance, $cid]);

            $stmt = $pdo->prepare('INSERT INTO transactions (customer_id, type, amount, description, balance_after, created_at) 
                                   VALUES (?, "withdraw", ?, ?, ?, NOW())');
            $stmt->execute([$cid, $amount, $note ?: 'Withdrawal via online portal', $newBalance]);

            $pdo->commit();
            $success = "✅ Withdrawal successful! Amount: " . number_format($amount, 2) . " BDT";
            $currentBalance = $newBalance; // update balance for display
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = 'Transaction failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Withdraw Funds | FinServe</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #dbeafe, #e0f2fe); margin:0; padding:0; color:#1e293b; }
header { background:#1e40af; color:white; padding:15px 40px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 4px 10px rgba(0,0,0,0.2); }
header img{ width:40px; height:40px; border-radius:50%; }
header h1{ font-size:22px; margin:0; }
header a{ background:#ef4444; padding:10px 18px; border-radius:25px; color:white; text-decoration:none; font-weight:600; }
header a:hover{ background:#b91c1c; }

.container{ max-width:700px; margin:50px auto; background:white; padding:25px; border-radius:15px; box-shadow:0 8px 25px rgba(0,0,0,0.1); }
h2{text-align:center;color:#1d4ed8;margin-bottom:25px;}
label{ font-weight:600; display:block; margin-bottom:6px; }
input, textarea{ width:100%; padding:12px; border-radius:8px; border:1px solid #cbd5e1; margin-bottom:15px; font-size:14px; outline:none; }
input:focus, textarea:focus{ border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,0.3); }
.btn{ background:#2563eb; color:white; border:none; padding:12px 18px; border-radius:8px; cursor:pointer; font-weight:600; width:100%; font-size:16px; transition:0.3s; }
.btn:hover{ background:#1e3a8a; }
.btn-secondary{ background:#e5e7eb; color:#1e293b; }
.btn-secondary:hover{ background:#cbd5e1; }
.alert{ padding:10px; border-radius:8px; margin-bottom:15px; }
.alert-error{ background:#fee2e2; color:#b91c1c; }
.alert-success{ background:#dcfce7; color:#15803d; }
.balance{ text-align:center; background:#e0f2fe; padding:10px; border-radius:8px; margin-bottom:20px; font-weight:600; }
</style>
</head>
<body>

<header>
  <div style="display:flex;align-items:center;gap:12px;">
    <img src="../assets/logo.png" alt="Logo">
    <h1>FinServe Withdraw</h1>
  </div>
  <a href="../index.php">Logout</a>
</header>

<div class="container">
    <h2>💸 Withdraw Funds</h2>

    <?php if($err): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <script>
            Swal.fire({
                icon: 'success',
                title: '✅ Withdrawal Successful!',
                text: '<?= addslashes($success) ?>',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        </script>
    <?php endif; ?>

    <div class="balance">💰 Current Balance: <?= number_format($currentBalance, 2) ?> BDT</div>

    <form id="withdrawForm" method="POST">
        <input type="hidden" name="csrf_withdraw" value="<?= htmlspecialchars($_SESSION['csrf_withdraw']) ?>">
        <div>
            <label>Amount (BDT)</label>
            <input type="number" name="amount" id="amount" step="0.01" required placeholder="e.g. 1500.00">
        </div>

        <div>
            <label>Note (optional)</label>
            <textarea name="note" placeholder="e.g. Cash Out, Utility Bill, etc"></textarea>
        </div>

        <button type="button" class="btn" id="withdrawBtn">Withdraw</button>
        <a href="dashboard.php" class="btn btn-secondary" style="display:block;margin-top:10px;">← Back to Dashboard</a>
    </form>
</div>

<script>
// ✅ SweetAlert Password Popup Before Submit (now fully PHP verified)
document.getElementById('withdrawBtn')?.addEventListener('click', async function() {
    const amount = document.getElementById('amount').value.trim();
    if (!amount || amount <= 0) {
        Swal.fire('⚠️ Invalid Amount', 'Please enter a valid amount.', 'warning');
        return;
    }

    const { value: password } = await Swal.fire({
        title: '🔒 Enter Your Account Password',
        input: 'password',
        inputAttributes: { autocapitalize: 'off', autocorrect: 'off' },
        inputPlaceholder: 'Enter your password',
        confirmButtonText: 'Confirm',
        showCancelButton: true,
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) return 'Please enter your password!';
        }
    });

    if(password){
        const form = document.getElementById('withdrawForm');
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'password';
        hidden.value = password;
        form.appendChild(hidden);
        form.submit();
    }
});
</script>

</body>
</html>
