<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php';

$userId = $_SESSION['user_id'] ?? 0;
$roomId = intval($_POST['room_id'] ?? 0);

if (!$userId || !$roomId) {
    echo json_encode(["status" => "error"]);
    exit();
}

// تحديث آخر ظهور
$conn->query("UPDATE room_players SET is_online = 1, last_seen = NOW() WHERE room_id = $roomId AND user_id = $userId");

// التحقق من عدد اللاعبين المتصلين
$res = $conn->query("SELECT COUNT(*) as online_count FROM room_players WHERE room_id = $roomId AND is_online = 1");
$onlineCount = $res->fetch_assoc()['online_count'];

$totalPlayers = $conn->query("SELECT COUNT(*) as total_count FROM room_players WHERE room_id = $roomId")->fetch_assoc()['total_count'];

if ($onlineCount < $totalPlayers) {
    echo json_encode(["status" => "paused", "msg" => "⏸ اللعبة متوقفة - لاعب غير متصل"]);
} else {
    echo json_encode(["status" => "running"]);
}
