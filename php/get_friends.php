<?php
require_once __DIR__ . '/_safe_wrappers.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
if ($userId === 0) {
    echo json_encode([]);
    exit;
}

// استعلام جلب الأصدقاء بدون تكرار
$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.username, u.avatar 
    FROM users u 
    JOIN friends f ON (
        (f.friend_id = u.id AND f.user_id = ?) 
        OR 
        (f.user_id = u.id AND f.friend_id = ?)
    )
    WHERE u.id != ?
");
$stmt->bind_param("iii", $userId, $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$friends = [];
while ($row = $result->fetch_assoc()) {
    $friends[] = $row;
}

echo json_encode($friends);
exit;
