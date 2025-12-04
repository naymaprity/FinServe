<?php
session_start();
require '../config/db.php';

// ✅ Only allow logged-in Loan Officer
if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role_id'] != 7) {
    die("Unauthorized access! Only Loan Officers can perform this action.");
}

// ✅ Get loan ID and action
$loan_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if (!$loan_id || !in_array($action, ['approve','reject'])) {
    die("Invalid request!");
}

// ✅ Logged-in Loan Officer ID
$officer_id = $_SESSION['user']['id'];

// ✅ Decision note
$decision_note = $action === 'approve' ? "Approved by Loan Officer" : "Rejected by Loan Officer";

// ✅ Update loan status in DB
try {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE loans SET status = 'approved', officer_id = ?, decision_note = ? WHERE id = ?");
        $stmt->execute([$officer_id, $decision_note, $loan_id]);
        $message = "Loan Approved Successfully!";
        $icon = "check-circle";
        $color = "#28a745";
    } else {
        $stmt = $pdo->prepare("UPDATE loans SET status = 'rejected', officer_id = ?, decision_note = ? WHERE id = ?");
        $stmt->execute([$officer_id, $decision_note, $loan_id]);
        $message = "Loan Rejected!";
        $icon = "times-circle";
        $color = "#dc3545";
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Loan Action Status</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background: #f2f5f9;
    font-family: 'Poppins', sans-serif;
}
.status-box {
    background: #fff;
    padding: 50px 60px;
    border-radius: 18px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
    text-align: center;
    animation: fadeIn 0.5s ease;
}
.icon-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    font-size: 56px;
    color: white;
    background-color: <?= $color ?>;
    margin-bottom: 25px;
    transform: scale(0);
    animation: popIn 0.6s forwards;
}
h2 {
    font-size: 26px;
    color: #333;
    margin-bottom: 25px;
    font-weight: 600;
}
a.btn {
    display: inline-block;
    background: linear-gradient(135deg,#0061ff,#60efff);
    color: #fff;
    text-decoration: none;
    padding: 12px 28px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s ease;
}
a.btn:hover {
    background: linear-gradient(135deg,#0046b3,#30c8ff);
    transform: scale(1.05);
}
@keyframes popIn { 0% { transform: scale(0); } 70% { transform: scale(1.2); } 100% { transform: scale(1); } }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-20px);} to { opacity: 1; transform: translateY(0);} }
</style>
</head>
<body>

<div class="status-box">
    <div class="icon-circle">
        <i class="fas fa-<?= $icon ?>"></i>
    </div>
    <h2><?= $message ?></h2>
    <a href="dashboard.php" class="btn">Back to Dashboard</a>
</div>

</body>
</html>
