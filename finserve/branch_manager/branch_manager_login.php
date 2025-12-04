<?php
session_start();



require '../config/db.php'; // Adjust path according to your folder structure

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Fetch user with role_id = 5
    $stmt = $pdo->prepare('
        SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE username = ? AND u.role_id = 5 
        LIMIT 1
    ');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // For hashed passwords
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header('Location: /finserve/branch_manager/dashboard.php');
            exit;
        } else {
            $error = 'Login failed: Incorrect password';
        }
    } else {
        $error = 'Sorry, You Have Not Registered Yet. Please Register First. Thank You For Your Concern.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Officer Login - Finserve</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        html, body {
            height: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #4e54c8, #8f94fb);
        }

        .login-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            display: flex;
            width: 900px;
            max-width: 90%;
            height: 500px;
            border-radius: 15px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(15px);
        }

        .login-section {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 0 15px 15px 0;
            backdrop-filter: blur(20px);
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.2);
        }

        .login-section h2 {
            color: #fff;
            font-size: 32px;
            margin-bottom: 30px;
            text-align: center;
            letter-spacing: 1px;
        }

        .login-form label {
            color: #fff;
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }

        .login-form input {
            width: 100%;
            padding: 14px;
            margin-bottom: 20px;
            border: none;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.85);
            outline: none;
            font-size: 16px;
            transition: 0.3s;
        }

        .login-form input:focus {
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.7);
        }

        .login-form button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #fff;
            color: #4e54c8;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: 0.4s;
            position: relative;
            overflow: hidden;
        }

        .login-form button::before {
            content: "";
            position: absolute;
            width: 0;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(78, 84, 200, 0.2);
            transition: 0.4s;
        }

        .login-form button:hover::before {
            width: 100%;
        }

        .login-form button:hover {
            color: #fff;
            background: #4e54c8;
        }

        /* 🔥 Error Box Styling */
        .error {
            background-color: rgba(255, 0, 0, 0.8);
            color: yellow;
            border: 2px solid #ff0000;
            border-radius: 8px;
            padding: 12px 18px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            box-shadow: 0 0 15px rgba(255, 0, 0, 0.6);
        }

        .login-help {
            color: #fff;
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }

        .logo-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px 0 0 15px;
        }

        .logo-circle {
    width:140px;
    height:140px;
    border-radius:50%;
    background: #fff url('../assets/logo.png') center center/cover no-repeat; /* এখানে লোগো path */
    box-shadow:0 8px 30px rgba(0,0,0,0.4);
    margin-bottom:25px;
    animation:bounce 2s infinite;
    display:flex;
    justify-content:center;
    align-items:center;
}

/* Remove text "F" inside circle */


        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .logo-section h1 {
            color: #fff;
            font-size: 32px;
            letter-spacing: 3px;
            text-align: center;
            text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.5);
        }

        @media(max-width: 900px) {
            .login-container {
                flex-direction: column;
                height: auto;
            }

            .login-section,
            .logo-section {
                border-radius: 15px;
                width: 100%;
                padding: 30px;
            }

            .logo-section {
                order: -1;
            }
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <div class="login-container">

            <div class="login-section">
                <h2>Branch Manager Login</h2>

                <?php if (!empty($error)) : ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="" class="login-form">
                    <label>Username</label>
                    <input type="text" name="username" required>

                    <label>Password</label>
                    <input type="password" name="password" required>

                    <button type="submit">Login</button>
                </form>

                <p class="login-help">Forgot password? Contact admin.</p>
            </div>

            <div class="logo-section">
                <div class="logo-circle"></div>
                <h1>Finserve</h1>
            </div>

        </div>
    </div>

    <?php require '../includes/footer.php'; ?>
</body>
</html>
