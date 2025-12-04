<?php
require '../config/db.php';


// --- Fetch totals for pie chart
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn();
$totalBalance = $pdo->query("SELECT SUM(balance) FROM accounts")->fetchColumn();

// --- Fetch monthly accounts
$monthlyData = array_fill(1,12,0);
$stmt = $pdo->query("SELECT MONTH(created_at) as month, COUNT(*) as count FROM accounts GROUP BY MONTH(created_at)");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $monthlyData[(int)$row['month']] = (int)$row['count'];
}

// --- Fetch yearly accounts
$currentYear = date('Y');
$yearlyData = [];
$startYear = $currentYear - 5;
for($y=$startYear; $y<=$currentYear; $y++){
    $yearlyData[$y] = 0;
}
$stmt = $pdo->query("SELECT YEAR(created_at) as year, COUNT(*) as count FROM accounts GROUP BY YEAR(created_at)");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $yearlyData[(int)$row['year']] = (int)$row['count'];
}

// --- Fetch top accounts by balance
$topAccountsStmt = $pdo->query("SELECT full_name, balance FROM accounts ORDER BY balance DESC LIMIT 5");
$topAccounts = $topAccountsStmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch unread messages for Account Officer (role_id 6) from Branch Manager (role_id 5)
$current_user_id = 3; // Account Officer id
$unreadMsgCount = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND sender_id=(SELECT id FROM users WHERE role_id=5 LIMIT 1) AND is_read=0");
$unreadMsgCount->execute([$current_user_id]);
$unreadCount = $unreadMsgCount->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Officer Dashboard | Finserve Bank</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
body { background:#f4f6f8; min-height:100vh; padding-bottom:80px; }

/* HEADER */
header { background:#003366; color:white; display:flex; justify-content:space-between; align-items:center; padding:15px 30px; position:fixed; width:100%; top:0; z-index:1000; box-shadow:0 4px 12px rgba(0,0,0,0.15);}
.logo { font-weight:bold; font-size:1.5rem; display:flex; align-items:center;}
.logo img { height:40px; margin-right:10px; border-radius:5px; }

/* HAMBURGER */
.hamburger { display:flex; flex-direction:column; justify-content:space-between; width:22px; height:16px; cursor:pointer; margin-left:20px; position:relative; }
.hamburger span { display:block; height:3px; background:white; border-radius:2px; }

/* Notification badge */
.hamburger .badge { position:absolute; top:-8px; right:-8px; background:red; color:white; font-size:12px; font-weight:bold; padding:2px 6px; border-radius:50%; display:flex; align-items:center; justify-content:center; }

/* DROPDOWN MENU */
.dropdown-menu { display:none; position:absolute; top:60px; right:30px; background:#004080; border-radius:8px; min-width:220px; box-shadow:0 5px 15px rgba(0,0,0,0.2); flex-direction:column; z-index:1001; }
.dropdown-menu a { color:white; padding:12px 18px; text-decoration:none; font-weight:500; display:block; transition:0.3s; }
.dropdown-menu a:hover { background:#0066cc; }

/* DASHBOARD CHARTS */
.charts-container { 
    display:grid; 
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
    gap:20px; 
    margin-top:20px; 
    padding:20px 30px; 
}
.chart-box { background:white; padding:20px; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.1); text-align:center; }
.chart-label { margin-top:10px; font-weight:bold; color:#003366; }

/* CUSTOMER TABLE */
.customer-section { max-width:95%; margin:20px auto; overflow-x:auto; }
table { width:100%; border-collapse:collapse; background:white; box-shadow:0 5px 20px rgba(0,0,0,0.1); border-radius:8px; }
table th, table td { padding:12px 15px; border:1px solid #ddd; text-align:center; }
table th { background:#003366; color:white; }
table tr:nth-child(even) { background:#f0f6fb; }

/* TOP ACCOUNTS TABLE */
.top-accounts { max-width:95%; margin:20px auto; overflow-x:auto; }
.top-accounts table { width:100%; border-collapse:collapse; background:white; box-shadow:0 5px 20px rgba(0,0,0,0.1); border-radius:8px; }
.top-accounts th, .top-accounts td { padding:12px 15px; border:1px solid #ddd; text-align:center; }
.top-accounts th { background:#003366; color:white; }

/* FOOTER */
footer { background:#003366; color:white; text-align:center; padding:20px 10px; position:fixed; bottom:0; width:100%; }
footer a { color:#00ccff; text-decoration:none; font-weight:500; }
footer a:hover { text-decoration:underline; }
</style>
</head>
<body>

<header>
    <div class="logo">
        <img src="../assets/logo.png" alt="Finserve Logo"> Finserve Bank
    </div>
    <div class="hamburger" id="menuToggle">
        <span></span>
        <span></span>
        <span></span>
        <?php if($unreadCount>0): ?>
            <div class="badge" id="msgBadge"><?= $unreadCount ?></div>
        <?php endif; ?>
    </div>

    <div class="dropdown-menu" id="dropdownMenu">
        <a href="profile.php">Profile</a>
        <a href="open_account.php">Open New Account</a>
        <a href="view_accounts.php">View Account</a>
        <a href="chat.php">Messages</a>
        <a href="../admin/index.php">Logout</a>
    </div>
</header>

<!-- Dashboard Overview -->
<div style="margin-top:80px; text-align:center; font-size:1.8rem; font-weight:bold; color:#003366;">
    Dashboard Overview
</div>

<div class="charts-container">
    <div class="chart-box">
        <canvas id="monthlyChart"></canvas>
        <div class="chart-label">Monthly New Accounts</div>
    </div>
    <div class="chart-box">
        <canvas id="yearlyChart"></canvas>
        <div class="chart-label">Yearly New Accounts</div>
    </div>
    <div class="chart-box">
        <canvas id="pieChart"></canvas>
        <div class="chart-label">Accounts vs Total Balance</div>
    </div>
</div>

<!-- Total Customers Table -->
<div class="customer-section">
    <h3 style="color:#003366; margin-bottom:15px;">Total Customers</h3>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Account Open Date</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->query("SELECT * FROM accounts ORDER BY created_at DESC");
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                echo "<tr>
                    <td>{$row['full_name']}</td>
                    <td>{$row['phone']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['created_at']}</td>
                    <td>{$row['balance']}</td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Top Accounts Table -->
<div class="top-accounts">
    <h3 style="color:#003366; margin-bottom:15px;">Top Accounts (By Balance)</h3>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($topAccounts as $acc): ?>
            <tr>
                <td><?= $acc['full_name'] ?></td>
                <td><?= $acc['balance'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<footer>
    <p>&copy; <?=date('Y')?> Finserve Bank. All Rights Reserved.</p>
    <p>Developed by <a href="#">Nayma Jahan Chowdhury</a></p>
</footer>

<script>
const menuToggle = document.getElementById('menuToggle');
const dropdownMenu = document.getElementById('dropdownMenu');
menuToggle.addEventListener('click', ()=>{
    dropdownMenu.style.display = dropdownMenu.style.display==='flex' ? 'none':'flex';
    dropdownMenu.style.flexDirection='column';
});
window.addEventListener('click', function(e){
    if(!menuToggle.contains(e.target) && !dropdownMenu.contains(e.target)){
        dropdownMenu.style.display='none';
    }
});

// --- AJAX to refresh unread messages badge every 5 sec
function refreshBadge(){
    fetch('check_unread.php') // create a separate PHP to return unread count
    .then(res => res.text())
    .then(count=>{
        let badge = document.getElementById('msgBadge');
        if(count>0){
            if(!badge){
                badge = document.createElement('div');
                badge.id='msgBadge';
                badge.className='badge';
                menuToggle.appendChild(badge);
            }
            badge.innerText = count;
        }else{
            if(badge) badge.remove();
        }
    });
}
setInterval(refreshBadge,5000);

// --- Charts
new Chart(document.getElementById('monthlyChart').getContext('2d'), {
    type:'bar',
    data:{
        labels:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets:[{
            label:'New Accounts',
            data:[<?=implode(',', $monthlyData)?>],
            backgroundColor:'#00ccff',
            borderRadius:6
        }]
    },
    options:{ responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
});

new Chart(document.getElementById('yearlyChart').getContext('2d'), {
    type:'line',
    data:{
        labels:[<?=implode(',', array_keys($yearlyData))?>],
        datasets:[{
            label:'New Accounts',
            data:[<?=implode(',', array_values($yearlyData))?>],
            borderColor:'#003366',
            backgroundColor:'rgba(0,51,102,0.3)',
            fill:true,
            tension:0.3
        }]
    },
    options:{ responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
});

new Chart(document.getElementById('pieChart').getContext('2d'), {
    type:'pie',
    data:{
        labels:['Total Accounts','Total Balance'],
        datasets:[{
            data:[<?= $totalCustomers ?>, <?= $totalBalance ?>],
            backgroundColor:['#003366','#00ccff'],
            hoverOffset:8
        }]
    },
    options:{ responsive:true, plugins:{legend:{position:'bottom'}} }
});
</script>

</body>
</html>
