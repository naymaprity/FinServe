<?php
session_start();
require 'config/db.php';

$error = '';
$new_password_message = ''; // <-- নতুন password দেখানোর জন্য

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Fetch customer by username
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $customer = $stmt->fetch();

        if (!$customer) {
            $error = 'No account found with this username. Please check your credentials.';
        } elseif (!password_verify($password, $customer['password'])) {
            // <-- Forgot Password Logic Added Here -->
            // Generate new random password
            $new_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

            // Update new password in database
            $update_stmt = $pdo->prepare('UPDATE customers SET password = ? WHERE id = ?');
            $update_stmt->execute([$new_password_hashed, $customer['id']]);

            $new_password_message = "You entered incorrect password. A new temporary password has been generated: <strong>$new_password</strong>";
        } else {
            // Set session
            $_SESSION['customer'] = [
                'id' => $customer['id'],
                'full_name' => $customer['full_name'],
                'account_number' => $customer['account_number'],
                'balance' => $customer['balance']
            ];

            // Redirect to dashboard
            header('Location: customer/dashboard.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Login - Finserve</title>
<style>
* {margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
html, body {height:100%; width:100%; background: linear-gradient(135deg, #4e54c8, #8f94fb); display:flex; flex-direction:column;}
.login-wrapper {flex:1; display:flex; justify-content:center; align-items:center; padding:20px;}
.login-container {display:flex; width:900px; max-width:90%; height:500px; border-radius:15px; overflow:hidden; background: rgba(255,255,255,0.05); box-shadow:0 8px 32px rgba(0,0,0,0.3); backdrop-filter:blur(15px);}
.login-section {flex:1; padding:50px; display:flex; flex-direction:column; justify-content:center; background: rgba(255,255,255,0.08); border-radius:0 15px 15px 0; box-shadow: inset 0 0 20px rgba(0,0,0,0.2);}
.login-section h2 {color:#fff; font-size:32px; margin-bottom:30px; text-align:center; letter-spacing:1px;}
.login-form label {color:#fff; font-size:14px; margin-bottom:5px; display:block;}
.login-form input {width:100%; padding:14px; margin-bottom:20px; border:none; border-radius:10px; background: rgba(255,255,255,0.85); outline:none; font-size:16px; transition:0.3s;}
.login-form input:focus {background:rgba(255,255,255,1); box-shadow:0 0 12px rgba(255,255,255,0.7);}
.login-form button {width:100%; padding:14px; border:none; border-radius:10px; background:#fff; color:#4e54c8; font-weight:bold; font-size:18px; cursor:pointer; transition:0.4s; position:relative; overflow:hidden;}
.login-form button::before {content:""; position:absolute; width:0; height:100%; top:0; left:0; background:rgba(78,84,200,0.2); transition:0.4s;}
.login-form button:hover::before {width:100%;}
.login-form button:hover {color:#fff; background:#4e54c8;}
.error {background: rgba(255, 0, 0, 0.85); border: 2px solid #ff0000; border-radius: 10px; color: yellow; font-weight: bold; text-align: center; padding: 15px; margin-bottom: 20px; box-shadow: 0 0 20px rgba(255, 0, 0, 0.7), inset 0 0 10px rgba(255,255,0,0.3); animation: glow 1.5s infinite alternate;}
@keyframes glow {from {box-shadow: 0 0 15px rgba(255, 0, 0, 0.8);} to {box-shadow: 0 0 25px rgba(255, 255, 0, 0.8);} }
.login-help {color:#fff; text-align:center; margin-top:15px; font-size:14px;}
.logo-section {flex:1; display:flex; justify-content:center; align-items:center; flex-direction:column; background: rgba(255,255,255,0.05); border-radius:15px 0 0 15px;}
.logo-circle {width: 140px; height: 140px; border-radius: 50%; background: url('assets/logo.png') center center/cover no-repeat; box-shadow: 0 8px 30px rgba(0,0,0,0.4); margin-bottom: 25px; animation: bounce 2s infinite; display: flex; justify-content: center; align-items: center;}
@keyframes bounce {0%,100%{transform:translateY(0);} 50%{transform:translateY(-20px);} }
.logo-section h1 {color:#fff; font-size:32px; letter-spacing:3px; text-align:center; text-shadow:1px 1px 5px rgba(0,0,0,0.5);}
@media(max-width:900px){.login-container{flex-direction:column; height:auto;} .login-section,.logo-section{border-radius:15px; width:100%; padding:30px;} .logo-section{order:-1;} }
</style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-container">
        <div class="login-section">
            <h2>Customer Login</h2>
            <?php 
            if(!empty($error)) echo '<p class="error">'.htmlspecialchars($error).'</p>'; 
            if(!empty($new_password_message)) echo '<p class="error">'.$new_password_message.'</p>'; // <-- নতুন password দেখানো
            ?>
            <form method="post" action="" class="login-form">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter your username" required>

                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>

                <button type="submit">Login</button>
            </form>
            <p class="login-help">If You have No Username and Password Please Verify Your Account First.</p>
        </div>

        <div class="logo-section">
            <div class="logo-circle"></div>
            <h1>Finserve</h1>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
