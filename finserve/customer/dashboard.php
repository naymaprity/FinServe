<?php
session_start();
require '../config/db.php';

// ✅ Only logged-in customers
if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    header('Location: ../login.php');
    exit;
}

$cid = $_SESSION['customer']['id'];

// Fetch account info
$stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$cid]);
$acct = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$acct) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Define actions based on account type
$account_type = $acct['account_type'];
$actions = [];

switch ($account_type) {
    case 'Savings':
    case 'Student Savings':
        $actions = [
            ['link'=>'#', 'icon'=>'fas fa-mobile-alt', 'label'=>'Mobile Recharge'],
['link'=>'#', 'icon'=>'fas fa-file-invoice-dollar', 'label'=>'Payments'],

            ['link'=>'transfer.php','icon'=>'fas fa-exchange-alt','label'=>'Transfer'],
            ['link'=>'transactions.php','icon'=>'fas fa-list-alt','label'=>'Transaction History'],
            ['link'=>'profile.php','icon'=>'fas fa-user-circle','label'=>'Profile'],
            ['link'=>'loan_request.php','icon'=>'fas fa-hand-holding-usd','label'=>'Request for Loan'],
            ['link'=>'loan.php','icon'=>'fas fa-info-circle','label'=>'All About Your Loan']
        ];

        // Fetch customer's latest loan request (if any)
        $stmt2 = $pdo->prepare("SELECT * FROM loans WHERE customer_id = ? ORDER BY applied_at DESC LIMIT 1");
        $stmt2->execute([$cid]);
        $latestLoan = $stmt2->fetch(PDO::FETCH_ASSOC);
        $loanStatus = $latestLoan['status'] ?? null; // Pending / Approved / Rejected / null
        break;

    case 'Current':
    case 'Monthly Salary Account':
    $actions = [
        //['link'=>'withdraw.php','icon'=>'fas fa-money-bill-wave','label'=>'Withdraw Money'],
        ['link'=>'transfer.php','icon'=>'fas fa-exchange-alt','label'=>'Transfer Funds'],
        ['link'=>'payroll.php','icon'=>'fas fa-file-invoice-dollar','label'=>'Payroll Records'],
        //['link'=>'interest.php','icon'=>'fas fa-percentage','label'=>'View 5% Interest'],
        ['link'=>'transactions.php','icon'=>'fas fa-list-alt','label'=>'Transaction History'],
        ['link'=>'profile.php','icon'=>'fas fa-user-circle','label'=>'Profile']
    ];
    break;


    case 'Fixed Deposit':
        $actions = [
            ['link'=>'fd_details.php','icon'=>'fas fa-calendar-alt','label'=>'FD Details'],
            ['link'=>'fd_withdraw.php','icon'=>'fas fa-hand-holding-dollar','label'=>'Premature Withdrawal'],
            ['link'=>'fd_interest.php','icon'=>'fas fa-percentage','label'=>'Interest Calculator'],
            ['link'=>'profile.php','icon'=>'fas fa-user-circle','label'=>'Profile']
        ];
        break;

    case 'Recurring Deposit':
        $actions = [
            ['link'=>'rd_details.php','icon'=>'fas fa-calendar-check','label'=>'RD Details'],
            ['link'=>'rd_modify.php','icon'=>'fas fa-edit','label'=>'Modify RD'],
            ['link'=>'rd_maturity.php','icon'=>'fas fa-hourglass-end','label'=>'Maturity Amount'],
            ['link'=>'profile.php','icon'=>'fas fa-user-circle','label'=>'Profile']
        ];
        break;

    case 'Joint Account':
        $actions = [
            ['link'=>'deposit.php','icon'=>'fas fa-wallet','label'=>'Deposit'],
            ['link'=>'withdraw.php','icon'=>'fas fa-money-bill-wave','label'=>'Withdraw'],
            ['link'=>'transfer.php','icon'=>'fas fa-exchange-alt','label'=>'Transfer'],
            ['link'=>'transactions.php','icon'=>'fas fa-list-alt','label'=>'Transaction History'],
            ['link'=>'joint_approval.php','icon'=>'fas fa-user-check','label'=>'Pending Approvals'],
            ['link'=>'profile.php','icon'=>'fas fa-user-circle','label'=>'Profile']
        ];
        break;

    case 'Foreign Currency Account':
        $actions = [
            ['link'=>'fx_deposit.php','icon'=>'fas fa-wallet','label'=>'Deposit FX'],
            ['link'=>'fx_withdraw.php','icon'=>'fas fa-money-bill-wave','label'=>'Withdraw FX'],
            ['link'=>'fx_transfer.php','icon'=>'fas fa-exchange-alt','label'=>'FX Transfer'],
            ['link'=>'transactions.php','icon'=>'fas fa-list-alt','label'=>'Transaction History'],
            ['link'=>'profile.php','icon'=>'fas fa-user-circle','label'=>'Profile']
        ];
        break;

    default:
        $actions = [
            ['link'=>'profile.php','icon'=>'fas fa-user-circle','label'=>'Profile']
        ];
        break;
}
?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
html, body {
    height: 100%;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #85dadfff, #e5e9d3ff);
    color: #1c1515ff;
    display: flex;
    flex-direction: column;
}

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

