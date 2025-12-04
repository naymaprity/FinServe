<?php
// transaction.php
session_start();
require '../config/db.php';

// ✅ Only allow logged-in customers
if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    header('Location: ../login.php');
    exit;
}

$cid = $_SESSION['customer']['id'];

// Fetch transactions
$stmt = $pdo->prepare('SELECT * FROM transactions WHERE customer_id = ? ORDER BY created_at DESC');
$stmt->execute([$cid]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
:root{
  --bg:#f4f6fa; --card:#fff; --primary:#1d4ed8; --success:#16a34a; --danger:#dc2626;
  --text:#111827; --muted:#6b7280; --border:#e5e7eb; --radius:12px; --max-width:900px;
  font-family:'Inter', sans-serif;
}
html, body { margin:0; padding:0; background:var(--bg); color:var(--text); }
.container { max-width:var(--max-width); margin:0 auto; padding:24px 12px; }
h2 { text-align:center; color:var(--primary); margin-bottom:24px; }

table { width:100%; border-collapse:collapse; background:var(--card); border-radius:var(--radius); overflow:hidden; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
th, td { padding:12px 16px; border-bottom:1px solid var(--border); text-align:left; }
th { background:var(--primary); color:#fff; text-transform:uppercase; }
tr:hover { background:#f1f5f9; }
@media(max-width:768px){
  table, thead, tbody, th, td, tr { display:block; }
  th { display:none; }
  td { position:relative; padding-left:50%; border:none; border-bottom:1px solid var(--border); }
  td:before { position:absolute; top:12px; left:12px; width:45%; white-space:nowrap; font-weight:600; content:attr(data-label); }
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
  <h2>Transaction History</h2>
  <table>
    <thead>
      <tr>
        <th>Date & Time</th>
        <th>Type</th>
        <th>Amount</th>
        <th>Balance After</th>
        <th>Description</th>
      </tr>
    </thead>
    <tbody>
      <?php if($transactions): ?>
        <?php foreach($transactions as $t): ?>
          <tr>
            <td data-label="Date & Time"><?= htmlspecialchars($t['created_at']) ?></td>
            <td data-label="Type"><?= htmlspecialchars(ucfirst(str_replace('_',' ',$t['type']))) ?></td>
            <td data-label="Amount"><?= number_format($t['amount'],2) ?></td>
            <td data-label="Balance After"><?= number_format($t['balance_after'],2) ?></td>
            <td data-label="Description"><?= htmlspecialchars($t['description']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5" style="text-align:center; padding:20px;">No transactions found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require '../includes/footer.php'; ?>
