<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php'; // تأكد من أن هذا الملف موجود ويتصل بقاعدة البيانات بشكل صحيح

// التحقق من وجود معرف المستخدم في الجلسة ومعرف الغرفة في الطلب
// Check for user_id in session and room_id in request
if (!isset($_SESSION['user_id']) || !isset($_POST['room_id'])) {
    // في حالة عدم وجود البيانات، لا تفعل شيئًا واخرج بهدوء.
    // هذا السكريبت يُستدعى في الخلفية ولا يحتاج إلى استجابة مرئية.
    // If data is missing, do nothing and exit quietly.
    // This script is called in the background and doesn't need a visible response.
    exit();
}

$roomId = intval($_POST['room_id']);
$userId = $_SESSION['user_id'];

// تحديث حالة اللاعب إلى "متصل" وتحديث وقت آخر ظهور له
// Update player's status to "online" and update their last seen time
$stmt = $conn->prepare("UPDATE room_players SET is_online = 1, last_seen = NOW() WHERE room_id = ? AND user_id = ?");
$stmt->bind_param("ii", $roomId, $userId);
$stmt->execute();

exit(); // لا توجد حاجة لإرجاع أي شيء
?>


/* WS AUTO-PUBLISH */
// no publish (read-only)
