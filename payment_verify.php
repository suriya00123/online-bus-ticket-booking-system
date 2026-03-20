<?php
header('Content-Type: application/json');
include 'config.php';

$input                = json_decode(file_get_contents('php://input'), true);
$razorpay_order_id    = $input['razorpay_order_id']    ?? '';
$razorpay_payment_id  = $input['razorpay_payment_id']  ?? '';
$razorpay_signature   = $input['razorpay_signature']   ?? '';
$booking_id           = $input['booking_id']           ?? 0;

// Verify signature
$generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, RAZORPAY_KEY_SECRET);

if ($generated_signature === $razorpay_signature) {
    $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'paid', razorpay_payment_id = ? WHERE id = ?");
    $stmt->execute([$razorpay_payment_id, $booking_id]);
    echo json_encode(['success' => true]);
} else {
    // Free up seats on failure
    $booking = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $booking->execute([$booking_id]);
    $booking = $booking->fetch();

    if ($booking) {
        $seats = explode(',', $booking['seat_numbers']);
        $upd   = $pdo->prepare("UPDATE seats SET status = 'available' WHERE bus_id = ? AND seat_number = ?");
        foreach ($seats as $seat) {
            $upd->execute([$booking['bus_id'], trim($seat)]);
        }
        $pdo->prepare("UPDATE bookings SET payment_status = 'failed' WHERE id = ?")->execute([$booking_id]);
    }
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
}
?>
