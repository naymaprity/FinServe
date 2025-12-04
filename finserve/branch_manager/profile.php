<?php
session_start();
require '../config/db.php';

// 🔒 User login check
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    header("Location: ../login.php");
    exit;
}

$uid = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$success = '';
$error = '';

// ✅ Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET full_name=?, email=?, phone=?, password=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $email, $phone, $hashed_password, $uid]);
    } else {
        $sql = "UPDATE users SET full_name=?, email=?, phone=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $email, $phone, $uid]);
    }

    $success = "✅ Profile updated successfully!";
    // Refresh user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile | Finserve Bank</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #1042a0ff, #447de0ff);
        color: #fff;
        margin: 0;
        padding: 0;
    }
    .profile-container {
        width: 75%;
        margin: 50px auto;
        background: rgba(255,255,255,0.1);
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.3);
        backdrop-filter: blur(12px);
        display: flex;
        gap: 40px;
        align-items: flex-start;
        flex-wrap: wrap;
    }
    .profile-left {
        flex: 1 1 280px;
        text-align: center;
    }
    .profile-left i {
        font-size: 100px;
        color: #FFD700;
        margin-bottom: 15px;
    }
    .profile-left h2 {
        color: #FFD700;
        margin: 10px 0;
    }
    .profile-right {
        flex: 2 1 400px;
    }
    .info-box {
        background: rgba(255,255,255,0.1);
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 15px;
    }
    .info-box h4 {
        margin: 0 0 5px;
        color: #FFD700;
    }
    .update-form {
        margin-top: 20px;
    }
    input {
        width: 100%;
        padding: 10px;
        border: none;
        border-radius: 8px;
        margin-bottom: 12px;
        background: rgba(255,255,255,0.15);
        color: #fff;
        font-size: 1em;
    }
    input::placeholder { color: rgba(255,255,255,0.7); }
    .update-btn {
        background: #FFD700;
        color: #1E3C72;
        padding: 10px 20px;
        border-radius: 25px;
        border: none;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }
    .update-btn:hover { background: #fff; color: #1E3C72; }
    .back-btn {
        display: inline-block;
        background: #FFD700;
        color: #1E3C72;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 25px;
        margin-top: 30px;
        transition: 0.3s;
    }
    .back-btn:hover { background: #fff; color: #1E3C72; }
    .message {
        text-align: center;
        margin-bottom: 15px;
        font-weight: bold;
    }
</style>
</head>
<body>

<div class="profile-container">
    <!-- Left Side (Avatar + Info) -->
    <div class="profile-left">
        <i class="ri-account-circle-line"></i>
        <h2><?= htmlspecialchars($user['full_name']) ?></h2>
        <p><i class="ri-user-settings-line"></i> <?= ucfirst($user['role'] ?? 'User') ?></p>
        <div class="info-box">
            <h4><i class="ri-mail-line"></i> Email</h4>
            <p><?= htmlspecialchars($user['email']) ?></p>
        </div>
        <div class="info-box">
            <h4><i class="ri-bank-line"></i> Branch</h4>
            <p><?= htmlspecialchars($user['branch_name'] ?? 'Main Branch') ?></p>
        </div>
        <div class="info-box">
            <h4><i class="ri-calendar-line"></i> Joined On</h4>
            <p><?= htmlspecialchars($user['created_at'] ?? 'N/A') ?></p>
        </div>
    </div>

    <!-- Right Side (Update Form) -->
    <div class="profile-right">
        <h3 style="color:#FFD700;">Update Profile</h3>
        <?php if ($success): ?>
            <p class="message" style="color:#90EE90;"><?= $success ?></p>
        <?php elseif ($error): ?>
            <p class="message" style="color:#FF6B6B;"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST" class="update-form">
            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" placeholder="Full Name" required>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" placeholder="Email" required>
            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Phone Number">
            <input type="password" name="password" placeholder="New Password (leave blank to keep current)">
            <button type="submit" class="update-btn"><i class="ri-save-line"></i> Save Changes</button>
        </form>

        <a href="dashboard.php" class="back-btn"><i class="ri-arrow-left-line"></i> Back to Dashboard</a>
    </div>
</div>

</body>
</html>
