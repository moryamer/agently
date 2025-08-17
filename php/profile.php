<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php';
header("Access-Control-Allow-Origin: *");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userId = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];
$canEdit = ($userId === $_SESSION['user_id']);

// هنجلب الـ id + باقي البيانات
$stmt = $conn->prepare("SELECT id, username, avatar, points, games_played, games_won, games_lost FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($id, $username, $avatar, $points, $gamesPlayed, $gamesWon, $gamesLost);
$stmt->fetch();
$stmt->close();

// حساب المستوى
$level = 1;
$remainingPoints = $points;

for ($i = 1; $i < 5; $i++) {
    $pointsNeededForNextLevel = 70;
    if ($remainingPoints >= $pointsNeededForNextLevel) {
        $level++;
        $remainingPoints -= $pointsNeededForNextLevel;
    } else break;
}

if ($level >= 5) {
    for ($i = 5; $i < 10; $i++) {
        $pointsNeededForNextLevel = 90;
        if ($remainingPoints >= $pointsNeededForNextLevel) {
            $level++;
            $remainingPoints -= $pointsNeededForNextLevel;
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

// استقبال رسالة إن وجدت
$message = '';
if (isset($_GET['message'])) {
    $message = htmlspecialchars(urldecode($_GET['message']));
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
/* نافذة الإشعارات */
.notification-popup {
    display: none;
    position: fixed;
    top: 60px;
    right: 20px;
    width: 320px;
    max-height: 400px;
    overflow-y: auto;

    background: rgba(17, 25, 40, 0.55); /* خلفية شفافة */
    backdrop-filter: blur(12px); /* البلور */
    -webkit-backdrop-filter: blur(12px); /* دعم سفاري */

    box-shadow: 0 0 20px rgba(0, 245, 255, 0.4);
    border-radius: 15px;
    padding: 15px;
    z-index: 9999;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    animation: popupFade 0.3s ease;
}

/* أنيميشن الظهور */
@keyframes popupFade {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* الطلبات داخل النافذة */
#requestsContainer {
    max-height: 330px;
    overflow-y: auto;
}

/* زر قبول/رفض */
.request-btn {
    background: #00d9e8;
    color: white;
    border: none;
    padding: 5px 8px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    margin-right: 5px;
    transition: background 0.2s ease;
}
.request-btn:hover {
    background: #00b5c4;
}
#notificationPopup {
    display: none;
    position: fixed;
    top: 110px;
    right: 65px;
    width: 280px;
    max-height: 400px;
    overflow-y: auto;

    background: rgba(17, 25, 40, 0.55);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);

    box-shadow: 0 0 20px rgba(0, 245, 255, 0.4);
    border-radius: 15px;
    padding: 15px;
    z-index: 9999;
    animation: popupFade 0.3s ease;
}
.accept-btn {
    background: rgba(0, 200, 83, 0.8); /* أخضر */
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    margin-right: 5px;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    transition: all 0.2s ease;
}
.accept-btn:hover {
    background: rgba(0, 150, 60, 0.9);
    transform: scale(1.05);
}

.reject-btn {
    background: rgba(229, 57, 53, 0.8); /* أحمر */
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    transition: all 0.2s ease;
}
.reject-btn:hover {
    background: rgba(200, 40, 40, 0.9);
    transform: scale(1.05);
}
.request-item {
    display: flex;
    align-items: center;
    gap: 10px; /* مسافة بين العناصر */
    padding: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.request-item img {
    width: 50px; /* أكبر من 40 */
    height: 50px;
    border-radius: 50%;
    object-fit: cover; /* يخلي الصورة متناسقة */
}

.request-item span {
    font-size: 20px; /* تكبير الاسم */
    font-weight: 500;
    color: white;
    flex: 1; /* ياخد المساحة المتبقية */
}

    </style>
</head>
<body>

    <?php if (!empty($message)) : ?>
        <div id="alertMessage" style="
            background-color: <?php echo (strpos($message, '❌') !== false) ? '#f8d7da' : '#d4edda'; ?>;
            color: <?php echo (strpos($message, '❌') !== false) ? '#721c24' : '#155724'; ?>;
            background-color: #f8d7da;
            color: #721c24;
            z-index: 9999;
            position: fixed;
            top: 200px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 20px;
            border-radius: 10px;
            width: 75%;
            max-width: 342px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            font-weight: 600;
            cursor: pointer;
        ">
            <?php echo $message; ?>
        </div>

        <script>
            // لما المستخدم يضغط في أي مكان تختفي الرسالة
            document.addEventListener("click", function () {
                let alertBox = document.getElementById("alertMessage");
                if (alertBox) {
                    alertBox.style.display = "none";
                }
            });
        </script>
    <?php endif; ?>


    <div class="profile-card" role="main" aria-label="الملف الشخصي للمستخدم">
        <div class="display-id-badge" aria-label="معرف المستخدم">
            ID: <?php echo htmlspecialchars($id); ?>
        </div>
        <?php if ($canEdit): ?>
            <!-- أيقونة الجرس مع عداد الإشعارات -->
            <div class="notification-bell" style="position: relative; display: inline-block;">
                <svg onclick="toggleNotifications()" style="cursor: pointer;" xmlns="http://www.w3.org/2000/svg" height="28" viewBox="0 0 24 24" width="28" fill="#00d9e8">
                    <path d="M12 24c1.3 0 2.4-1 2.5-2.3h-5C9.6 23 10.7 24 12 24zM18.3 16V11c0-3.1-1.6-5.6-4.7-6.3V4c0-.8-.7-1.5-1.5-1.5S10.5 3.2 10.5 4v.7C7.4 5.4 5.8 7.9 5.8 11v5L4 18v1h16v-1l-1.7-2z"/>
                </svg>
                <span id="notifCount" style="
                    scale: 0.8;
                    position: absolute;
                    top: -5px;
                    right: -5px;
                    background: red;
                    color: white;
                    font-size: 12px;
                    padding: 0px 8px;
                    border-radius: 50%;
                    display: none;
                ">0</span>
            </div>

            <!-- نافذة الإشعارات -->
            <div id="notificationPopup" class="notification-popup" style="display: none;">
                <h4 style="margin: 0 0 10px; color: #00d9e8; font-weight: 600; font-size: 18px;">
                    طلبات الصداقة
                </h4>
                <div id="requestsContainer"></div>
            </div>
        <?php endif; ?>

        <?php if ($canEdit): ?>
            <button class="edit-icon-btn" title="تعديل الملف الشخصي" aria-label="تعديل الملف الشخصي"
                onclick="window.location.href='edit_profile.php'">
                ✏️
            </button>
        <?php endif; ?>

        <img src="avatars/<?php echo htmlspecialchars($avatar); ?>" alt="صورة المستخدم" class="avatar" />

        <div class="title-badge <?php echo $titleColorClass; ?> <?php echo $titleTextColorClass; ?>">
            <?php echo htmlspecialchars($title); ?>
        </div>

        <h2 class="username" tabindex="0"><?php echo htmlspecialchars($username); ?></h2>

        <div class="stats" role="list">
            <div class="stat-item" role="listitem">
                <span>المستوى:</span>
                <span><?php echo htmlspecialchars($level); ?></span>
            </div>
            <div class="stat-item" role="listitem">
                <span>النقاط:</span>
                <span><?php echo htmlspecialchars($points); ?> 🌟</span>
            </div>
            <div class="stat-item" role="listitem">
                <span>الألعاب التي لعبتها:</span>
                <span><?php echo htmlspecialchars($gamesPlayed); ?></span>
            </div>
            <div class="stat-item" role="listitem">
                <span>الألعاب التي فزت بها:</span>
                <span><?php echo htmlspecialchars($gamesWon); ?> ✅</span>
            </div>
            <div class="stat-item" role="listitem">
                <span>الألعاب التي خسرتها:</span>
                <span><?php echo htmlspecialchars($gamesLost); ?> ❌</span>
            </div>
        </div>
        <a href="../lobby.php" class="back-link" role="link" aria-label="العودة إلى اللوبي">العودة إلى اللوبي</a>
    </div>
<script src="../js/friend_requests_notif.js"></script>

<script>
function sendFriendRequest(friendId) {
    fetch('send_friend_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'friend_id=' + encodeURIComponent(friendId)
    })
    .then(res => res.text())
    .then(msg => alert(msg))
    .catch(err => alert("⚠️ حدث خطأ أثناء إرسال الطلب."));
}

function toggleNotifications() {
    const popup = document.getElementById("notificationPopup");
    popup.style.display = popup.style.display === "block" ? "none" : "block";
    if (popup.style.display === "block") {
        loadRequests();
    }
}
// إغلاق عند الضغط في أي مكان خارج البوب أب
document.addEventListener("click", function (event) {
    const popup = document.getElementById("notificationPopup");
    const bell = document.querySelector(".notification-bell") || document.querySelector("svg[onclick='toggleNotifications()']");
    
    if (popup && popup.style.display === "block") {
        if (!popup.contains(event.target) && !bell.contains(event.target)) {
            popup.style.display = "none";
        }
    }
});

function loadFriends() {
    const container = document.getElementById("friendsContainer");
    if (!container) return;

    container.innerHTML = "⏳ جاري تحميل الأصدقاء...";

    fetch('get_friends.php')
        .then(res => res.json())
        .then(friends => {
            if (friends.length === 0) {
                container.innerHTML = "<p>لا يوجد أصدقاء حاليا</p>";
                return;
            }

            container.innerHTML = "";
            friends.forEach(friend => {
                const div = document.createElement('div');
                div.className = 'friend-item';
                div.textContent = `${friend.username} (ID: ${friend.id})`;
                container.appendChild(div);
            });
        })
        .catch(() => {
            container.innerHTML = "<p>⚠️ حدث خطأ أثناء تحميل الأصدقاء.</p>";
        });
}

// تحميل الطلبات أول ما الصفحة تفتح
document.addEventListener("DOMContentLoaded", () => {
    loadRequests();
});


function loadRequests() {
    const container = document.getElementById("requestsContainer");
    const notifCountBell = document.getElementById("notifCount");
    const notifCountProfile = document.getElementById("notifCountProfile");

    fetch('get_friend_requests.php')
        .then(res => res.json())
        .then(requests => {
            const count = requests.length;

            // تحديث العدادات
            if (notifCountBell) {
                notifCountBell.textContent = count;
                notifCountBell.style.display = count > 0 ? 'inline-block' : 'none';
            }
            if (notifCountProfile) {
                notifCountProfile.textContent = count;
                notifCountProfile.style.display = count > 0 ? 'inline-block' : 'none';
            }

            if (!container) return;

            if (count === 0) {
                container.innerHTML = "<p>لا توجد طلبات صداقة</p>";
                return;
            }

            container.innerHTML = "";
            requests.forEach(req => {
                const div = document.createElement('div');
                div.className = 'request-item';
                div.setAttribute('data-request-id', req.request_id);

                div.innerHTML = `
                    <a href="view_profile.php?id=${req.sender_id}" 
                    style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px; padding:8px; border-radius:6px;">
                        <img src="avatars/${req.avatar || 'default.png'}" width="50" style="margin-right: 4px; border-radius: 50%; scale: 1.2;">
                        <div style="display:flex; flex-direction:column; justify-content:center;">
                            <strong style="margin-bottom:8px;  font-size: 25px;">${req.username}</strong>
                            <div>
                                <button class="accept-btn" onclick="event.preventDefault(); respondRequest(${req.request_id}, 'accept')">✅ قبول</button>
                                <button class="reject-btn" onclick="event.preventDefault(); respondRequest(${req.request_id}, 'reject')">❌ رفض</button>
                            </div>
                        </div>
                    </a>
                `;


                container.appendChild(div);
            });
        })
        .catch(() => {
            if (container) {
                container.innerHTML = "<p>⚠️ خطأ في تحميل الطلبات</p>";
            }
        });
}




function respondRequest(requestId, accept) {
    fetch("respond_friend_request.php", {
        method: "POST",
        headers: { 
            "Content-Type": "application/json" 
        },
        body: JSON.stringify({
            request_id: requestId,
            accept: accept
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message); // هتظهر رسالة واضحة

        // ✅ بعد النجاح: احذف العنصر من الواجهة
        const requestElement = document.querySelector(`[data-request-id="${requestId}"]`);
        if (requestElement) {
            requestElement.remove();
        }

        // لو قبلت، حدّث قائمة الأصدقاء
        if (data.success && accept) {
            loadFriends();
        }

        // لو ما فيش طلبات تاني، اظهر رسالة
        const container = document.getElementById("requestsContainer");
        if (container && container.children.length === 0) {
            container.innerHTML = "<p>لا توجد طلبات صداقة</p>";
        }
    })
    .catch(err => {
        alert("⚠️ خطأ في معالجة الطلب");
    });
}

// تشغيل تحميل الأصدقاء عند فتح الصفحة
document.addEventListener('DOMContentLoaded', loadFriends);
</script>
<script src="js/ws-client.js"></script>



</body>
</html>
