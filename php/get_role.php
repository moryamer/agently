<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php';

$roomId = intval($_GET['room_id'] ?? 0);
$userId = $_SESSION['user_id'] ?? 0;

$roleRes = $conn->prepare("SELECT role FROM player_roles WHERE room_id = ? AND user_id = ?");
$roleRes->bind_param("ii", $roomId, $userId);
$roleRes->execute();
$result = $roleRes->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["role" => $result->fetch_assoc()['role']]);
} else {
    echo json_encode(["role" => null]);
}
