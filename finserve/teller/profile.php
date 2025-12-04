<?php
session_start();
require '../config/db.php';

$tid = $_SESSION['user']['id'];

// ✅ Fetch teller info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$tid]);
$teller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teller) {
    echo "<p style='text-align:center;color:red;'>Teller info not found!</p>";
    exit;
}

// ✅ Count new messages from Branch Manager (role_id=5)
$msgCountStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM messages m
    JOIN users u ON m.sender_id=u.id
    WHERE m.receiver_id=? AND u.role_id=5 AND m.is_read=0");
$msgCountStmt->execute([$tid]);
$msgCount = $msgCountStmt->fetch(PDO::FETCH_ASSOC)['cnt'];

$success = $error = '';

// ✅ Handle Update (username + optional password)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $change_pass = $_POST['change_password'] ?? 'no';
    $old_password = trim($_POST['old_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');

    $password_hash = $teller['password'];

    // Password change handling
    if ($change_pass === 'yes') {
        if (empty($old_password) || empty($new_password)) {
            $error = "Please enter both old and new passwords.";
        } elseif (!password_verify($old_password, $teller['password'])) {
            $error = "Old password is incorrect.";
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        }
    }

    if (!$error) {
        $upd = $pdo->prepare("UPDATE users SET username=?, password=? WHERE id=?");
        $upd->execute([$new_username, $password_hash, $tid]);
        $success = "Profile updated successfully!";
        $_SESSION['user']['username'] = $new_username;
        // fetch updated info
        $stmt->execute([$tid]);
        $teller = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!-- ===== Header ===== -->
<header style="background:#0f172a;padding:15px;color:white;display:flex;justify-content:space-between;align-items:center;">
    <div style="display:flex;align-items:center;gap:10px;">
        <img src="../assets/logo.png" alt="Logo" style="width:40px;height:40px;">
        <h2 style="margin:0;font-size:20px;">FinServe Teller</h2>
    </div>
    <div style="display:flex;gap:12px;">
        <a href="dashboard.php" style="background:#FFD700;color:#000;padding:8px 16px;border-radius:8px;font-weight:bold;text-decoration:none;">
            Back to Dashboard
            <?php if($msgCount > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:2px 6px;font-size:12px;margin-left:5px;"><?= $msgCount ?></span>
            <?php endif; ?>
        </a>
        <a href="../logout.php" style="background:#ef4444;color:#fff;padding:8px 16px;border-radius:8px;font-weight:bold;text-decoration:none;">Logout</a>
    </div>
</header>

<style>
body { font-family:'Poppins',sans-serif; margin:0; background:#f4f6fa; color:#111; }
.container{max-width:850px;margin:50px auto;padding:20px;}
.profile-card { background:#fff;border-radius:16px;padding:30px;box-shadow:0 4px 15px rgba(0,0,0,0.1); }
.profile-header { display:flex;align-items:center;gap:15px;border-bottom:2px solid #8bbaf8;margin-bottom:15px;padding-bottom:10px; }
.profile-header img { width:80px;height:80px;border-radius:50%;border:3px solid #0066cc;object-fit:cover; }
.profile-header h3 { font-size:18px;margin:0; }
.info-row { display:grid;grid-template-columns:200px auto;margin-bottom:10px; }
.info-row span:first-child { font-weight:600;color:#333; }
.update-btn { background:#0066cc;color:white;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;margin-top:15px; }
.update-btn:hover { background:#004c99; }
/* Modal */
.modal {display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;}
.modal-content{background:#fff;padding:25px;border-radius:12px;width:350px;}
.modal-content h3{margin-bottom:10px;}
.modal-content input, .modal-content select{width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:10px;}
.modal-content button{background:#0066cc;color:white;border:none;padding:8px 18px;border-radius:8px;cursor:pointer;}
.close-btn{background:#dc3545;margin-left:10px;}
</style>

<div class="container">
    <h2 style="text-align:center;margin-bottom:25px;">Teller Profile</h2>

    <?php if($success): ?><p style="color:green;text-align:center;"><?= $success ?></p><?php endif; ?>
    <?php if($error): ?><p style="color:red;text-align:center;"><?= $error ?></p><?php endif; ?>

    <div class="profile-card">
        <div class="profile-header">
            <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Teller Icon">
            <div>
                <h3><?= htmlspecialchars($teller['full_name']) ?></h3>
                <small><?= htmlspecialchars($teller['email']) ?></small>
            </div>
        </div>

        <!-- User Information -->
        <div class="info-row"><span>Full Name:</span><span><?= htmlspecialchars($teller['full_name']) ?></span></div>
        <div class="info-row"><span>Email:</span><span><?= htmlspecialchars($teller['email']) ?></span></div>
        <div class="info-row"><span>Phone:</span><span><?= htmlspecialchars($teller['phone']) ?></span></div>
        <div class="info-row"><span>Username:</span><span><?= htmlspecialchars($teller['username']) ?></span></div>
        <div class="info-row"><span>National ID:</span><span><?= htmlspecialchars($teller['national_id']) ?></span></div>
        <div class="info-row"><span>Address:</span><span><?= htmlspecialchars($teller['address']) ?></span></div>
        <div class="info-row"><span>Role:</span><span><?= htmlspecialchars($teller['role_name']) ?></span></div>
        <div class="info-row"><span>Branch:</span><span><?= htmlspecialchars($teller['branch']) ?></span></div>
        <div class="info-row"><span>Status:</span>
            <?php if (strtolower($teller['status']) === 'active'): ?>
                <span style="color:green;font-weight:600;">Active</span>
            <?php else: ?>
                <span style="color:red;font-weight:600;">Inactive</span>
            <?php endif; ?>
        </div>
        <div class="info-row"><span>Created At:</span><span><?= htmlspecialchars($teller['created_at']) ?></span></div>
        <div class="info-row"><span>Updated At:</span><span><?= htmlspecialchars($teller['updated_at'] ?? '—') ?></span></div>

        <button class="update-btn" onclick="openModal()">Update Profile</button>
    </div>
</div>

<!-- Modal -->
<div class="modal" id="updateModal">
    <div class="modal-content">
        <h3>Update Information</h3>
        <form method="POST">
            <input type="text" name="username" value="<?= htmlspecialchars($teller['username']) ?>" placeholder="New Username" required>

            <label>Do you want to change your password?</label>
            <select name="change_password" id="changePassword" onchange="togglePasswordFields()" required>
                <option value="no">No</option>
                <option value="yes">Yes</option>
            </select>

            <div id="passwordFields" style="display:none;">
                <input type="password" name="old_password" placeholder="Enter Old Password">
                <input type="password" name="new_password" placeholder="Enter New Password">
            </div>

            <button type="submit">Save</button>
            <button type="button" class="close-btn" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function openModal() { document.getElementById('updateModal').style.display='flex'; }
function closeModal() { document.getElementById('updateModal').style.display='none'; }
function togglePasswordFields() {
    const select = document.getElementById('changePassword');
    document.getElementById('passwordFields').style.display = (select.value==='yes')?'block':'none';
}
</script>
