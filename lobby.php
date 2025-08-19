<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/php/_safe_wrappers.php';
session_start();

// التحقق من وجود جلسة للمستخدم
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="img/logo.png" type="image/png" />
    <title>اللوبي - العميل والقاتل</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/lobby.js" defer></script>

    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100vh;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            font-family: 'Cairo', sans-serif;
            color: white;
        }

        /* البوب أب */
        .friends-popup {
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
            border: 1px solid rgba(0, 245, 255, 0.3);
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

        /* مربع البحث */
        #friendSearch {
            width: 100%;
            padding: 8px 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            border: none;
            outline: none;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        /* تغيير لون Placeholder */
        #friendSearch::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        /* قائمة الأصدقاء */
        #friendsList {
            max-height: 330px;
            overflow-y: auto;
        }

        .add-friend-btn {
            background: #00d9e8;
            color: white;
            border: none;
            padding: 5px 8px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s ease;
        }
        .add-friend-btn:hover {
            background: #00b5c4;
        }

.profile-link {
    position: fixed;
    font-size: 1.5rem;
    color: white;

    text-decoration: none;
}


.profile-link.friends {
    top: 0px;
    right: -170px; /* الأصدقاء في اليمين */
}

.notif-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: red;
    color: white;
    font-size: 12px;
    padding: 0px 8px;
    border-radius: 50%;
}

.friends-popup {
    display: none;
    position: fixed;
    top: 60px;
    right: 15px;
    width: 320px;
    background: rgba(17, 25, 40, 0.86);
    color: white;
    border-radius: 10px;
    padding: 15px;
    z-index: 1000;
}


    </style>
</head>
<body>
<div class="container">

<!-- رابط الملف الشخصي + أيقونة الأصدقاء -->
        <a href="./php/profile.php" class="profile-link" aria-label="الملف الشخصي" style="position: relative; display: inline-block;">
            👤
            <span id="notifCountLobby" style="
                position: absolute;
                top: -8px;
                right: -8px;
                background: red;
                color: white;
                font-size: 12px;
                padding: 0px 8px;
                border-radius: 50%;
                display: none;
            ">0</span>
        </a>
        <div id="friendsPopup" class="friends-popup">
            <input type="text" id="friendSearch" placeholder="ابحث عن صديق..." />
            <div id="friendsList"></div>
        </div>
        <!-- أيقونة الأصدقاء -->
        <a id="friendsToggleBtn" class="profile-link friends" href="javascript:void(0)" style="display:inline-block; position:relative;">
            👥
        </a>


    <h2>مرحبًا يا <?php echo htmlspecialchars($_SESSION["username"], ENT_QUOTES, 'UTF-8'); ?> 👋</h2>

    <!-- نموذج إنشاء غرفة جديدة -->
    <h3>إنشاء غرفة جديدة</h3>
    <form id="createRoomForm">
        <input type="text" id="roomName" name="roomName" placeholder="اسم الغرفة" required>
        <input type="number" id="maxPlayers" name="maxPlayers" placeholder="عدد اللاعبين (5-10)" min="5" max="10" required>
        <div class="custom-select-wrapper">
            <div class="custom-select-trigger">عدد المتورطين: 1</div>
            <div class="custom-options">
                <span class="custom-option selected" data-value="1">1</span>
                <span class="custom-option" data-value="2">2</span>
            </div>
            <input type="hidden" id="conspirators" name="conspirators" value="1">
        </div>
        <button type="submit">إنشاء</button>
    </form>

    <hr>

    <!-- قائمة الغرف المتاحة -->
    <h3>الغرف المتاحة</h3>
    <div id="roomsList" aria-live="polite" aria-atomic="true">
        ⏳ جاري تحميل الغرف...
    </div>

    <!-- رابط تسجيل الخروج -->
    <a href="logout.php" class="logout-link" aria-label="تسجيل الخروج">🚪 تسجيل الخروج</a>
</div>

<!-- سكريبت الأصدقاء -->
<script src="./js/friend_requests_notif.js"></script>

<script>
let allFriends = []; // نخزن فيها الأصدقاء

