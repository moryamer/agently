<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;
$friend_id = $_POST['friend_id'] ?? 0;

if (!$user_id || !$friend_id || !is_numeric($friend_id)) {
    echo "بيانات غير صالحة";
    exit;
}

$friend_id = (int)$friend_id;

// تحقق من وجود المستخدم
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $friend_id);
$stmt->execute();
if ($stmt->get_result()->num_rows == 0) {
    echo "المستخدم غير موجود";
    exit;
}

// ✅ التحقق من حالة الطلب الحالي
$stmt = $conn->prepare("SELECT status FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
$stmt->bind_param("ii", $user_id, $friend_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if ($request) {
    if ($request['status'] === 'pending') {
        echo "لقد أرسلت طلب صداقة لهذا المستخدم من قبل.";
        exit;
    }
    // ✅ إذا كان مرفوض، احذفه علشان نسمح بإعادة الإرسال
    if ($request['status'] === 'rejected') {
        $deleteStmt = $conn->prepare("DELETE FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
        $deleteStmt->bind_param("ii", $user_id, $friend_id);
        $deleteStmt->execute();
    }
    // ✅ إذا كان مقبول (نادر)، احذفه كمان (ممكن يصير)
    if ($request['status'] === 'accepted') {
        $deleteStmt = $conn->prepare("DELETE FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
        $deleteStmt->bind_param("ii", $user_id, $friend_id);
        $deleteStmt->execute();
    }
}

// إرسال طلب جديد
$stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("ii", $user_id, $friend_id);

if ($stmt->execute()) {
    echo "تم إرسال طلب الصداقة";
} else {
    echo "فشل في الإرسال";
}
exit;

/* WS AUTO-PUBLISH */
require_once __DIR__ . '/notify_ws.php';
// Notify target user to update notifications
if (isset($target_user_id)) {
    ws_publish('friend_requests_updated', null, null, $target_user_id);
}
