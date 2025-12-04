<?php
session_start();
require '../config/db.php';

// GM session
$user = $_SESSION['user'] ?? ['full_name'=>'GM','email'=>'gm@finserve.com','role_name'=>'GM'];

// Safe fetch helper
function fetchValue($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        return 0;
    }
}

// Stats
$totalCustomers   = fetchValue($pdo, "SELECT COUNT(*) FROM customers");
$totalAccounts    = fetchValue($pdo, "SELECT COUNT(*) FROM accounts");
$totalUsers       = fetchValue($pdo, "SELECT COUNT(*) FROM users");
$totalLoans       = fetchValue($pdo, "SELECT COUNT(*) FROM loans");
$pendingLoans     = fetchValue($pdo, "SELECT COUNT(*) FROM loans WHERE status='pending'");
$totalDeposits    = fetchValue($pdo, "SELECT SUM(amount) FROM transactions WHERE type='deposit'");
$totalWithdrawals = fetchValue($pdo, "SELECT SUM(amount) FROM transactions WHERE type='withdrawal'");
$totalExpenses    = fetchValue($pdo, "SELECT SUM(amount) FROM branch_expenses");

// Fetch last 10 transactions
$transactions = $pdo->query("SELECT t.id, t.id, t.type, t.amount, t.created_at, c.full_name AS customer_name
    FROM transactions t 
    LEFT JOIN customers c ON c.id = t.id
    ORDER BY t.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Fetch last 10 loans
$loans = $pdo->query("SELECT l.id, l.customer_id, l.loan_type, l.amount, l.status, l.applied_at, c.full_name AS customer_name
    FROM loans l
    LEFT JOIN customers c ON c.id = l.customer_id
    ORDER BY l.applied_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reports — FinServe Bank</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
body {
    background:#f0f2f5; font-family:'Inter', sans-serif;
}
.navbar {
    box-shadow:0 3px 8px rgba(0,0,0,0.1);
}
.card-custom {
    border-radius:15px; 
    box-shadow:0 6px 15px rgba(0,0,0,0.1); 
    margin-bottom:20px;
    transition: all 0.3s ease;
}
.card-custom:hover {
    transform: translateY(-5px);
    box-shadow:0 12px 25px rgba(0,0,0,0.15);
}
.stat-icon {
    font-size:2.2rem; 
    opacity:0.9; 
    margin-bottom:10px;
}
.hero-title {
    font-size:1rem; 
    font-weight:600; 
    opacity:0.85; 
    margin-bottom:5px;
}
.hero-value {
    font-size:1.6rem; 
    font-weight:700;
}
.table-xs td, .table-xs th {
    padding:.5rem .75rem;
    font-size:.875rem;
}
.table thead th {
    background:#343a40;
    color:#fff;
}
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,.03);
}
.table-hover tbody tr:hover {
    background-color: rgba(13,110,253,0.1);
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white px-4 py-3 mb-4">
  <a class="navbar-brand d-flex align-items-center gap-2" href="#">
    <img src="../assets/logo.png" alt="FinServe" width="36">
    <span style="font-weight:700; font-size:1.2rem;">FinServe Bank</span>
  </a>
</nav>

<div class="container-fluid">
  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <?php 
    $cards = [
      ['title'=>'Customers','value'=>$totalCustomers,'icon'=>'fa-users','bg'=>'linear-gradient(135deg,#0d6efd,#6610f2)'],
      ['title'=>'Accounts','value'=>$totalAccounts,'icon'=>'fa-wallet','bg'=>'linear-gradient(135deg,#198754,#20c997)'],
      ['title'=>'Users','value'=>$totalUsers,'icon'=>'fa-user','bg'=>'linear-gradient(135deg,#6f42c1,#6610f2)'],
      ['title'=>'Total Loans','value'=>$totalLoans,'icon'=>'fa-hand-holding-dollar','bg'=>'linear-gradient(135deg,#fd7e14,#ffc107)'],
      ['title'=>'Pending Loans','value'=>$pendingLoans,'icon'=>'fa-exclamation-triangle','bg'=>'linear-gradient(135deg,#dc3545,#e83e8c)'],
      ['title'=>'Deposits','value'=>number_format($totalDeposits,2),'icon'=>'fa-coins','bg'=>'linear-gradient(135deg,#0dcaf0,#20c997)'],
      ['title'=>'Withdrawals','value'=>number_format($totalWithdrawals,2),'icon'=>'fa-money-bill-wave','bg'=>'linear-gradient(135deg,#198754,#0dcaf0)'],
      ['title'=>'Expenses','value'=>number_format($totalExpenses,2),'icon'=>'fa-file-invoice-dollar','bg'=>'linear-gradient(135deg,#6c757d,#343a40)'],
    ];
    foreach($cards as $c): ?>
      <div class="col-6 col-md-3 col-xl-3">
        <div class="card-custom text-center p-4" style="background:<?= $c['bg'] ?>; color:#fff;">
          <i class="fas <?= $c['icon'] ?> stat-icon"></i>
          <div class="hero-title"><?= e($c['title']) ?></div>
          <div class="hero-value"><?= e($c['value']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Transactions Table -->
  <div class="card card-custom p-4">
    <h5 class="mb-3"><i class="fas fa-exchange-alt me-2"></i> Last 10 Transactions</h5>
    <div class="table-responsive">
      <table class="table table-xs table-striped table-hover align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Account / Customer</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($transactions as $t): ?>
          <tr>
            <td><?= e($t['id']) ?></td>
            <td><?= e($t['customer_name'] ?? 'N/A') ?> (<?= e($t['id']) ?>)</td>
            <td class="text-capitalize"><?= e($t['type']) ?></td>
            <td><?= number_format($t['amount'],2) ?></td>
            <td><?= date('d M Y H:i', strtotime($t['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Loans Table -->
  <div class="card card-custom p-4 mt-4">
    <h5 class="mb-3"><i class="fas fa-hand-holding-dollar me-2"></i> Last 10 Loans</h5>
    <div class="table-responsive">
      <table class="table table-xs table-striped table-hover align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Loan Type</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($loans as $l): ?>
          <tr>
            <td><?= e($l['id']) ?></td>
            <td><?= e($l['customer_name'] ?? 'N/A') ?> (<?= e($l['customer_id']) ?>)</td>
            <td><?= e($l['loan_type']) ?></td>
            <td><?= number_format($l['amount'],2) ?></td>
            <td class="text-capitalize"><?= e($l['status']) ?></td>
            <td><?= date('d M Y H:i', strtotime($l['applied_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<footer class="text-center mt-4 mb-4 small text-muted">&copy; <?= date('Y') ?> FinServe Bank — Reports</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
