<?php
session_start();
require '../config/db.php';

// ✅ Check DB connection
try {
    $pdo->query("SELECT 1");
    $dbStatus = "Connected";
} catch (Exception $e) {
    $dbStatus = "Disconnected";
}

// ✅ Safe query helper
function safeQuery($pdo, $query) {
    try {
        return $pdo->query($query)->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// ✅ Fetch Data
$totalDeposits = safeQuery($pdo, "SELECT IFNULL(SUM(amount),0) FROM transactions WHERE type='deposit'");
$totalWithdrawals = safeQuery($pdo, "SELECT IFNULL(SUM(amount),0) FROM transactions WHERE type='withdrawal'");
$newAccountsToday = safeQuery($pdo, "SELECT COUNT(*) FROM accounts WHERE DATE(created_at)=CURDATE()");
$totalAccounts = safeQuery($pdo, "SELECT COUNT(*) FROM accounts");
$failedLogins = safeQuery($pdo, "SELECT COUNT(*) FROM login_attempts WHERE success=0 AND attempt_time > NOW() - INTERVAL 24 HOUR");
$successfulLoginsToday = safeQuery($pdo, "SELECT COUNT(*) FROM login_attempts WHERE success=1 AND DATE(attempt_time)=CURDATE()");
$totalFailedTransactions = safeQuery($pdo, "SELECT COUNT(*) FROM transactions WHERE status='failed'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IT Officer Dashboard | FinServe</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body { background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: #fff; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
.navbar { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.2); }
.navbar-brand { font-weight: 600; color: #00e6ac !important; }
.logout-btn { color: #fff; background: #dc3545; border: none; border-radius: 25px; padding: 5px 15px; transition: 0.3s; }
.logout-btn:hover { background: #ff4d6d; }
.card { background: rgba(255,255,255,0.1); backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.2); border-radius: 15px; color: #fff; transition: all 0.3s ease; }
.card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
.icon { font-size: 2.5rem; color: #fff; padding: 15px; border-radius: 50%; }
.bg-deposit { background: linear-gradient(135deg, #28a745, #5ed28a); }
.bg-withdraw { background: linear-gradient(135deg, #dc3545, #f87676); }
.bg-accounts { background: linear-gradient(135deg, #007bff, #6cb2ff); }
.bg-failed { background: linear-gradient(135deg, #ffc107, #ffe182); color: #000; }
.bg-successful { background: linear-gradient(135deg, #17a2b8, #5dc0de); }
h2,h4,h5 { font-weight: 600; }
.chart-card { background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border-radius: 15px; padding: 20px; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark px-4">
  <a class="navbar-brand" href="#"><i class="fas fa-shield-alt me-2"></i>FinServe IT Panel</a>
  <div class="ms-auto d-flex align-items-center gap-2">
    <a href="profile.php" class="btn btn-info" style="border-radius:25px;padding:5px 15px;"><i class="fas fa-user me-1"></i> Profile</a>
    <a href="chat.php" class="btn btn-success position-relative" style="border-radius:25px;padding:5px 15px;"><i class="fas fa-comments me-1"></i> Chat
      <span id="chatNotify" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="display:none;"></span>
    </a>
    <a href="../admin/index.php" class="logout-btn"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
  </div>
</nav>

<script>
function checkChatNotifications(){
    fetch('chat.php?check_new=1')
    .then(res => res.text())
    .then(count => {
        const notifyDot = document.getElementById('chatNotify');
        notifyDot.style.display = (parseInt(count) > 0) ? 'inline-block' : 'none';
    });
}
setInterval(checkChatNotifications, 3000);
checkChatNotifications();
</script>

<div class="container my-5">
    <h2 class="mb-4 text-center">Welcome, IT Officer 👩‍💻</h2>

    <!-- Cards Row -->
    <div class="row g-4">
        <?php
        $cards = [
            ['title'=>'DB Status','value'=>$dbStatus,'icon'=>'fa-database','bg'=>'bg-accounts'],
            ['title'=>'Total Deposits','value'=>number_format($totalDeposits),'icon'=>'fa-money-bill-wave','bg'=>'bg-deposit'],
            ['title'=>'Total Withdrawals','value'=>number_format($totalWithdrawals),'icon'=>'fa-hand-holding-dollar','bg'=>'bg-withdraw'],
            ['title'=>'New Accounts (Today)','value'=>$newAccountsToday,'icon'=>'fa-user-plus','bg'=>'bg-accounts'],
            ['title'=>"Today's Successful Logins",'value'=>$successfulLoginsToday,'icon'=>'fa-sign-in-alt','bg'=>'bg-successful'],
            ['title'=>'Failed Logins (24h)','value'=>$failedLogins,'icon'=>'fa-exclamation-triangle','bg'=>'bg-failed'],
            ['title'=>'Total Accounts','value'=>$totalAccounts,'icon'=>'fa-users','bg'=>'bg-accounts'],
            ['title'=>'Failed Transactions','value'=>$totalFailedTransactions,'icon'=>'fa-times-circle','bg'=>'bg-failed'],
        ];

        foreach($cards as $c){
            echo '<div class="col-md-4 col-lg-3">
                    <div class="card p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon '.$c['bg'].' me-3"><i class="fas '.$c['icon'].'"></i></div>
                            <div>
                                <h6>'.$c['title'].'</h6>
                                <h4>'.$c['value'].'</h4>
                            </div>
                        </div>
                    </div>
                  </div>';
        }
        ?>
    </div>

    <!-- Chart Section -->
    <div class="row mt-5">
        <div class="col-md-12">
            <div class="chart-card">
                <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Daily Transactions Overview</h5>
                <canvas id="transactionChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('transactionChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Deposits','Withdrawals','New Accounts'],
        datasets:[{
            label:'Amount / Count',
            data:[<?= (float)$totalDeposits ?>, <?= (float)$totalWithdrawals ?>, <?= (int)$newAccountsToday ?>],
            backgroundColor:['#28a745','#dc3545','#007bff'],
            borderRadius:10
        }]
    },
    options:{
        responsive:true,
        plugins:{legend:{display:false}},
        scales:{
            y:{ticks:{color:'#fff'},grid:{color:'rgba(255,255,255,0.1)'}},
            x:{ticks:{color:'#fff'},grid:{color:'rgba(255,255,255,0.1)'}}
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
