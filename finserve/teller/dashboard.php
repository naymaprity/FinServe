<?php
session_start();
require '../config/db.php';

// ✅ Teller Login Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 8) {
    header('Location: teller_login.php');
    exit;
}

$teller_name = $_SESSION['user']['full_name'] ?? 'Teller';

// ✅ Fetch Customers & Transactions
$customers = $pdo->query("SELECT * FROM customers ORDER BY id DESC LIMIT 10")->fetchAll();
$transactions = $pdo->query("SELECT t.*, c.account_number, c.account_type 
                              FROM transactions t 
                              JOIN customers c ON t.customer_id = c.id
                              ORDER BY t.created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teller Dashboard | Finserve</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #5D54A4, #7C78B8);
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 50px;
            background: rgba(0,0,0,0.25);
            backdrop-filter: blur(8px);
        }
        .header .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .header .logo img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        .header h1 {
            font-size: 1.8em;
            margin: 0;
            letter-spacing: 1px;
        }
        .header .welcome {
            font-size: 1em;
        }
        .logout-btn {
            background-color: #ff6b6b;
            border: none;
            padding: 10px 20px;
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }
        .logout-btn:hover {
            background-color: #ff4040;
        }

        /* Dashboard Grid */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            width: 90%;
            margin: 40px auto;
        }

        .card {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
            text-align: center;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card i {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #FFD700;
        }
        .card h3 {
            margin: 10px 0;
            font-size: 1.2em;
        }
        .card a {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 15px;
            background: #fff;
            color: #5D54A4;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }
        .card a:hover {
            background: #FFD700;
            color: #000;
        }

        /* Tables */
        .table-container {
            width: 90%;
            margin: 40px auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            margin-bottom: 40px;
        }
        table thead {
            background: rgba(255,255,255,0.15);
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        tr:hover {
            background: rgba(255,255,255,0.08);
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="logo">
        <img src="../assets/logo.png" alt="Logo">
        <h1>Finserve</h1>
    </div>

    <div class="header-buttons" style="display:flex; gap:15px; align-items:center;">

    <!-- Profile Button -->
    <a href="profile.php" style="
        background:#FFD700;
        color:#000;
        padding:10px 20px;
        border-radius:8px;
        font-weight:bold;
        text-decoration:none;
        transition:0.3s;
    " onmouseover="this.style.background='#FFC700'" onmouseout="this.style.background='#FFD700'">
        Profile
    </a>

    <?php
// Fetch unread message count for Teller
$teller_id = $_SESSION['user']['id'];
$unread_count = $pdo->query("SELECT COUNT(*) FROM messages WHERE receiver_id=$teller_id AND is_read=0")->fetchColumn() ?? 0;
?>

<!-- 💬 Chat Icon Button -->
<a href="chat.php" title="Chat" style="
    background:rgba(255,255,255,0.2);
    color:#fff;
    padding:10px 15px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.4em;
    position:relative;
    text-decoration:none;
    transition:0.3s;
" onmouseover="this.style.background='#FFD700'; this.style.color='#000';" 
  onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.color='#fff';">
    <i class="ri-chat-3-line"></i>
    <?php if($unread_count > 0): ?>
        <span style="
            position:absolute;
            top:-5px;
            right:-5px;
            background:red;
            color:#fff;
            border-radius:50%;
            padding:2px 6px;
            font-size:0.8rem;
        "><?= $unread_count ?></span>
    <?php endif; ?>
</a>


    <!-- Logout Button -->
    <form action="../admin/index.php" method="post" style="margin:0;">
        <button type="submit" style="
            background:#ef4444;
            color:#fff;
            padding:10px 20px;
            border-radius:8px;
            font-weight:bold;
            cursor:pointer;
            border:none;
            transition:0.3s;
        " onmouseover="this.style.background='#cc0000'" onmouseout="this.style.background='#ef4444'">
            Logout
        </button>
    </form>
</div>

</div>



<!-- Teller Features -->
<div class="dashboard">
    <div class="card">
        <i class="ri-bank-line"></i>
        <h3>Deposit Money</h3>
        <a href="deposit.php">Go</a>
    </div>
    <div class="card">
        <i class="ri-wallet-line"></i>
        <h3>Withdraw Money</h3>
        <a href="withdraw.php">Go</a>
    </div>
    <div class="card">
        <i class="ri-user-3-line"></i>
        <h3>Customer List</h3>
        <a href="customers.php">View</a>
    </div>
    <div class="card">
        <i class="ri-exchange-dollar-line"></i>
        <h3>Transaction History</h3>
        <a href="transactions.php">View</a>
    </div>
    <div class="card">
        <i class="ri-alert-line"></i>
        <h3>Suspicious Alerts</h3>
        <a href="teller_alerts.php">Check</a>
    </div>
    <div class="card">
        <i class="ri-calculator-line"></i>
        <h3>Cash Counter</h3>
        <a href="cash.php">Open</a>
    </div>
</div>

<!-- Recent Customers Table -->
<div class="table-container">
    <h2><i class="ri-user-3-line"></i> Recent Customers</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Account Number</th>
                <th>Type</th>
                <th>Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($customers): ?>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><?= htmlspecialchars($c['account_number']) ?></td>
                        <td><?= htmlspecialchars($c['account_type']) ?></td>
                        <td><?= number_format($c['balance'], 2) ?></td>
                        <td><?= ucfirst($c['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No customers found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h2><i class="ri-exchange-dollar-line"></i> Latest Transactions</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Account</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($transactions): ?>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?= $t['id'] ?></td>
                        <td><?= htmlspecialchars($t['account_number']) . " (" . htmlspecialchars($t['account_type']) . ")" ?></td>
                        <td><?= ucfirst($t['type']) ?></td>
                        <td><?= number_format($t['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($t['description']) ?></td>
                        <td><?= $t['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No transactions available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
