<?php
require_once __DIR__ . '/_safe_wrappers.php';
// end_voting.php — نسخة معدلة
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

require 'db.php';
global $conn;

$roomId = intval($_POST['room_id'] ?? $_GET['room_id'] ?? 0);
if ($roomId <= 0) {
    echo json_encode(["status" => "error", "message" => "❌ بيانات الغرفة غير صالحة."]);
    exit;
}

$stmt = $conn->prepare("SELECT mission, results_posted FROM rooms WHERE id = ?");
$stmt->bind_param("i", $roomId);
$stmt->execute();
$roomInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$roomInfo) {
    echo json_encode(["status" => "error", "message" => "❌ الغرفة غير موجودة."]);
    exit;
}

if (!empty($roomInfo['results_posted'])) {
    echo json_encode(["status" => "info", "message" => "ℹ️ النتائج تم نشرها بالفعل لهذه الجولة."]);
    exit;
}

$mission_text = trim($roomInfo['mission'] ?? '') ?: "لا توجد مهمة مسجلة";

$updateRoom = $conn->prepare("UPDATE rooms SET voting_started = 0, results_posted = 1 WHERE id = ?");
$updateRoom->bind_param("i", $roomId);
$updateRoom->execute();
$updateRoom->close();

$all_players_data = [];
$rp = $conn->prepare("SELECT u.id AS id, u.username AS username, pr.role AS role
                      FROM player_roles pr
                      JOIN users u ON pr.user_id = u.id
                      WHERE pr.room_id = ?");
$rp->bind_param("i", $roomId);
$rp->execute();
$res = $rp->get_result();
while ($r = $res->fetch_assoc()) {
    $all_players_data[$r['id']] = [
        'username' => $r['username'],
        'role' => $r['role']
    ];
}
$rp->close();

$killer_id = $conspirator_id = $agent_id = null;
foreach ($all_players_data as $uid => $info) {
    if ($info['role'] === 'القاتل') $killer_id = $uid;
    if ($info['role'] === 'متورط') $conspirator_id = $uid;
    if ($info['role'] === 'العميل') $agent_id = $uid;
}

function get_voters_for_target($conn, $roomId, $target_id) {
    $names = [];
    if (empty($target_id)) return $names;
    $s = $conn->prepare("SELECT u.username FROM votes v JOIN users u ON v.voter_id = u.id WHERE v.room_id = ? AND v.target_id = ?");
    $s->bind_param("ii", $roomId, $target_id);
    $s->execute();
    $res = $s->get_result();
    while ($row = $res->fetch_assoc()) $names[] = $row['username'];
    $s->close();
    return $names;
}

$messages = [];

// 1- عرض أدوار اللاعبين
$roles_msg = "🃏 أدوار اللاعبين:\n";
foreach ($all_players_data as $player) {
    $roles_msg .= "- {$player['username']}: {$player['role']}\n";
}
$messages[] = trim($roles_msg);

// 2- حالة القاتل والمتورط
if (!empty($killer_id)) {
    $killer_voters = get_voters_for_target($conn, $roomId, $killer_id);
    $killer_name = $all_players_data[$killer_id]['username'] ?? 'غير معروف';
    $conspirator_name = $all_players_data[$conspirator_id]['username'] ?? 'غير معروف';

    if (count($killer_voters) > 0) {
        $messages[] = "🎯 تم اكتشاف القاتل والمتورط: {$killer_name} و {$conspirator_name}\n📌 المهمة: {$mission_text}\n🔍 تم اكتشافه بواسطة: " . implode(", ", $killer_voters);
    } else {
        $messages[] = "✅ نجح القاتل والمتورط ولم يتم اكتشافهما\n👤 القاتل: {$killer_name} | المتورط: {$conspirator_name}\n📌 المهمة: {$mission_text}";
    }
}

// 3- حالة العميل
if (!empty($agent_id)) {
    $agent_voters = get_voters_for_target($conn, $roomId, $agent_id);
    $agent_name = $all_players_data[$agent_id]['username'] ?? 'غير معروف';

    if (count($agent_voters) > 0) {
        $messages[] = "💀 تم اكتشاف العميل: {$agent_name}\n🔍 تم اكتشافه بواسطة: " . implode(", ", $agent_voters);
    } else {
        $messages[] = "🎭 نجح العميل ولم يتم اكتشافه\n👤 العميل: {$agent_name}";
    }
}

if (empty($messages)) {
    echo json_encode(["status" => "error", "message" => "❌ لم يتم العثور على أدوار قابلة للمعالجة في هذه الغرفة."]);
    exit;
}

foreach ($messages as $msg) {
    $ins = $conn->prepare("INSERT INTO system_messages (room_id, message) VALUES (?, ?)");
    $ins->bind_param("is", $roomId, $msg);
    $ins->execute();
    $ins->close();
}

$del = $conn->prepare("DELETE FROM votes WHERE room_id = ?");
$del->bind_param("i", $roomId);
$del->execute();
$del->close();

echo json_encode(["status" => "done", "message" => "✅ تم إعلان النتائج في الشات."]);
exit;
?>
