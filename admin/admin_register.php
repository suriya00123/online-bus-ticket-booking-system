<?php
require_once '../config.php';
if (isset($_SESSION['admin_id'])) { header("Location: dashboard.php"); exit(); }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username         = trim($_POST['username']         ?? '');
    $email            = trim($_POST['email']            ?? '');
    $password         = $_POST['password']              ?? '';
    $confirm_password = $_POST['confirm_password']      ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "❌ All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ Invalid email format";
    } elseif (strlen($password) < 6) {
        $error = "❌ Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $error = "❌ Passwords do not match";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "❌ Username already exists!";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "❌ Email already exists!";
            } else {
                // Store plain password for display/testing
                $stmt = $pdo->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
                if ($stmt->execute([$username, $email, $password])) {
                    $success = "✅ Admin Registered Successfully!";
                } else {
                    $error = "❌ Registration failed!";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Register</title>
<style>
body {
    font-family: 'Segoe UI';
    margin: 0;
    height: 100vh;
    background: linear-gradient(135deg, #1d2671, #c33764);
    display: flex;
    justify-content: center;
    align-items: center;
}
.box {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(15px);
    padding: 30px;
    border-radius: 20px;
    width: 360px;
    text-align: center;
    color: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
}
h2 { margin-bottom: 20px; }
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
    margin-top: 10px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(45deg, #00c6ff, #0072ff);
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}
button:hover { transform: scale(1.05); }
.error   { background: #ff4d4d; padding: 10px; border-radius: 8px; }
.success { background: #2ecc71; padding: 10px; border-radius: 8px; }
.btn {
    display: inline-block;
    margin: 10px;
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    color: white;
    font-weight: bold;
    background: #e67e22;
    transition: 0.3s;
}
.btn:hover { transform: scale(1.05); opacity: 0.9; }
</style>
</head>
<body>
<div class="box">
    <h2>🛠 Admin Register</h2>
    <?php if ($error)   echo "<p class='error'>$error</p>"; ?>
    <?php if ($success) echo "<p class='success'>$success</p>"; ?>
    <form method="POST">
        <input type="text"     name="username"         placeholder="👤 Username"         required>
        <input type="email"    name="email"             placeholder="📧 Email"             required>
        <input type="password" name="password"          placeholder="🔒 Password"          required>
        <input type="password" name="confirm_password"  placeholder="🔒 Confirm Password"  required>
        <button type="submit">🚀 Register</button>
    </form>
    <br>
    <a href="admin_login.php" class="btn">🔐 Login</a>
    <a href="../home.html"    class="btn">🏠 Home</a>
</div>
</body>
</html>
