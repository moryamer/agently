<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php'; // تأكد من أن هذا الملف موجود ويتصل بقاعدة البيانات بشكل صحيح

// دائماً أرسل رأس JSON لضمان أن JavaScript يتوقع الاستجابة الصحيحة
// Always send JSON header to ensure JavaScript expects the correct response
header('Content-Type: application/json; charset=utf-8');

// التحقق من تسجيل الدخول ووجود معرف الغرفة
// Check for login and room_id
if (!isset($_SESSION['user_id']) || !isset($_POST['room_id'])) {
    echo json_encode(['success' => false, 'message' => '❌ بيانات ناقصة لبدء اللعبة.']);
    exit();
}

$roomId = intval($_POST['room_id']);
$userId = $_SESSION['user_id'];

// التحقق أن المستخدم هو صاحب الغرفة
// Check if the user is the host of the room
$roomData = $conn->prepare("SELECT host_id FROM rooms WHERE id = ?");
$roomData->bind_param("i", $roomId);
$roomData->execute();
$room = $roomData->get_result()->fetch_assoc();

if (!$room || $room['host_id'] != $userId) {
    echo json_encode(['success' => false, 'message' => '❌ لا تملك صلاحية بدء اللعبة.']);
    exit();
}

// جلب اللاعبين المتصلين فقط
// Fetch only online players
$playersRes = $conn->prepare("SELECT user_id FROM room_players WHERE room_id = ? AND is_online = 1");
$playersRes->bind_param("i", $roomId);
$playersRes->execute();
$players = $playersRes->get_result()->fetch_all(MYSQLI_ASSOC);

// يجب أن يكون هناك 5 لاعبين على الأقل لبدء اللعبة (وفقاً لمنطقك)
// There must be at least 5 players to start the game (according to your logic)
if (count($players) < 5) {
    echo json_encode(['success' => false, 'message' => '❌ يجب أن يكون هناك 5 لاعبين متصلين على الأقل لبدء اللعبة.']);
    exit();
}

// مسح أي أدوار قديمة للغرفة
// Clear any old roles for the room
$delStmt = $conn->prepare("DELETE FROM player_roles WHERE room_id = ?");
$delStmt->bind_param("i", $roomId);
$delStmt->execute();

// توزيع الأدوار
shuffle($players); // خلط ترتيب اللاعبين عشوائياً
$roles = [];

// تعيين الأدوار الأساسية
// Assign core roles
$roles[$players[0]['user_id']] = "المراقب"; // Observer
$roles[$players[1]['user_id']] = "العميل";   // Agent
$roles[$players[2]['user_id']] = "القاتل";   // Killer

// جلب عدد المتورطين من إعدادات الغرفة
// Fetch conspirators count from room settings
$conspiratorsCountRes = $conn->query("SELECT conspirators FROM rooms WHERE id = $roomId");
$conspiratorsCount = $conspiratorsCountRes->fetch_assoc()['conspirators'];

// تعيين أدوار المتورطين
// Assign conspirator roles
for ($i = 3; $i < 3 + $conspiratorsCount; $i++) {
    if (isset($players[$i])) { // تأكد أن اللاعب موجود في المصفوفة
        $roles[$players[$i]['user_id']] = "متورط"; // Conspirator
    }
}

// تعيين أدوار المشاهدين لباقي اللاعبين
// Assign spectator roles to remaining players
for ($i = 0; $i < count($players); $i++) {
    $uid = $players[$i]['user_id'];
    if (!isset($roles[$uid])) {
        $roles[$uid] = "مشاهد"; // Spectator
    }
}

// حفظ الأدوار في قاعدة البيانات
// Save roles to the database
$stmt = $conn->prepare("INSERT INTO player_roles (room_id, user_id, role) VALUES (?, ?, ?)");
foreach ($roles as $uid => $role) {
    $stmt->bind_param("iis", $roomId, $uid, $role);
    $stmt->execute();
}

// جلب اسم المراقب وإضافة رسالة نظام
// Fetch observer's name and add a system message
$monitorId = array_search("المراقب", $roles);
if ($monitorId !== false) {
    $monitorNameRes = $conn->query("SELECT username FROM users WHERE id = $monitorId");
    $monitorName = $monitorNameRes->fetch_assoc()['username'];

    $msg = "👁 المراقب هو: {$monitorName}";
    $sysStmt = $conn->prepare("INSERT INTO system_messages (room_id, message) VALUES (?, ?)");
    $sysStmt->bind_param("is", $roomId, $msg);
    $sysStmt->execute();
}

// تحديث حالة الغرفة إلى "started"
// Update room status to "started"
$updateRoomStatusStmt = $conn->prepare("UPDATE rooms SET status='started', game_started = 1 WHERE id=?");
$updateRoomStatusStmt->bind_param("i", $roomId);
$updateRoomStatusStmt->execute();

// إرجاع استجابة النجاح مع مسار إعادة التوجيه
// Return success response with redirect path
echo json_encode([
    "success" => true,
    "redirect" => "game.php?room_id=" . $roomId // تم تعديل المسار ليكون نسبياً
]);
exit();

?>


/* WS AUTO-PUBLISH */
require_once __DIR__ . '/notify_ws.php';
if (isset($room_id)) {
    ws_publish('game_started', null, $room_id, null);
}
