
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Finserve | Empowering Digital Banking</title>

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: #f8f9fa;
      overflow-x: hidden;
      scroll-behavior: smooth;
    }

    /* ---------- NAVBAR ---------- */
    header {
      background: #003366;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 60px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 1000;
    }

    .logo {
      display: flex;
      align-items: center;
      font-weight: bold;
      font-size: 1.5rem;
      color: #fff;
    }

    .logo img {
      height: 40px;
      margin-right: 10px;
      border-radius: 5px;
    }

    nav ul {
      list-style: none;
      display: flex;
      align-items: center;
    }

    nav ul li {
      margin: 0 20px;
      position: relative;
    }

    nav ul li a {
      text-decoration: none;
      color: white;
      font-weight: 500;
      transition: color 0.3s;
      cursor: pointer;
    }

    nav ul li a:hover {
      color: #00ccff;
    }

    /* Hamburger 3-dot */
    .hamburger {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      width: 20px;
      height: 15px;
      cursor: pointer;
    }

    .hamburger span {
      display: block;
      height: 3px;
      background-color: white;
      border-radius: 2px;
    }

    /* Dropdown menu */
    .menu-dropdown {
      display: none;
      position: absolute;
      top: 25px;
      right: 0;
      background-color: #004080;
      border-radius: 6px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      z-index: 1001;
      flex-direction: column;
      min-width: 180px;
    }

    .menu-dropdown a {
      color: white;
      padding: 10px 15px;
      display: block;
      text-decoration: none;
      font-weight: 500;
    }

    .menu-dropdown a:hover {
      background-color: #0066cc;
    }

    /* Sub-dropdown for login roles */
    .sub-dropdown {
      display: none;
      flex-direction: column;
      background-color: #0059b3;
      border-radius: 6px;
      margin-top: 5px;
    }

    .sub-dropdown a {
      padding-left: 25px;
    }

    .menu-dropdown .login-option.active + .sub-dropdown {
      display: flex;
    }

    /* ---------- HERO SECTION ---------- */
    .hero {
      margin-top: 80px;
      height: 90vh;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      color: #fff;
      position: relative;
      overflow: hidden;
    }

    .hero video {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: 0;
    }

    /* ---------- ABOUT SECTION ---------- */
    .about {
      padding: 100px 80px;
      background: #fff;
      text-align: center;
    }

    .about h2 {
      color: #003366;
      font-size: 2.8rem;
      margin-bottom: 25px;
    }

    .about p {
      color: #444;
      line-height: 1.9;
      font-size: 1.2rem;
      max-width: 950px;
      margin: auto;
    }

    /* ---------- SERVICES SECTION ---------- */
    .services {
      background: #f0f6fb;
      text-align: center;
      padding: 80px 40px;
    }

    .services h2 {
      color: #003366;
      font-size: 2.4rem;
      margin-bottom: 40px;
    }

    .service-container {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 25px;
    }

    .service-box {
      background: #fff;
      border-radius: 10px;
      padding: 30px;
      width: 280px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
    }

    .service-box:hover {
      transform: translateY(-10px);
    }

    .service-box h3 {
      color: #003366;
      margin-bottom: 15px;
    }

    .service-box p {
      color: #555;
      font-size: 1rem;
      line-height: 1.6;
    }

    /* ---------- CAREER SECTION ---------- */
    .career {
      position: relative;
      text-align: center;
      color: white;
      padding: 100px 40px;
      background-color: #003366;
      overflow: hidden;
    }

    .career video {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: 0;
      filter: brightness(60%);
    }

    .career-content {
      position: relative;
      z-index: 2;
      max-width: 700px;
      margin: auto;
    }

    /* ---------- FOOTER ---------- */
    footer {
      background: #003366;
      color: #fff;
      text-align: center;
      padding: 25px 10px;
      margin-top: 60px;
    }

    footer p {
      margin: 5px 0;
    }

    footer a {
      color: #00ccff;
      text-decoration: none;
      font-weight: 500;
    }

    footer a:hover {
      text-decoration: underline;
    }

  </style>
</head>

