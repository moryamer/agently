<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_POST['room_id']) || !isset($_POST['type']) || !isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "❌ بيانات غير صالحة."], JSON_UNESCAPED_UNICODE);
    exit();
}

$roomId = intval($_POST['room_id']);
$taskType = $_POST['type']; // 'killer' or 'conspirator'
$userId = $_SESSION['user_id'];
$points_for_task = 25; // نقاط تضاف للقاتل/المتورط عند إكمال مهمته

// تحقق إذا كان المستخدم الحالي هو المراقب لهذه الغرفة
$roleCheckStmt = $conn->prepare("SELECT role FROM player_roles WHERE room_id = ? AND user_id = ? LIMIT 1");
$roleCheckStmt->bind_param("ii", $roomId, $userId);
$roleCheckStmt->execute();
$roleResult = $roleCheckStmt->get_result();
$userRole = $roleResult->fetch_assoc()['role'] ?? null;
$roleCheckStmt->close();

if ($userRole !== 'المراقب') {
    echo json_encode(["status" => "error", "message" => "🚫 ليس لديك صلاحية لتنفيذ هذا الإجراء."], JSON_UNESCAPED_UNICODE);
    exit();
}

$updateColumn = "";
$roleToReward = "";
$msg = ""; // رسالة تأكيد المهمة للاعب

if ($taskType === 'killer') {
    $updateColumn = "killer_done";
    $roleToReward = "القاتل";
    $msg = "✅ تم تأكيد مهمة القاتل.";
} elseif ($taskType === 'conspirator') {
    $updateColumn = "conspirator_done";
    $roleToReward = "متورط";
    $msg = "✅ تم تأكيد مهمة المتورط.";
} else {
    echo json_encode(["status" => "error", "message" => "❌ نوع مهمة غير صالح."], JSON_UNESCAPED_UNICODE);
    exit();
}

// تحديث حالة المهمة في قاعدة البيانات
$stmt = $conn->prepare("UPDATE rooms SET {$updateColumn} = 1 WHERE id = ?");
$stmt->bind_param("i", $roomId);

if ($stmt->execute()) {
    // إرسال رسالة نظام إلى الشات بأن المراقب أكد المهمة
    $system_msg_confirmed = "✅ تم تأكيد مهمة " . ($taskType === 'killer' ? 'القاتل' : 'المتورط') . " بواسطة المراقب.";
    $stmt_sys_msg_confirmed = $conn->prepare("INSERT INTO system_messages (room_id, message) VALUES (?, ?)");
    $stmt_sys_msg_confirmed->bind_param("is", $roomId, $system_msg_confirmed);
    $stmt_sys_msg_confirmed->execute();
    $stmt_sys_msg_confirmed->close();

    // إضافة نقاط للدور الذي أكمل مهمته
    $player_id_res = $conn->prepare("SELECT user_id FROM player_roles WHERE room_id = ? AND role = ? LIMIT 1");
    $player_id_res->bind_param("is", $roomId, $roleToReward);
    $player_id_res->execute();
    $player_id = $player_id_res->get_result()->fetch_assoc()['user_id'] ?? null;
    $player_id_res->close();

    if ($player_id) {
        $update_points_stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $update_points_stmt->bind_param("ii", $points_for_task, $player_id);
        $update_points_stmt->execute();
        $update_points_stmt->close();
    }

    // التحقق مما إذا كانت كلتا المهمتين قد اكتملتا
    $progress = $conn->prepare("SELECT killer_done, conspirator_done FROM rooms WHERE id = ?");
    $progress->bind_param("i", $roomId);
    $progress->execute();
    $progressData = $progress->get_result()->fetch_assoc();
    $progress->close();

    if ($progressData['killer_done'] == 1 && $progressData['conspirator_done'] == 1) {
        $system_msg_all_done = "✅ تم الانتهاء من جميع المهام! يمكن للمراقب بدء التصويت الآن.";
        $stmt_sys_msg_all_done = $conn->prepare("INSERT INTO system_messages (room_id, message) VALUES (?, ?)");
        $stmt_sys_msg_all_done->bind_param("is", $roomId, $system_msg_all_done);
        $stmt_sys_msg_all_done->execute();
        $stmt_sys_msg_all_done->close();

        // إضافة نقاط للعميل إذا تم إكمال المهمتين (وفقًا لمنطقك)
        $agent_id_res = $conn->prepare("SELECT user_id FROM player_roles WHERE room_id = ? AND role = 'العميل' LIMIT 1");
        $agent_id_res->bind_param("i", $roomId);
        $agent_id_res->execute();
        $agent_id = $agent_id_res->get_result()->fetch_assoc()['user_id'] ?? null;
        $agent_id_res->close();

        if ($agent_id) {
            $points_for_agent = 10;
            $update_agent_points_stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
            $update_agent_points_stmt->bind_param("ii", $points_for_agent, $agent_id);
            $update_agent_points_stmt->execute();
            $update_agent_points_stmt->close();
        }
    }

    echo json_encode(["status" => "success", "message" => $msg], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["status" => "error", "message" => "❌ حدث خطأ أثناء تحديث المهمة: " . $stmt->error], JSON_UNESCAPED_UNICODE);
}
$stmt->close();
?>
