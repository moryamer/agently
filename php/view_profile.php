<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$current_user = $_SESSION['user_id'];
$viewId = intval($_GET['id'] ?? 0);
if ($viewId <= 0) {
    exit("❌ معرف المستخدم غير صالح.");
}

// جلب بيانات الشخص
$stmt = $conn->prepare("SELECT id, username, avatar, points, games_played, games_won, games_lost FROM users WHERE id = ?");
$stmt->bind_param("i", $viewId);
$stmt->execute();
$stmt->bind_result($id, $username, $avatar, $points, $gamesPlayed, $gamesWon, $gamesLost);
if (!$stmt->fetch()) {
    exit("❌ المستخدم غير موجود.");
}
$stmt->close();

// التحقق إذا كان الطلب مرسل مسبقًا
$isRequested = false;
$check = $conn->prepare("SELECT 1 FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
$check->bind_param("ii", $current_user, $viewId);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    $isRequested = true;
}
$check->close();

// حساب المستوى
$level = 1;
$remainingPoints = $points;
for ($i = 1; $i < 5; $i++) {
    $pointsNeeded = 70;
    if ($remainingPoints >= $pointsNeeded) {
        $level++;
        $remainingPoints -= $pointsNeeded;
    } else break;
}
if ($level >= 5) {
    for ($i = 5; $i < 10; $i++) {
        $pointsNeeded = 90;
        if ($remainingPoints >= $pointsNeeded) {
            $level++;
            $remainingPoints -= $pointsNeeded;
        } else break;
    }
}
if ($level >= 10) {
    while ($remainingPoints >= 100) {
        $level++;
        $remainingPoints -= 100;
    }
}

// تحديد اللقب
$title = '';
$titleColorClass = '';
$titleTextColorClass = 'text-white';
if ($level >= 1 && $level < 5) {
    $title = 'المواطن 🧍';
    $titleColorClass = 'bg-gray-600';
} elseif ($level >= 5 && $level < 10) {
    $title = 'الشجاع 🛡️';
    $titleColorClass = 'bg-yellow-500';
} elseif ($level >= 10 && $level < 20) {
    $title = 'المحارب ⚔️';
    $titleColorClass = 'bg-red-600';
} elseif ($level >= 20 && $level <= 500) {
    $title = 'القائد 👑';
    $titleColorClass = 'bg-red-700';
} else {
    $title = 'القائد ✨';
    $titleColorClass = 'bg-red-700';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="icon" href="../img/logo.png" type="image/png" />

    <title>الملف الشخصي</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            color: white;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .profile-card {
            background: rgba(0, 0, 0, 0.85);
            border-radius: 20px;
            padding: 30px 25px;
            width: 100%;
            max-width: 400px;
            box-shadow:
                0 0 20px rgba(0, 245, 255, 0.3),
                0 0 40px rgba(0, 245, 255, 0.2),
                inset 0 0 15px rgba(0, 245, 255, 0.1);
            text-align: center;
            position: relative;
        }

        .display-id-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #00f5ff;
            color: black;
            padding: 6px 14px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 0 8px #00f5ff;
            user-select: none;
        }

        .edit-icon-btn {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: rgba(107, 114, 128, 0.9);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease, transform 0.2s ease;
            user-select: none;
        }

        .edit-icon-btn:hover {
            background-color: rgba(75, 85, 99, 0.9);
            transform: scale(1.1);
        }

        .edit-icon-btn svg {
            fill: white;
            width: 22px;
            height: 22px;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid #00f5ff;
            object-fit: cover;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            margin-bottom: 15px;
            margin-left: auto;
            margin-right: auto;
            display: block;
        }

        .title-badge {
            background-color: #00f5ff;
            padding: 8px 25px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: black;
            user-select: none;
            margin-left: auto;
            margin-right: auto;
        }

        .bg-gray-600 {
            background-color: #4b5563 !important;
            color: white !important;
        }

        .bg-yellow-500 {
            background-color: #eab308 !important;
            color: black !important;
        }

        .bg-red-600 {
            background-color: #dc2626 !important;
            color: white !important;
        }

        .bg-purple-700 {
            background-color: #6b21a8 !important;
            color: white !important;
        }

        .bg-blue-500 {
            background-color: #3b82f6 !important;
            color: white !important;
        }

        h2.username {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #00f5ff;
            text-shadow: 0 0 8px #00f5ff;
            user-select: none;
        }

        .stats {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px 20px;
            border-radius: 15px;
            user-select: none;
            box-shadow:
                inset 0 1px 3px rgba(0, 0, 0, 0.2),
                0 0 8px rgba(0, 245, 255, 0.15);
            font-weight: 600;
            min-width: 140px;
            text-align: center;
            font-size: 1.1rem;
        }

        .stat-item span:first-child {
            display: block;
            margin-bottom: 5px;
            font-size: 0.95rem;
            color: #a0eaff;
        }

        .back-link {
            margin-top: 30px;
            display: inline-block;
            color: #00f5ff;
            font-weight: 700;
            text-decoration: none;
            font-size: 1.1rem;
            text-shadow: 0 0 6px #00f5ff;
            transition: color 0.3s ease;
            user-select: none;
        }

        .back-link:hover {
            color: #a0ffff;
        }

        /* Responsive */
        @media (max-width: 450px) {
            .profile-card {
                padding: 25px 20px;
                max-width: 100%;
            }

            h2.username {
                font-size: 1.7rem;
            }

            .stats {
                justify-content: center;
            }

            .stat-item {
                min-width: 100%;
                font-size: 1rem;
            }

            .title-badge {
                margin-top: 12px;
                padding: 6px 20px;
                font-size: 1rem;
            }

            .avatar {
                width: 100px;
                height: 100px;
                margin-bottom: 10px;
            }
        }

        .friend-btn {
            background-color: #4CAF50;
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
        }
        .friend-btn:hover {
            background-color: #45a049;
        }
        .friend-btn:disabled {
            background-color: #aaa;
            cursor: not-allowed;
        }
    </style>
