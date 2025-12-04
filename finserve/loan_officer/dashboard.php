<?php
session_start();
require '../config/db.php';

// 🔒 Logged-in Loan Officer Check
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    header("Location: loan_officer_login.php");
    exit;
}

$uid = $_SESSION['user']['id'];

// Officer info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 🔹 Fetch Loan Data
$totalLoans = $pdo->query("SELECT COUNT(*) FROM loans")->fetchColumn();
$approvedLoans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='Approved'")->fetchColumn();
$pendingLoans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='Pending'")->fetchColumn();
$rejectedLoans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='Rejected'")->fetchColumn();

$totalLoanAmount = $pdo->query("SELECT IFNULL(SUM(amount),0) FROM loans")->fetchColumn();
$approvedAmount = $pdo->query("SELECT IFNULL(SUM(amount),0) FROM loans WHERE status='Approved'")->fetchColumn();
$pendingAmount = $pdo->query("SELECT IFNULL(SUM(amount),0) FROM loans WHERE status='Pending'")->fetchColumn();
$rejectedAmount = $pdo->query("SELECT IFNULL(SUM(amount),0) FROM loans WHERE status='Rejected'")->fetchColumn();

$stmtToday = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE DATE(applied_at) = CURDATE()");
$stmtToday->execute();
$todayApplications = $stmtToday->fetchColumn();

// 🔹 Fetch unread messages for notification
$stmtMsg = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0");
$stmtMsg->execute([$uid]);
$unreadMessages = $stmtMsg->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Loan Officer Dashboard | FinServe</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
    --bg-color: #f8f9fc;
    --text-color: #1d1d1d;
    --card-bg: #fff;
}
body {
    background: var(--bg-color);
    color: var(--text-color);
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
}

/* Navbar */
.navbar {
    background: linear-gradient(90deg, #445f88ff, #9dc5f0ff);
    color: white;
}
.navbar-brand {
    font-weight: 600;
    color: #b8eb41ff !important;
}
.navbar .menu-wrapper {
    position: relative;
}
.menu-icon {
    cursor: pointer;
    font-size: 1.5rem;
    color: white;
}
.dropdown-menu-custom {
    display: none;
    position: absolute;
    top: 40px;
    right: 0;
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    width: 180px;
    overflow: hidden;
    z-index: 999;
}
.dropdown-menu-custom a {
    display: block;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.3s;
}
.dropdown-menu-custom a:hover {
    background: #f1f1f1;
}
.show-menu {
    display: block !important;
}

/* Notification bubble */
.notify {
    background:red;
    color:white;
    font-size:0.7rem;
    font-weight:bold;
    padding:2px 6px;
    border-radius:50%;
    position:absolute;
    top:-5px;
    right:-10px;
}

/* Cards */
.card {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 20px;
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
}
.card .icon {
    font-size: 2.5rem;
    padding: 15px;
    border-radius: 50%;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
}
.bg-total { background: linear-gradient(135deg,#007bff,#6cb2ff); }
.bg-approved { background: linear-gradient(135deg,#28a745,#5ed28a); }
.bg-pending { background: linear-gradient(135deg,#ffc107,#ffe182); color:#000; }
.bg-rejected { background: linear-gradient(135deg,#dc3545,#f87676); }

/* Chart */
.chart-card {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 20px;
    margin-top: 30px;
}

/* Footer */
footer {
    text-align: center;
    padding: 10px;
    color: #777;
    font-size: 14px;
    margin-top: 40px;
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg px-4 d-flex justify-content-between align-items-center">
  <a class="navbar-brand" href="#"><i class="fas fa-hand-holding-usd me-2"></i>FinServe Loan Panel</a>
  <div class="menu-wrapper position-relative">
      <i class="fas fa-ellipsis-v menu-icon" id="menuIcon"></i>
      <?php if($unreadMessages > 0): ?>
        <span class="notify"><?= $unreadMessages ?></span>
      <?php endif; ?>
      <div class="dropdown-menu-custom" id="menuDropdown">
          <a href="profile.php">👤 Profile</a>
          <a href="chat.php">💬 Messages <?php if($unreadMessages>0) echo "($unreadMessages)"; ?></a>
          <a href="view_loans.php">📄 View Loans</a>
          <a href="monthly_EMI_check.php">✅ Monthly EMI Check</a>
          <a href="../admin/index.php">🚪 Logout</a>
      </div>
  </div>
</nav>

<div class="container my-5">
    <h2 class="mb-4 text-center">Welcome, <?= htmlspecialchars($user['full_name']) ?> 👩‍💼</h2>

    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="card d-flex align-items-center">
                <div class="icon bg-total"><i class="fas fa-list"></i></div>
                <div>
                    <h6>Total Loans</h6>
                    <h4><?= $totalLoans ?></h4>
                    <small>Amount: <?= number_format($totalLoanAmount) ?> BDT</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card d-flex align-items-center">
                <div class="icon bg-approved"><i class="fas fa-check-circle"></i></div>
                <div>
                    <h6>Approved</h6>
                    <h4><?= $approvedLoans ?></h4>
                    <small><?= number_format($approvedAmount) ?> BDT</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card d-flex align-items-center">
                <div class="icon bg-pending"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <h6>Pending</h6>
                    <h4><?= $pendingLoans ?></h4>
                    <small><?= number_format($pendingAmount) ?> BDT</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card d-flex align-items-center">
                <div class="icon bg-rejected"><i class="fas fa-times-circle"></i></div>
                <div>
                    <h6>Rejected</h6>
                    <h4><?= $rejectedLoans ?></h4>
                    <small><?= number_format($rejectedAmount) ?> BDT</small>
                </div>
            </div>
        </div>
    </div>

    <div class="chart-card">
        <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i> Loan Applications Overview (<?= date('F Y') ?>)</h5>
        <canvas id="loanChart" height="100"></canvas>
    </div>

    <div class="text-center mt-4">
        <p><i class="fas fa-calendar-day text-primary"></i> <strong>Today's New Applications:</strong> <?= $todayApplications ?></p>
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> FinServe | All Rights Reserved
</footer>

<script>
const ctx = document.getElementById('loanChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Approved', 'Pending', 'Rejected'],
        datasets: [{
            label: 'Applications',
            data: [<?= $approvedLoans ?>, <?= $pendingLoans ?>, <?= $rejectedLoans ?>],
            backgroundColor: ['#28a745','#ffc107','#dc3545'],
            borderRadius: 10
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { ticks: { color: '#333' }, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { ticks: { color: '#333' }, grid: { color: 'rgba(0,0,0,0.05)' } }
        }
    }
});

// 🔸 Dropdown menu toggle
const menuIcon = document.getElementById('menuIcon');
const menuDropdown = document.getElementById('menuDropdown');
menuIcon.addEventListener('click', () => {
    menuDropdown.classList.toggle('show-menu');
});
// Hide dropdown when clicking outside
document.addEventListener('click', (e) => {
    if (!menuIcon.contains(e.target) && !menuDropdown.contains(e.target)) {
        menuDropdown.classList.remove('show-menu');
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
