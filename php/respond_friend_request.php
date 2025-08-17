<?php
require_once __DIR__ . '/_safe_wrappers.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "غير مسجل الدخول"]);
    exit;
}

$userId = intval($_SESSION['user_id']);
$data = json_decode(file_get_contents("php://input"), true);

$requestId = intval($data['request_id'] ?? 0);
$accept = !empty($data['accept']); // true لو 1 أو true

if (!$requestId) {
    echo json_encode(["success" => false, "message" => "رقم الطلب مفقود"]);
    exit;
}

// التحقق أن الطلب موجه لك وحالته pending
$stmt = $conn->prepare("
    SELECT sender_id 
    FROM friend_requests 
    WHERE id = ? AND receiver_id = ? AND status = 'pending'
");
$stmt->bind_param("ii", $requestId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "الطلب غير موجود أو تم معالجته"]);
    exit;
}

$senderId = $result->fetch_assoc()['sender_id'];
$stmt->close();

if ($accept) {
    // قبول: أضف كأصدقاء (اتنين اتجاه)
    $stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)");
    $stmt->bind_param("iiii", $userId, $senderId, $senderId, $userId);
    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "message" => "خطأ في الإضافة"]);
        exit;
    }
    $stmt->close();

    // حدّث حالة الطلب
    $stmt = $conn->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        "success" => true, 
        "message" => "✅ تم قبول الطلب"
    ]);
} else {
    // رفض: غير الحالة لـ rejected
    $stmt = $conn->prepare("UPDATE friend_requests SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        "success" => true, 
        "message" => "❌ تم رفض الطلب"
    ]);
}

/* WS AUTO-PUBLISH */
require_once __DIR__ . '/notify_ws.php';
// Notify both users to refresh friends list/count
if (isset($from_user_id)) { ws_publish('friend_requests_updated', null, null, $from_user_id); }
if (isset($_SESSION['user_id'])) { ws_publish('friend_requests_updated', null, null, $_SESSION['user_id']); }