</head>
<body>


<div class="profile-card">
    <div class="display-id-badge">ID: <?php echo htmlspecialchars($id); ?></div>

    <img src="avatars/<?php echo htmlspecialchars($avatar); ?>" class="avatar" />

    <div class="title-badge <?php echo $titleColorClass; ?> <?php echo $titleTextColorClass; ?>">
        <?php echo htmlspecialchars($title); ?>
    </div>

    <h2 class="username"><?php echo htmlspecialchars($username); ?></h2>

    <div class="stats">
        <div class="stat-item"><span>المستوى:</span><span><?php echo $level; ?></span></div>
        <div class="stat-item"><span>النقاط:</span><span><?php echo $points; ?> 🌟</span></div>
        <div class="stat-item"><span>الألعاب التي لعبتها:</span><span><?php echo $gamesPlayed; ?></span></div>
        <div class="stat-item"><span>الألعاب التي فزت بها:</span><span><?php echo $gamesWon; ?> ✅</span></div>
        <div class="stat-item"><span>الألعاب التي خسرتها:</span><span><?php echo $gamesLost; ?> ❌</span></div>
    </div>

    <a href="../lobby.php" class="back-link">العودة إلى اللوبي</a>
</div>

<script>
document.getElementById('friendBtn')?.addEventListener('click', function() {
    let btn = this;
    btn.disabled = true;
    btn.textContent = 'جارٍ الإرسال...';

    fetch('send_request.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'receiver_id=<?php echo $viewId; ?>'
    })
    .then(res => res.text())
    .then(msg => {
        btn.textContent = 'تم إرسال الطلب ✅';
    })
    .catch(err => {
        btn.disabled = false;
        btn.textContent = 'إرسال طلب صداقة';
        alert("⚠️ حدث خطأ أثناء الإرسال");
    });
});

// تحميل الطلبات
function loadFriendRequests() {
    fetch('get_friend_requests.php')
        .then(res => res.json())
        .then(data => {
            let count = data.length;
            let countElem = document.getElementById('notification-count');
            let listElem = document.getElementById('friend-requests-list');

            if (count > 0) {
                countElem.textContent = count;
                countElem.style.display = 'inline-block';
            } else {
                countElem.style.display = 'none';
            }

            if (count > 0) {
                listElem.innerHTML = '';
                data.forEach(req => {
                    let div = document.createElement('div');
                    div.style.marginBottom = '10px';
                    div.innerHTML = `
                        <strong>${req.username}</strong>
                        <div>
                            <button onclick="respondRequest(${req.request_id}, 'accept')">✅ قبول</button>
                            <button onclick="respondRequest(${req.request_id}, 'reject')">❌ رفض</button>
                        </div>
                    `;
                    listElem.appendChild(div);
                });
            } else {
                listElem.innerHTML = 'لا توجد طلبات';
            }
        });
}

function respondRequest(id, action) {
    let formData = new FormData();
    formData.append('request_id', id);
    formData.append('action', action);

    fetch('respond_friend_request.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadFriendRequests();
            }
        });
}

document.getElementById('notification-bell').addEventListener('click', () => {
    let popup = document.getElementById('friend-request-popup');
    popup.style.display = (popup.style.display === 'none') ? 'block' : 'none';
});

loadFriendRequests();
</script>
<script src="js/ws-client.js"></script>

</body>
</html>
