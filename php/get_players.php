<?php
require_once __DIR__ . '/_safe_wrappers.php';
require 'db.php';
$roomId = intval($_GET['room_id']);
$res = $conn->query("SELECT u.username FROM room_players rp JOIN users u ON rp.user_id = u.id WHERE rp.room_id = $roomId");
while ($row = $res->fetch_assoc()) {
    echo $row['username'] . "<br>";
}
