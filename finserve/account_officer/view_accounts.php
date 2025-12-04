<?php
require '../config/db.php';

// Fetch all accounts with optional nominee and trade license info
try {
    $stmt = $pdo->query("
        SELECT 
            a.id, a.full_name, a.email, a.phone, a.nid, a.dob, a.address, a.account_type, 
            a.account_number, a.balance, a.created_at,
            n.nominee_name, n.nid AS nominee_nid, n.relation AS nominee_relation, n.phone AS nominee_phone, n.nominee_address,
            t.license_number, t.business_name, t.issue_date, t.expiry_date, t.business_address
        FROM accounts a
        LEFT JOIN nominees n ON a.nominee_id = n.id
        LEFT JOIN trade_licenses t ON a.trade_license_id = t.id
        ORDER BY a.created_at ASC
    ");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Accounts | Finserve</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
html, body { height:100%; margin:0; padding:0; font-family:'Inter', sans-serif; background:#f1f5f9; display:flex; flex-direction:column;}
.header { background:#003366; color:white; padding:15px 30px; display:flex; align-items:center; justify-content:space-between; }
.header h1 { margin:0; font-size:24px; display:flex; align-items:center; }
.header img { height:40px; margin-right:10px; }
.header a { color:white; text-decoration:none; font-weight:600; background:#00509e; padding:8px 12px; border-radius:6px; transition:0.3s; }
.header a:hover { background:#0066cc; }
.container { flex:1; max-width:1200px; margin:30px auto; padding:0 15px; }
.card { background:white; border-radius:10px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,0.08); margin-bottom:20px; transition:0.3s; }
.card:hover { box-shadow:0 6px 18px rgba(0,0,0,0.12); }
.card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px; }
.card-header h2 { margin:0; color:#003366; }
.table-container { overflow-x:auto; }
table { width:100%; border-collapse:collapse; }
th, td { padding:12px 15px; text-align:left; font-size:14px; }
th { background:#00509e; color:white; text-transform:uppercase; font-weight:600; }
tr:nth-child(even) { background:#f9fbfd; }
tr:hover { background:#e1efff; }
.status { padding:4px 8px; border-radius:6px; font-weight:600; font-size:13px; }
.nominee { background:#d1f0d1; color:#155724; }
.trade { background:#ffe0b3; color:#663c00; }
.print-btn { background:#1d3557; color:white; border:none; padding:5px 10px; border-radius:6px; cursor:pointer; transition:0.3s; font-size:13px; }
.print-btn:hover { background:#457b9d; }
footer { text-align:center; padding:15px 0; background:rgba(29,53,87,0.1); color:#1d3557; font-weight:bold; margin-top:auto; }
.search-input { padding:8px 12px; border-radius:6px; border:1px solid #ccc; width:250px; }
@media(max-width:900px){ table th, table td{ font-size:12px; padding:10px; } .card-header { flex-direction:column; align-items:flex-start; } }
</style>
<script>
function printAccount(accountId){
    window.open('print_account.php?account_id=' + accountId, '_blank');
}

function searchAccount(){
    const input = document.getElementById('accountSearch').value.trim().toLowerCase();
    if(!input) return;
    const row = document.querySelector('tr[data-account-number="'+input+'"]');
    if(row){
        row.scrollIntoView({behavior: 'smooth', block: 'center'});
        row.style.backgroundColor = '#ffeeba';
        setTimeout(()=>{row.style.backgroundColor='';}, 2000);
    } else {
        alert('Account not found!');
    }
}
</script>
</head>
<body>

<div class="header">
    <h1>
        <img src="../assets/logo.png" alt="Finserve Logo"> Finserve Bank
    </h1>
    <a href="../account_officer/dashboard.php">Back to Dashboard</a>
</div>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>All Accounts</h2>
            <span>Total Accounts: <?=count($accounts)?></span>
            <div>
                <input type="text" id="accountSearch" class="search-input" placeholder="Search by Account Number">
                <button onclick="searchAccount()" class="print-btn">Search</button>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Account Type</th>
                        <th>Account Number</th>
                        <th>Balance</th>
                        <th>Nominee</th>
                        <th>Trade License</th>
                        <th>Created At</th>
                        <th>Print</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($accounts): ?>
                        <?php $count=1; ?>
                        <?php foreach($accounts as $acc): ?>
                            <tr data-account-number="<?=strtolower($acc['account_number'])?>">
                                <td><?=$count++?></td>
                                <td><?=htmlspecialchars($acc['full_name'])?></td>
                                <td><?=htmlspecialchars($acc['email'])?></td>
                                <td><?=htmlspecialchars($acc['phone'])?></td>
                                <td><?=htmlspecialchars($acc['account_type'])?></td>
                                <td><?=htmlspecialchars($acc['account_number'])?></td>
                                <td><?=number_format($acc['balance'], 2)?></td>
                                <td>
                                    <?php if($acc['nominee_name']): ?>
                                        <span class="status nominee"><?=htmlspecialchars($acc['nominee_name'])?> (<?=htmlspecialchars($acc['nominee_relation'])?>)</span>
                                    <?php else: ?>N/A<?php endif; ?>
                                </td>
                                <td>
                                    <?php if($acc['license_number']): ?>
                                        <span class="status trade"><?=htmlspecialchars($acc['license_number'])?> (<?=htmlspecialchars($acc['business_name'])?>)</span>
                                    <?php else: ?>N/A<?php endif; ?>
                                </td>
                                <td><?=htmlspecialchars($acc['created_at'])?></td>
                                <td>
                                    <button class="print-btn" onclick="printAccount(<?=$acc['id']?>)">Print</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" style="text-align:center;">No accounts found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer>
    &copy; <?=date('Y')?> Finserve Bank. All Rights Reserved.
</footer>

</body>
</html>
