<?php
session_start();
require '../config/db.php';

// DMD session
$user = $_SESSION['user'] ?? ['full_name'=>'DMD','email'=>'dmd@finserve.com','role_name'=>'DMD'];

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
$totalDeposits    = fetchValue($pdo, "SELECT SUM(amount) FROM transactions WHERE type='deposit'");
$totalLoans       = fetchValue($pdo, "SELECT COUNT(*) FROM loans");
$pendingApprovals = fetchValue($pdo, "SELECT COUNT(*) FROM loans WHERE status='pending'");

// Staff overview (exclude DMD, MD, GM)
$staff = $pdo->query("SELECT full_name, role_name, email FROM users WHERE role_name NOT IN ('GM','MD','DMD') ORDER BY role_name, full_name")->fetchAll(PDO::FETCH_ASSOC);

// Dynamic notices
$notices = [
    "Quarterly audit scheduled on ".date('F d, Y', strtotime('+7 days')),
    "Update customer KYC documents before ".date('F t, Y'),
    "IT maintenance scheduled on ".date('F d, Y', strtotime('+3 days')),
];

// Fetch monthly salary for graph and table
$monthlySalaryData = $pdo->query("SELECT pay_month, SUM(total_salary) AS total FROM monthly_salary GROUP BY pay_month ORDER BY pay_month ASC")->fetchAll(PDO::FETCH_ASSOC);

// Prepare graph data
$graphMonths = [];
$graphSalaries = [];
foreach($monthlySalaryData as $row) {
    $graphMonths[] = $row['pay_month'];
    $graphSalaries[] = $row['total'];
}

// Fetch staff monthly salary table
$staffSalary = $pdo->query("SELECT employee_role, basic_salary, hra, allowances, deductions, total_salary, pay_month FROM monthly_salary ORDER BY pay_month DESC")->fetchAll(PDO::FETCH_ASSOC);

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DMD Dashboard — FinServe Bank</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { background:#f5f6fa; font-family: 'Inter', sans-serif; }
.navbar { box-shadow:0 2px 6px rgba(0,0,0,.05); }
.hero-card { 
    border-radius:12px; 
    color:#fff; 
    padding:20px; 
    box-shadow:0 4px 10px rgba(0,0,0,.05); 
    transition: transform 0.3s; 
    cursor:pointer; 
    display: flex; 
    flex-direction: column; 
    justify-content: center; 
    align-items: center; 
    min-height:120px;
    text-align: center;
}
.stat-icon { font-size:2rem; margin-bottom:10px; }
.hero-title { font-size:0.9rem; opacity:0.85; font-weight:600; margin-bottom:5px; }
.hero-value { font-size:1.6rem; font-weight:700; }
.hero-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }

.small-muted { color:#6c757d; font-size:.85rem; }
.card-custom { border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,.05); margin-bottom:15px; }
.notice-card { background: #fff3cd; border-left: 5px solid #ffc107; margin-bottom:10px; }
.notice-title { font-weight:600; font-size:1rem; }
.notice-text { font-size:.9rem; color:#856404; }
.table-xs td, .table-xs th { padding:.4rem .6rem; font-size:.85rem; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white px-4 py-2">
  <a class="navbar-brand d-flex align-items-center gap-2" href="#">
    <img src="../assets/logo.png" alt="FinServe" width="36">
    <span style="font-weight:700;">FinServe Bank</span>
  </a>
  <div class="ms-auto d-flex align-items-center gap-3">
    <div class="dropdown">
      <div data-bs-toggle="dropdown" class="d-flex align-items-center gap-2 cursor-pointer">
        <i class="fas fa-user-circle fa-2x text-primary"></i>
        <div class="text-end">
          <div style="font-weight:600;"><?= e($user['full_name']) ?></div>
          <div class="small-muted"><?= e($user['email'] ?? '') ?> · <?= e($user['role_name'] ?? 'DMD') ?></div>
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
      <div class="col-6 col-md-3">
        <div class="hero-card text-center" style="background:<?= $c['bg'] ?>;">
          <i class="fas <?= $c['icon'] ?> stat-icon"></i>
          <div class="hero-title"><?= e($c['title']) ?></div>
          <div class="hero-value"><?= e($c['value']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Salary Graph -->
  <div class="row mt-4">
    <div class="col-12 col-xl-12">
      <div class="card card-custom p-3">
        <h6>Monthly Salary Graph</h6>
        <canvas id="salaryChart" height="100"></canvas>
      </div>
    </div>
  </div>

  <!-- Staff Salary Table & Key Staff + Notices -->
  <div class="row mt-4 g-3">
    <!-- Staff Salary Table -->
    <div class="col-12 col-xl-6">
      <div class="card card-custom p-3">
        <h6>Staff Monthly Salary</h6>
        <div class="table-responsive">
          <table class="table table-xs table-striped table-hover">
            <thead>
              <tr>
                <th>Role</th>
                <th>Basic</th>
                <th>HRA</th>
                <th>Allowances</th>
                <th>Deductions</th>
                <th>Total</th>
                <th>Month</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($staffSalary as $s): ?>
              <tr>
                <td><?= e($s['employee_role']) ?></td>
                <td><?= number_format($s['basic_salary'],2) ?></td>
                <td><?= number_format($s['hra'],2) ?></td>
                <td><?= number_format($s['allowances'],2) ?></td>
                <td><?= number_format($s['deductions'],2) ?></td>
                <td><?= number_format($s['total_salary'],2) ?></td>
                <td><?= e($s['pay_month']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Key Staff & Notices -->
    <div class="col-12 col-xl-6">
      <!-- Key Staff -->
      <div class="card card-custom p-3 mb-3">
        <h6>Key Staff</h6>
        <ul class="list-group list-group-flush mt-2">
          <?php if(!$staff) echo '<li class="list-group-item small-muted">No staff to display.</li>'; 
          else foreach($staff as $s): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <div style="font-weight:600;"><?= e($s['full_name']) ?></div>
                <div class="small-muted"><?= e($s['role_name'] ?? 'Staff') ?> · <?= e($s['email']) ?></div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Compliance Notices -->
      <div class="card card-custom p-3">
        <h6>Compliance & Alerts</h6>
        <?php foreach($notices as $n): ?>
          <div class="notice-card p-2 mt-2">
            <div class="notice-title"><i class="fas fa-exclamation-circle me-2"></i> Notice</div>
            <div class="notice-text"><?= e($n) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<footer class="text-center mt-4 mb-4 small-muted">&copy; <?= date('Y') ?> FinServe Bank — DMD Dashboard</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('salaryChart').getContext('2d');
const salaryChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($graphMonths) ?>,
        datasets: [{
            label: 'Total Monthly Salary',
            data: <?= json_encode($graphSalaries) ?>,
            backgroundColor: 'rgba(13,110,253,0.2)',
            borderColor: 'rgba(13,110,253,1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true,
            pointRadius:4,
            pointHoverRadius:6
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display:true },
            tooltip: { mode:'index', intersect:false }
        },
        scales: {
            y: { beginAtZero:true },
            x: { title: { display:true, text:'Month' } }
        }
    }
});
</script>

</body>
</html>
