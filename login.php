<?php
require_once 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Plain-text password check (for testing/display)
    if ($user && $user['password'] === $password) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_phone'] = $user['phone'];
        header("Location: user/user_dashboard.php");
        exit();
    } else {
        $error = "❌ Invalid login!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>User Login</title>
<style>
body {
    font-family: 'Segoe UI';
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg, #1d2671, #c33764);
}
.box {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(15px);
    padding: 30px;
    border-radius: 20px;
    width: 350px;
    color: white;
    text-align: center;
}
input {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border: none;
    border-radius: 10px;
    box-sizing: border-box;
}
button {
    width: 100%;
    padding: 12px;
    background: linear-gradient(45deg, #00c6ff, #0072ff);
    border: none;
    color: white;
    border-radius: 10px;
    cursor: pointer;
    font-size: 16px;
}
.error { background: red; padding: 10px; border-radius: 8px; }
.btn {
    display: inline-block;
    margin: 10px;
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    color: white;
    background: #e67e22;
}
</style>
</head>
<body>
<div class="box">
    <h2>🔐 User Login</h2>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <input type="email"    name="email"    placeholder="📧 Email"    required>
        <input type="password" name="password" placeholder="🔒 Password" required>
        <button>Login</button>
    </form>
    <a href="register.php" class="btn">➕ New User</a>
    <a href="home.html"    class="btn">🏠 Home</a>
</div>
</body>
</html>
