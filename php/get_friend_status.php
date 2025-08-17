<?php
require_once __DIR__ . '/_safe_wrappers.php';
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "not_logged_in"]);
    exit;
}

$myId = intval($_SESSION['user_id']);
$friendId = intval($_GET['friend_id'] ?? 0);

if ($friendId <= 0) {
    echo json_encode(["status" => "none"]);
    exit;
}

// تحقق من الصداقة
$stmt = $conn->prepare("SELECT 1 FROM friends WHERE user_id = ? AND friend_id = ?");
$stmt->bind_param("ii", $myId, $friendId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(["status" => "friend"]);
    exit;
}
$stmt->close();

// تحقق من طلب صداقة معلق (أنا بعثتله)
$stmt = $conn->prepare("SELECT 1 FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $myId, $friendId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(["status" => "sent"]);
    exit;
}
$stmt->close();

// تحقق من طلب صداقة وارد (هو بعثلي)
$stmt = $conn->prepare("SELECT 1 FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $friendId, $myId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(["status" => "received"]);
    exit;
}
$stmt->close();

// لا يوجد شيء
echo json_encode(["status" => "none"]);
?>