<?php
session_start();

// ✅ Only allow logged-in customers
if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    header('Location: ../login.php');
    exit;
}

$cid = $_SESSION['customer']['id'];

// Static demo FD info
$fd_details = [
    'amount' => 100000,  // deposit amount
    'interest_rate' => 7.5, // per annum
    'start_date' => '2025-01-01',
    'maturity_date' => '2026-01-01',
    'tenure_years' => 1
];

// Calculate maturity
$principal = $fd_details['amount'];
$rate = $fd_details['interest_rate'];
$years = $fd_details['tenure_years'];

$interest = ($principal * $rate * $years) / 100;
$maturity_amount = $principal + $interest;
?>

<style>
:root {
  --bg-gradient: linear-gradient(135deg, #1e0b73ff, #059590ff); /* vibrant background */
  --card-bg: rgba(255,255,255,0.95);
  --primary: #1d4ed8;
  --primary-hover: #2563eb;
  --success: #16a34a;
  --danger: #dc2626;
  --text: #111827;
  --muted: #6b7280;
  --border: #cfd8dc;
  --radius:15px;
  --max-width:900px;
  font-family:'Inter', 'Segoe UI', Roboto, sans-serif;
}

body {
  background: var(--bg-gradient);
  color: var(--text);
  margin:0;
  padding:24px;
  min-height:100vh;
  display:flex;
  flex-direction:column;
}

.container { 
  max-width: var(--max-width); 
  margin:0 auto; 
  padding:16px; 
  flex:1;
}

h2 {
  color: #fff; 
  text-align:center; 
  margin-bottom:30px; 
  font-size:28px;
  text-shadow: 1px 1px 5px rgba(0,0,0,0.5);
}

.card {
  background: var(--card-bg);
  border-radius: var(--radius);
  box-shadow: 0 8px 25px rgba(0,0,0,0.3);
  border:1px solid rgba(0,0,0,0.1);
  overflow:hidden;
  transition: transform 0.3s, box-shadow 0.3s;
}
.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px rgba(0,0,0,0.35);
}

.card-header {
  padding:20px 24px;
  background: linear-gradient(90deg, #ff6a00, #ee0979);
  color: #fff;
  font-size:20px;
  font-weight:600;
  text-align:center;
  border-bottom:none;
}

.card-body {
  padding:24px;
}

.table-info {
  width:100%;
  border-collapse:collapse;
  margin-bottom:20px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  border-radius:10px;
  overflow:hidden;
}

.table-info th, .table-info td {
  padding:14px 12px;
  text-align:left;
  font-size:15px;
  border-bottom:1px solid rgba(0,0,0,0.1);
}

.table-info th {
  background: rgba(29,78,216,0.15);
  color: var(--primary);
  font-weight:600;
}

.table-info td {
  color: var(--text);
}

.summary {
  background: rgba(29,78,216,0.08);
  border:1px solid rgba(29,78,216,0.25);
  padding:18px;
  border-radius:12px;
  margin-top:20px;
  font-size:16px;
  text-align:center;
  font-weight:600;
  color: var(--primary);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.btn {
  padding:12px 22px;
  border-radius:12px;
  font-weight:600;
  cursor:pointer;
  border:none;
  transition:0.3s;
  text-align:center;
  display:inline-block;
  margin:10px 5px 0 5px;
}

.btn-primary { 
  background: var(--primary); 
  color:#fff; 
}
.btn-primary:hover { 
  background: var(--primary-hover); 
  transform: scale(1.05);
}

.btn-light { 
  background:#fff; 
  color: var(--text); 
  border:1px solid rgba(0,0,0,0.1); 
}
.btn-light:hover {
  background: rgba(255,255,255,0.9);
  transform: scale(1.05);
}

/* Header */
header {
    background: linear-gradient(90deg, #320d81ff, #2bacd8ff);
    color: #fff;
    padding: 15px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    border-radius: var(--radius);
    margin-bottom: 30px;
}
header .logo-section {
    display: flex;
    align-items: center;
    gap: 15px;
}
header img {
    height: 45px;
    width: 45px;
    border-radius: 50%;
    border:2px solid #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}
header h1 {
    font-size: 24px;
    font-weight: bold;
    letter-spacing: 2px;
    text-shadow: 1px 1px 5px rgba(0,0,0,0.4);
}
header .logout-btn {
    background: #ffeb3b;
    color: #111827;
    padding: 12px 22px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}
header .logout-btn:hover {
    background: #ffc107;
    transform: scale(1.05);
}

.footer {
  padding:20px;
  text-align:center;
  background: rgba(255,255,255,0.9);
  border-top:1px solid rgba(0,0,0,0.1);
  font-size:14px;
  color: var(--text);
  margin-top:auto;
}

/* Responsive */
@media(max-width:900px){
  .container{padding:12px;}
  table{font-size:14px;}
  th, td{padding:12px 8px;}
  header{flex-direction:column; gap:10px; text-align:center;}
  header .logout-btn{margin-top:10px;}
}
</style>


<!-- ✅ Header Section -->
<header>
    <div class="logo-section">
        <img src="../assets/logo.png" alt="Logo">
        <h1>Finserve</h1>
    </div>
    <a href="../index.php" class="logout-btn">Logout</a>
</header>
<div class="container">
  <h2>Fixed Deposit Interest Details</h2>

  <div class="card">
    <div class="card-header">
      <h3>FD Summary</h3>
    </div>

    <div class="card-body">
      <table class="table-info">
        <tr>
          <th>Deposit Amount</th>
          <td><?= number_format($principal, 2) ?> BDT</td>
        </tr>
        <tr>
          <th>Interest Rate</th>
          <td><?= $rate ?> % per annum</td>
        </tr>
        <tr>
          <th>Tenure</th>
          <td><?= $years ?> Year</td>
        </tr>
        <tr>
          <th>Start Date</th>
          <td><?= htmlspecialchars($fd_details['start_date']) ?></td>
        </tr>
        <tr>
          <th>Maturity Date</th>
          <td><?= htmlspecialchars($fd_details['maturity_date']) ?></td>
        </tr>
      </table>

      <div class="summary">
        <strong>Estimated Interest:</strong> <?= number_format($interest, 2) ?> BDT<br>
        <strong>Total Maturity Amount:</strong> <?= number_format($maturity_amount, 2) ?> BDT
      </div>

      <div style="text-align:center;">
        <a href="fd_withdraw.php" class="btn btn-primary">Withdraw FD</a>
        <a href="dashboard.php" class="btn btn-light">Back to Dashboard</a>
      </div>
    </div>
  </div>
</div>

<div class="footer">
  &copy; <?= date('Y') ?> FinServe Bank. All rights reserved.
</div>

