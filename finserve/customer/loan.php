<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    header('Location: ../login.php');
    exit;
}

$cid = $_SESSION['customer']['id'];

// Fetch latest loan
$stmt = $pdo->prepare("SELECT * FROM loans WHERE customer_id = ? ORDER BY applied_at DESC LIMIT 1");
$stmt->execute([$cid]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loan) {
    echo "<div class='no-loan'><h2>No Active Loan Found</h2><p>You have not applied for any loan yet.</p></div>";
    exit;
}

// Loan details
$loan_id = $loan['id'];
$principal = $loan['amount'];
$months = $loan['term_months'];
$rate = 10; // yearly interest %
$monthlyRate = $rate / 12 / 100;
$emi = ($principal * $monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);
$totalPayment = $emi * $months;
$totalInterest = $totalPayment - $principal;

// Fetch EMI payments history
$stmt = $pdo->prepare("SELECT * FROM loan_payments WHERE loan_id=? ORDER BY due_date ASC");
$stmt->execute([$loan_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine next EMI due month
$lastPayment = end($payments);
$nextEMIDate = $lastPayment ? date('Y-m-d', strtotime('+1 month', strtotime($lastPayment['due_date']))) : date('Y-m-d');
$nextMonthYM = date('Y-m', strtotime($nextEMIDate));
$currentYM = date('Y-m');

// Check if EMI for current month is already paid
$alreadyPaid = false;
foreach ($payments as $p) {
    if (date('Y-m', strtotime($p['due_date'])) == $currentYM && $p['status'] == 'paid') {
        $alreadyPaid = true;
        break;
    }
}

// Handle EMI Payment
if (isset($_POST['pay_emi']) && !$alreadyPaid) {
    $pdo->beginTransaction();
try {
    // Lock customer balance
    $stmt = $pdo->prepare("SELECT balance FROM customers WHERE id=? FOR UPDATE");
    $stmt->execute([$cid]);
    $cust = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cust) throw new Exception("Customer not found");

    if ($cust['balance'] < $emi) {
        $error = "❌ Insufficient balance to pay this month's installment.";
        $pdo->rollBack();
    } else {
        $newBalance = $cust['balance'] - $emi;
        $stmt = $pdo->prepare("UPDATE customers SET balance=? WHERE id=?");
        $stmt->execute([$newBalance, $cid]);

        // Insert transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (customer_id,type,amount,description,balance_after) VALUES (?,?,?,?,?)");
        $stmt->execute([$cid, 'loan_payment', $emi, 'Monthly EMI Payment', $newBalance]);

        // Calculate remaining amount
        $paidSum = array_sum(array_column($payments, 'paid_amount')) + $emi;
        $remaining = max(0, $principal + $totalInterest - $paidSum);

        // Insert loan_payment
       $stmt = $pdo->prepare("INSERT INTO loan_payments (loan_id, due_date, emi_amount, paid_amount, remaining_amount, status) VALUES (?,?,?,?,?,?)");
$stmt->execute([$loan_id, date('Y-m-d'), $emi, $emi, $remaining, 'paid']);

        $pdo->commit();
        $success = "✅ EMI of " . number_format($emi, 2) . " BDT has been paid successfully!";
        $alreadyPaid = true;
        $payments[] = [
            'due_date' => date('Y-m-d'),
            'emi_amount' => $emi,
            'paid_amount' => $emi,
            'remaining_amount' => $remaining,
            'status' => 'paid'
        ];
    }
} catch (Exception $e) {
    $pdo->rollBack();
    $error = "❌ An error occurred: " . $e->getMessage();
}
}
?>

<style>
    /* ✅ Header Styling */
header {
    background: rgba(0, 51, 102, 0.9);
    color: #fff;
    padding: 15px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
header .logo-section {
    display: flex;
    align-items: center;
    gap: 12px;
}
header img {
    height: 40px;
    width: 40px;
    border-radius: 50%;
}
header h1 {
    font-size: 22px;
    font-weight: bold;
    letter-spacing: 1px;
}
header .logout-btn {
    background: #ff4d4d;
    color: #fff;
    padding: 10px 18px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: bold;
    transition: background 0.3s;
}
header .logout-btn:hover {
    background: #cc0000;
}
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #d3e0ea, #f7f7f7); margin:0; padding:0; }
.loan-container { max-width: 800px; margin: 50px auto; background: #fff; padding: 35px; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
.loan-container h2 { text-align:center; color:#003366; margin-bottom:25px; }
.loan-details { display:grid; grid-template-columns:1fr 1fr; gap:15px; font-size:16px; margin-bottom:20px; }
.loan-details div { background:#f5f8fa; padding:15px 20px; border-radius:10px; box-shadow: inset 0 0 6px rgba(0,0,0,0.05); }
.loan-details strong { color:#003366; }
button.pay-btn { background:#007bff; color:white; border:none; padding:12px 25px; border-radius:8px; font-size:16px; font-weight:bold; cursor:pointer; display:block; margin:25px auto 0; transition: background 0.3s; }
button.pay-btn:hover { background:#0056b3; }
.success, .error, .already-paid { text-align:center; font-weight:bold; margin-bottom:15px; }
.success { color:#28a745; }
.error, .already-paid { color:#dc3545; }
.payment-table { width:100%; border-collapse:collapse; margin-top:30px; }
.payment-table th, .payment-table td { border:1px solid #ddd; padding:10px 12px; text-align:center; }
.payment-table th { background:#2563eb; color:#fff; }
.payment-table tr:nth-child(even) { background:#f9f9f9; }
.no-loan { text-align:center; padding:100px 20px; font-family: 'Segoe UI'; }
.no-loan h2 { color:#003366; }
</style>
<!-- ✅ Header Section -->
<header>
    <div class="logo-section">
        <img src="../assets/logo.png" alt="Logo">
        <h1>Finserve</h1>
    </div>
    <a href="../index.php" class="logout-btn">Logout</a>
</header>
<div class="loan-container">
  <h2>Your Loan Details</h2>

  <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>
  <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
  <?php if ($alreadyPaid && empty($success)) echo "<p class='already-paid'>💡 EMI Already Paid for this month</p>"; ?>

  <div class="loan-details">
    <div><strong>Loan Type:</strong> <?=htmlspecialchars($loan['loan_type'])?></div>
    <div><strong>Loan Amount:</strong> <?=number_format($principal, 2)?> BDT</div>
    <div><strong>Term:</strong> <?=$months?> months</div>
    <div><strong>Interest Rate:</strong> <?=$rate?>% per year</div>
    <div><strong>Monthly EMI:</strong> <?=number_format($emi, 2)?> BDT</div>
    <div><strong>Total Interest:</strong> <?=number_format($totalInterest, 2)?> BDT</div>
    <div><strong>Total Payment:</strong> <?=number_format($totalPayment, 2)?> BDT</div>
    <div><strong>Status:</strong> <?=htmlspecialchars($loan['status'])?></div>
    <div><strong>Next EMI Due:</strong> <?=date('F Y', strtotime($nextEMIDate))?></div>
  </div>

  <?php if(!$alreadyPaid): ?>
      <form method="POST">
          <button type="submit" name="pay_emi" class="pay-btn">💳 Pay This Month's EMI</button>
      </form>
  <?php else: ?>
      <div style="text-align:center; margin-top:20px;">
          <a href="dashboard.php" class="pay-btn" style="background:#28a745;">✅ Back to Dashboard</a>
      </div>
  <?php endif; ?>

  <!-- Payment history table -->
  <table class="payment-table">
      <thead>
          <tr>
              <th>Month</th>
              <th>EMI Amount</th>
              <th>Paid Amount</th>
              <th>Remaining Amount</th>
              <th>Status</th>
          </tr>
      </thead>
      <tbody>
          <?php if(empty($payments)): ?>
              <tr><td colspan="5">No payments made yet.</td></tr>
          <?php else: ?>
              <?php foreach($payments as $p): 
                $monthName = date('F Y', strtotime($p['due_date']));
              ?>
              <tr>
                  <td><?=$monthName?></td>
                  <td><?=number_format($p['emi_amount'],2)?> BDT</td>
                  <td><?=number_format($p['paid_amount'],2)?> BDT</td>
                  <td><?=number_format($p['remaining_amount'],2)?> BDT</td>
                  <td><?=ucfirst($p['status'])?></td>
              </tr>
              <?php endforeach; ?>
          <?php endif; ?>
      </tbody>
  </table>
</div>
