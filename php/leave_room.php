<?php
session_start();
require './php/db.php';  // تأكد إنه فيه تعريف $conn (MySQLi)

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "❌ لم يتم تسجيل الدخول"]);
    exit;
}

if (!isset($_POST['room_id'])) {
    echo json_encode(["success" => false, "message" => "❌ لا يوجد رقم غرفة"]);
    exit;
}

$roomId = intval($_POST['room_id']);
$userId = $_SESSION['user_id'];

if ($roomId <= 0) {
    echo json_encode(["success" => false, "message" => "❌ معرف الغرفة غير صالح"]);
    exit;
}

// جلب بيانات اللاعب
$stmt = $conn->prepare("SELECT u.username, rp.last_status 
                        FROM room_players rp
                        JOIN users u ON rp.user_id = u.id
                        WHERE rp.room_id = ? AND rp.user_id = ?");
$stmt->bind_param("ii", $roomId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$username = $data['username'] ?? '';
$lastStatus = $data['last_status'] ?? 'in';

// تسجيل رسالة خروج إذا اللاعب في الغرفة
if ($lastStatus === 'in' && $username) {
    $msg = "🚪 اللاعب {$username} غادر اللعبة";
    $stmtMsg = $conn->prepare("INSERT INTO system_messages (room_id, message) VALUES (?, ?)");
    $stmtMsg->bind_param("is", $roomId, $msg);
    $stmtMsg->execute();
    $stmtMsg->close();
}

// حذف اللاعب من الغرفة
$stmtDel = $conn->prepare("DELETE FROM room_players WHERE room_id = ? AND user_id = ?");
$stmtDel->bind_param("ii", $roomId, $userId);
$stmtDel->execute();
$affected = $stmtDel->affected_rows;
$stmtDel->close();

unset($_SESSION['room_id']);

// الرد بالنتيجة
echo json_encode([
    "success" => $affected > 0,
    "message" => $affected > 0 ? "✅ تم مغادرة الغرفة" : "❌ لم يتم تعديل أي بيانات",
    "affected_rows" => $affected
]);

exit;
