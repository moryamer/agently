<?php
require_once __DIR__ . '/_safe_wrappers.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$userId = intval($_SESSION['user_id']);

// جلب الطلبات المعلقة فقط
$stmt = $conn->prepare("
    SELECT fr.id AS request_id, 
           fr.sender_id, 
           u.username, 
           u.avatar
    FROM friend_requests fr
    JOIN users u ON fr.sender_id = u.id
    WHERE fr.receiver_id = ? AND fr.status = 'pending'  -- ⚠️ هنا التغيير
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$requests = [];

while ($row = $result->fetch_assoc()) {
    $requests[] = [
        'request_id' => intval($row['request_id']),
        'sender_id' => intval($row['sender_id']),
        'username' => $row['username'],
        'avatar' => $row['avatar'] ?: 'default.png'
    ];
}

echo json_encode($requests);
?>