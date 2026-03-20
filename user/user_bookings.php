<?php
include '../config.php';
if (!isset($_SESSION['user_id'])) header('Location: ../login.php');

$stmt = $pdo->prepare("
    SELECT b.pnr, bu.bus_name, bu.from_city, bu.to_city, bu.travel_date,
           b.seat_numbers, b.total_amount, b.booking_date, b.payment_status,
           b.razorpay_payment_id
    FROM bookings b
    JOIN buses bu ON b.bus_id = bu.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<title>My Bookings</title>
<style>
body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f4f4f4; }
h1   { color: #333; text-align: center; }
.card { background: white; padding: 20px; margin: 15px 0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.badge        { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; }
.badge-paid   { background: #d4edda; color: #155724; }
.badge-pending{ background: #fff3cd; color: #856404; }
.badge-failed { background: #f8d7da; color: #721c24; }
.btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; border-radius: 6px; text-decoration: none; }
</style>
</head>
<body>
<h1>📋 My Bookings — <a href="user_dashboard.php" style="font-size:16px;color:#3498db">← Back</a></h1>

<?php if (empty($bookings)): ?>
    <div class="card"><p>No bookings found. <a href="user_dashboard.php">Book a ticket</a></p></div>
<?php else: ?>
    <?php foreach ($bookings as $b): ?>
    <div class="card">
        <strong>PNR: <?php echo $b['pnr']; ?></strong>
        <span class="badge badge-<?php echo $b['payment_status']; ?>"><?php echo ucfirst($b['payment_status']); ?></span>
        <br><strong><?php echo $b['bus_name']; ?></strong><br>
        <?php echo $b['from_city']; ?> → <?php echo $b['to_city']; ?>
        &nbsp;|&nbsp; 📅 <?php echo $b['travel_date']; ?><br>
        💺 Seats: <?php echo $b['seat_numbers']; ?><br>
        💰 <strong style="color:#4CAF50">₹<?php echo number_format($b['total_amount'], 2); ?></strong><br>
        <?php if ($b['razorpay_payment_id']): ?>
            💳 Payment ID: <?php echo $b['razorpay_payment_id']; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
