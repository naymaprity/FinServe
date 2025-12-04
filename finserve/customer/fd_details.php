<?php
session_start();


// ✅ Only allow logged-in customers
if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    header('Location: ../login.php');
    exit;
}
?>

<style>
:root {
  --bg-gradient: linear-gradient(135deg, #2f9ee8ff, #dcee83ff); /* colorful background */
  --card-bg: rgba(255,255,255,0.9); /* card slightly white for readability */
  --primary: #1d4ed8;
  --primary-hover: #2563eb;
  --accent: #ff6f61;
  --text: #111827;
  --muted: #f3f4f6;
  --border: #cfd8dc;
  --radius: 15px;
  --max-width: 900px;
  font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
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

.container { max-width: var(--max-width); margin:0 auto; padding:12px; flex:1; }

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
  overflow:hidden;
  border:1px solid rgba(255,255,255,0.3);
  margin-bottom:30px;
  transition: transform 0.3s, box-shadow 0.3s;
}
.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px rgba(0,0,0,0.35);
}

.card-header {
  padding:20px 24px;
  border-bottom:1px solid rgba(0,0,0,0.1);
  background: linear-gradient(90deg, #ff6a00, #ee0979); /* colorful header */
  color: #fff;
}
.card-header h3 { 
  margin:0; 
  font-size:20px; 
  letter-spacing:1px;
}

.card-body { padding:20px 24px; }

table {
  width:100%;
  border-collapse: collapse;
  font-size: 14px;
}

th, td {
  padding:14px 10px;
  text-align:center;
  border-bottom:1px solid rgba(0,0,0,0.1);
  transition: background 0.3s;
}

th {
  background: linear-gradient(90deg, #1d4ed8, #2563eb); /* gradient header */
  color:#fff;
  text-transform: uppercase;
  font-size: 13px;
  letter-spacing:1px;
}

tr:hover td { 
  background: rgba(255,255,255,0.3); 
  color: #fff;
  font-weight: 600;
  cursor:pointer;
}

td:first-child { font-weight: bold; color: #e93209ff; }

.muted { color: rgba(255,255,255,0.7); font-size:13px; }

/* Header Styling */
header {
    background: linear-gradient(90deg, #1d0465ff, #5843e2ff);
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
  table{font-size:13px;}
  th, td{padding:12px 6px;}
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
    <h2>Fixed Deposit Details</h2>

    <?php
    // Static/demo FD information
    $fds = [
        [
            'amount' => 50000,
            'interest_rate' => 7.5,
            'start_date' => '2025-01-01',
            'maturity_date' => '2026-01-01',
            'status' => 'Active'
        ],
        [
            'amount' => 100000,
            'interest_rate' => 8.0,
            'start_date' => '2024-06-15',
            'maturity_date' => '2025-06-15',
            'status' => 'Closed'
        ],
        [
            'amount' => 75000,
            'interest_rate' => 7.8,
            'start_date' => '2025-03-20',
            'maturity_date' => '2026-03-20',
            'status' => 'Active'
        ]
    ];
    ?>

    <div class="card">
        <div class="card-header">
            <h3>Your Fixed Deposits</h3>
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>Amount (BDT)</th>
                        <th>Interest Rate (%)</th>
                        <th>Start Date</th>
                        <th>Maturity Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($fds as $fd): ?>
                    <tr>
                        <td><?= number_format($fd['amount'],2) ?></td>
                        <td><?= $fd['interest_rate'] ?></td>
                        <td><?= $fd['start_date'] ?></td>
                        <td><?= $fd['maturity_date'] ?></td>
                        <td><?= $fd['status'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
        </div>
    </div>
</div>



<?php require '../includes/footer.php'; ?>
