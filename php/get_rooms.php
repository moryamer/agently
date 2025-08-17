<?php
require_once __DIR__ . '/_safe_wrappers.php';
header('Content-Type: application/json; charset=UTF-8');
require 'db.php';
session_start();

$sql = "
SELECT 
    r.id,
    r.room_name,
    r.max_players,
    COUNT(CASE WHEN rp.last_status = 'in' THEN 1 END) AS current_players
FROM rooms r
LEFT JOIN room_players rp ON r.id = rp.room_id
GROUP BY r.id
HAVING current_players > 0
ORDER BY r.id DESC
";


$result = $conn->query($sql);
if (!$result) {
    echo json_encode(["error" => $conn->error]);
    exit;
}

$rooms = [];
while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}

echo json_encode($rooms, JSON_UNESCAPED_UNICODE);
