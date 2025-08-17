<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php';

if (!isset($_POST['room_id'], $_POST['mission'])) {
    exit("❌ بيانات ناقصة");
}

$roomId = intval($_POST['room_id']);
$mission = trim($_POST['mission']);

if (!$roomId || !$mission) {
    exit("❌ بيانات غير صحيحة");
}

// تحديث المهمة في جدول rooms
$stmt = $conn->prepare("UPDATE rooms SET mission = ?, mission_sent = 1, killer_done = 0, conspirator_done = 0 WHERE id = ?");
$stmt->bind_param("si", $mission, $roomId);

if ($stmt->execute()) {
    // تسجيل رسالة في system_messages
    $msg = "تم ارسال الحكم";

    $stmt2 = $conn->prepare("INSERT INTO system_messages (room_id, message) VALUES (?, ?)");
    $stmt2->bind_param("is", $roomId, $msg);
    $stmt2->execute();
    $stmt2->close();

    echo "✅ تم إرسال المهمة للقاتل والمتورط";
} else {
    echo "❌ فشل في إرسال المهمة";
}

$stmt->close();
?>
