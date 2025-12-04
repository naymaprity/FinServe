<?php
require '../config/db.php';
session_start();

// ✅ Only allow Loan Officers (role_id = 7)
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 7) {
    die("Access denied!");
}

// Fetch all EMI payments with loan & customer info
$stmt = $pdo->prepare("
    SELECT 
        lp.id AS payment_id,
        c.full_name AS customer_name,
        l.id AS loan_id,
        l.loan_type,
        l.amount AS loan_amount,
        lp.due_date,
        lp.emi_amount,
        lp.paid_amount,
        lp.remaining_amount,
        lp.status
    FROM loan_payments lp
    JOIN loans l ON lp.loan_id = l.id
    JOIN customers c ON l.customer_id = c.id
    ORDER BY lp.due_date ASC
");
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Monthly EMI Check</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f6f8; padding:30px; }
table { width:100%; border-collapse:collapse; background:#fff; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
th, td { padding:12px 15px; border:1px solid #ddd; text-align:center; }
th { background:#2563eb; color:#fff; }
tr:nth-child(even) { background:#f9f9f9; }
.status-pending { color:#f59e0b; font-weight:600; }
.status-paid { color:#16a34a; font-weight:600; }
.status-late { color:#dc2626; font-weight:600; }
</style>
</head>
<body>

<h2>Monthly EMI Payments</h2>
<table>
    <thead>
        <tr>
            <th>Customer</th>
            <th>Loan ID</th>
            <th>Loan Type</th>
            <th>Loan Amount</th>
            <th>Due Date</th>
            <th>EMI Amount</th>
            <th>Paid Amount</th>
            <th>Remaining Amount</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($payments)): ?>
        <tr><td colspan="9">No EMI records found.</td></tr>
    <?php else: ?>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['customer_name']) ?></td>
                <td><?= $p['loan_id'] ?></td>
                <td><?= htmlspecialchars($p['loan_type']) ?></td>
                <td><?= number_format($p['loan_amount'],2) ?> BDT</td>
                <td><?= $p['due_date'] ?></td>
                <td><?= number_format($p['emi_amount'],2) ?> BDT</td>
                <td><?= number_format($p['paid_amount'],2) ?> BDT</td>
                <td><?= number_format($p['remaining_amount'],2) ?> BDT</td>
                <td class="status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

</body>
</html>