document.addEventListener('DOMContentLoaded', () => {
  const popup = document.getElementById('friendsPopup');
  const toggleBtn = document.getElementById('friendsToggleBtn');

  if (!popup || !toggleBtn) {
    console.error('Popup or toggle button not found!');
    return;
  }

  // فتح أو إغلاق الـ popup
  function toggleFriendsPopup() {
    if (popup.style.display === 'block') {
      popup.style.display = 'none';
      document.removeEventListener('click', outsideClickListener);
    } else {
      popup.style.display = 'block';
      loadFriends();
      document.addEventListener('click', outsideClickListener);
    }
  }

  // استماع لزر الفتح
  toggleBtn.addEventListener('click', e => {
    e.stopPropagation();
    toggleFriendsPopup();
  });

  // منع إغلاق النافذة عند الضغط داخلها
  popup.addEventListener('click', e => e.stopPropagation());

  // إغلاق النافذة عند الضغط خارجها
  function outsideClickListener(event) {
    if (!popup.contains(event.target) && !toggleBtn.contains(event.target)) {
      popup.style.display = 'none';
      document.removeEventListener('click', outsideClickListener);
    }
  }

  // تحميل الأصدقاء
  function loadFriends() {
    const container = document.getElementById("friendsList");
    container.innerHTML = "⏳ جاري تحميل الأصدقاء...";

    fetch('php/get_friends.php')
      .then(res => res.json())
      .then(friends => {
        allFriends = friends; // تخزين الأصدقاء لتحسين البحث المحلي
        if (friends.length === 0) {
          container.innerHTML = "<p>لا يوجد أصدقاء بعد</p>";
          return;
        }
        displayFriends(friends);
      })
      .catch(() => {
        container.innerHTML = "<p>حدث خطأ أثناء تحميل الأصدقاء.</p>";
      });
  }

function displayFriends(friends) {
    const container = document.getElementById("friendsList");
    container.innerHTML = "";
    friends.forEach(friend => {
        const div = document.createElement('div');
        div.className = 'friend-item';
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.justifyContent = 'space-between'; 
        div.style.marginBottom = '10px';

        // التحقق من الحالة
        const isFriend = friend.is_friend || allFriends.some(f => f.id == friend.id);
        const requestStatus = friend.request_status;

        let buttonHtml = "";
        if (!isFriend) {
            if (requestStatus === "pending") {
                buttonHtml = `<span class="req-status" style="color:#00d9e8;font-size:14px;">تم الإرسال</span>`;
            } else {
                buttonHtml = `<button class="add-friend-btn" data-id="${friend.id}">➕</button>`;
            }
        } else {
            buttonHtml = `<span class="req-status" style="color:#00d9e8;font-size:14px;">صديق</span>`;
        }

        div.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px; cursor: pointer;" class="friend-info">
                <img src="php/avatars/${friend.avatar || 'default.png'}" 
                    alt="${friend.username}"
                    style="width:40px; height:40px; border-radius:50%; object-fit: cover; border: 2px solid #333;">
                <span class="friend-name" style="font-weight: 600; color: ${isFriend ? '#00d9e8' : 'white'};">
                    ${friend.username}
                </span>
                <span class="friend-id" style="margin-left: 5px;">ID: ${friend.id}</span>
            </div>
            ${buttonHtml}
        `;

        div.querySelector(".friend-info").onclick = () => {
            window.location.href = `php/profile.php?id=${friend.id}`;
        };

        const addBtn = div.querySelector(".add-friend-btn");
        if (addBtn) {
            addBtn.onclick = (e) => {
                e.stopPropagation();
                sendFriendRequest(friend.id);
            };
        }

        container.appendChild(div);
    });
}

function sendFriendRequest(friendId) {
    fetch("php/send_friend_request.php", {
        method: "POST",
        headers: { 
            "Content-Type": "application/x-www-form-urlencoded" 
        },
        body: "friend_id=" + encodeURIComponent(friendId)
    })
    .then(res => res.text())
    .then(response => {
        console.log("Server response:", response);
        alert(response.trim());

        // ✅ نخفي الزر ونظهر "تم الإرسال"
        const button = document.querySelector(`.add-friend-btn[data-id="${friendId}"]`);
        if (button) {
            const status = document.createElement('span');
            status.style.color = '#00d9e8';
            status.style.fontSize = '14px';
            status.textContent = "تم الإرسال";
            button.parentElement.replaceChild(status, button);
        }

        // ✅ نحدث بيانات allFriends لو موجود
        const friend = allFriends.find(f => f.id == friendId);
        if (friend) {
            friend.request_status = "pending";
        }
    })
    .catch(err => {
        console.error("Fetch error:", err);
        alert("❌ حدث خطأ في الاتصال بالسيرفر.");
    });
}

  // البحث داخل القائمة المحملة محليًا
  const friendSearchInput = document.getElementById("friendSearch");
  friendSearchInput.addEventListener("input", function() {
    const query = this.value.trim();

    if (query === "") {
      displayFriends(allFriends);
    } else {
      searchUsers(query);
    }
  });

  // البحث في السيرفر
function searchUsers(query) {
    fetch('php/search_friends.php?q=' + encodeURIComponent(query))
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP Error: ${res.status}`);
            }
            return res.json().catch(err => {
                throw new Error('Invalid JSON: ' + err.message);
            });
        })
        .then(users => {
            if (!Array.isArray(users)) {
                throw new Error('Response is not an array');
            }
            if (users.length === 0) {
                document.getElementById("friendsList").innerHTML = "<p>🚫 لا يوجد نتائج</p>";
                return;
            }
            displayFriends(users);
        })
        .catch((err) => {
            console.error("Search Error:", err);
            document.getElementById("friendsList").innerHTML = `<p style="color: red;">❌ خطأ: ${err.message}</p>`;
        });
}

  // تحميل الأصدقاء أول ما تحمل الصفحة
  loadFriends();
});
</script>



<script src="js/ws-client.js"></script>

</body>
</html>
