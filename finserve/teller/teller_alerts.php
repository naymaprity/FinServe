<?php
session_start();
require '../config/db.php';

// ✅ Teller Login Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 8) {
    header('Location: teller_login.php');
    exit;
}

$teller_name = $_SESSION['user']['full_name'] ?? 'Teller';

// ✅ Define thresholds
$highValueAmount = 100000; // High-value transaction threshold
$frequentTxnCount = 3;     // Frequent transactions threshold

// Get today's date
$today = date('Y-m-d');

// 1️⃣ High-Value Transaction Alerts
$highValueTxns = $pdo->prepare("SELECT t.*, c.full_name, c.account_number 
                                FROM transactions t 
                                JOIN customers c ON t.customer_id = c.id
                                WHERE t.amount >= ? AND DATE(t.created_at)=? 
                                ORDER BY t.created_at DESC");
$highValueTxns->execute([$highValueAmount, $today]);
$highValueAlerts = $highValueTxns->fetchAll();

// 2️⃣ Daily Cash Reconciliation
$totalDepositStmt = $pdo->prepare("SELECT SUM(amount) as total_deposit FROM transactions 
                                   WHERE type='deposit' AND DATE(created_at)=?");
$totalDepositStmt->execute([$today]);
$totalDeposit = $totalDepositStmt->fetchColumn() ?? 0;

$totalWithdrawStmt = $pdo->prepare("SELECT SUM(amount) as total_withdraw FROM transactions 
                                    WHERE type='withdraw' AND DATE(created_at)=?");
$totalWithdrawStmt->execute([$today]);
$totalWithdraw = $totalWithdrawStmt->fetchColumn() ?? 0;

$netCash = $totalDeposit - $totalWithdraw;

// 3️⃣ Frequent Customer Activity Alerts
$frequentTxnsStmt = $pdo->prepare("SELECT c.full_name, c.account_number, COUNT(t.id) as txn_count
                                   FROM transactions t
                                   JOIN customers c ON t.customer_id=c.id
                                   WHERE DATE(t.created_at)=?
                                   GROUP BY t.customer_id
                                   HAVING txn_count >= ?");
$frequentTxnsStmt->execute([$today, $frequentTxnCount]);
$frequentAlerts = $frequentTxnsStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Teller Alerts & Cash Monitoring</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
/* 🌈 Dashboard style similar */
body { font-family:'Segoe UI',sans-serif; margin:0; padding:0; background: linear-gradient(135deg,#5D54A4,#7C78B8); color:#fff; }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 40px; background: rgba(0,0,0,0.25); backdrop-filter: blur(5px); box-shadow:0 4px 12px rgba(0,0,0,0.3); }
header h1 { margin:0; font-size:22px; color:#FFD700; }
header a { color:#fff; text-decoration:none; font-weight:bold; }
.container { max-width:1100px; margin:50px auto; }
.card { background: rgba(255,255,255,0.1); backdrop-filter: blur(15px); border-radius:20px; padding:30px 25px; box-shadow:0 8px 25px rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.2); margin-bottom:30px; transition: transform 0.3s ease; }
.card:hover { transform: translateY(-5px); }
.card h2 { font-size:24px; margin-bottom:20px; display:flex; align-items:center; gap:10px; color:#FFD700; }
table { width:100%; border-collapse: collapse; background: rgba(255,255,255,0.05); border-radius:12px; overflow:hidden; }
table th, table td { padding:12px 10px; text-align:left; }
table th { background: rgba(255,255,255,0.15); color:#FFD700; font-weight:600; }
table td { color:#fff; border-bottom:1px solid rgba(255,255,255,0.2); }
.high { background: #16a34a; color:#fff; font-weight:bold; padding:4px 8px; border-radius:6px; } /* Green for high-value */
.freq { background: #dc2626; color:#fff; font-weight:bold; padding:4px 8px; border-radius:6px; } /* Red for frequent */
p { margin:8px 0; font-size:16px; }
</style>
</head>
<body>

<header>
    <h1><i class="ri-alert-line"></i> Teller Alerts & Cash Monitoring</h1>
    <a href="dashboard.php">Back to Dashboard</a>
</header>

<div class="container">
    <!-- 1️⃣ High-Value Alerts -->
    <div class="card">
        <h2>High-Value Transactions (>= <?= number_format($highValueAmount) ?> BDT)</h2>
        <?php if($highValueAlerts): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Account</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($highValueAlerts as $txn): ?>
                <tr>
                    <td><?= $txn['id'] ?></td>
                    <td><?= htmlspecialchars($txn['full_name']) ?></td>
                    <td><?= htmlspecialchars($txn['account_number']) ?></td>
                    <td><?= ucfirst($txn['type']) ?></td>
                    <td><span class="high"><?= number_format($txn['amount'],2) ?></span></td>
                    <td><?= $txn['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>No high-value transactions today.</p>
        <?php endif; ?>
    </div>

    <!-- 2️⃣ Daily Cash Reconciliation -->
    <div class="card">
        <h2>Daily Cash Reconciliation (<?= $today ?>)</h2>
        <p>Total Deposits: <strong><?= number_format($totalDeposit,2) ?> BDT</strong></p>
        <p>Total Withdrawals: <strong><?= number_format($totalWithdraw,2) ?> BDT</strong></p>
        <p>Net Cash: <strong><?= number_format($netCash,2) ?> BDT</strong></p>
    </div>

    <!-- 3️⃣ Frequent Customer Activity -->
    <div class="card">
        <h2>Frequent Customer Activity (>= <?= $frequentTxnCount ?> transactions)</h2>
        <?php if($frequentAlerts): ?>
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Account</th>
                    <th>Number of Transactions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($frequentAlerts as $f): ?>
                <tr>
                    <td><span class="freq"><?= htmlspecialchars($f['full_name']) ?></span></td>
                    <td><span class="freq"><?= htmlspecialchars($f['account_number']) ?></span></td>
                    <td><span class="freq"><?= $f['txn_count'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>No frequent customer activity today.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
