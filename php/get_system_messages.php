<?php
require_once __DIR__ . '/_safe_wrappers.php';
require 'db.php';

$roomId = intval($_GET['room_id'] ?? 0);

header('Content-Type: application/json; charset=UTF-8');

$messages = [];

if ($roomId > 0) {
    $stmt = $conn->prepare("
        SELECT message, created_at 
        FROM system_messages 
        WHERE room_id = ? 
        ORDER BY id ASC
    ");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            "message" => $row['message'],
            "time" => date("H:i", strtotime($row['created_at']))
        ];
    }
}

// لو مفيش أي رسائل
if (empty($messages)) {
    $messages[] = [
        "message" => "📢 لا توجد رسائل بعد...",
        "time" => date("H:i")
    ];
}

echo json_encode($messages, JSON_UNESCAPED_UNICODE);
