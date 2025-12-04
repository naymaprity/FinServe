<?php
session_start();
require '../config/db.php';

// ✅ Teller Login Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 8) {
    header('Location: teller_login.php');
    exit;
}

// Fetch all customers
$customers = $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer List</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
body { font-family:'Segoe UI', sans-serif; margin:0; padding:0; background: linear-gradient(135deg,#5D54A4,#7C78B8); color:#fff; }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 40px; background: rgba(0,0,0,0.25); backdrop-filter: blur(5px); }
header h1 { margin:0; color:#FFD700; }
.container { max-width:1000px; margin:50px auto; }
.card { background: rgba(255,255,255,0.1); backdrop-filter: blur(15px); border-radius:20px; padding:25px; box-shadow:0 8px 20px rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.2); }
.card h2 { font-size:24px; margin-bottom:20px; color:#FFD700; }
table { width:100%; border-collapse: collapse; }
table th, table td { padding:12px 10px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.2); }
table th { color:#FFD700; font-weight:600; }
table td { color:#fff; }
</style>
</head>
<body>

<header>
  <h1><i class="ri-team-line"></i> Customer List</h1>
</header>

<div class="container">
  <div class="card">
    <h2>All Customers</h2>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Account Number</th>
          <th>Account Type</th>
          <th>Full Name</th>
          <th>Balance (BDT)</th>
          <th>Created At</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($customers as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['id']) ?></td>
          <td><?= htmlspecialchars($c['account_number']) ?></td>
          <td><?= htmlspecialchars($c['account_type']) ?></td>
          <td><?= htmlspecialchars($c['full_name']) ?></td>
          <td><?= number_format($c['balance'],2) ?></td>
          <td><?= htmlspecialchars($c['opened_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
