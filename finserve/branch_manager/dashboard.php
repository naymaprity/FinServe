<?php
session_start();
require '../config/db.php';

$role = $_SESSION['user']['full_name'] ?? 'Branch Manager';
$manager_id = $_SESSION['user']['id'];

// ✅ Handle new expense submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category'], $_POST['amount'], $_POST['description'])) {
    $category = trim($_POST['category']);
    $amount = (float)$_POST['amount'];
    $description = trim($_POST['description']);
    if ($category != '' && $amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO branch_expenses (manager_id, category, amount, description, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$manager_id, $category, $amount, $description]);
    }
}

// ✅ Fetch Data
$customers = $pdo->query("SELECT * FROM customers ORDER BY id DESC LIMIT 10")->fetchAll();
$loan_status_data = $pdo->query("SELECT status, COUNT(*) as count FROM loans GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
$loan_types_data = $pdo->query("SELECT loan_type, SUM(amount) as total_amount FROM loans GROUP BY loan_type")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Branch Summary
$total_deposit = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type='deposit'")->fetchColumn() ?? 0;
$total_withdraw = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type='withdraw'")->fetchColumn() ?? 0;
$total_loans = $pdo->query("SELECT SUM(amount) FROM loans")->fetchColumn() ?? 0;
$total_expenses = $pdo->query("SELECT SUM(amount) FROM branch_expenses")->fetchColumn() ?? 0;
$net_profit = ($total_loans * 0.12) - ($total_deposit * 0.06) - $total_expenses;

// ✅ Pending Loan Requests
$pending_loans = $pdo->query("SELECT id, customer_id, amount, loan_type FROM loans WHERE status='pending' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Branch Expenses
$expenses = $pdo->prepare("SELECT * FROM branch_expenses WHERE manager_id=? ORDER BY created_at DESC");
$expenses->execute([$manager_id]);
$all_expenses = $expenses->fetchAll(PDO::FETCH_ASSOC);

// Prepare Chart Data
$loan_status_labels = json_encode(array_column($loan_status_data, 'status'));
$loan_status_counts = json_encode(array_column($loan_status_data, 'count'));
$loan_types_labels = json_encode(array_column($loan_types_data, 'loan_type'));
$loan_types_totals = json_encode(array_column($loan_types_data, 'total_amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Branch Manager Dashboard | Finserve</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { background: linear-gradient(135deg, #1E3C72, #2A5298); color:#fff; font-family:'Segoe UI',sans-serif; margin:0; padding:0; }
header { display:flex; justify-content:space-between; align-items:center; background: rgba(0,0,0,0.4); padding:12px 40px; position:fixed; top:0; left:0; right:0; z-index:1000; backdrop-filter: blur(8px); box-shadow:0 2px 10px rgba(0,0,0,0.3); }
.logo-section { display:flex; align-items:center; gap:12px; }
.logo-section img { width:45px; height:45px; border-radius:50%; }
.logo-section h1 { font-size:1.4em; color:#FFD700; letter-spacing:1px; margin:0; }
.header-links a { color:#fff; text-decoration:none; margin-left:25px; font-weight:500; transition:0.3s; }
.header-links a:hover { color:#FFD700; }
h2 { text-align:center; margin-top:90px; color:#FFD700; }
.summary-container { display:flex; justify-content:center; gap:30px; margin-top:30px; flex-wrap:wrap; }
.summary-box { background: rgba(255,255,255,0.1); border-radius:15px; padding:20px 30px; box-shadow:0 4px 12px rgba(0,0,0,0.3); text-align:center; flex:1 1 200px; max-width:230px; }
.summary-box h3 { color:#FFD700; margin-bottom:8px; font-size:1.1em; }
.summary-box p { font-size:1.3em; font-weight:bold; margin:0; }
.chart-container { width:85%; margin:30px auto; display:flex; flex-wrap:wrap; gap:20px; justify-content:center; }
.chart-box { background: rgba(255,255,255,0.1); border-radius:20px; padding:15px; box-shadow:0 8px 20px rgba(0,0,0,0.25); backdrop-filter: blur(12px); flex:1 1 250px; max-width:300px; transition: all 0.3s ease; }
.chart-box:hover { transform:scale(1.03); }
.chart-box canvas { height:180px !important; width:180px !important; }
table { width:90%; margin:20px auto; border-collapse:collapse; border-radius:10px; overflow:hidden; }
th, td { border:1px solid rgba(255,255,255,0.2); padding:12px; text-align:center; color:#fff; }
th { background: rgba(255,215,0,0.2); color:#FFD700; }
form.expense-form { width:90%; margin:20px auto; display:flex; gap:10px; flex-wrap:wrap; background: rgba(255,255,255,0.1); padding:15px; border-radius:10px; }
form.expense-form input, form.expense-form select { padding:10px; border-radius:6px; border:none; flex:1; }
form.expense-form button { padding:10px 15px; border:none; border-radius:6px; background:#FFD700; color:#000; font-weight:bold; cursor:pointer; transition:0.2s; }
form.expense-form button:hover { background:#FFC200; }
.notice-board { width:90%; margin:40px auto; background: rgba(255,255,255,0.1); border-radius:15px; padding:20px; }
.notice-board h3 { color:#FFD700; margin-bottom:10px; }
</style>
</head>
<body>

<header>
    <div class="logo-section">
        <img src="../assets/logo.png" alt="Finserve Logo">
        <h1>Finserve Bank</h1>
    </div>
    <div class="header-links">
    <a href="profile.php"><i class="ri-user-line"></i> Profile</a>

    <?php
    // Fetch unread message count for Branch Manager
    $unread_count = $pdo->query("SELECT COUNT(*) FROM messages WHERE receiver_id=$manager_id AND is_read=0")->fetchColumn() ?? 0;
    ?>
    <a href="chat.php">
        <i class="ri-chat-3-line"></i> Chat
        <?php if($unread_count > 0): ?>
            <span style="background:red;color:#fff;border-radius:50%;padding:2px 6px;font-size:0.8rem;margin-left:5px;"><?= $unread_count ?></span>
        <?php endif; ?>
    </a>

    <a href="../admin/index.php"><i class="ri-logout-box-line"></i> Logout</a>
</div>
</header>

<h2><i class="ri-bank-line"></i> Welcome, <?= htmlspecialchars($role) ?>!</h2>

<!-- Branch Summary -->
<div class="summary-container">
    <div class="summary-box"><h3>Total Deposit</h3><p>BDT <?= number_format($total_deposit,2) ?></p></div>
    <div class="summary-box"><h3>Total Withdrawal</h3><p>BDT <?= number_format($total_withdraw,2) ?></p></div>
    <div class="summary-box"><h3>Total Loans</h3><p>BDT <?= number_format($total_loans,2) ?></p></div>
    <div class="summary-box"><h3>Total Expenses</h3><p>BDT <?= number_format($total_expenses,2) ?></p></div>
    <div class="summary-box"><h3>Net Profit (Est.)</h3><p>BDT <?= number_format($net_profit,2) ?></p></div>
</div>

<!-- Charts -->
<div class="chart-container">
    <div class="chart-box"><h3>Loan Status Overview</h3><canvas id="loanStatusChart"></canvas></div>
    <div class="chart-box"><h3>Loan Amount by Type</h3><canvas id="loanTypeChart"></canvas></div>
</div>

<!-- Branch Expenses Section -->
<h2><i class="ri-file-list-3-line"></i> Branch Expenses</h2>

<!-- Add New Expense Form -->
<form class="expense-form" method="post">
    <select name="category" required>
        <option value="">Select Category</option>
        <option value="Utilities">Utilities</option>
        <option value="Salaries">Salaries</option>
        <option value="Maintenance">Maintenance</option>
        <option value="Other">Other</option>
    </select>
    <input type="number" step="0.01" name="amount" placeholder="Amount" required>
    <input type="text" name="description" placeholder="Description" required>
    <button type="submit">Add Expense</button>
</form>

<!-- All Expenses Table -->
<table>
    <tr><th>ID</th><th>Category</th><th>Amount</th><th>Description</th><th>Date</th></tr>
    <?php foreach($all_expenses as $exp): ?>
    <tr>
        <td><?= $exp['id'] ?></td>
        <td><?= htmlspecialchars($exp['category']) ?></td>
        <td>BDT <?= number_format($exp['amount'],2) ?></td>
        <td><?= htmlspecialchars($exp['description']) ?></td>
        <td><?= $exp['created_at'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<!-- Pending Loan Requests -->
<h2><i class="ri-timer-line"></i> Pending Loan Requests</h2>
<table>
    <tr><th>ID</th><th>Customer ID</th><th>Amount</th><th>Type</th><th>Status</th></tr>
    <?php foreach ($pending_loans as $loan): ?>
        <tr>
            <td><?= $loan['id'] ?></td>
            <td><?= $loan['customer_id'] ?></td>
            <td>BDT <?= number_format($loan['amount'], 2) ?></td>
            <td><?= htmlspecialchars($loan['loan_type']) ?></td>
            <td><span style="color:orange;">Pending</span></td>
        </tr>
    <?php endforeach; ?>
</table>

<!-- Notice Board -->
<div class="notice-board">
    <h3><i class="ri-notification-3-line"></i> Internal Notice Board</h3>
    <p>📌 “Monthly audit will be held on October 28. Please prepare all transaction reports.”</p>
    <p>📌 “Staff meeting scheduled for October 25 at 10 AM.”</p>
</div>

<!-- Charts Script -->
<script>
const chartColors=['#FFD700','#FF6384','#36A2EB','#4BC0C0','#9966FF','#FF9F40','#33FF99','#FF5733'];
new Chart(document.getElementById('loanStatusChart'),{
    type:'doughnut',
    data:{labels: <?= $loan_status_labels ?>, datasets:[{label:'Loan Status Count', data: <?= $loan_status_counts ?>, backgroundColor:chartColors, borderColor:'#fff', borderWidth:2, hoverOffset:12}]},
    options:{responsive:true, plugins:{legend:{position:'bottom', labels:{color:'#fff'}}}}
});
new Chart(document.getElementById('loanTypeChart'),{
    type:'bar',
    data:{labels: <?= $loan_types_labels ?>, datasets:[{label:'Total Loan Amount (BDT)', data: <?= $loan_types_totals ?>, backgroundColor:chartColors, borderRadius:8}]},
    options:{responsive:true, plugins:{legend:{display:false}}, scales:{x:{ticks:{color:'#fff'}},y:{ticks:{color:'#fff'}}}}
});
</script>

</body>
</html>
