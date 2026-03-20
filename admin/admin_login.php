<?php
require_once '../config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    // Plain-text password check (for testing/display)
    if ($admin && $admin['password'] === $password) {
        $_SESSION['admin_id']       = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "❌ Invalid login!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Login</title>
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
    padding: 10px;
    background: #e67e22;
    color: white;
    text-decoration: none;
    border-radius: 8px;
}
</style>
</head>
<body>
<div class="box">
    <h2>🛠 Admin Login</h2>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <input type="email"    name="email"    placeholder="📧 Email"    required>
        <input type="password" name="password" placeholder="🔒 Password" required>
        <button>Login</button>
    </form>
    <a href="admin_register.php" class="btn">➕ New Admin</a>
    <a href="../home.html"       class="btn">🏠 Home</a>
</div>
</body>
</html>
