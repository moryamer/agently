<?php
require_once __DIR__ . '/_safe_wrappers.php';
require 'db.php';

$room_id = intval($_GET['room_id']);
$res = $conn->query("SELECT users.username, player_roles.role 
                     FROM player_roles 
                     JOIN users ON users.id = player_roles.user_id 
                     WHERE room_id = $room_id");

$players = [];
while ($row = $res->fetch_assoc()) {
    $players[] = $row;
}

echo json_encode($players, JSON_UNESCAPED_UNICODE);
