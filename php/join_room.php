<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php';

$roomId = isset($_POST['room_id']) ? intval($_POST['room_id']) : (isset($_GET['room_id']) ? intval($_GET['room_id']) : null);
$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (!$roomId || !$userId) {
    exit("❌ بيانات ناقصة");
}

// جلب حالة اللعبة
$checkGame = $conn->prepare("SELECT game_started FROM rooms WHERE id = ?");
$checkGame->bind_param("i", $roomId);
$checkGame->execute();
$gameData = $checkGame->get_result()->fetch_assoc();
$gameStarted = $gameData ? intval($gameData['game_started']) : 0;

// تحقق إذا اللاعب موجود بالفعل
$check = $conn->prepare("SELECT * FROM room_players WHERE room_id = ? AND user_id = ?");
$check->bind_param("ii", $roomId, $userId);
$check->execute();
$result = $check->get_result();

$isNewPlayer = ($result->num_rows === 0);

if ($isNewPlayer) {
    // اللاعب جديد، أضف إدخال جديد
    $stmt = $conn->prepare("INSERT INTO room_players (room_id, user_id, is_online) VALUES (?, ?, 1)");
    $stmt->bind_param("ii", $roomId, $userId);
    $stmt->execute();

    // جلب اسم المستخدم لإضافة رسالة النظام
    $getUser = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $getUser->bind_param("i", $userId);
    $getUser->execute();
    $userData = $getUser->get_result()->fetch_assoc();
    $username = $userData['username'];

    // إضافة رسالة نظام بانضمام اللاعب
    $msg = "✅ اللاعب {$username} انضم إلى الغرفة";
    $stmt = $conn->prepare("INSERT INTO system_messages (room_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $roomId, $msg);
    $stmt->execute();

} else {
    // تحقق إذا كان اللاعب كان أوفلاين
    $checkStatus = $conn->prepare("SELECT is_online FROM room_players WHERE room_id = ? AND user_id = ?");
    $checkStatus->bind_param("ii", $roomId, $userId);
    $checkStatus->execute();
    $status = $checkStatus->get_result()->fetch_assoc();

    // حدث حالته إلى متصل
    $update = $conn->prepare("UPDATE room_players SET is_online = 1 WHERE room_id = ? AND user_id = ?");
    $update->bind_param("ii", $roomId, $userId);
    $update->execute();

    // لو كان أوفلاين قبل كده، أضف رسالة انضمام
    if ($status['is_online'] == 0) {
        $getUser = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $getUser->bind_param("i", $userId);
        $getUser->execute();
        $userData = $getUser->get_result()->fetch_assoc();
        $username = $userData['username'];

        $msg = "✅ اللاعب {$username} انضم إلى الغرفة";
        $stmt = $conn->prepare("INSERT INTO system_messages (room_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $roomId, $msg);
        $stmt->execute();
    }
}

// توجيه حسب حالة اللعبة
if ($gameStarted === 1) {
    echo "success:" . $roomId . ":game";
} else {
    echo "success:" . $roomId . ":room";
}