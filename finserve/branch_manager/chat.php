<?php
session_start();
require '../config/db.php';

// Check if user is Branch Manager
if(!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 5){
    header("Location: ../login.php");
    exit;
}

$manager_id = $_SESSION['user']['id'];
$manager_name = $_SESSION['user']['full_name'];

// Fetch users (role_id 6,7,8,9) to chat with
$users_stmt = $pdo->query("SELECT id, full_name FROM users WHERE role_id IN (6,7,8,9)");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['receiver_id'])) {
    $message = trim($_POST['message']);
    $receiver_id = (int)$_POST['receiver_id'];
    if($message != ''){
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, username, created_at, is_read) VALUES (?, ?, ?, ?, NOW(), 0)");
        $stmt->execute([$manager_id, $receiver_id, $message, $manager_name]);
    }
}

// Handle fetching messages for selected user via Ajax
if(isset($_GET['chat_with'])){
    $chat_with = (int)$_GET['chat_with'];
    $messages_stmt = $pdo->prepare("SELECT m.*, u.full_name AS sender_name 
        FROM messages m 
        LEFT JOIN users u ON m.sender_id=u.id 
        WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) 
        ORDER BY created_at ASC");
    $messages_stmt->execute([$manager_id, $chat_with, $chat_with, $manager_id]);
    $chat_messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($chat_messages);
    exit;
}

// Handle marking messages as read
if(isset($_GET['mark_read'])){
    $sender = (int)$_GET['mark_read'];
    $stmt = $pdo->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=?");
    $stmt->execute([$sender, $manager_id]);
    exit;
}

// Fetch unread message counts for sidebar
$unread_stmt = $pdo->query("SELECT sender_id, COUNT(*) as cnt FROM messages WHERE receiver_id=$manager_id AND is_read=0 GROUP BY sender_id");
$unread_counts = [];
while($row = $unread_stmt->fetch(PDO::FETCH_ASSOC)){
    $unread_counts[$row['sender_id']] = $row['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Branch Manager Chat | Finserve</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
body { font-family:'Segoe UI',sans-serif; background:#f4f6f8; margin:0; padding:0; display:flex; height:100vh; }
.sidebar { width:250px; background:#003366; color:#fff; display:flex; flex-direction:column; padding:20px; }
.sidebar h2 { font-size:1.2rem; margin-bottom:15px; text-align:center; }
.sidebar .user { padding:10px; cursor:pointer; border-radius:8px; margin-bottom:5px; transition:0.2s; display:flex; justify-content:space-between; align-items:center; }
.sidebar .user:hover, .sidebar .user.active { background:#0055aa; }
.unread-count { background:red; color:#fff; border-radius:50%; padding:2px 8px; font-size:0.8rem; }
.chat-section { flex:1; display:flex; flex-direction:column; }
.chat-header { background:#003366; color:#fff; padding:15px; font-weight:bold; }
.chat-container { flex:1; padding:15px; overflow-y:auto; background:#e4e6eb; display:flex; flex-direction:column; gap:10px; }
.chat-bubble { max-width:70%; padding:10px 15px; border-radius:20px; word-wrap:break-word; }
.chat-bubble.left { background:#4BC0C0; align-self:flex-start; color:#fff; }
.chat-bubble.right { background:#FFD700; align-self:flex-end; color:#000; }
.chat-input { display:flex; padding:10px; border-top:1px solid #ccc; background:#fff; }
.chat-input input { flex:1; padding:10px; border-radius:8px; border:1px solid #ccc; outline:none; }
.chat-input button { padding:10px 15px; margin-left:10px; border:none; border-radius:8px; background:#003366; color:#fff; cursor:pointer; }
.chat-input button:hover { background:#0055aa; }
</style>
</head>
<body>

<div class="sidebar">
    <h2>Users</h2>
    <?php foreach($users as $user): ?>
        <div class="user" data-id="<?= $user['id'] ?>">
            <?= htmlspecialchars($user['full_name']) ?>
            <?php if(isset($unread_counts[$user['id']])): ?>
                <span class="unread-count"><?= $unread_counts[$user['id']] ?></span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="chat-section">
    <div class="chat-header" id="chatHeader">Select a user to chat</div>
    <div class="chat-container" id="chatContainer"></div>
    <form id="chatForm" class="chat-input" style="display:none;">
        <input type="text" name="message" placeholder="Type your message..." required>
        <button type="submit">Send</button>
    </form>
</div>

<script>
let selectedUser = null;
const users = document.querySelectorAll('.user');
const chatContainer = document.getElementById('chatContainer');
const chatHeader = document.getElementById('chatHeader');
const chatForm = document.getElementById('chatForm');

users.forEach(user=>{
    user.addEventListener('click', ()=>{
        users.forEach(u=>u.classList.remove('active'));
        user.classList.add('active');
        selectedUser = user.dataset.id;
        chatHeader.textContent = user.textContent.replace(/\d+$/,'').trim(); // remove unread count
        chatForm.style.display='flex';
        fetchMessages();
    });
});

chatForm.addEventListener('submit', e=>{
    e.preventDefault();
    if(selectedUser){
        const formData = new FormData(chatForm);
        formData.append('receiver_id', selectedUser);
        fetch('', { method:'POST', body:formData })
            .then(()=>{ chatForm.reset(); fetchMessages(); });
    }
});

function fetchMessages(){
    if(!selectedUser) return;
    fetch('?chat_with='+selectedUser)
        .then(res=>res.json())
        .then(data=>{
            chatContainer.innerHTML = '';
            data.forEach(msg=>{
                const div = document.createElement('div');
                div.classList.add('chat-bubble');
                div.classList.add(msg.sender_id == <?= $manager_id ?> ? 'right':'left');
                div.innerHTML = `<strong>${msg.sender_name}</strong><br>${msg.message}`;
                chatContainer.appendChild(div);
            });
            chatContainer.scrollTop = chatContainer.scrollHeight;
        });

    // Mark messages as read
    fetch('?mark_read='+selectedUser).then(()=>{ refreshSidebar(); });
}

// Refresh unread counts in sidebar
function refreshSidebar(){
    fetch(window.location.href)
        .then(res=>res.text())
        .then(html=>{
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newUsers = doc.querySelectorAll('.sidebar .user');
            newUsers.forEach((u,i)=>{
                users[i].innerHTML = u.innerHTML;
            });
        });
}

// Refresh messages & unread counts every 3 sec
setInterval(fetchMessages, 3000);
</script>

</body>
</html>
