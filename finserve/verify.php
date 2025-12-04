<?php
session_start();
require 'config/db.php';

$success = $error = '';
$generated_username = $generated_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_number = trim($_POST['account_number']);
    $login_code = trim($_POST['login_code']);

    // ✅ Validate format
    if (!preg_match('/^[A-Za-z0-9]{12}$/', $account_number)) {
        $error = '❌ Invalid Account Number. Must be 12 alphanumeric characters.';
    } elseif (!preg_match('/^[A-Za-z0-9]{10}$/', $login_code)) {
        $error = '❌ Invalid Login Code. Must be 10 alphanumeric characters.';
    } else {
        // ✅ Check if account exists
        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE account_number = ? AND login_code = ? LIMIT 1");
        $stmt->execute([$account_number, $login_code]);
        $acct = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$acct) {
            $error = "⚠️ No account found with this Account Number and Login Code.";
        } else {
            // ✅ Check if already verified
            $stmt2 = $pdo->prepare("SELECT * FROM customers WHERE account_number = ? LIMIT 1");
            $stmt2->execute([$account_number]);
            $customer = $stmt2->fetch(PDO::FETCH_ASSOC);

            if ($customer && !empty($customer['username']) && !empty($customer['password'])) {
                $error = "✅ Account already verified. Use your existing credentials to login.";
                $generated_username = $customer['username'];
                $generated_password = '********';
            } else {
                // ✅ Generate unique username & password
                $generated_username = strtolower(substr(preg_replace('/\s+/', '', $acct['full_name']), 0, 3)) . rand(100, 999);
                $generated_password = bin2hex(random_bytes(4));
                $hashed_password = password_hash($generated_password, PASSWORD_BCRYPT);

                if (!$customer) {
                    // Insert new verified customer
                    $insert = $pdo->prepare("INSERT INTO customers (account_number, account_type, balance, full_name, username, password) VALUES (?, ?, ?, ?, ?, ?)");
                    $insert->execute([
                        $acct['account_number'],
                        $acct['account_type'],
                        $acct['balance'],
                        $acct['full_name'],
                        $generated_username,
                        $hashed_password
                    ]);
                } else {
                    // Update existing customer record if exists but empty username/password
                    $upd = $pdo->prepare("UPDATE customers SET username = ?, password = ? WHERE account_number = ?");
                    $upd->execute([$generated_username, $hashed_password, $account_number]);
                }

                $success = "🎉 Account verified successfully! Your login credentials are shown below.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Verification - FinServe</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:linear-gradient(135deg,#4e54c8,#8f94fb);display:flex;justify-content:center;align-items:center;min-height:100vh;}
.container{width:420px;background:rgba(255,255,255,0.05);padding:35px;border-radius:15px;box-shadow:0 8px 32px rgba(0,0,0,0.3);backdrop-filter:blur(15px);}
h2{text-align:center;color:#fff;margin-bottom:25px;}
input{width:100%;padding:12px;margin-bottom:15px;border:none;border-radius:10px;background:rgba(255,255,255,0.85);font-size:15px;}
input:focus{background:#fff;box-shadow:0 0 10px rgba(255,255,255,0.7);}
button{width:100%;padding:12px;border:none;border-radius:10px;background:#fff;color:#4e54c8;font-weight:bold;font-size:16px;cursor:pointer;transition:0.3s;}
button:hover{background:#4e54c8;color:#fff;}
.success{background:rgba(0,128,0,0.9);color:#fff;padding:10px;border-radius:8px;text-align:center;margin-bottom:15px;}
.error{background:rgba(255,0,0,0.85);color:#fff;padding:10px;border-radius:8px;text-align:center;margin-bottom:15px;}
.credentials{background:#fff3cd;color:#856404;padding:12px;border-radius:8px;text-align:center;margin-top:10px;font-weight:bold;}
a.button-link{text-decoration:none;display:block;width:100%;text-align:center;padding:12px;margin-top:10px;border-radius:10px;background:#fff;color:#4e54c8;font-weight:bold;font-size:16px;transition:0.3s;}
a.button-link:hover{background:#4e54c8;color:#fff;}
</style>
</head>
<body>
  <div class="container">
    <h2>Verify Your Account</h2>

    <?php if($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if($success): ?>
      <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="account_number" placeholder="Account Number (12 chars)" pattern="[A-Za-z0-9]{12}" required>
      <input type="text" name="login_code" placeholder="Login Code (10 chars)" pattern="[A-Za-z0-9]{10}" required>
      <button type="submit">Verify Account</button>
    </form>

    <?php if($generated_username && $generated_password && empty($error)): ?>
      <div class="credentials">
        Username: <strong><?= htmlspecialchars($generated_username) ?></strong><br>
        Password: <strong><?= htmlspecialchars($generated_password) ?></strong>
      </div>
      <a href="login.php" class="button-link">Go to Login</a>
    <?php endif; ?>
  </div>
</body>
</html>
