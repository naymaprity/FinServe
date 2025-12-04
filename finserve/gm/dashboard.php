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
$totalCustomers = fetchValue($pdo, "SELECT COUNT(*) FROM customers");
$totalAccounts  = fetchValue($pdo, "SELECT COUNT(*) FROM accounts");
$totalDeposits  = fetchValue($pdo, "SELECT SUM(amount) FROM transactions WHERE type='deposit'");
$totalLoans     = fetchValue($pdo, "SELECT COUNT(*) FROM loans");
$pendingApprovals = fetchValue($pdo, "SELECT COUNT(*) FROM loans WHERE status = 'pending'");

// Staff overview (exclude GM, MD, DMD)
$staff = $pdo->query("SELECT id, full_name, role_name, email 
    FROM users 
    WHERE role_name NOT IN ('GM','MD','DMD') 
    ORDER BY role_name, full_name")->fetchAll(PDO::FETCH_ASSOC);

// Charts data
$monthLabels = [];
$depositData = [];
$loanData = [];
for ($i=5; $i>=0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $monthLabels[] = date('M Y', strtotime($m));
    $depositData[] = fetchValue($pdo, "SELECT SUM(amount) FROM transactions WHERE type='deposit' AND DATE_FORMAT(created_at,'%Y-%m') = ?", [$m]);
    $loanData[]    = fetchValue($pdo, "SELECT COUNT(*) FROM loans WHERE DATE_FORMAT(created_at,'%Y-%m') = ?", [$m]);
}

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GM Dashboard — FinServe Bank</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
body { background:#f5f6fa; font-family: 'Inter', sans-serif; }
.navbar { box-shadow:0 2px 6px rgba(0,0,0,.05); }
.hero-card { border-radius:12px; color:#fff; padding:25px 20px; box-shadow:0 4px 12px rgba(0,0,0,.07); transition: transform 0.3s; cursor:pointer; text-align:center; }
.hero-card:hover { transform: translateY(-5px); box-shadow:0 8px 25px rgba(0,0,0,0.15); }
.card-custom { border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,.05); }
.stat-icon { font-size:2rem; opacity:0.9; margin-bottom:10px; }
.hero-title { font-size:1rem; font-weight:600; opacity:0.9; color:#fff; margin-bottom:5px; }
.hero-value { font-size:1.6rem; font-weight:700; color:#fff; }
.profile-dropdown { cursor:pointer; }
.small-muted { color:#6c757d; font-size:.85rem; }
.table-xs td, .table-xs th { padding:.4rem .6rem; }
.section-card { margin-bottom:15px; }
</style>
</head>
<body>
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-light bg-white px-4 py-2">
  <a class="navbar-brand d-flex align-items-center gap-2" href="#">
    <img src="../assets/logo.png" alt="FinServe" width="36">
    <span style="font-weight:700;">FinServe Bank</span>
  </a>
  <div class="ms-auto d-flex align-items-center gap-3">
    <div class="dropdown profile-dropdown">
      <div data-bs-toggle="dropdown" class="d-flex align-items-center gap-2">
        <i class="fas fa-user-circle fa-2x text-primary"></i>
        <div class="text-end">
          <div style="font-weight:600;"><?= e($user['full_name']) ?></div>
          <div class="small-muted"><?= e($user['email'] ?? '') ?> · <?= e($user['role_name'] ?? 'GM') ?></div>
        </div>
      </div>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid mt-4">
  <!-- Hero Cards -->
  <div class="row g-3">
    <?php 
    $cards = [
      ['title'=>'Customers','value'=>$totalCustomers,'icon'=>'fa-users','bg'=>'linear-gradient(135deg,#0d6efd,#6610f2)'],
      ['title'=>'Accounts','value'=>$totalAccounts,'icon'=>'fa-wallet','bg'=>'linear-gradient(135deg,#198754,#20c997)'],
      ['title'=>'Active Loans','value'=>$totalLoans,'icon'=>'fa-hand-holding-dollar','bg'=>'linear-gradient(135deg,#fd7e14,#ffc107)'],
      ['title'=>'Pending Approvals','value'=>$pendingApprovals,'icon'=>'fa-exclamation-triangle','bg'=>'linear-gradient(135deg,#dc3545,#e83e8c)'],
      ['title'=>'Total Deposits','value'=>number_format($totalDeposits,2),'icon'=>'fa-coins','bg'=>'linear-gradient(135deg,#0dcaf0,#6610f2)'],
    ];
    foreach($cards as $c): ?>
      <div class="col-6 col-md-3 col-xl-2">
        <div class="hero-card" style="background:<?= $c['bg'] ?>;">
          <i class="fas <?= $c['icon'] ?> stat-icon"></i>
          <div class="hero-title"><?= e($c['title']) ?></div>
          <div class="hero-value"><?= e($c['value']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="row mt-4 g-3">
    <!-- Left: Chart -->
    <div class="col-12 col-xl-8">
      <div class="card card-custom p-3 section-card">
        <h6>Monthly Performance Overview</h6>
        <canvas id="monthlyChart" height="120"></canvas>
      </div>

      <!-- Staff Management List -->
      <div id="staffList" class="card card-custom p-3 section-card">
        <h6>Staff Management</h6>
        <ul class="list-group list-group-flush mt-2">
          <?php if(!$staff) echo '<li class="list-group-item small-muted">No staff to display.</li>'; 
          else foreach($staff as $s): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <div style="font-weight:600;"><?= e($s['full_name']) ?></div>
                <div class="small-muted"><?= e($s['role_name']) ?> · <?= e($s['email']) ?></div>
              </div>
              <a href="profile.php?id=<?= e($s['id']) ?>" class="btn btn-sm btn-outline-secondary">View</a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <!-- Right: Alerts & Notices -->
    <div class="col-12 col-xl-4">
      <div class="card card-custom p-3 section-card">
        <h6>Alerts & Notices</h6>
        <ul class="mt-2 small-muted">
          <li>Compliance report pending sign-off.</li>
          <li><?= e($pendingApprovals) ?> loans awaiting GM approval.</li>
          <li>IT maintenance scheduled on <?= date('F d, Y', strtotime('+3 days')) ?>.</li>
        </ul>
      </div>

      <div class="card card-custom p-3 section-card">
        <h6>Quick Actions</h6>
        <div class="d-grid gap-2 mt-2">
          <button class="btn btn-outline-info btn-sm" onclick="scrollToStaff()"><i class="fas fa-users-cog me-2"></i> Staff Management</button>
          <a href="approvals.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-check-circle me-2"></i> Approvals & Escalations</a>
          <a href="reports.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-file-alt me-2"></i> Generate Reports</a>
          <a href="compliance.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-gavel me-2"></i> Compliance Review</a>
          <a href="risk.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-shield-alt me-2"></i> Risk Assessment</a>
        </div>
      </div>
    </div>
  </div>
</div>

<footer class="text-center mt-4 mb-4 small-muted">&copy; <?= date('Y') ?> FinServe Bank — GM Dashboard</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function scrollToStaff(){
    document.getElementById('staffList').scrollIntoView({ behavior: 'smooth' });
}

const ctx = document.getElementById('monthlyChart');
if(ctx){
  new Chart(ctx, {
    type:'line',
    data:{
      labels: <?= json_encode($monthLabels) ?>,
      datasets:[
        { label:'Deposits', data: <?= json_encode($depositData) ?>, borderColor:'#0d6efd', backgroundColor:'rgba(13,110,253,0.2)', tension:0.3, fill:true },
        { label:'Loans', data: <?= json_encode($loanData) ?>, borderColor:'#dc3545', backgroundColor:'rgba(220,53,69,0.2)', tension:0.3, fill:true }
      ]
    },
    options:{
      responsive:true,
      plugins:{ legend:{ position:'top' } },
      scales:{ y:{ beginAtZero:true } }
    }
  });
}
</script>
</body>
</html>