<body>

  <!-- HEADER -->
  <header>
    <div class="logo">
      <img src="../assets/logo.png" alt="Finserve Logo">
      Finserve Bank
    </div>
    <nav>
      <ul>
        <li><a href="#home">Home</a></li>
        <li><a href="#about">About Us</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#career">Career</a></li>
        <li style="position:relative;">
          <div class="hamburger" id="menuToggle">
            <span></span>
            <span></span>
            <span></span>
          </div>
          <!-- Dropdown menu -->
<div class="menu-dropdown" id="menuDropdown">
  <a href="/finserve/admin/register_officer.php">Registration</a>
  <a class="login-option">Login ▾</a>
  <div class="sub-dropdown">
    <a href="/finserve/account_officer/account_officer_login.php">Account Officer</a>
    <a href="/finserve/loan_officer/loan_officer_login.php">Loan Officer</a>
    <a href="/finserve/branch_manager/branch_manager_login.php">Branch Manager</a>
    <a href="/finserve/md/managing_director_login.php">MD</a>
    <a href="/finserve/gm/general_manager_login.php">GM</a>
    <a href="/finserve/dmd/deputy_md_login.php">DMD</a>
    <a href="/finserve/teller/teller_login.php">Teller</a>
    <a href="/finserve/it/it_officer_login.php">IT Officer</a>
  </div>
</div>
        </li>
      </ul>
    </nav>
  </header>

  <!-- HERO SECTION -->
  <section class="hero" id="home">
    <video autoplay muted loop>
      <source src="../assets/bank-bg.mp4" type="video/mp4">
    </video>
  </section>

  <!-- ABOUT SECTION -->
  <section class="about" id="about">
    <h2>About Finserve</h2>
    <p>
      Finserve Bank is a next-generation digital financial institution built to redefine the banking 
      experience in Bangladesh. We believe banking should be smart, transparent, and accessible for everyone — 
      anytime, anywhere. Our mission is to empower individuals, entrepreneurs, and businesses by 
      offering seamless financial solutions through innovation and trust.
    </p>
  </section>

  <!-- SERVICES SECTION -->
  <section class="services" id="services">
    <h2>Our Services</h2>
    <div class="service-container">
      <div class="service-box">
        <h3>Online Banking</h3>
        <p>Access your account anytime, anywhere with our secure online banking system.</p>
      </div>
      <div class="service-box">
        <h3>Corporate Solutions</h3>
        <p>Empowering businesses with customized financial solutions and partnerships.</p>
      </div>
      <div class="service-box">
        <h3>Card Services</h3>
        <p>Enjoy the convenience of Finserve credit and debit cards with exclusive benefits.</p>
      </div>
    </div>
  </section>

  <!-- CAREER SECTION -->
  <section class="career" id="career">
    <video autoplay muted loop>
      <source src="../assets/career.mp4" type="video/mp4">
    </video>
    <div class="career-content">
      <h2>Join Our Team</h2>
      <p>Be part of Finserve’s mission to redefine the banking experience in Bangladesh.  
         Explore exciting opportunities and grow your career with us today!</p>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <p>&copy; <?=date('Y')?> Finserve Bank. All Rights Reserved.</p>
    <p>Developed by <a href="#">Nayma Jahan Chowdhury</a></p>
  </footer>

  <script>
    const menuToggle = document.getElementById('menuToggle');
    const menuDropdown = document.getElementById('menuDropdown');
    const loginOption = document.querySelector('.login-option');

    // Hamburger click toggle
    menuToggle.addEventListener('click', () => {
      if(menuDropdown.style.display === 'flex') {
        menuDropdown.style.display = 'none';
      } else {
        menuDropdown.style.display = 'flex';
        menuDropdown.style.flexDirection = 'column';
      }
    });

    // Login click toggle
    loginOption.addEventListener('click', () => {
      loginOption.classList.toggle('active');
    });

    // Click outside to close menu
    window.addEventListener('click', function(e) {
      if (!menuToggle.contains(e.target) && !menuDropdown.contains(e.target)) {
        menuDropdown.style.display = 'none';
        loginOption.classList.remove('active');
      }
    });
  </script>

</body>
</html>
