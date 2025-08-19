<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Ensure _safe_wrappers.php is correctly included. Adjust path if necessary.
// If it's in the same directory as sse.php (i.e., htdocs/php/), then this path is correct:
require_once __DIR__ . '/php/_safe_wrappers.php';// If _safe_wrappers.php is in the parent directory (htdocs/), you'd use:
// require_once __DIR__ . '/../_safe_wrappers.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

set_time_limit(0);
ignore_user_abort(true);

// Ensure db.php is correctly included. If it's a sibling in the 'php' folder:
require_once __DIR__ . '/php/db.php';// If db.php is in the main htdocs/ folder, you'd use:
// require_once __DIR__ . '/../db.php';


$last_friend_count = null;
$last_room_id = null;
$last_chat_id = null;

while (true) {
    // Friend requests
    $res = $conn->query("SELECT COUNT(*) as cnt FROM friend_requests WHERE status = 'pending'");
    if ($res && $row = $res->fetch_assoc()) {
        $count = (int)$row['cnt'];
        if ($last_friend_count !== $count) {
            $last_friend_count = $count;
            echo "event: friend_requests_updated\n";
            echo 'data: ' . json_encode(['count' => $count]) . "\n\n";
            // Check if sse_stats.php exists and its path is correct before using file_get_contents
            // file_get_contents("sse_stats.php?size=" . strlen(json_encode(['count' => $count])));
        }
    }

    // Rooms
    $res = $conn->query("SELECT id, room_name FROM rooms ORDER BY id DESC LIMIT 1"); // Assuming 'name' is 'room_name' in your DB
    if ($res && $row = $res->fetch_assoc()) {
        if ($last_room_id !== $row['id']) {
            $last_room_id = $row['id'];
            $rooms = [];
            $res2 = $conn->query("SELECT id, room_name FROM rooms ORDER BY id DESC LIMIT 10"); // Assuming 'name' is 'room_name'
            while ($res2 && $r = $res2->fetch_assoc()) {
                $rooms[] = $r;
            }
            echo "event: rooms_updated\n"; // Corrected 'صدى' to 'echo' and removed extra spaces
            echo "data:" . json_encode ($rooms) . "\n\n"; // Corrected 'صدى' to 'echo' and removed extra spaces
            // Check if sse_stats.php exists and its path is correct before using file_get_contents
            // file_get_contents("sse_stats.php?size=" . strlen(json_encode($rooms)));
        }
    }

    // Chat (ثرثرة - assuming this is for chat messages)
    $res = $conn->query("SELECT id, message, user_id, room_id, created_at FROM chat ORDER BY id DESC LIMIT 1"); // Corrected 'تحديد معرف ، رسالة ...'
    if ($res && $row = $res->fetch_assoc()) {
        if ($last_chat_id !== $row['id']) {
            $last_chat_id = $row['id'];
            echo "event: chat_message\n"; // Corrected 'صدى' to 'echo'
            echo "data:" . json_encode ($row) . "\n\n"; // Corrected 'صدى' to 'echo'
            // Check if sse_stats.php exists and its path is correct before using file_get_contents
            // file_get_contents("sse_stats.php?size=" . strlen(json_encode($row)));
        }
    }

    @ob_flush();
    @flush();

    // Ping event to keep connection alive
    $last_ping = isset($last_ping) ? $last_ping : 0; // Initialize if not set
    if (time() - $last_ping >= 30) {
        echo "event: ping\n";
        echo "data: {}\n\n";
        $last_ping = time();
        @ob_flush();
        @flush();
    }

    sleep(1); // Wait for 1 second before checking again
}
?>
