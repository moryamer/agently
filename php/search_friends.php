<?php
require_once __DIR__ . '/_safe_wrappers.php';
// تفعيل عرض الأخطاء (فقط أثناء التطوير)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];
$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$isNumber = ctype_digit($q);

if ($isNumber) {
    $sql = "SELECT id, username, avatar FROM users WHERE (id = ? OR username LIKE ?) AND id != ?";
} else {
    $sql = "SELECT id, username, avatar FROM users WHERE (username LIKE ? OR CAST(id AS CHAR) LIKE ?) AND id != ?";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'فشل في إعداد الاستعلام', 'details' => $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$like = "%$q%";
if ($isNumber) {
    $stmt->bind_param("isi", $q, $like, $user_id);
} else {
    $stmt->bind_param("ssi", $like, $like, $user_id);
}

if (!$stmt->execute()) {
    echo json_encode(['error' => 'فشل في التنفيذ', 'details' => $stmt->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = $stmt->get_result();
$users = [];

while ($row = $result->fetch_assoc()) {
    $row['avatar'] = $row['avatar'] ?? 'default.png';
    $users[] = $row;
}

echo json_encode($users, JSON_UNESCAPED_UNICODE);
exit;