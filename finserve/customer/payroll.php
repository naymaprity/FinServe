<?php
session_start();
require '../config/db.php';

// ✅ Login check
if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    header("Location: ../login.php");
    exit;
}

$cid = $_SESSION['customer']['id'];

// ✅ Fetch customer info
$stmt_cust = $pdo->prepare("SELECT full_name, account_number, balance, account_type FROM customers WHERE id = ?");
$stmt_cust->execute([$cid]);
$cust = $stmt_cust->fetch(PDO::FETCH_ASSOC);

if (!$cust) {
    echo "<p style='text-align:center;color:red;'>Customer not found!</p>";
    exit;
}

// ✅ Fetch salary for THIS customer using account_number
$stmt_payroll = $pdo->prepare("SELECT * FROM monthly_salary WHERE account_number = ? ORDER BY pay_month DESC, id DESC");
$stmt_payroll->execute([$cust['account_number']]);
$payrolls = $stmt_payroll->fetchAll(PDO::FETCH_ASSOC);

// ✅ Update balance only for unpaid salaries
$latest_salary = 0;
foreach ($payrolls as $row) {
    if (is_null($row['paid_at'])) {
        $new_balance = $cust['balance'] + $row['total_salary'];

        // Update customers table balance
        $stmt_upd_cust = $pdo->prepare("UPDATE customers SET balance = ? WHERE account_number = ?");
        $stmt_upd_cust->execute([$new_balance, $cust['account_number']]);
        $cust['balance'] = $new_balance;

        // Mark salary as paid
        $stmt_upd_salary = $pdo->prepare("UPDATE monthly_salary SET paid_at = NOW() WHERE id = ?");
        $stmt_upd_salary->execute([$row['id']]);
    }
    $latest_salary = $row['total_salary'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>FinServe | Monthly Payroll</title>
<style>
/* CSS আগের মতোই রাখা হয়েছে */
body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #0f645aff, #0d3832ff); margin:0; color:#fff; min-height:100vh; }
header { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 15px 25px; display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid rgba(255,255,255,0.2); }
header h2 { margin:0; font-size:22px; letter-spacing:1px; }
header img { width:40px; height:40px; }
.back-btn { background:#00c853; color:white; padding:8px 18px; text-decoration:none; border-radius:8px; font-weight:bold; transition:0.3s; }
.back-btn:hover { background:#009624; transform:scale(1.05); }
.container { max-width:1000px; margin:50px auto; background: rgba(255,255,255,0.12); backdrop-filter:blur(20px); border-radius:15px; box-shadow:0 8px 32px rgba(0,0,0,0.3); padding:30px; color:#fff; animation:fadeIn 1s ease-in-out; }
@keyframes fadeIn { from {opacity:0; transform:translateY(10px);} to {opacity:1; transform:translateY(0);} }
.container h2 { text-align:center; color:#d6f45fff; font-size:26px; margin-bottom:15px; text-shadow:0 0 10px rgba(255,255,255,0.3); }
table { width:100%; border-collapse:collapse; margin-top:25px; overflow:hidden; border-radius:10px; }
th, td { padding:12px; text-align:center; }
th { background: rgba(0,0,0,0.4); color:#ffea00; text-transform:uppercase; }
tr { background: rgba(255,255,255,0.08); transition:0.3s; }
tr:nth-child(even) { background: rgba(255,255,255,0.15); }
tr:hover { background: rgba(76,175,80,0.2); transform:scale(1.01); }
td strong { color:#00e676; }
.no-record { text-align:center; font-size:18px; color:#ff5252; margin-top:20px; font-weight:bold; }
.interest-box { margin-top:25px; text-align:center; background: rgba(0, 230, 118, 0.2); border:1px solid #00e676; border-radius:12px; padding:15px; font-weight:bold; font-size:18px; color:#b2ff59; text-shadow:0 0 8px rgba(178,255,89,0.5); animation:pulse 2s infinite; }
@keyframes pulse { 0%,100% {box-shadow:0 0 10px #00e676;} 50% {box-shadow:0 0 25px #76ff03;} }
</style>
</head>
<body>

<header>
  <div style="display:flex;align-items:center;gap:10px;">
    <img src="../assets/logo.png" alt="Logo">
    <h2>FinServe Payroll Portal</h2>
  </div>
  <a href="dashboard.php" class="back-btn">🏦 Dashboard</a>
</header>

<div class="container">
  <h2>💼 Monthly Salary Details</h2>

  <p style="text-align:center;font-size:16px;">
    Account Holder: <strong><?= htmlspecialchars($cust['full_name']) ?></strong><br>
    Account Number: <strong><?= htmlspecialchars($cust['account_number']) ?></strong><br>
    Account Type / Role: <strong><?= htmlspecialchars($cust['account_type']) ?></strong><br>
    Current Balance: <strong><?= number_format($cust['balance'],2) ?> BDT</strong>
  </p>

  <?php if (count($payrolls) > 0): ?>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Role</th>
        <th>Basic</th>
        <th>HRA</th>
        <th>Allowances</th>
        <th>Deductions</th>
        <th>Total Salary</th>
        <th>Month</th>
        <th>Paid By</th>
        <th>Paid At</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($payrolls as $row): ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['employee_role']) ?></td>
        <td><?= number_format($row['basic_salary'],2) ?> BDT</td>
        <td><?= number_format($row['hra'],2) ?> BDT</td>
        <td><?= number_format($row['allowances'],2) ?> BDT</td>
        <td><?= number_format($row['deductions'],2) ?> BDT</td>
        <td><strong><?= number_format($row['total_salary'],2) ?> BDT</strong></td>
        <td><?= htmlspecialchars($row['pay_month']) ?></td>
        <td><?= htmlspecialchars($row['paid_by']) ?></td>
        <td><?= htmlspecialchars($row['paid_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php 
    $bonus = $latest_salary * 0.05;
  ?>
  <div class="interest-box">
    💰 You’ve earned a <span style="color:#76ff03;">5%</span> bonus on your last salary! <br>
    Estimated Bonus: <strong><?= number_format($bonus,2) ?> BDT</strong>
  </div>

  <?php else: ?>
    <p class="no-record">❌ No payroll records found.</p>
  <?php endif; ?>
</div>

</body>
</html>
