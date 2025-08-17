<?php
require_once __DIR__ . '/_safe_wrappers.php';
// إعدادات لعرض جميع الأخطاء لتشخيص المشكلة. يجب إزالتها في بيئة الإنتاج.
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // تأكد من بدء الجلسة لجلب user_id
header('Content-Type: application/json');
require 'db.php'; // Assuming db.php provides $conn (mysqli connection)

$response = [
    "voting_started" => false,
    "players" => [],
    "results" => [],
    "has_voted" => false, // إضافة حالة التصويت للمستخدم الحالي
    "killer_done" => 0, // إضافة حالة مهمة القاتل
    "conspirator_done" => 0, // إضافة حالة مهمة المتورط
    "error" => ""
];

try {
    global $conn; // Declare $conn as global to use it

    if (!isset($_GET['room_id'])) {
        throw new Exception("Room ID is required");
    }

    $room_id = intval($_GET['room_id']);
    $current_user_id = $_SESSION['user_id'] ?? 0; // جلب معرف المستخدم الحالي من الجلسة

    // جلب حالة التصويت وحالة المهام من جدول rooms
    $stmt_room_status = $conn->prepare("SELECT voting_started, killer_done, conspirator_done FROM rooms WHERE id = ?");
    $stmt_room_status->bind_param("i", $room_id);
    $stmt_room_status->execute();
    $room_status = $stmt_room_status->get_result()->fetch_assoc();
    $stmt_room_status->close();

    if (!$room_status) {
        throw new Exception("Room not found");
    }

    $response['voting_started'] = (bool)$room_status['voting_started'];
    $response['killer_done'] = (bool)$room_status['killer_done'];
    $response['conspirator_done'] = (bool)$room_status['conspirator_done'];

    if ($response['voting_started']) {
        // التحقق مما إذا كان المستخدم الحالي قد صوت بالفعل
        if ($current_user_id > 0) {
            $stmt_has_voted = $conn->prepare("SELECT 1 FROM votes WHERE room_id = ? AND voter_id = ? LIMIT 1");
            $stmt_has_voted->bind_param("ii", $room_id, $current_user_id);
            $stmt_has_voted->execute();
            $response['has_voted'] = $stmt_has_voted->get_result()->num_rows > 0;
            $stmt_has_voted->close();
        }

        // جلب كل اللاعبين (المعرف واسم المستخدم) في الغرفة، باستثناء المراقب
        $sql = "
            SELECT u.id, u.username
            FROM room_players rp
            JOIN users u ON rp.user_id = u.id
            JOIN player_roles pr ON rp.user_id = pr.user_id AND rp.room_id = pr.room_id
            WHERE rp.room_id = ? AND pr.role != 'المراقب'
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $response['players'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // جلب نتيجة التصويت لو فيه
        $stmt = $conn->prepare("
            SELECT u.username AS target, COUNT(v.id) AS vote_count
            FROM votes v
            JOIN users u ON v.target_id = u.id
            WHERE v.room_id = ?
            GROUP BY v.target_id, u.username
        ");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $response['results'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
