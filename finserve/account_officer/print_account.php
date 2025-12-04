<?php
require '../config/db.php';

$account_id = $_GET['account_id'] ?? null;
if (!$account_id) {
    die("Invalid request!");
}

// Fetch account details
$stmt = $pdo->prepare("
    SELECT a.*, n.nominee_name, n.nid, n.relation, n.phone, n.nominee_address,
           t.license_number AS trade_license_number, t.business_name AS trade_business_name, 
           t.issue_date AS trade_issue_date, t.expiry_date AS trade_expiry_date, t.business_address AS trade_business_address
    FROM accounts a
    LEFT JOIN nominees n ON a.nominee_id = n.id
    LEFT JOIN trade_licenses t ON a.trade_license_id = t.id
    WHERE a.id = ?
");
$stmt->execute([$account_id]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    die("Account not found!");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Receipt | Finserve Bank</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f2f5;
    margin: 0;
    padding: 0;
}
.print-container {
    max-width: 850px;
    margin: 30px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 25px rgba(0,0,0,0.1);
    position: relative;
}

/* Watermark */
.print-container::before {
    content: "Finserve";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg);
    font-size: 50px;
    color: rgba(200,200,200,0.12);
    width: 100%;
    text-align: center;
    z-index: 0;
    pointer-events: none;
}

.header {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 20px;
    margin-bottom: 15px;
}
.header img {
    height: 60px;
}
.header h1 {
    font-size: 28px;
    color: #1d3557;
    margin: 0;
}

.sub-header {
    text-align: center;
    color: #457b9d;
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 10px;
}

.success-msg {
    text-align: center;
    color: green;
    font-weight: bold;
    font-size: 16px;
    margin: 10px 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    position: relative;
    z-index: 1;
}
th, td {
    padding: 12px 10px;
    border-bottom: 1px solid #eee;
    text-align: left;
}
th {
    width: 35%;
    color: #1d3557;
    font-weight: 600;
}
.section-title {
    background: #1d3557;
    color: #fff;
    padding: 8px 10px;
    border-radius: 6px;
    margin-top: 20px;
    font-size: 16px;
}
.btn {
    display: inline-block;
    background: #1d3557;
    color: #fff;
    padding: 10px 22px;
    border-radius: 6px;
    text-decoration: none;
    text-align: center;
    margin-top: 20px;
    cursor: pointer;
}
.btn:hover {
    background: #457b9d;
}
.footer {
    text-align: center;
    font-size: 13px;
    color: #555;
    margin-top: 25px;
}

@media print {
    .btn { display: none; }
    .success-msg { display: none; }
}
</style>
</head>
<body>
<div class="print-container">
    <div class="header">
        <img src="../assets/logo.png" alt="Bank Logo">
        <h1>Finserve Bank</h1>
    </div>
    <div class="sub-header">Account Opening Receipt</div>
    <p class="success-msg">✅ New Account Successfully Created</p>

    <table>
        <tr><th>Full Name</th><td><?=htmlspecialchars($account['full_name'])?></td></tr>
        <tr><th>Email</th><td><?=htmlspecialchars($account['email'])?></td></tr>
        <tr><th>Phone</th><td><?=htmlspecialchars($account['phone'])?></td></tr>
        <tr><th>Account Number</th><td><?=htmlspecialchars($account['account_number'])?></td></tr>
        <tr><th>Login Code</th><td><?=htmlspecialchars($account['login_code'])?></td></tr>
        <tr><th>Account Type</th><td><?=htmlspecialchars($account['account_type'])?></td></tr>
        <tr><th>Balance</th><td><?=htmlspecialchars($account['balance'])?> BDT</td></tr>

        <?php if($account['nominee_name']): ?>
        <tr><th class="section-title" colspan="2">Nominee Details</th></tr>
        <tr><th>Nominee Name</th><td><?=htmlspecialchars($account['nominee_name'])?></td></tr>
        <tr><th>Nominee NID</th><td><?=htmlspecialchars($account['nid'])?></td></tr>
        <tr><th>Relation</th><td><?=htmlspecialchars($account['relation'])?></td></tr>
        <tr><th>Nominee Phone</th><td><?=htmlspecialchars($account['phone'])?></td></tr>
        <tr><th>Nominee Address</th><td><?=htmlspecialchars($account['nominee_address'])?></td></tr>
        <?php endif; ?>

        <?php if($account['trade_license_number']): ?>
        <tr><th class="section-title" colspan="2">Trade License Details</th></tr>
        <tr><th>Trade License No</th><td><?=htmlspecialchars($account['trade_license_number'])?></td></tr>
        <tr><th>Business Name</th><td><?=htmlspecialchars($account['trade_business_name'])?></td></tr>
        <tr><th>Issue Date</th><td><?=htmlspecialchars($account['trade_issue_date'])?></td></tr>
        <tr><th>Expiry Date</th><td><?=htmlspecialchars($account['trade_expiry_date'])?></td></tr>
        <tr><th>Business Address</th><td><?=htmlspecialchars($account['trade_business_address'])?></td></tr>
        <?php endif; ?>
    </table>

    <div style="margin-top: 20px;">
    <a href="#" class="btn" onclick="window.print()">Print Receipt</a>
    <a href="dashboard.php" class="btn" style="margin-left: 10px;">Back to Dashboard</a>
</div>
    <div class="footer">&copy; <?=date('Y')?> Finserve Bank. All Rights Reserved.</div>
</div>
</body>
</html>
