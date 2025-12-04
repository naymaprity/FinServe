<?php
session_start();
require '../config/db.php';

// ✅ Teller Login Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 8) {
    header('Location: teller_login.php');
    exit;
}

$teller_name = $_SESSION['user']['full_name'] ?? 'Teller';

// ✅ Fetch all customers
$customers = $pdo->query("SELECT id, account_number, full_name, account_type, balance FROM customers ORDER BY id DESC")->fetchAll();

// ✅ Total Cash Available (sum of all customer balances)
$totalCash = $pdo->query("SELECT SUM(balance) as total_cash FROM customers")->fetch(PDO::FETCH_ASSOC)['total_cash'] ?? 0;

// ✅ Total Deposits Today
$totalDeposits = $pdo->query("SELECT SUM(amount) as total_deposit FROM transactions WHERE type='deposit' AND DATE(created_at) = CURDATE()")->fetch(PDO::FETCH_ASSOC)['total_deposit'] ?? 0;

// ✅ Total Withdrawals Today
$totalWithdrawals = $pdo->query("SELECT SUM(amount) as total_withdraw FROM transactions WHERE type='withdraw' AND DATE(created_at) = CURDATE()")->fetch(PDO::FETCH_ASSOC)['total_withdraw'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cash Counter | Teller</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
body { font-family:'Segoe UI', sans-serif; margin:0; padding:0; background: linear-gradient(135deg,#5D54A4,#7C78B8); color:#fff; }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 40px; background: rgba(0,0,0,0.25); backdrop-filter: blur(5px); }
header h1 { margin:0; color:#FFD700; }
header .logout-btn { background:#ef4444; color:#fff; padding:8px 15px; border-radius:25px; text-decoration:none; font-weight:bold; }
.container { max-width:1200px; margin:50px auto; }
.card { background: rgba(255,255,255,0.1); backdrop-filter: blur(15px); border-radius:20px; padding:25px; box-shadow:0 8px 20px rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.2); margin-bottom:25px; }
.card h2 { font-size:22px; margin-bottom:20px; color:#FFD700; }
.stats { display:flex; gap:20px; flex-wrap:wrap; }
.stat-box { flex:1; min-width:200px; background:#fff; color:#000; padding:20px; border-radius:15px; box-shadow:0 6px 15px rgba(0,0,0,0.2); font-weight:bold; text-align:center; }
.stat-box h3 { margin:0 0 10px 0; font-size:16px; color:#555; font-weight:600; }
.stat-box p { font-size:20px; margin:0; color:#111; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
table th, table td { padding:12px 10px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.2); }
table th { color:#FFD700; font-weight:600; }
table td { color:#fff; }
</style>
</head>
<body>

<header>
  <h1><i class="ri-money-dollar-circle-line"></i> Cash Counter</h1>
  <a href="dashboard.php" class="logout-btn">Back to Dashboard</a>
</header>

<div class="container">
  <div class="card">
    <h2>Summary</h2>
    <div class="stats">
      <div class="stat-box">
        <h3>Total Cash Available</h3>
        <p><?= number_format($totalCash,2) ?> BDT</p>
      </div>
      <div class="stat-box">
        <h3>Today's Deposits</h3>
        <p><?= number_format($totalDeposits,2) ?> BDT</p>
      </div>
      <div class="stat-box">
        <h3>Today's Withdrawals</h3>
        <p><?= number_format($totalWithdrawals,2) ?> BDT</p>
      </div>
    </div>
  </div>

  <div class="card">
    <h2>Customer Balances</h2>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Account Number</th>
          <th>Account Type</th>
          <th>Balance (BDT)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($customers as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['id']) ?></td>
          <td><?= htmlspecialchars($c['full_name']) ?></td>
          <td><?= htmlspecialchars($c['account_number']) ?></td>
          <td><?= htmlspecialchars($c['account_type']) ?></td>
          <td><?= number_format($c['balance'],2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
