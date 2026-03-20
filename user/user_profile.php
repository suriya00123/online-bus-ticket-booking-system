<?php
include '../config.php';
if (!isset($_SESSION['user_id'])) header('Location: ../login.php');

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$success = '';
$error   = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = trim($_POST['phone']);
    $stmt  = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
    if ($stmt->execute([$phone, $_SESSION['user_id']])) {
        $success = "✅ Profile updated!";
        $_SESSION['user_phone'] = $phone;
    } else {
        $error = "❌ Update failed!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>My Profile</title>
<style>
body { font-family: Arial, sans-serif; max-width: 500px; margin: 80px auto; padding: 20px; background: #f4f4f4; }
.card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
input  { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
button { padding: 12px 24px; background: #4CAF50; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
.success { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; }
.error   { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; }
a { color: #3498db; }
</style>
</head>
<body>
<div class="card">
    <h2>👤 My Profile — <a href="user_dashboard.php">← Back</a></h2>
    <?php if ($success) echo "<p class='success'>$success</p>"; ?>
    <?php if ($error)   echo "<p class='error'>$error</p>"; ?>
    <p><strong>Username:</strong> <?php echo $user['username']; ?></p>
    <p><strong>Email:</strong>    <?php echo $user['email']; ?></p>
    <p><strong>Password:</strong> <?php echo $user['password']; ?> <small style="color:#888">(plain text for testing)</small></p>
    <p><strong>Joined:</strong>   <?php echo date('d M Y', strtotime($user['created_at'])); ?></p>
    <form method="POST">
        <label>Phone:</label>
        <input type="text" name="phone" value="<?php echo $user['phone']; ?>" placeholder="Phone number">
        <button type="submit">Update</button>
    </form>
</div>
</body>
</html>
