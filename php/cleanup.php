<?php
require 'db.php';

// شوف لو مفيش أي روم شغالة
$checkRooms = $conn->query("SELECT COUNT(*) AS total FROM rooms");
$row = $checkRooms->fetch_assoc();

if ($row['total'] == 0) {
    // احذف البيانات اللي ملهاش لازمة
    $tablesToClear = [
        'votes',
        'system_messages',
        'room_players',
        'player_roles',
        'missions'
    ];

    foreach ($tablesToClear as $table) {
        $conn->query("TRUNCATE TABLE `$table`");
    }

    echo "✅ تم مسح البيانات بنجاح.\n";
} else {
    echo "ℹ️ يوجد غرف حالياً، لن يتم المسح.\n";
}
