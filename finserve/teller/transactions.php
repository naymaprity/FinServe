<?php
session_start();
require '../config/db.php';

// ✅ Teller Login Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 8) {
    header('Location: teller_login.php');
    exit;
}

// Fetch all transactions with customer info
$transactions = $pdo->query("SELECT t.*, c.account_number, c.full_name 
                             FROM transactions t 
                             JOIN customers c ON t.customer_id = c.id
                             ORDER BY t.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transaction History</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
body { font-family:'Segoe UI', sans-serif; margin:0; padding:0; background: linear-gradient(135deg,#5D54A4,#7C78B8); color:#fff; }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 40px; background: rgba(0,0,0,0.25); backdrop-filter: blur(5px); }
header h1 { margin:0; color:#FFD700; }
.container { max-width:1200px; margin:50px auto; }
.card { background: rgba(255,255,255,0.1); backdrop-filter: blur(15px); border-radius:20px; padding:25px; box-shadow:0 8px 20px rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.2); }
.card h2 { font-size:24px; margin-bottom:20px; color:#FFD700; }
table { width:100%; border-collapse: collapse; }
table th, table td { padding:12px 10px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.2); }
table th { color:#FFD700; font-weight:600; }
table td { color:#fff; }
.badge { padding:4px 10px; border-radius:6px; font-weight:bold; font-size:13px; display:inline-block; text-align:center; min-width:70px; }
.deposit { background-color:#16a34a; color:#000; font-weight:bold; }  /* solid green, black bold text */
.withdraw { background-color:#dc2626; color:#000; font-weight:bold; } /* solid red, black bold text */
</style>

</head>
<body>

<header>
  <h1><i class="ri-history-line"></i> Transaction History</h1>
</header>

<div class="container">
  <div class="card">
    <h2>All Transactions</h2>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Customer</th>
          <th>Account Number</th>
          <th>Type</th>
          <th>Amount (BDT)</th>
          <th>Balance After (BDT)</th>
          <th>Description</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($transactions as $t): ?>
        <tr>
          <td><?= htmlspecialchars($t['id']) ?></td>
          <td><?= htmlspecialchars($t['full_name']) ?></td>
          <td><?= htmlspecialchars($t['account_number']) ?></td>
          <td><span class="badge <?= $t['type']=='deposit'?'deposit':'withdraw' ?>"><?= ucfirst($t['type']) ?></span></td>
          <td><?= number_format($t['amount'],2) ?></td>
          <td><?= number_format($t['balance_after'],2) ?></td>
          <td><?= htmlspecialchars($t['description']) ?></td>
          <td><?= htmlspecialchars($t['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
