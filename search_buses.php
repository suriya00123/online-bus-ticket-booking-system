<?php
header('Content-Type: application/json');
include 'config.php';

$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to']   ?? '');
$date = trim($_GET['date'] ?? '');

$stmt = $pdo->prepare("
    SELECT b.id, b.bus_name, b.from_city, b.to_city, b.travel_date,
           b.total_seats, b.price_per_seat,
           COUNT(CASE WHEN s.status = 'available' THEN 1 END) AS available_seats
    FROM buses b
    LEFT JOIN seats s ON b.id = s.bus_id
    WHERE b.from_city LIKE ? AND b.to_city LIKE ? AND b.travel_date = ?
    GROUP BY b.id, b.bus_name, b.from_city, b.to_city, b.travel_date, b.total_seats, b.price_per_seat
    LIMIT 10
");
$stmt->execute(["%$from%", "%$to%", $date]);
$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'buses' => $buses]);
?>
