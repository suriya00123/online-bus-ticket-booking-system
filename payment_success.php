<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$pnr  = $_GET['pnr'] ?? '';
$stmt = $pdo->prepare("
    SELECT b.*, bu.bus_name, bu.from_city, bu.to_city, bu.travel_date
    FROM bookings b
    JOIN buses bu ON b.bus_id = bu.id
    WHERE b.pnr = ? AND b.user_id = ?
");
$stmt->execute([$pnr, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: user/user_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Payment Successful</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
}
.success-box {
    background: white;
    padding: 40px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    max-width: 500px;
    width: 90%;
}
.success-icon { font-size: 80px; color: #4CAF50; margin-bottom: 20px; }
h1 { color: #4CAF50; margin-bottom: 20px; }
.ticket-details {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    text-align: left;
}
.ticket-details p { margin: 10px 0; font-size: 16px; }
.pnr {
    background: #4CAF50;
    color: white;
    padding: 15px;
    border-radius: 8px;
    font-size: 24px;
    font-weight: bold;
    margin: 20px 0;
}
.btn {
    display: inline-block;
    padding: 15px 30px;
    background: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    margin: 10px;
    font-weight: bold;
}
.btn:hover { background: #45a049; }
</style>
</head>
<body>
<div class="success-box">
    <div class="success-icon">✅</div>
    <h1>Payment Successful!</h1>
    <p>Your ticket has been booked successfully.</p>
    <div class="pnr">PNR: <?php echo $booking['pnr']; ?></div>
    <div class="ticket-details">
        <p><strong>🚌 Bus:</strong>    <?php echo $booking['bus_name']; ?></p>
        <p><strong>🛣️ Route:</strong>  <?php echo $booking['from_city']; ?> → <?php echo $booking['to_city']; ?></p>
        <p><strong>📅 Date:</strong>   <?php echo $booking['travel_date']; ?></p>
        <p><strong>💺 Seats:</strong>  <?php echo $booking['seat_numbers']; ?></p>
        <p><strong>💰 Amount:</strong> ₹<?php echo number_format($booking['total_amount'], 2); ?></p>
        <p><strong>💳 Payment ID:</strong> <?php echo $booking['razorpay_payment_id'] ?: 'N/A'; ?></p>
    </div>
    <a href="user/user_dashboard.php" class="btn">Book Another Ticket</a>
    <button onclick="window.print()" class="btn" style="background:#3498db;border:none;cursor:pointer;">🖨️ Print Ticket</button>
</div>
</body>
</html>
