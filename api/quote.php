<?php
// api/quote.php
// คำนวณราคาการจองแบบสด ๆ ให้หน้าจอง (ตอบกลับเป็น JSON พร้อม HTML สรุปราคา)
require_once '../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'html' => '', 'total_text' => null]);
    exit;
}

$user = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

$quote = quoteBooking($pdo, $user, intval($_POST['sport_id'] ?? 0), [
    'court_id'   => $_POST['court_id'] ?? 0,
    'slot_ids'   => $_POST['slot_ids'] ?? [],
    'date'       => $_POST['date'] ?? '',
    'equipment'  => $_POST['equipment'] ?? [],
    'use_points' => !empty($_POST['use_points']),
]);

ob_start();
if (isset($quote['court_fee'])) include '../includes/booking_summary.php';
else echo '<div class="alert alert-danger">' . icon('alert') . '<div>' . htmlspecialchars(implode(' ', $quote['errors'])) . '</div></div>';
$html = ob_get_clean();

echo json_encode([
    'ok' => $quote['ok'],
    'html' => $html,
    'total_text' => isset($quote['total']) ? formatPrice($quote['total']) : null,
]);
