<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit("❌ يجب تسجيل الدخول.");
}

$sender_id = $_SESSION['user_id'];
$receiver_id = intval($_POST['receiver_id'] ?? 0);

if ($receiver_id <= 0 || $receiver_id == $sender_id) {
    exit("❌ بيانات غير صالحة.");
}

// التحقق لو الطلب موجود
$stmt = $conn->prepare("SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
$stmt->bind_param("ii", $sender_id, $receiver_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    exit("⚠️ لقد أرسلت طلب صداقة من قبل.");
}
$stmt->close();

// إدخال الطلب
$stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("ii", $sender_id, $receiver_id);
if ($stmt->execute()) {
    echo "✅ تم إرسال طلب الصداقة.";
} else {
    echo "❌ حدث خطأ.";
}
$stmt->close();
