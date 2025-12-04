<?php
session_start();
require '../config/db.php';

// Login check
if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    header("Location: ../login.php");
    exit;
}

$cid = $_SESSION['customer']['id'];

// Fetch customer info first
$stmt_cust = $pdo->prepare("SELECT username, password, transaction_pin, account_number FROM customers WHERE id = ?");
$stmt_cust->execute([$cid]);
$cust = $stmt_cust->fetch(PDO::FETCH_ASSOC);
if (!$cust) {
    echo "<p style='text-align:center;color:red;'>Customer info not found!</p>";
    exit;
}

// Fetch related account info using account_number
$stmt_acc = $pdo->prepare("SELECT * FROM accounts WHERE account_number = ?");
$stmt_acc->execute([$cust['account_number']]);
$acct = $stmt_acc->fetch(PDO::FETCH_ASSOC);
if (!$acct) {
    echo "<p style='text-align:center;color:red;'>Account not found!</p>";
    exit;
}


$success = $error = '';

// ✅ Update Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $change_pass = $_POST['change_password'] ?? 'no';
    $old_password = trim($_POST['old_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $new_pin = trim($_POST['transaction_pin']);

    if (!empty($new_username)) {
        $password_hash = $cust['password'];

        // If password change selected
        if ($change_pass === 'yes') {
            if (empty($old_password) || empty($new_password)) {
                $error = "Please enter both old and new passwords.";
            } elseif (!password_verify($old_password, $cust['password'])) {
                $error = "Old password is incorrect.";
            } else {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            }
        }

        if (!$error) {
            $upd_cust = $pdo->prepare("UPDATE customers SET username = ?, password = ?, transaction_pin = ? WHERE id = ?");
            $upd_cust->execute([$new_username, $password_hash, $new_pin, $cid]);
            $success = "Profile updated successfully!";
            $stmt_cust->execute([$cid]);
            $cust = $stmt_cust->fetch(PDO::FETCH_ASSOC);
        }
    } else {
        $error = "Username is required.";
    }
}
?>

<!-- ===== Header ===== -->
<header style="background:#0066cc;padding:15px;color:white;display:flex;justify-content:space-between;align-items:center;">
    <div style="display:flex;align-items:center;gap:10px;">
        <img src="../assets/logo.png" alt="Logo" style="width:35px;height:35px;">
        <h2 style="margin:0;font-size:20px;">FinServe</h2>
    </div>
    <a href="../logout.php" style="color:white;text-decoration:none;background:#dc3545;padding:6px 14px;border-radius:6px;">Logout</a>
</header>

<style>
body {
    background: var(--bg-color);
    color: var(--text-color);
    font-family:'Poppins',sans-serif;
    margin:0;
}
:root { --bg-color: #f8f9fc; --text-color: #1d1d1d; --card-bg: #fff; }
.dark-mode { --bg-color: #181a1b; --text-color: #f8f9fa; --card-bg: #242526; }
.container{max-width:800px;margin:50px auto;padding:20px;}
.theme-toggle { position: absolute; top: 80px; right: 40px; background: #0066cc; color: #fff; border: none; padding: 6px 14px; border-radius: 8px; cursor: pointer; font-size: 14px; transition: 0.3s; }
.theme-toggle:hover { background: #004c99; }
.profile-card {
    background: var(--card-bg);
    border-radius:16px;
    padding:30px;
    box-shadow:0 4px 15px rgba(0,0,0,0.1);
}
.profile-header{display:flex;align-items:center;gap:15px;border-bottom:2px solid #8bbaf8ff;margin-bottom:15px;padding-bottom:10px;}
.profile-header img{width:70px;height:70px;border-radius:50%;border:3px solid #0066cc;object-fit:cover;}
.profile-header h3{font-size:18px;margin:0;}
.info-row{display:grid;grid-template-columns:180px auto;margin-bottom:8px;}
.update-btn{background:#0066cc;color:white;border:none;padding:6px 18px;border-radius:8px;cursor:pointer;margin-top:15px;}
.update-btn:hover{background:#004c99;}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;}
.modal-content{background:#fff;padding:25px;border-radius:12px;width:350px;}
.modal-content h3{margin-bottom:10px;}
.modal-content input, .modal-content select{width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:10px;}
.modal-content button{background:#0066cc;color:white;border:none;padding:8px 18px;border-radius:8px;cursor:pointer;}
.close-btn{background:#dc3545;margin-left:10px;}
</style>

<button class="theme-toggle" onclick="toggleTheme()">🌙</button>

<div class="container">
    <h2 style="text-align:center;margin-bottom:25px;">Customer Profile</h2>

    <?php if($success): ?><p style="color:green;text-align:center;"><?= $success ?></p><?php endif; ?>
    <?php if($error): ?><p style="color:red;text-align:center;"><?= $error ?></p><?php endif; ?>

    <div class="profile-card">
        <div class="profile-header">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="User">
            <div>
                <h3><?= htmlspecialchars($acct['full_name']) ?></h3>
                <small><?= htmlspecialchars($acct['email']) ?></small>
            </div>
        </div>

        <div class="info-row"><span>Username:</span><span><?= htmlspecialchars($cust['username']) ?></span></div>
        <div class="info-row"><span>Account Number:</span><span><?= htmlspecialchars($acct['account_number']) ?></span></div>
        <div class="info-row"><span>Account Type:</span><span><?= htmlspecialchars($acct['account_type']) ?></span></div>
        <div class="info-row"><span>Balance:</span><span><?= number_format($acct['balance'],2) ?> BDT</span></div>
        <div class="info-row"><span>Phone:</span><span><?= htmlspecialchars($acct['phone']) ?></span></div>
        <div class="info-row"><span>NID:</span><span><?= htmlspecialchars($acct['nid']) ?></span></div>
        <div class="info-row"><span>DOB:</span><span><?= htmlspecialchars($acct['dob']) ?></span></div>
        <div class="info-row"><span>Address:</span><span><?= htmlspecialchars($acct['address']) ?></span></div>
        <div class="info-row"><span>Transaction PIN:</span><span><?= htmlspecialchars($cust['transaction_pin'] ?? 'Not set') ?></span></div>
        <div class="info-row"><span>Nominee ID:</span><span><?= htmlspecialchars($acct['nominee_id']) ?></span></div>
        <div class="info-row"><span>Trade License ID:</span><span><?= htmlspecialchars($acct['trade_license_id']) ?></span></div>
        <div class="info-row"><span>Created At:</span><span><?= htmlspecialchars($acct['created_at']) ?></span></div>

        <button class="update-btn" onclick="openModal()">Update Profile</button>
    </div>
</div>

<!-- Modal -->
<div class="modal" id="updateModal">
    <div class="modal-content">
        <h3>Update Information</h3>
        <form method="POST">
            <input type="text" name="username" value="<?= htmlspecialchars($cust['username']) ?>" placeholder="New Username" required>

            <label>Do you want to change your password?</label>
            <select name="change_password" id="changePassword" onchange="togglePasswordFields()" required>
                <option value="no">No</option>
                <option value="yes">Yes</option>
            </select>

            <div id="passwordFields" style="display:none;">
                <input type="password" name="old_password" placeholder="Enter Old Password">
                <input type="password" name="new_password" placeholder="Enter New Password">
            </div>

            <input type="text" name="transaction_pin" value="<?= htmlspecialchars($cust['transaction_pin'] ?? '') ?>" placeholder="Set Transaction PIN">
            <button type="submit">Save</button>
            <button type="button" class="close-btn" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function toggleTheme() {
    document.body.classList.toggle('dark-mode');
}

function openModal() {
    document.getElementById('updateModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('updateModal').style.display = 'none';
}

function togglePasswordFields() {
    const select = document.getElementById('changePassword');
    const fields = document.getElementById('passwordFields');
    fields.style.display = (select.value === 'yes') ? 'block' : 'none';
}
</script>
