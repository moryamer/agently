<?php
// تفعيل عرض الأخطاء للتصحيح - قم بإزالتها بعد التأكد من عمل الملف
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/_safe_wrappers.php'; // تأكد من المسار الصحيح
require_once __DIR__ . '/db.php'; // تأكد من المسار الصحيح

session_start();

header('Content-Type: application/json; charset=utf-8'); // لضمان استجابة JSON

// التحقق من أن المستخدم مسجل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '❌ لم يتم تسجيل الدخول.']);
    exit();
}

$userId = $_SESSION['user_id'];
$roomId = isset($_POST['room_id']) ? intval($_POST['room_id']) : null;

if (!$roomId) {
    echo json_encode(['success' => false, 'message' => '❌ بيانات الغرفة غير موجودة.']);
    exit();
}

try {
    // التحقق مما إذا كان اللاعب موجوداً بالفعل في الغرفة
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM room_players WHERE room_id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $roomId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_row();
    $playerExists = $row[0] > 0;
    $checkStmt->close();

    if ($playerExists) {
        // إذا كان اللاعب موجوداً، قم بتحديث حالته إلى "متصل"
        $updateStmt = $conn->prepare("UPDATE room_players SET is_online = 1, last_activity = NOW() WHERE room_id = ? AND user_id = ?");
        $updateStmt->bind_param("ii", $roomId, $userId);
        $updateStmt->execute();
        $updateStmt->close();
        echo json_encode(['success' => true, 'message' => '✅ تم تحديث حالة التواجد.']);
    } else {
        // إذا لم يكن اللاعب موجوداً في الغرفة (حالة غير متوقعة إذا كان يجب أن يكون قد انضم)
        echo json_encode(['success' => false, 'message' => '🚫 اللاعب غير موجود في هذه الغرفة.']);
    }

} catch (mysqli_sql_exception $e) {
    // التعامل مع أخطاء قاعدة البيانات
    error_log("Database error in update_online_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '❌ خطأ في قاعدة البيانات.']);
} catch (Exception $e) {
    // التعامل مع أي أخطاء أخرى
    error_log("General error in update_online_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '❌ حدث خطأ غير متوقع.']);
}

$conn->close();
?>
