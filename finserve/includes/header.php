<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function is_logged_in(){ 
    return isset($_SESSION['user']); 
}

function require_role($roles = []) {
    if (!is_logged_in()) { 
        header('Location: /finserve/login.php'); 
        exit; 
    }
    if (!in_array($_SESSION['user']['role_name'], $roles)) {
        echo "<h3>Access denied. Your role: " . htmlspecialchars($_SESSION['user']['role_name']) . "</h3>";
        echo "<a href='/finserve/logout.php'>Logout</a>";
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Finserve Bank</title>
  <style>
    /* Reset */
    * {
        margin:0; 
        padding:0; 
        box-sizing:border-box; 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    body {
        background: #f5f6fa; 
        color: #333;
    }

    /* Header styling */
    header {
        position: sticky;
        top:0;
        width:100%;
        background: rgba(15, 20, 115, 0.9);
        color:#fff;
        display:flex;
        justify-content: space-between;
        align-items: center;
        padding:15px 40px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        z-index:1000;
        transition: background 0.3s ease;
    }

    /* Logo + Title */
    .logo-container {
        display:flex;
        align-items:center;
        gap:10px;
    }

    .logo-container img {
        width:40px;
        height:40px;
        border-radius:50%;
        object-fit:cover;
        box-shadow:0 2px 6px rgba(0,0,0,0.2);
        transition: transform 0.3s ease;
    }

    .logo-container img:hover {
        transform: scale(1.1);
    }

    .logo-container h1 {
        font-size: 24px;
        letter-spacing:1.5px;
        cursor: default;
    }

    /* Navigation */
    .nav {
        font-size: 14px;
        display:flex;
        align-items:center;
        gap:15px;
    }

    .nav a {
        color:#fff;
        text-decoration:none;
        font-weight:500;
        position:relative;
        transition:0.3s;
    }

    .nav a::after {
        content:"";
        position:absolute;
        width:0;
        height:2px;
        left:0;
        bottom:-3px;
        background:#fff;
        transition:0.3s;
    }

    .nav a:hover::after {
        width:100%;
    }

    /* Responsive */
    @media(max-width:768px){
        header {
            flex-direction: column;
            align-items: flex-start;
            padding: 15px 25px;
        }
        .nav {
            margin-top:10px;
            flex-wrap: wrap;
        }
        .logo-container img {
            width:35px;
            height:35px;
        }
        .logo-container h1 {
            font-size:20px;
        }
    }
  </style>
</head>

<body>
<header id="main-header">
  <div class="logo-container">
    <img src="/finserve/assets/logo.png" alt="Finserve Logo">
    <h1>Finserve Bank</h1>
  </div>

  <?php if(is_logged_in()): ?>
    <div class="nav">
      Hello, <?= htmlspecialchars($_SESSION['user']['full_name']) ?> | 
      <a href="../admin/index.php">Logout</a>
    </div>
  <?php endif; ?>
</header>

<script>
  // Header scroll effect
  const header = document.getElementById('main-header');
  window.addEventListener('scroll', () => {
      if(window.scrollY > 50){
          header.style.background = 'rgba(7, 13, 121, 1)';
          header.style.boxShadow = '0 6px 20px rgba(0,0,0,0.3)';
      } else {
          header.style.background = 'rgba(10, 16, 112, 0.9)';
          header.style.boxShadow = '0 4px 10px rgba(0,0,0,0.2)';
      }
  });
</script>

<main>
