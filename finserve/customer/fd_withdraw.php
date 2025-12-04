<?php
session_start();

// ✅ Only allow logged-in customers
if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    header('Location: ../login.php');
    exit;
}

$cid = $_SESSION['customer']['id'];

// CSRF token
if (empty($_SESSION['csrf_fd_withdraw'])) {
    $_SESSION['csrf_fd_withdraw'] = bin2hex(random_bytes(32));
}

// Static/demo FD info
$fds = [
    [
        'id'=>1,
        'amount'=>50000,
        'interest_rate'=>7.5,
        'start_date'=>'2025-01-01',
        'maturity_date'=>'2026-01-01',
        'status'=>'Active'
    ],
    [
        'id'=>2,
        'amount'=>75000,
        'interest_rate'=>7.8,
        'start_date'=>'2025-03-20',
        'maturity_date'=>'2026-03-20',
        'status'=>'Active'
    ]
];

$err = '';
$success = '';
$withdrawnFD = null;

// Handle POST withdrawal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_fd_withdraw'] ?? '';
    $fd_id = intval($_POST['fd_id'] ?? 0);

    if (!hash_equals($_SESSION['csrf_fd_withdraw'], $token)) {
        $err = 'Invalid form submission.';
    } else {
        // Find FD by ID
        foreach($fds as $fd) {
            if($fd['id'] === $fd_id && $fd['status'] === 'Active') {
                $withdrawnFD = $fd;
                break;
            }
        }

        if(!$withdrawnFD) {
            $err = 'Selected FD is not available for withdrawal.';
        } else {
            $success = 'FD Withdrawal successful! Amount: ' . number_format($withdrawnFD['amount'],2) . ' BDT will be credited to your account.';
            $_SESSION['csrf_fd_withdraw'] = bin2hex(random_bytes(32));
        }
    }
}
?>

<style>
:root {
  --bg-gradient: linear-gradient(135deg, #2a0d7aff, #63dee2ff); /* vibrant background */
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
  border:1px solid rgba(0,0,0,0.1);
  margin-bottom:30px;
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
  font-size:18px;
  font-weight:600;
}

.card-body { padding:20px 24px; }

.alert { padding:12px 16px; border-radius:12px; font-size:14px; margin-bottom:16px; font-weight:500; }
.alert-success { background: rgba(22,163,74,0.15); color: var(--success); border:1px solid rgba(22,163,74,0.4); }
.alert-error { background: rgba(220,38,38,0.15); color: var(--danger); border:1px solid rgba(220,38,38,0.4); }

table {
  width:100%;
  border-collapse:collapse;
  margin-bottom:20px;
  border-radius: var(--radius);
  overflow:hidden;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

th, td {
  padding:14px 10px;
  text-align:center;
  border-bottom:1px solid rgba(0,0,0,0.1);
  transition: background 0.3s, color 0.3s;
}

th {
  background: linear-gradient(90deg, #1d4ed8, #2563eb);
  color:#fff;
  text-transform: uppercase;
  font-size:13px;
  letter-spacing:1px;
}

tr:hover td {
  background: rgba(29,78,216,0.1);
  font-weight:600;
  color: #1d4ed8;
}

td:first-child { font-weight:bold; color: #ff5722; }

.form-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:12px; justify-content:center; }
.btn { padding:12px 20px; border-radius:10px; font-weight:600; cursor:pointer; border:none; transition:0.3s; text-align:center; display:inline-block; }
.btn-primary { background: var(--primary); color:#fff; }
.btn-primary:hover { background: var(--primary-hover); transform: scale(1.05);}
.btn-light { background:#fff; color:var(--text); border:1px solid var(--border); }

.muted { color: var(--muted); font-size:13px; }

/* Header */
header {
    background: linear-gradient(90deg, #0e0759ff, #18aec8ff);
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
  <h2>Fixed Deposit Withdrawal</h2>

  <div class="card">
    <div class="card-header"><h3>Select FD to Withdraw</h3></div>
    <div class="card-body">

      <?php if($err): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <?php if($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <?php if(!$success): ?>
      <form method="POST" novalidate>
        <input type="hidden" name="csrf_fd_withdraw" value="<?= htmlspecialchars($_SESSION['csrf_fd_withdraw']) ?>">

        <table>
          <thead>
            <tr>
              <th>Amount (BDT)</th>
              <th>Interest Rate (%)</th>
              <th>Start Date</th>
              <th>Maturity Date</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($fds as $fd): ?>
              <?php if($fd['status'] === 'Active'): ?>
                <tr>
                  <td><?= number_format($fd['amount'],2) ?></td>
                  <td><?= $fd['interest_rate'] ?></td>
                  <td><?= $fd['start_date'] ?></td>
                  <td><?= $fd['maturity_date'] ?></td>
                  <td><?= $fd['status'] ?></td>
                  <td>
                    <button type="submit" name="fd_id" value="<?= $fd['id'] ?>" class="btn btn-primary">Withdraw</button>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </form>
      <?php else: ?>
        <div class="form-actions">
          <a href="dashboard.php" class="btn btn-primary">Check Balance</a>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>



<?php require '../includes/footer.php'; ?>
