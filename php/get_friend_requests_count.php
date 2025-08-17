<?php
// === تفعيل عرض الأخطاء للتشخيص (يمكنك تعطيله في الإنتاج) ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// === بدء الجلسة ===
session_start();

// === إعداد رأس الاستجابة كـ JSON ===
header('Content-Type: application/json; charset=UTF-8');

// === استدعاء ملفات الاتصال بقاعدة البيانات ===
require_once __DIR__ . '/db.php'; // تأكد المسار صحيح

// === التحقق من تسجيل الدخول ===
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '❌ لم يتم تسجيل الدخول', 'count' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = intval($_SESSION['user_id']);

try {
    // === جلب عدد الطلبات ===
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt 
        FROM friend_requests 
        WHERE receiver_id = ? AND status = 'pending'
    ");
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = intval($row['cnt'] ?? 0);
    $stmt->close();

    echo json_encode(['success' => true, 'count' => $count], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // في حالة أي خطأ
    echo json_encode([
        'success' => false,
        'message' => '❌ حدث خطأ أثناء جلب عدد الطلبات: ' . $e->getMessage(),
        'count' => 0
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
