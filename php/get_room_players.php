<?php
// تأكد من أن المسار صحيح لـ _safe_wrappers.php
// إذا كان في نفس مجلد php/، فالمسار هو __DIR__ . '/_safe_wrappers.php'
// إذا كان في المجلد الرئيسي htdocs/، فالمسار هو __DIR__ . '/../_safe_wrappers.php'
require_once __DIR__ . '/_safe_wrappers.php';

session_start();

// تأكد من أن المسار صحيح لـ db.php (عادةً يكون في نفس مجلد php/)
require 'db.php'; 

// يجب أن تكون الدالة header() قبل أي إخراج
header('Content-Type: application/json; charset=utf-8');

// التحقق من وجود معرف الغرفة في الطلب
if (!isset($_GET['room_id'])) {
    echo json_encode([]); // إرجاع مصفوفة فارغة إذا لم يتم توفير room_id
    exit();
}

$roomId = intval($_GET['room_id']); // تحويل معرف الغرفة إلى عدد صحيح

// جلب اللاعبين المتصلين في الغرفة
$stmt = $conn->prepare("
    SELECT
        u.username
    FROM
        room_players rp
    JOIN
        users u ON rp.user_id = u.id
    WHERE
        rp.room_id = ? AND rp.is_online = 1
");
$stmt->bind_param("i", $roomId);
$stmt->execute();
$result = $stmt->get_result();

$players = [];
while ($row = $result->fetch_assoc()) {
    $players[] = $row;
}

echo json_encode($players, JSON_UNESCAPED_UNICODE);

// إغلاق الاتصال بقاعدة البيانات
$conn->close();
