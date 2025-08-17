<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit("❌ لم يتم تسجيل الدخول");
}

$roomName = trim($_POST['room_name']);
$maxPlayers = intval($_POST['max_players']);
$conspirators = intval($_POST['conspirators']);
$userId = $_SESSION['user_id'];

// إنشاء الغرفة
$stmt = $conn->prepare("INSERT INTO rooms (room_name, host_id, max_players, conspirators, game_started) VALUES (?, ?, ?, ?, 0)");
$stmt->bind_param("siii", $roomName, $userId, $maxPlayers, $conspirators);

if ($stmt->execute()) {
    $roomId = $stmt->insert_id;

    // إضافة صاحب الغرفة كأول لاعب (أونلاين)
    $stmt2 = $conn->prepare("INSERT INTO room_players (room_id, user_id, is_online) VALUES (?, ?, 1)");
    $stmt2->bind_param("ii", $roomId, $userId);
    $stmt2->execute();

    // جلب اسم صاحب الغرفة
    $getUser = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $getUser->bind_param("i", $userId);
    $getUser->execute();
    $userData = $getUser->get_result()->fetch_assoc();
    $username = $userData['username'];

    // إضافة رسالة بانضمام صاحب الغرفة
    $msg = "✅ اللاعب {$username} انضم إلى الغرفة";
    $stmt3 = $conn->prepare("INSERT INTO system_messages (room_id, message) VALUES (?, ?)");
    $stmt3->bind_param("is", $roomId, $msg);
    $stmt3->execute();

    echo "success:$roomId";
} else {
    echo "❌ حدث خطأ أثناء إنشاء الغرفة";
}
?>


/* WS AUTO-PUBLISH */
// After successful insert, publish an update
require_once __DIR__ . '/notify_ws.php';
ws_publish('rooms_updated', null, null, null);
