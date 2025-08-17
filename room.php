<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/php/_safe_wrappers.php';
session_start();
require 'php/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['room_id'])) {
    header("Location: lobby.php");
    exit();
}

$roomId = intval($_GET['room_id']);

// تحقق إذا اللاعب موجود في الغرفة
$check = $conn->prepare("SELECT * FROM room_players WHERE room_id = ? AND user_id = ?");
$check->bind_param("ii", $roomId, $_SESSION['user_id']);
$check->execute();
if ($check->get_result()->num_rows == 0) {
    header("Location: lobby.php");
    exit();
}

// تحقق إذا اللاعب هو صاحب الغرفة
$isHost = false;
$res = $conn->query("SELECT host_id, game_started FROM rooms WHERE id = $roomId");
if ($res->num_rows > 0) {
    $room = $res->fetch_assoc();
    $isHost = ($room['host_id'] == $_SESSION['user_id']);
    if ($room['game_started'] == 1) {
        header("Location: game.php?room_id=$roomId");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>انتظار اللاعبين</title>
        <link rel="icon" href="img/logo.png" type="image/png" />

    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="js/room.js" defer></script>
</head>

<body>
    <div class="container" style="margin-bottom: 80px;">
        <h2>🕹 انتظار اللاعبين في الغرفة</h2>
        <div id="playersList">جاري تحميل اللاعبين...</div>

        <?php if ($isHost): ?>
            <button id="startBtn">🚀 ابدأ اللعبة</button>
        <?php endif; ?>

        <button id="leaveRoomButton">🚪 مغادرة الغرفة</button>
    </div>

    <script>
        const roomId = <?= $roomId ?>;
        setInterval(() => {
            // تأكد من وضع المسار بين علامتي اقتباس مفردتين أو مزدوجتين
            fetch('php/get_room_status.php?room_id=' + roomId) // تم التعديل هنا
                .then(res => res.json())
                .then(data => {
                    if (data.status === "started") {
                        // تأكد من وضع المسار بين علامتي اقتباس مفردتين أو مزدوجتين
                        window.location.href = 'game.php?room_id=' + roomId; // تم التعديل هنا
                    }
                })
                .catch(err => console.error(err));
        }, 2000);

    </script>
    <script src="js/ws-client.js"></script>

</body>

</html>