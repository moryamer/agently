<?php
require_once __DIR__ . '/_safe_wrappers.php';
require 'db.php';

$roomId = intval($_GET['room_id'] ?? 0);
$res = $conn->prepare("SELECT status FROM rooms WHERE id = ?");
$res->bind_param("i", $roomId);
$res->execute();
$result = $res->get_result()->fetch_assoc();

header('Content-Type: application/json');
echo json_encode([
    'status' => $result['status'] ?? 'waiting'
]);
