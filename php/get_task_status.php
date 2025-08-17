<?php
require_once __DIR__ . '/_safe_wrappers.php';
require 'db.php';

$roomId = intval($_GET['room_id'] ?? 0);

$res = $conn->prepare("SELECT killer_done, conspirator_done FROM rooms WHERE id = ?");
$res->bind_param("i", $roomId);
$res->execute();
$status = $res->get_result()->fetch_assoc();

echo json_encode([
    'killer_done' => (bool)$status['killer_done'],
    'conspirator_done' => (bool)$status['conspirator_done']
], JSON_UNESCAPED_UNICODE);
