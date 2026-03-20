<?php
header('Content-Type: application/json');
include 'config.php';
if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false]); exit; }
$input = json_decode(file_get_contents('php://input'), true);
$phone = trim($input['phone'] ?? '');
$stmt  = $pdo->prepare("UPDATE users SET phone=? WHERE id=?");
if ($stmt->execute([$phone, $_SESSION['user_id']])) {
    $_SESSION['user_phone'] = $phone;
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false]);
}
?>
