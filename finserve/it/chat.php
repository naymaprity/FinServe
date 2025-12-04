<?php
session_start();
require '../config/db.php';

// ✅ Login Check
if (!isset($_SESSION['user'])) {
    header('Location: ../admin/login.php');
    exit;
}

$current_user_id = $_SESSION['user']['id'];
$current_role_id = $_SESSION['user']['role_id'];

// ✅ Identify Chat Partner for IT Officer
if ($current_role_id == 9) { // IT Officer → Branch Manager
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role_id = 5 LIMIT 1"); // Branch Manager role_id = 5
} else {
    die("<p style='text-align:center;color:red;'>Access Denied! Only IT Officer can access this chat.</p>");
}
$stmt->execute();
$partner = $stmt->fetch(PDO::FETCH_ASSOC);
$partner_id = $partner ? $partner['id'] : null;
if (!$partner_id) die("<p style='text-align:center;color:red;'>No valid chat partner found.</p>");

// ✅ Send Text Message
if (isset($_POST['message']) && !empty(trim($_POST['message']))) {
    $msg = trim($_POST['message']);
    $insert = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $insert->execute([$current_user_id, $partner_id, $msg]);
    exit; // AJAX stops here
}

// ✅ Send File
if(isset($_FILES['file']) && $_FILES['file']['size'] > 0){
    $file = $_FILES['file'];
    $filename = time().'_'.$file['name'];
    $target = 'uploads/'.$filename;
    if(!is_dir('uploads')) mkdir('uploads', 0777, true);
    if(move_uploaded_file($file['tmp_name'], $target)){
        $insert = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, file, created_at) VALUES (?, ?, ?, ?, NOW())");
        $insert->execute([$current_user_id, $partner_id, '[File]', $filename]);
    }
    exit;
}

// ✅ Fetch Messages (AJAX)
if (isset($_GET['fetch']) && $_GET['fetch'] == 1) {
    $stmt = $pdo->prepare("SELECT * FROM messages 
        WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) 
        ORDER BY created_at ASC");
    $stmt->execute([$current_user_id, $partner_id, $partner_id, $current_user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as $msg) {
        $cls = $msg['sender_id'] == $current_user_id ? 'sent' : 'received';
        echo "<div class='message $cls'>";

        // ✅ Display file message as link
        if(!empty($msg['file'])){
            echo "<a href='uploads/".htmlspecialchars($msg['file'])."' download>".htmlspecialchars($msg['file'])."</a><br>";
        }

        // ✅ Display normal text message separately
        if(!empty($msg['message']) && $msg['message'] != '[File]'){
            echo htmlspecialchars($msg['message'])."<br>";
        }

        echo "<small>{$msg['created_at']}</small></div>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat | IT Officer & Branch Manager</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
body { font-family:'Poppins',sans-serif; margin:0; background:#f4f6fa; }
header { background:#0f172a;padding:15px;color:white;display:flex;justify-content:space-between;align-items:center; }
header .logo { display:flex;align-items:center;gap:10px; }
header .logo img { width:40px;height:40px;border-radius:50%; }
.chat-container { max-width:800px;margin:40px auto;background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1); }
.chat-box { height:400px;overflow-y:auto;border:1px solid #ddd;border-radius:8px;padding:15px;background:#f9fafb; }
.message { margin:10px 0;padding:10px 15px;border-radius:10px;max-width:70%;word-wrap:break-word; }
.sent { background:#007bff;color:white;margin-left:auto; }
.received { background:#e5e7eb;color:#111; }
.send-box { display:flex;gap:10px;margin-top:15px; }
.send-box input[type="text"] { flex:1;padding:10px;border:1px solid #ccc;border-radius:8px; }
.send-box button { background:#007bff;color:white;border:none;padding:10px 18px;border-radius:8px;cursor:pointer; }
.send-box button:hover { background:#0056b3; }
.send-box input[type="file"] { border:none; }
</style>
</head>
<body>

<header>
    <div class="logo">
        <img src="../assets/logo.png" alt="Logo">
        <h2 style="margin:0;font-size:20px;">Secure Chat</h2>
    </div>
    <div style="display:flex;gap:12px;">
        <a href="dashboard.php" style="background:#FFD700;color:#000;padding:8px 16px;border-radius:8px;font-weight:bold;text-decoration:none;">Dashboard</a>
        <a href="../admin/index.php" style="background:#ef4444;color:#fff;padding:8px 16px;border-radius:8px;font-weight:bold;text-decoration:none;">Logout</a>
    </div>
</header>

<div class="chat-container">
    <h3 style="text-align:center;color:#333;margin-bottom:15px;">
        Chat between IT Officer and Branch Manager
    </h3>

    <div class="chat-box" id="chat-box">
        <!-- Messages loaded by JS -->
    </div>

    <form id="chat-form" class="send-box" enctype="multipart/form-data">
        <input type="text" name="message" id="message-input" placeholder="Type your message..." required>
        <input type="file" name="file" id="file-input">
        <button type="submit"><i class="ri-send-plane-2-line"></i> Send</button>
    </form>
</div>

<script>
const chatBox = document.getElementById('chat-box');
const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message-input');
const fileInput = document.getElementById('file-input');

// ✅ Fetch messages
function fetchMessages(){
    fetch('chat.php?fetch=1')
    .then(res => res.text())
    .then(data => {
        chatBox.innerHTML = data;
        chatBox.scrollTop = chatBox.scrollHeight;
    });
}

// ✅ Send message or file
chatForm.addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData();
    if(messageInput.value.trim() !== '') formData.append('message', messageInput.value.trim());
    if(fileInput.files.length > 0) formData.append('file', fileInput.files[0]);

    fetch('chat.php', { method:'POST', body: formData })
    .then(() => {
        messageInput.value = '';
        fileInput.value = '';
        fetchMessages();
    });
});

// ✅ Auto-refresh every 2 sec
setInterval(fetchMessages, 2000);
fetchMessages();
</script>

</body>
</html>
