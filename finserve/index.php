<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Finserve | Empowering Digital Banking</title>
<style>
/* ---------- Reset & Body ---------- */
* {margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
body {background-color: #f8f9fa; overflow-x: hidden; scroll-behavior: smooth;}

/* ---------- NAVBAR ---------- */
header {background: #003366; color: white; display: flex; justify-content: space-between; align-items: center; padding: 15px 60px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); position: fixed; width: 100%; top: 0; z-index: 1000;}
.logo {display:flex; align-items:center; font-weight:bold; font-size:1.5rem; color:#fff;}
.logo img {height:40px; margin-right:10px; border-radius:5px;}
nav ul {list-style:none; display:flex; align-items:center;}
nav ul li {margin:0 20px;}
nav ul li a {text-decoration:none; color:white; font-weight:500; transition: color 0.3s;}
nav ul li a:hover {color:#00ccff;}

/* ---------- HERO ---------- */
.hero {margin-top:80px; height:90vh; display:flex; justify-content:center; align-items:center; text-align:center; color:#fff; position:relative; overflow:hidden;}
.hero video {position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; z-index:0;}
.hero-content {position:relative; z-index:2; max-width:700px; padding:20px; animation:fadeIn 2s ease-in-out;}
.hero-content h1 {font-size:3.5rem; margin-bottom:15px;}
.hero-content p {font-size:1.2rem; margin-bottom:25px;}
.hero-content a {background:#00ccff; color:#003366; padding:12px 28px; border-radius:6px; font-weight:bold; text-decoration:none; transition: background 0.3s ease;}
.hero-content a:hover {background:#00a3cc;}

@keyframes fadeIn {from {opacity:0; transform:translateY(30px);} to {opacity:1; transform:translateY(0);}}

/* ---------- Sections ---------- */
.about {padding:100px 80px; background:#fff; text-align:center;}
.about h2 {color:#003366; font-size:2.8rem; margin-bottom:25px;}
.about p {color:#444; line-height:1.9; font-size:1.2rem; max-width:950px; margin:auto;}

.services {background:#f0f6fb; text-align:center; padding:80px 40px;}
.services h2 {color:#003366; font-size:2.4rem; margin-bottom:40px;}
.service-container {display:flex; justify-content:center; flex-wrap:wrap; gap:25px;}
.service-box {background:#fff; border-radius:10px; padding:30px; width:280px; box-shadow:0 3px 10px rgba(0,0,0,0.1); transition: transform 0.3s ease;}
.service-box:hover {transform:translateY(-10px);}
.service-box h3 {color:#003366; margin-bottom:15px;}
.service-box p {color:#555; font-size:1rem; line-height:1.6;}

.career {position:relative; text-align:center; color:white; padding:100px 40px; background-color:#003366; overflow:hidden;}
.career video {position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; z-index:0; filter:brightness(60%);}
.career-content {position:relative; z-index:2; max-width:700px; margin:auto;}
.career-content h2 {font-size:2.5rem; margin-bottom:20px;}
.career-content p {font-size:1.2rem; line-height:1.8;}

/* ---------- FOOTER ---------- */
footer {background:#003366; color:#fff; text-align:center; padding:25px 10px; margin-top:60px;}
footer p {margin:5px 0;}
footer a {color:#00ccff; text-decoration:none; font-weight:500;}
footer a:hover {text-decoration:underline;}
</style>
</head>
<body>

<header>
  <div class="logo">
    <img src="assets/logo.png" alt="Finserve Logo">
    Finserve Bank
  </div>
  <nav>
    <ul>
      <li><a href="#home">Home</a></li>
      <li><a href="#about">About Us</a></li>
      <li><a href="#services">Services</a></li>
      <li><a href="#career">Career</a></li>
      <li><a href="verify.php">Verification</a></li>
      <li><a href="login.php">Login</a></li> <!-- direct link to login.php -->
    </ul>
  </nav>
</header>

<section class="hero" id="home">
  <video autoplay muted loop>
    <source src="assets/bank-bg.mp4" type="video/mp4">
  </video>
</section>

<section class="about" id="about">
  <h2>About Finserve</h2>
  <p>Finserve Bank stands at the forefront of Bangladesh’s digital banking revolution. Our vision is to transform every financial interaction into a simple, secure, and empowering experience.</p>
</section>

<section class="services" id="services">
  <h2>Our Services</h2>
  <div class="service-container">
    <div class="service-box"><h3>Online Banking</h3><p>Access your account anytime, anywhere with our secure online banking system.</p></div>
    <div class="service-box"><h3>Corporate Solutions</h3><p>Empowering businesses with customized financial solutions and partnerships.</p></div>
    <div class="service-box"><h3>Card Services</h3><p>Enjoy the convenience of Finserve credit and debit cards with exclusive benefits.</p></div>
  </div>
</section>

<section class="career" id="career">
  <video autoplay muted loop>
    <source src="assets/career.mp4" type="video/mp4">
  </video>
  <div class="career-content">
    <h2>Join Our Team</h2>
    <p>Explore exciting opportunities and grow your career with us today!</p>
  </div>
</section>

<footer>
  <p>&copy; <?=date('Y')?> Finserve Bank. All Rights Reserved.</p>
  <p>Developed by <a href="#">Nayma Jahan Chowdhury</a></p>
</footer>

</body>
</html>