/* ✅ Dashboard Styles */
.dashboard-wrapper {
    flex: 1;
    max-width: 1200px;
    margin: 20px auto;
    padding: 40px 30px;
    background: rgba(255,255,255,0.05);
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    backdrop-filter: blur(15px);
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}
.account-info {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    gap: 25px;
    margin-bottom: 50px;
}
.account-info div {
    background: rgba(255,255,255,0.1);
    padding: 30px 25px;
    border-radius: 15px;
    text-align: center;
    flex: 1;
    min-width: 220px;
    box-shadow: inset 0 0 12px rgba(0,0,0,0.2);
    transition: transform 0.3s;
}
.account-info div:hover {
    transform: translateY(-5px);
}
.account-info strong {
    display: block;
    font-size: 18px;
    margin-bottom: 10px;
    color: #003366;
}
.account-info p {
    font-size: 22px;
    font-weight: bold;
    margin: 0;
    color: #000;
}
.dashboard-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 30px;
    margin-bottom: 20px;
}
.action-card {
    background: rgba(255,255,255,0.08);
    padding: 30px 20px;
    border-radius: 15px;
    text-align: center;
    font-size: 16px;
    transition: transform 0.3s, background 0.3s;
    cursor: pointer;
    box-shadow: 0 6px 25px rgba(0,0,0,0.25);
    text-decoration: none;
}
.action-card:hover {
    transform: translateY(-5px);
    background: rgba(255,255,255,0.18);
}
.action-card i {
    font-size: 32px;
    margin-bottom: 12px;
    display: block;
    color: #003366;
    transition: color 0.3s;
}
.action-card:hover i {
    color: #000;
}
.action-card span {
    display: block;
    font-weight: bold;
    color: #003366;
    font-size: 16px;
}
.loan-status {
    background: rgba(255,255,255,0.1);
    padding: 20px;
    border-radius: 12px;
    margin-top: 20px;
    text-align: center;
    font-weight: bold;
    font-size: 18px;
}
.loan-pending { color: #ffc107; }
.loan-approved { color: #28a745; }
.loan-rejected { color: #dc3545; }
footer {
    text-align: center;
    padding: 15px;
    background: rgba(0,0,0,0.05);
    color: #003366;
    font-weight: bold;
    border-top: 1px solid rgba(0,0,0,0.1);
    margin-top: auto;
}
@media(max-width: 480px) {
    .account-info { flex-direction: column; align-items: center; }
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

<!-- ✅ Dashboard Content -->
<div class="dashboard-wrapper">
    <div class="account-info">
        <div>
            <strong>Account Number</strong>
            <p><?=htmlspecialchars($acct['account_number'])?></p>
        </div>
        <div>
            <strong>Account Type</strong>
            <p><?=htmlspecialchars($acct['account_type'])?></p>
        </div>
        <div>
            <strong>Balance</strong>
            <p><?=number_format($acct['balance'],2)?> BDT</p>
        </div>
    </div>

    <div class="dashboard-actions">
        <?php foreach($actions as $act): ?>
            <a href="<?=htmlspecialchars($act['link'])?>" class="action-card">
                <i class="<?=htmlspecialchars($act['icon'])?>"></i>
                <span><?=htmlspecialchars($act['label'])?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if(isset($loanStatus) && $loanStatus): ?>
        <div class="loan-status 
            <?= $loanStatus==='Pending' ? 'loan-pending' : ($loanStatus==='Approved' ? 'loan-approved' : 'loan-rejected') ?>">
            Latest Loan Status: <?=htmlspecialchars($loanStatus)?>
        </div>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>
