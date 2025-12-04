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

// Example dynamic stats
$totalCustomers   = fetchValue($pdo, "SELECT COUNT(*) FROM customers");
$totalLoans       = fetchValue($pdo, "SELECT COUNT(*) FROM loans");
$pendingLoans     = fetchValue($pdo, "SELECT COUNT(*) FROM loans WHERE status='pending'");

// Dynamic Notices
$notices = [
    "Quarterly audit scheduled on ".date('F d, Y', strtotime('+7 days')),
    "Update customer KYC documents before ".date('F t, Y'), // last day of current month
    "IT maintenance scheduled on ".date('F d, Y', strtotime('+3 days')),
];

// Optional: you can fetch more notices from DB if you create a table for it

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Compliance — FinServe Bank</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
body { background:#f5f6fa; font-family: 'Inter', sans-serif; }
.navbar { box-shadow:0 2px 6px rgba(0,0,0,.05); }
.card-custom { border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,.05); margin-bottom:15px; }
.notice-card { background: #fff3cd; border-left: 5px solid #ffc107; }
.notice-title { font-weight:600; font-size:1rem; }
.notice-text { font-size:.9rem; color:#856404; }
.stats-card { background: linear-gradient(135deg,#0d6efd,#6610f2); color:#fff; }
.stats-title { font-size:0.9rem; opacity:0.85; }
.stats-value { font-size:1.5rem; font-weight:700; }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white px-4 py-2">
  <a class="navbar-brand d-flex align-items-center gap-2" href="#">
    <img src="../assets/logo.png" alt="FinServe" width="36">
    <span style="font-weight:700;">FinServe Bank</span>
  </a>
</nav>

<div class="container-fluid mt-4">
  <!-- Compliance Stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card card-custom p-3 stats-card text-center">
        <div class="stats-title">Total Customers</div>
        <div class="stats-value"><?= e($totalCustomers) ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-custom p-3 stats-card text-center">
        <div class="stats-title">Total Loans</div>
        <div class="stats-value"><?= e($totalLoans) ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-custom p-3 stats-card text-center">
        <div class="stats-title">Pending Loans</div>
        <div class="stats-value"><?= e($pendingLoans) ?></div>
      </div>
    </div>
  </div>

  <!-- Compliance Notices -->
  <div class="row g-3">
    <?php foreach($notices as $n): ?>
      <div class="col-12 col-md-6">
        <div class="card card-custom p-3 notice-card">
          <div class="notice-title"><i class="fas fa-exclamation-circle me-2"></i>Notice</div>
          <div class="notice-text"><?= e($n) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<footer class="text-center mt-4 mb-4 small-muted">&copy; <?= date('Y') ?> FinServe Bank — Compliance</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
