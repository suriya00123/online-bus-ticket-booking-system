<?php
require_once 'config.php';
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "❌ All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ Invalid email!";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "❌ Username already exists!";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "❌ Email already exists!";
            } else {
                // Store plain password for display/testing
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                if ($stmt->execute([$username, $email, $password])) {
                    $success = "✅ Registration Successful!";
                } else {
                    $error = "❌ Registration Failed!";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>User Register</title>
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
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}
input {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border: none;
    border-radius: 10px;
    outline: none;
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
button:hover { opacity: 0.9; }
.error   { background: #ff4d4d; padding: 10px; border-radius: 8px; }
.success { background: #2ecc71; padding: 10px; border-radius: 8px; }
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
    <h2>👤 User Register</h2>
    <?php if ($error)   echo "<p class='error'>$error</p>"; ?>
    <?php if ($success) echo "<p class='success'>$success</p>"; ?>
    <form method="POST">
        <input type="text"     name="username" placeholder="👤 Username" required>
        <input type="email"    name="email"    placeholder="📧 Email"    required>
        <input type="password" name="password" placeholder="🔒 Password" required>
        <button type="submit">Register</button>
    </form>
    <a href="login.php" class="btn">🔐 Login</a>
    <a href="home.html" class="btn">🏠 Home</a>
</div>
</body>
</html>
