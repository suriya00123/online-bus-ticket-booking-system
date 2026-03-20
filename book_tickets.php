<?php
header('Content-Type: application/json');
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input        = json_decode(file_get_contents('php://input'), true);
$user_id      = $_SESSION['user_id'];
$bus_id       = $input['bus_id']       ?? 0;
$seats        = $input['seats']        ?? [];
$total_amount = $input['total_amount'] ?? 0;

if (empty($seats)) {
    echo json_encode(['success' => false, 'message' => 'No seats selected']);
    exit;
}

// Check seats are still available
$placeholders = implode(',', array_fill(0, count($seats), '?'));
$params       = array_merge([$bus_id], $seats);
$check        = $pdo->prepare("SELECT COUNT(*) FROM seats WHERE bus_id = ? AND seat_number IN ($placeholders) AND status = 'available'");
$check->execute($params);
if ($check->fetchColumn() != count($seats)) {
    echo json_encode(['success' => false, 'message' => 'One or more seats already booked']);
    exit;
}

// Generate PNR
$pnr = 'PNR' . strtoupper(substr(md5(uniqid()), 0, 10));
$seat_numbers = implode(',', $seats);

// Create Razorpay order (mock if keys not set)
$razorpay_order_id = 'order_' . strtoupper(substr(md5(uniqid()), 0, 14));

try {
    $pdo->beginTransaction();

    // Insert booking
    $stmt = $pdo->prepare("INSERT INTO bookings (pnr, user_id, bus_id, seat_numbers, total_amount, razorpay_order_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$pnr, $user_id, $bus_id, $seat_numbers, $total_amount, $razorpay_order_id]);
    $booking_id = $pdo->lastInsertId();

    // Mark seats as booked
    $upd = $pdo->prepare("UPDATE seats SET status = 'booked' WHERE bus_id = ? AND seat_number = ?");
    foreach ($seats as $seat) {
        $upd->execute([$bus_id, $seat]);
    }

    $pdo->commit();
    echo json_encode([
        'success'           => true,
        'pnr'               => $pnr,
        'booking_id'        => $booking_id,
        'razorpay_order_id' => $razorpay_order_id
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
