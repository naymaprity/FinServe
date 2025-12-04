<?php
require '../config/db.php';

$error = '';
$success = '';

/* --- Safe function declaration --- */
if (!function_exists('generateLoginCode')) {
    function generateLoginCode($length = 10) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $nid = trim($_POST['nid'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $account_type = $_POST['account_type'] ?? '';
    $initial_deposit = $_POST['initial_deposit'] ?? '';

    // Nominee info
    $has_nominee = $_POST['has_nominee'] ?? 'no';
    $nominee_name = trim($_POST['nominee_name'] ?? '');
    $nominee_nid = trim($_POST['nominee_nid'] ?? '');
    $nominee_relation = trim($_POST['nominee_relation'] ?? '');
    $nominee_phone = trim($_POST['nominee_phone'] ?? '');
    $nominee_address = trim($_POST['nominee_address'] ?? '');

    // Trade license info
    $has_trade = $_POST['has_trade'] ?? 'no';
    $trade_license_number = trim($_POST['trade_license_number'] ?? '');
    $trade_business_name = trim($_POST['trade_business_name'] ?? '');
    $trade_issue_date = trim($_POST['trade_issue_date'] ?? '');
    $trade_expiry_date = trim($_POST['trade_expiry_date'] ?? '');
    $trade_business_address = trim($_POST['trade_business_address'] ?? '');

    if(empty($full_name) || empty($email) || empty($phone) || empty($nid) || empty($dob) || empty($address) || empty($account_type) || $initial_deposit === '') {
        $error = "Please fill all required fields!";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE email = ?");
        $stmt->execute([$email]);
        if($stmt->rowCount() > 0){
            $error = "This email is already registered!";
        } else {

            // optional nominee insert
            $nominee_id = null;
            if ($has_nominee === 'yes' && !empty($nominee_name)) {
                $stmt_nom = $pdo->prepare("INSERT INTO nominees (nominee_name, nid, relation, phone, nominee_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt_nom->execute([$nominee_name, $nominee_nid, $nominee_relation, $nominee_phone, $nominee_address]);
                $nominee_id = $pdo->lastInsertId();
            }

            // optional trade license insert
            $trade_license_id = null;
            if ($has_trade === 'yes' && !empty($trade_license_number)) {
                $stmt_trade = $pdo->prepare("INSERT INTO trade_licenses (license_number, business_name, issue_date, expiry_date, business_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt_trade->execute([$trade_license_number, $trade_business_name, $trade_issue_date, $trade_expiry_date, $trade_business_address]);
                $trade_license_id = $pdo->lastInsertId();
            }

            // generate account number & login code
            $account_number = 'BA' . rand(1000000000, 9999999999);
            $login_code = generateLoginCode();

            $stmt = $pdo->prepare("
                INSERT INTO accounts 
                (full_name, email, phone, nid, dob, address, account_type, account_number, login_code, balance, nominee_id, trade_license_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $ok = $stmt->execute([
                $full_name, $email, $phone, $nid, $dob, $address, $account_type,
                $account_number, $login_code, $initial_deposit,
                $nominee_id, $trade_license_id
            ]);

            if($ok){
                $success = "Account successfully created!";
                $account_id = $pdo->lastInsertId();

                // Redirect to printable page
                header("Location: print_account.php?account_id=$account_id");
                exit;
            } else {
                $error = "Something went wrong!";
            }
        }
    }
}
?>

<!-- ==== Account Creation Form ==== -->
<style>
/* ==== Your existing CSS ==== */
body, html { margin:0; padding:0; min-height:100%; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f0f4f7; display:flex; flex-direction:column;}
.logo-bar {display:flex; align-items:center; padding:20px 30px; z-index:2; position:relative;}
.logo-bar img { height:50px; margin-right:15px; }
.logo-bar h1 { font-size:28px; color:#1d3557; }
.logo-bg {position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:100vmin; height:100vmin; background:url('../assets/logo.png') no-repeat center/contain; border-radius:50%; filter:blur(15px) brightness(0.7); z-index:0;}
.main-container { position:relative; z-index:1; display:flex; flex-direction:column; align-items:center; flex:1; }
.form-title { font-size:28px; color:#e5f1f5ff; margin:20px 0; }
.form-container { width:800px; display:grid; grid-template-columns:repeat(2,1fr); gap:20px; color:#1d3557; }
label { color:#10110eff; font-weight:bold; }
input, select, button { padding:12px; font-size:16px; border-radius:12px; border:1px solid #ccc; background:rgba(255,255,255,0.85); backdrop-filter:blur(8px); width:100%; box-sizing:border-box; }
input:focus, select:focus { border-color:#457b9d; outline:none; box-shadow:0 0 10px rgba(69,123,157,0.4); }
button { grid-column:span 2; background:#1d3557; color:white; cursor:pointer; border:none; transition:0.3s; }
button:hover { background:#457b9d; }
.success, .error { padding:12px; border-radius:10px; font-weight:bold; grid-column:span 2; }
.form-container h4 { grid-column:span 2; margin-top:15px; margin-bottom:10px; color:#1d3557; font-weight:bold; font-size:18px; }
.hidden { display:none !important; }
.success { background: rgba(212, 237, 218, 0.9); color: #155724; }
.error { background: rgba(248, 215, 218, 0.9); color: #721c24; }
footer { text-align:center; padding:15px 0; background:rgba(29,53,87,0.1); color:#1d3557; font-weight:bold; z-index:2; }
@media(max-width:850px){ .form-container{ grid-template-columns:1fr; } button{ grid-column:span 1; } }
</style>

<div class="logo-bg"></div>
<div class="logo-bar">
    <img src="../assets/logo.png" alt="Bank Logo">
    <h1>Finserve</h1>
</div>

<div class="main-container">
    <h2 class="form-title">Open New Account</h2>

    <form method="POST" class="form-container">
        <?php if($success): ?>
            <div class="success"><?=$success?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error"><?=$error?></div>
        <?php endif; ?>

        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="text" name="nid" placeholder="NID Number" required>
        <input type="date" name="dob" required>
        <input type="text" name="address" placeholder="Address" required>
        <select name="account_type" required>
            <option value="">Select Account Type</option>
            <option value="Savings">Savings Account</option>
            <option value="Current">Current Account</option>
            <option value="Fixed Deposit">Fixed Deposit</option>
            <option value="Student Savings">Student Savings</option>
            <option value="Joint Account">Joint Account</option>
            <option value="Monthly Salary Account">Monthly Salary Account</option>
            <option value="Foreign Currency Account">Foreign Currency Account</option>
        </select>
        <input type="number" name="initial_deposit" placeholder="Initial Deposit (BDT)" required>

        <!-- Nominee section -->
        <label>Do you want to add a Nominee?</label>
        <select name="has_nominee" id="has_nominee">
            <option value="no">No</option>
            <option value="yes">Yes</option>
        </select>
        <div id="nominee_section" class="hidden" style="grid-column:span 2;">
            <h4>Nominee Details</h4>
            <input type="text" name="nominee_name" placeholder="Nominee Name">
            <input type="text" name="nominee_nid" placeholder="Nominee NID">
            <input type="text" name="nominee_relation" placeholder="Relation with Account Holder">
            <input type="text" name="nominee_phone" placeholder="Nominee Phone Number">
            <input type="text" name="nominee_address" placeholder="Nominee Address" style="grid-column:span 2;">
        </div>

        <!-- Trade License section -->
        <label>Do you have a Trade License?</label>
        <select name="has_trade" id="has_trade">
            <option value="no">No</option>
            <option value="yes">Yes</option>
        </select>
        <div id="trade_section" class="hidden" style="grid-column:span 2;">
            <h4>Trade License Details</h4>
            <input type="text" name="trade_license_number" placeholder="License Number">
            <input type="text" name="trade_business_name" placeholder="Business Name">
            <input type="date" name="trade_issue_date" placeholder="Issue Date">
            <input type="date" name="trade_expiry_date" placeholder="Expiry Date">
            <input type="text" name="trade_business_address" placeholder="Business Address" style="grid-column:span 2;">
        </div>

        <button type="submit">Create Account</button>
    </form>
</div>

<script>
document.getElementById('has_nominee').addEventListener('change', function(){
    document.getElementById('nominee_section').classList.toggle('hidden', this.value === 'no');
});
document.getElementById('has_trade').addEventListener('change', function(){
    document.getElementById('trade_section').classList.toggle('hidden', this.value === 'no');
});
</script>

<footer>
    &copy; <?=date('Y')?> Finserve Bank. All Rights Reserved.
</footer>
