<?php 
session_start();

require '../includes/header.php';
require '../config/db.php';

$success = $error = '';

// ✅ Fetch roles dynamically from roles table
$stmtRoles = $pdo->query("SELECT role_name FROM roles");
$roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['mobile']); // database column = phone
    $role_name = trim($_POST['role_name']); // must match roles table exactly
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $national_id = trim($_POST['national_id']);
    $address = trim($_POST['address']);

    // Get role_id from roles table
    $stmtRole = $pdo->prepare("SELECT id FROM roles WHERE role_name = ? LIMIT 1");
    $stmtRole->execute([$role_name]);
    $role = $stmtRole->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        $error = "⚠️ Invalid role selected.";
    } elseif ($name && $email && $phone && $username && $password && $national_id && $address) {
        $stmt = $pdo->prepare("
            INSERT INTO users (full_name, email, phone, role_name, role_id, username, password, national_id, address, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        if ($stmt->execute([$name, $email, $phone, $role_name, $role['id'], $username, $password, $national_id, $address])) {
            $success = "✅ Registration Successful! You can now login.";
        } else {
            $error = "⚠️ Something went wrong. Please try again.";
        }
    } else {
        $error = "⚠️ Please fill out all required fields.";
    }
}
?>

<style>
body { background: url('assets/images/logo.png') no-repeat center center/cover; font-family: 'Poppins', sans-serif; margin: 0; padding: 0; min-height: 100vh; display: flex; flex-direction: column; }
.main-content { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
.register-wrapper { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 18px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.18); width: 750px; padding: 40px; animation: fadeIn 1s ease-in-out; }
@keyframes fadeIn { from {opacity: 0; transform: translateY(-10px);} to {opacity: 1; transform: translateY(0);} }
.register-wrapper h2 { color: #0a3d91; font-weight: 700; margin-bottom: 25px; font-size: 24px; text-align: center; }
form { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px 25px; }
form label { text-align: left; font-weight: 600; color: #333; font-size: 14px; margin-bottom: 6px; display: block; }
form input, form select { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #ccc; transition: all 0.3s; font-size: 14px; }
form input:focus, form select:focus { border-color: #0a3d91; box-shadow: 0 0 6px rgba(10, 61, 145, 0.3); }
button { grid-column: span 2; width: 100%; background-color: #0a3d91; color: #fff; border: none; padding: 12px; font-weight: 600; border-radius: 8px; cursor: pointer; transition: 0.3s; font-size: 15px; }
button:hover { background-color: #072b6f; transform: scale(1.02); }
.success, .error { text-align: center; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: 500; font-size: 14px; }
.success { background-color: #d1f7c4; color: #145c2e; }
.error { background-color: #ffd1d1; color: #a10d0d; }
/* Back to Login Button */
.back-btn {
    display: block;
    text-align: center;
    margin: 15px 0 0 0;
    width: 100%;
    padding: 12px;
    background-color: #0a3d91;
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: 0.3s;
}
.back-btn:hover { background-color: #072b6f; transform: scale(1.02); }

@media(max-width: 600px) { .register-wrapper { width: 92%; padding: 30px 20px; } form { grid-template-columns: 1fr; } button { grid-column: span 1; } }
</style>

<main class="main-content">
  <div class="register-wrapper">
    <h2>Officer Registration</h2>

    <?php if($success): ?>
      <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php elseif($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div>
        <label>Full Name <span style="color:red">*</span></label>
        <input type="text" name="full_name" placeholder="Enter full name" required>
      </div>
      <div>
        <label>Email Address <span style="color:red">*</span></label>
        <input type="email" name="email" placeholder="Enter email address" required>
      </div>
      <div>
        <label>Mobile Number <span style="color:red">*</span></label>
        <input type="text" name="mobile" placeholder="+8801XXXXXXXXX" required>
      </div>
      <div>
        <label>National ID <span style="color:red">*</span></label>
        <input type="text" name="national_id" placeholder="Enter national ID" required>
      </div>
      <div>
        <label>Address <span style="color:red">*</span></label>
        <input type="text" name="address" placeholder="Enter address" required>
      </div>
      <div>
        <label>Post / Designation <span style="color:red">*</span></label>
        <select name="role_name" required>
          <option value="">-- Select Role --</option>
          <?php foreach($roles as $role): ?>
            <option value="<?= htmlspecialchars($role) ?>"><?= htmlspecialchars(ucwords(str_replace('_',' ',$role))) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Username <span style="color:red">*</span></label>
        <input type="text" name="username" placeholder="Create username" required>
      </div>
      <div>
        <label>Password <span style="color:red">*</span></label>
        <input type="password" name="password" placeholder="Create password" required>
      </div>
      <button type="submit">Register Officer</button>
    </form>

    <!-- Back to Login button BELOW submit button -->
    <?php if($success): ?>
      <a href="../admin/index.php" class="back-btn">Back to Login</a>
    <?php endif; ?>
  </div>
</main>

<?php require '../includes/footer.php'; ?>
