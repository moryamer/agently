<?php
require_once __DIR__ . '/_safe_wrappers.php';
require 'db.php';

$roomId = intval($_GET['room_id'] ?? 0);

$res = $conn->prepare("SELECT mission, killer_done, conspirator_done FROM rooms WHERE id = ?");
$res->bind_param("i", $roomId);
$res->execute();
$data = $res->get_result()->fetch_assoc();

echo json_encode([
    'mission' => $data['mission'] ?? '',
    'killer_done' => (int)$data['killer_done'],
    'conspirator_done' => (int)$data['conspirator_done']
], JSON_UNESCAPED_UNICODE);
?>
