<?php
session_start();
require '../config/db.php';

// লগইন চেক
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    header("Location: account_officer_login.php");
    exit;
}

// Logged user data
$uid = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<p style='text-align:center;color:red;'>User not found!</p>";
    exit;
}

$role_id = $user['role_id'];
$role_name = $user['role_name'];

// ঐ role এর সকল user তথ্য আনা
$stmt2 = $pdo->prepare("SELECT * FROM users WHERE role_id = ? AND role_name = ?");
$stmt2->execute([$role_id, $role_name]);
$profiles = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// ✅ Update Handler
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email = trim($_POST['email']);
    $new_pass = trim($_POST['password']);
    $target_id = $_POST['user_id'];

    if (!empty($new_email) && !empty($new_pass)) {
        $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
        $upd->execute([$new_email, $hashed, $target_id]);
        $success = "Profile updated successfully!";
    } else {
        $error = "Please fill out all fields.";
    }
}
?>

<!-- ===== Header Start ===== -->
<header style="background:#6f42c1;padding:15px;color:white;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h2 style="margin:0;font-size:20px;">FinServe - <?= htmlspecialchars($role_name) ?> Dashboard</h2>
    </div>
    <div>
        <span style="margin-right:15px;">Hello, <?= htmlspecialchars($user['full_name']) ?></span>
        <a href="dashboard.php" style="color:white;text-decoration:none;background:#dc3545;padding:6px 12px;border-radius:6px;">Logout</a>
    </div>
</header>
<!-- ===== Header End ===== -->

<style>
body {
    background: var(--bg-color);
    color: var(--text-color);
    font-family: 'Poppins', sans-serif;
    transition: background 0.4s, color 0.4s;
    margin:0;
}
:root {
    --bg-color: #f8f9fc;
    --text-color: #1d1d1d;
    --card-bg: #fff;
}
.dark-mode {
    --bg-color: #181a1b;
    --text-color: #f8f9fa;
    --card-bg: #242526;
}

.container {
    max-width: 1100px;
    margin: 60px auto;
    padding: 20px;
}

.theme-toggle {
    position: absolute;
    top: 110px;
    right: 50px;
    background: #6f42c1;
    color: #fff;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: 0.3s;
}
.theme-toggle:hover { background: #5a32a1; }

.profile-card {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 25px 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 25px;
    transition: background 0.3s;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: 2px solid #6f42c1;
    margin-bottom: 15px;
    padding-bottom: 10px;
}

.profile-header img {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    border: 3px solid #6f42c1;
    object-fit: cover;
}

.profile-header h3 {
    font-size: 18px;
    color: var(--text-color);
    margin: 0;
}

.info-row {
    display: grid;
    grid-template-columns: 180px auto;
    margin-bottom: 8px;
}
.info-row span:first-child {
    font-weight: 600;
    color: var(--text-color);
}
.info-row span:last-child {
    color: #666;
}

.update-btn {
    background: #6f42c1;
    color: white;
    border: none;
    padding: 5px 18px;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 10px;
}
.update-btn:hover { background: #5a32a1; }

/* Modal */
.modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
}
.modal-content {
    background: var(--card-bg);
    padding: 25px;
    border-radius: 12px;
    width: 350px;
}
.modal-content h3 {
    color: var(--text-color);
    margin-bottom: 10px;
}
.modal-content input {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    margin-bottom: 10px;
    background: var(--bg-color);
    color: var(--text-color);
}
.modal-content button {
    background: #6f42c1;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    cursor: pointer;
}
.close-btn {
    background: #dc3545;
    margin-left: 10px;
}
</style>

<button class="theme-toggle" onclick="toggleTheme()">🌙</button>

<div class="container">
    <h2 style="color:var(--text-color);text-align:center;margin-bottom:25px;">
        <?= htmlspecialchars($role_name) ?> Profile
    </h2>

    <?php if ($success): ?><p style="color:green;text-align:center;"><?= $success ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:red;text-align:center;"><?= $error ?></p><?php endif; ?>

    <?php foreach ($profiles as $p): ?>
    <div class="profile-card">
        <div class="profile-header">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="User">
            <div>
                <h3><?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['username']) ?>)</h3>
                <small><?= htmlspecialchars($p['email']) ?></small>
            </div>
        </div>
        <div class="info-row"><span>Role Name:</span><span><?= htmlspecialchars($p['role_name']) ?></span></div>
        <div class="info-row"><span>National ID:</span><span><?= htmlspecialchars($p['national_id']) ?></span></div>
        <div class="info-row"><span>Phone:</span><span><?= htmlspecialchars($p['phone']) ?></span></div>
        <div class="info-row"><span>Branch:</span><span><?= htmlspecialchars($p['branch']) ?></span></div>
        <div class="info-row"><span>Address:</span><span><?= htmlspecialchars($p['address']) ?></span></div>
        <div class="info-row"><span>Status:</span><span><?= htmlspecialchars($p['status']) ?></span></div>
        <div class="info-row"><span>Created At:</span><span><?= htmlspecialchars($p['created_at']) ?></span></div>
        <div class="info-row"><span>Updated At:</span><span><?= htmlspecialchars($p['updated_at']) ?></span></div>
        <button class="update-btn" onclick="openModal(<?= $p['id'] ?>)">Update</button>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
<div class="modal" id="updateModal">
    <div class="modal-content">
        <h3>Update Profile</h3>
        <form method="POST">
            <input type="hidden" name="user_id" id="user_id">
            <input type="email" name="email" placeholder="New Email" required>
            <input type="password" name="password" placeholder="New Password" required>
            <button type="submit">Save</button>
            <button type="button" class="close-btn" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function toggleTheme() {
    document.body.classList.toggle('dark-mode');
}
function openModal(id) {
    document.getElementById('updateModal').style.display = 'flex';
    document.getElementById('user_id').value = id;
}
function closeModal() {
    document.getElementById('updateModal').style.display = 'none';
}
</script>
