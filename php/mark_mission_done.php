<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php';

if (!isset($_POST['room_id'], $_POST['type'])) {
    exit("❌ بيانات ناقصة");
}

$roomId = intval($_POST['room_id']);
$type = $_POST['type']; // killer أو conspirator

if ($type == "killer") {
    $conn->query("UPDATE rooms SET killer_done = 1 WHERE id = $roomId");
} elseif ($type == "conspirator") {
    $conn->query("UPDATE rooms SET conspirator_done = 1 WHERE id = $roomId");
}

echo "✅ تم التحديث";
