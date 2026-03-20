<?php
header('Content-Type: application/json');
include 'config.php';

$bus_id = $_GET['bus_id'] ?? 0;

$stmt = $pdo->prepare("SELECT seat_number, status FROM seats WHERE bus_id = ? ORDER BY seat_number");
$stmt->execute([$bus_id]);
$seats = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'seats' => $seats]);
?>
