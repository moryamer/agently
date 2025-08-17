<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php'; // Assuming db.php is in the same directory

header('Content-Type: application/json'); // Ensure JSON response

if (!isset($_POST['room_id']) || !isset($_POST['target_id']) || !isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "❌ بيانات غير صالحة.", "all_voted" => false], JSON_UNESCAPED_UNICODE);
    exit();
}

$roomId = intval($_POST['room_id']);
$targetId = intval($_POST['target_id']);
$voterId = $_SESSION['user_id'];

// Check if voting is actually active for the room
$votingStatus = $conn->query("SELECT voting_started FROM rooms WHERE id = $roomId")->fetch_assoc();
if (!$votingStatus || $votingStatus['voting_started'] == 0) {
    echo json_encode(["status" => "error", "message" => "❌ التصويت غير نشط حالياً.", "all_voted" => false], JSON_UNESCAPED_UNICODE);
    exit();
}

// Check to prevent self-voting
if ($voterId == $targetId) {
    echo json_encode(["status" => "error", "message" => "❌ لا يمكنك التصويت لنفسك.", "all_voted" => false], JSON_UNESCAPED_UNICODE);
    exit();
}

// Check if the user has already voted in this room
$alreadyVoted = $conn->query("SELECT 1 FROM votes WHERE room_id = $roomId AND voter_id = $voterId LIMIT 1")->num_rows > 0;
if ($alreadyVoted) {
    echo json_encode(["status" => "error", "message" => "⚠ لقد قمت بالتصويت بالفعل في هذه الجولة.", "all_voted" => false], JSON_UNESCAPED_UNICODE);
    exit();
}

// Record the vote
$stmt = $conn->prepare("INSERT INTO votes (room_id, voter_id, target_id) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $roomId, $voterId, $targetId);

if ($stmt->execute()) {
    // Get voter's username for the system message
    $voter_username_query = $conn->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
    $voter_username_query->bind_param("i", $voterId);
    $voter_username_query->execute();
    $voter_username = $voter_username_query->get_result()->fetch_assoc()['username'] ?? 'لاعب مجهول';
    $voter_username_query->close();

    // Insert system message: "X has voted."
    $vote_msg = "🗳 {$voter_username} قام بالتصويت.";
    $stmt_msg = $conn->prepare("INSERT INTO system_messages (room_id, message) VALUES (?, ?)");
    $stmt_msg->bind_param("is", $roomId, $vote_msg);
    $stmt_msg->execute();
    $stmt_msg->close();

    // After recording the vote, check if all non-observer players have voted
    $totalVotersQuery = $conn->query("
        SELECT COUNT(rp.user_id) AS count
        FROM room_players rp
        JOIN player_roles pr ON pr.user_id = rp.user_id AND pr.room_id = rp.room_id
        WHERE rp.room_id = $roomId AND pr.role != 'المراقب'
    ");
    $totalVoters = $totalVotersQuery->fetch_assoc()['count'] ?? 0;

    $totalVotesQuery = $conn->query("SELECT COUNT(*) as count FROM votes WHERE room_id = $roomId");
    $totalVotes = $totalVotesQuery->fetch_assoc()['count'] ?? 0;

    $all_voted = ($totalVotes >= $totalVoters && $totalVoters > 0);

    echo json_encode(["status" => "success", "message" => "✅ تم إرسال صوتك بنجاح." . ($all_voted ? " سيتم إعلان النتائج الآن." : " انتظر باقي اللاعبين."), "all_voted" => $all_voted], JSON_UNESCAPED_UNICODE);

} else {
    echo json_encode(["status" => "error", "message" => "❌ حدث خطأ أثناء إرسال صوتك: " . $stmt->error, "all_voted" => false], JSON_UNESCAPED_UNICODE);
}
?>
