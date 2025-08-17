<?php
require_once __DIR__ . '/_safe_wrappers.php';
require 'db.php';

$roomId = intval($_GET['room_id']);
$res = $conn->query("SELECT game_started FROM rooms WHERE id = $roomId");
$row = $res->fetch_assoc();

header('Content-Type: application/json');
echo json_encode(['started' => $row['game_started'] == 1]);
