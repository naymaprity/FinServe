<?php
session_start();
require '../config/db.php';

// MD session
$user = $_SESSION['user'] ?? ['full_name'=>'MD','email'=>'md@finserve.com','role_name'=>'MD'];

// Search filters
$searchSection = $_GET['section'] ?? '';
$customerId = $_GET['customer_id'] ?? '';
$accountId  = $_GET['account_id'] ?? '';
$loanId     = $_GET['loan_id'] ?? '';
$staffId    = $_GET['staff_id'] ?? '';
$depositId  = $_GET['deposit_id'] ?? '';

// Section list for dropdown / auto-suggest
$sections = ['Customers', 'Accounts', 'Active Loans', 'Total Deposits', 'Staff Monthly Salary', 'Compliance & Alerts'];

// Safe fetch helper
function fetchAll($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Customers
$customers = fetchAll($pdo, "SELECT * FROM customers ".($customerId ? "WHERE id=?" : ""), $customerId ? [$customerId] : []);

// Accounts
$accounts = fetchAll($pdo, "SELECT * FROM accounts ".($accountId ? "WHERE id=?" : ""), $accountId ? [$accountId] : []);

// Loans
$loans = fetchAll($pdo, "SELECT * FROM loans ".($loanId ? "WHERE id=?" : ""), $loanId ? [$loanId] : []);

// Staff salary
$staffSalary = fetchAll($pdo, "SELECT * FROM monthly_salary ".($staffId ? "WHERE id=?" : ""), $staffId ? [$staffId] : []);

// Deposits
$deposits = fetchAll($pdo, "SELECT * FROM transactions WHERE type='deposit' ".($depositId ? "AND id=?" : ""), $depositId ? [$depositId] : []);

// Notices
$notices = [
    "Quarterly audit scheduled on ".date('F d, Y', strtotime('+7 days')),
    "Update customer KYC documents before ".date('F t, Y'),
    "IT maintenance scheduled on ".date('F d, Y', strtotime('+3 days')),
];

// Handle salary transfer
$successMsg = '';
if(isset($_POST['transfer_salary'])) {
    // Check if today is 25th
    if(date('d') != '25') {
        $successMsg = "Salary transfer is allowed only on the 25th of each month.";
    } else {
        foreach($staffSalary as $s) {
            if(empty($s['paid_by'])) {
                // Fetch customer account using account_number
                $account = $pdo->prepare("SELECT id, balance FROM accounts WHERE account_number=? LIMIT 1");
                $account->execute([$s['account_number']]);
                $acc = $account->fetch(PDO::FETCH_ASSOC);

                if($acc) {
                    $newBalance = $acc['balance'] + $s['total_salary'];

                    // Update accounts table
                    $upd_acc = $pdo->prepare("UPDATE accounts SET balance=? WHERE id=?");
                    $upd_acc->execute([$newBalance, $acc['id']]);

                    // Update customers table balance
                    $upd_cust = $pdo->prepare("UPDATE customers SET balance=? WHERE account_number=?");
                    $upd_cust->execute([$newBalance, $s['account_number']]);

                    // Mark salary as paid
                    $pay = $pdo->prepare("UPDATE monthly_salary SET paid_by=?, paid_at=NOW() WHERE id=?");
                    $pay->execute([$user['full_name'], $s['id']]);
                }
            }
        }
        $successMsg = "All unpaid salaries have been transferred successfully.";
    }
}

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MD Dashboard — FinServe Bank</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
body { background:#f0f4f8; font-family:'Inter', sans-serif; scroll-behavior: smooth;}
.navbar { box-shadow:0 3px 8px rgba(0,0,0,.1); background: linear-gradient(90deg, #4e54c8, #8f94fb); color:white; }
.navbar .navbar-brand, .navbar .navbar-brand span { color:white; }
.navbar .form-control { width:250px; }
.card-custom { border-radius:15px; box-shadow:0 5px 15px rgba(0,0,0,.1); margin-bottom:25px; transition: 0.3s; }
.card-custom:hover { transform: translateY(-2px); box-shadow:0 8px 20px rgba(0,0,0,.15);}
.section-title { font-weight:700; font-size:1.3rem; margin-bottom:12px; color:#4e54c8; }
.table-xs td, .table-xs th { padding:.5rem .7rem; font-size:.88rem; }
.notice-card { background: #fffbeb; border-left: 6px solid #facc15; margin-bottom:12px; border-radius:6px;}
.notice-title { font-weight:600; font-size:1rem; color:#78350f; }
.notice-text { font-size:.9rem; color:#92400e; }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg px-4 py-2">
  <a class="navbar-brand d-flex align-items-center gap-2" href="#">
    <img src="../assets/logo.png" alt="FinServe Logo" width="36" height="36">
    <span style="font-weight:700; color:white;">FinServe Bank</span>
  </a>
  
  <div class="ms-auto d-flex align-items-center gap-3">
    <div class="dropdown">
      <div data-bs-toggle="dropdown" class="d-flex align-items-center gap-2 cursor-pointer">
        <i class="fas fa-user-circle fa-2x text-white"></i>
        <div class="text-end text-white">
          <div style="font-weight:600;"><?= e($user['full_name']) ?></div>
          <div class="small-muted"><?= e($user['email'] ?? '') ?> · <?= e($user['role_name'] ?? 'MD') ?></div>
        </div>
      </div>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="../admin/index.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid mt-4">

<!-- Customers Section -->
<div id="customers" class="card card-custom p-3">
  <div class="section-title">Customers Section</div>
  <form method="get" class="mb-2">
    <div class="input-group" style="max-width:300px;">
      <input type="text" class="form-control form-control-sm" placeholder="Search by ID" name="customer_id" value="<?= e($customerId) ?>">
      <button class="btn btn-sm btn-primary">Search</button>
    </div>
  </form>
  <div class="table-responsive">
    <table class="table table-xs table-striped table-hover">
      <thead>
        <tr>
          <th>ID</th>
          <th>Account Number</th>
          <th>Full Name</th>
          <th>Account Type</th>
          <th>Balance</th>
          <th>Status</th>
          <th>Opened At</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($customers as $c): ?>
        <tr>
          <td><?= e($c['id']) ?></td>
          <td><?= e($c['account_number']) ?></td>
          <td><?= e($c['full_name']) ?></td>
          <td><?= e($c['account_type'] ?? '') ?></td>
          <td><?= e($c['balance'] ?? '') ?></td>
          <td><?= e($c['status'] ?? '') ?></td>
          <td><?= e($c['opened_at'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Accounts Section -->
<div class="card card-custom p-3">
  <div class="section-title">Accounts Section</div>
  <form method="get" class="mb-2">
    <div class="input-group" style="max-width:300px;">
      <input type="text" class="form-control form-control-sm" placeholder="Search by ID" name="account_id" value="<?= e($accountId) ?>">
      <button class="btn btn-sm btn-primary">Search</button>
    </div>
  </form>
  <div class="table-responsive">
    <table class="table table-xs table-striped table-hover">
      <thead>
        <tr>
          <th>ID</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>NID</th>
          <th>Date Of Birth</th>
          <th>Address</th>
          <th>Account Type</th>
          <th>Balance</th>
          <th>Nominee ID</th>
          <th>Trade License ID</th>
          <th>Created At</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($accounts as $a): ?>
        <tr>
          <td><?= e($a['id']) ?></td>
          <td><?= e($a['full_name']) ?></td>
          <td><?= e($a['email']) ?></td>
          <td><?= e($a['phone']) ?></td>
          <td><?= e($a['nid']) ?></td>
          <td><?= e($a['dob']) ?></td>
          <td><?= e($a['address']) ?></td>
          <td><?= e($a['account_type']) ?></td>
          <td><?= number_format($a['balance'],2) ?></td>
          <td><?= e($a['nominee_id']) ?></td>
          <td><?= e($a['trade_license_id']) ?></td>
          <td><?= e($a['created_at'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Active Loans Section -->
<div class="card card-custom p-3">
  <div class="section-title">Active Loans Section</div>
  <form method="get" class="mb-2">
    <div class="input-group" style="max-width:300px;">
      <input type="text" class="form-control form-control-sm" placeholder="Search by ID" name="loan_id" value="<?= e($loanId) ?>">
      <button class="btn btn-sm btn-primary">Search</button>
    </div>
  </form>
  <div class="table-responsive">
    <table class="table table-xs table-striped table-hover">
      <thead>
        <tr>
          <th>ID</th>
          <th>Customer ID</th>
          <th>Loan Type</th>
          <th>Amount</th>
          <th>Reason</th>
          <th>Term Months</th>
          <th>Status</th>
          <th>Applied at</th>
          <th>Officer ID</th>
          <th>Decision Note</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($loans as $l): ?>
        <tr>
          <td><?= e($l['id']) ?></td>
          <td><?= e($l['customer_id']) ?></td>
          <td><?= e($l['loan_type']) ?></td>
          <td><?= number_format($l['amount'],2) ?></td>
          <td><?= e($l['reason']) ?></td>
          <td><?= e($l['term_months']) ?></td>
          <td><?= e($l['status']) ?></td>
          <td><?= e($l['applied_at'] ?? '') ?></td>
          <td><?= e($l['officer_id']) ?></td>
          <td><?= e($l['decision_note']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Total Deposits Section -->
<div class="card card-custom p-3">
  <div class="section-title">Total Deposits Section</div>
  <form method="get" class="mb-2">
    <div class="input-group" style="max-width:300px;">
      <input type="text" class="form-control form-control-sm" placeholder="Search by ID" name="deposit_id" value="<?= e($depositId) ?>">
      <button class="btn btn-sm btn-primary">Search</button>
    </div>
  </form>
  <div class="table-responsive">
    <table class="table table-xs table-striped table-hover">
      <thead>
        <tr>
          <th>ID</th>
          <th>Customer ID</th>
          <th>Amount</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($deposits as $d): ?>
        <tr>
          <td><?= e($d['id']) ?></td>
          <td><?= e($d['customer_id']) ?></td>
          <td><?= number_format($d['amount'],2) ?></td>
          <td><?= e($d['created_at'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Staff Monthly Salary Section -->
<div class="card card-custom p-3">
  <div class="section-title">Staff Monthly Salary</div>
  <?php if($successMsg): ?>
    <div class="alert alert-success"><?= e($successMsg) ?></div>
  <?php endif; ?>
  <form method="post" class="mb-2">
    <button type="submit" name="transfer_salary" class="btn btn-primary btn-sm">Transfer All Unpaid Salaries</button>
  </form>
  <form method="get" class="mb-2">
    <div class="input-group" style="max-width:300px;">
      <input type="text" class="form-control form-control-sm" placeholder="Search by Staff ID" name="staff_id" value="<?= e($staffId) ?>">
      <button class="btn btn-sm btn-primary">Search</button>
    </div>
  </form>
  <div class="table-responsive">
    <table class="table table-xs table-striped table-hover">
      <thead>
        <tr>
          <th>ID</th>
          <th>Role</th>
          <th>Account Number</th>
          <th>Basic</th>
          <th>HRA</th>
          <th>Allowances</th>
          <th>Deductions</th>
          <th>Total</th>
          <th>Pay Month</th>
          <th>Paid By</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($staffSalary as $s): ?>
        <tr>
          <td><?= e($s['id']) ?></td>
          <td><?= e($s['employee_role']) ?></td>
          <td><?= e($s['account_number']) ?></td>
          <td><?= number_format($s['basic_salary'],2) ?></td>
          <td><?= number_format($s['hra'],2) ?></td>
          <td><?= number_format($s['allowances'],2) ?></td>
          <td><?= number_format($s['deductions'],2) ?></td>
          <td><?= number_format($s['total_salary'],2) ?></td>
          <td><?= e($s['pay_month']) ?></td>
          <td><?= e($s['paid_by'] ?? 'Pending') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Notices Section -->
<div id="notices" class="card card-custom p-3">
  <div class="section-title">Compliance & Alerts</div>
  <?php foreach($notices as $n): ?>
    <div class="notice-card p-2 mt-2">
      <div class="notice-title"><i class="fas fa-exclamation-circle me-2"></i> Notice</div>
      <div class="notice-text"><?= e($n) ?></div>
    </div>
  <?php endforeach; ?>
</div>

</div>

<footer class="text-center mt-4 mb-4 small-muted">&copy; <?= date('Y') ?> FinServe Bank — MD Dashboard</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>