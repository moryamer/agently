<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php';

if (!isset($_POST['room_id'])) {
    exit("❌ بيانات غير صالحة");
}

$roomId = intval($_POST['room_id']);

// تحديد أن التصويت بدأ ومسح علم النتائج المنشورة السابقة
$conn->query("UPDATE rooms SET voting_started = 1, results_posted = 0 WHERE id = $roomId");

// مسح أي تصويتات قديمة
$conn->query("DELETE FROM votes WHERE room_id = $roomId");

// إرسال رسالة للنظام بأن التصويت بدأ
$msg = "🗳 بدأ التصويت! اختار اللاعب المشتبه به.";
$stmt = $conn->prepare("INSERT INTO system_messages (room_id, message) VALUES (?, ?)");
$stmt->bind_param("is", $roomId, $msg);
$stmt->execute();
$stmt->close(); // أغلق الـ statement بعد التنفيذ لضمان تحرير الموارد

echo "✅ تم بدء التصويت";

?>
