// ... بقية الكود الذي يستخدم evtSource
document.addEventListener("DOMContentLoaded", async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const roomId = urlParams.get('room_id');

    if (!roomId) {
        console.error("Room ID not found in URL.");
        window.location.href = "lobby.php";
        return;
    }

    // === مكان تعريف evtSource ===
    // هنا تقوم بتعريف evtSource بعد التأكد من وجود roomId
    // المسار لملف SSE (Server-Sent Events) الخاص بالغرف
    // يُفضل أن يكون لديك ملف SSE مخصص للغرف يركز على الأحداث الخاصة بها
    // مثال: `sse_room.php`
    const evtSource = new EventSource(`sse_room.php?room_id=${roomId}`);

    // === SSE Event Listeners ===
    // انقل معالجات الأحداث الخاصة بـ SSE هنا، بعد تعريف evtSource
    evtSource.addEventListener('chat_message', function(e) {
        const msg = JSON.parse(e.data);
        if (msg && typeof window.appendChatMessage === 'function') {
            window.appendChatMessage(msg);
        }
    });

    // يمكنك إضافة المزيد من معالجات أحداث SSE هنا، مثل تحديث اللاعبين أو حالة الغرفة
    evtSource.addEventListener('players_updated', function(e) {
        console.log('تحديث اللاعبين عبر SSE:', e.data);
        loadPlayers(); // استدعاء دالة تحديث اللاعبين
    });

    evtSource.addEventListener('game_status_updated', function(e) {
        console.log('تحديث حالة اللعبة عبر SSE:', e.data);
        const data = JSON.parse(e.data);
        if (data.status === 'started') {
            window.location.href = `game.php?room_id=${roomId}`;
        }
    });

    evtSource.onerror = function(err) {
        console.error('EventSource failed in room.js:', err);
        evtSource.close();
        // يمكنك إضافة منطق لإعادة الاتصال أو إظهار رسالة خطأ للمستخدم
    };
    // === نهاية معالجات أحداث SSE ===


    const playersList = document.getElementById("playersList");
    const messageDisplay = document.getElementById("messageDisplay");

    // تحديث حالة الاتصال
    const updatePresence = async () => {
        const formData = new FormData();
        formData.append("room_id", roomId);
        try {
            // تأكد أن المسار صحيح ويتم التعامل مع الأخطاء في update_online_status.php
            await fetch("php/update_online_status.php", {
                method: "POST",
                body: formData
            });
        } catch (error) {
            console.error("Error updating presence:", error);
        }
    };

    await updatePresence();

    function displayMessage(message, type = 'info') {
        if (messageDisplay) {
            messageDisplay.textContent = message;
            messageDisplay.className = `p-2 my-2 rounded-md text-center ${type === 'error' ? 'bg-red-200 text-red-800' : 'bg-blue-200 text-blue-800'}`;
            messageDisplay.style.display = 'block';
            setTimeout(() => {
                messageDisplay.style.display = 'none';
            }, 5000);
        } else {
            console.log(message);
        }
    }

    function loadPlayers() {
        // تأكد من أن php/get_room_players.php يرجع JSON نظيفاً
        fetch(`php/get_room_players.php?room_id=${roomId}`)
            .then(res => res.json())
            .then(players => {
                if (playersList) {
                    playersList.innerHTML = "";
                    if (players.length === 0) {
                        playersList.textContent = "لا يوجد لاعبون حالياً في الغرفة.";
                    } else {
                        players.forEach(p => {
                            let div = document.createElement("div");
                            div.className = "p-2 my-1 bg-gray-100 rounded-md";
                            div.textContent = p.username;
                            playersList.appendChild(div);
                        });
                    }
                }
            })
            .catch(error => {
                console.error("Error loading players:", error);
                displayMessage("⚠️ خطأ في تحميل اللاعبين.", "error");
            });
    }

    // بدء تحديث قائمة اللاعبين وحالة اللعبة
    loadPlayers(); // الآن هذه الدالة ستعمل بعد تحديث التواجد

    // فحص إذا بدأت اللعبة (هذا الجزء يمكن استبداله بحدث SSE: game_status_updated)
    function checkGameStart() {
        fetch(`php/get_room_status.php?room_id=${roomId}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'started') {
                    window.location.href = "game.php?room_id=" + roomId;
                }
            })
            .catch(error => {
                console.error("Error checking game status:", error);
            });
    }

    // معالج زر بدء اللعبة
    const startBtn = document.getElementById("startBtn");
    if (startBtn) {
        startBtn.addEventListener("click", () => {
            fetch("php/start_game.php", {
                method: "POST",
                body: new URLSearchParams({ room_id: roomId })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        displayMessage(data.message || "❌ حدث خطأ في بدء اللعبة", "error");
                    }
                })
                .catch(error => {
                    console.error("Error starting game:", error);
                    displayMessage("⚠ حدث خطأ في الاتصال بالسيرفر لبدء اللعبة.", "error");
                });
        });
    }

    // مغادرة الغرفة بالزر
    const leaveRoomButton = document.getElementById('leaveRoomButton');
    if (leaveRoomButton) {
        leaveRoomButton.addEventListener('click', () => {
            fetch("leave_room.php", {
                method: "POST",
                body: new URLSearchParams({
                    room_id: roomId
                })
            })
                .then(res => res.json())
                .then(data => {
                    console.log("Leave response:", data);
                    // clearInterval(playersInterval); // تأكد من إزالة هذه الـ clear إذا لم تعد تستخدم setInterval
                    // clearInterval(statusInterval); // تأكد من إزالة هذه الـ clear إذا لم تعد تستخدم setInterval
                    window.location.href = "lobby.php";
                })
                .catch(error => {
                    console.error("Error leaving room via button:", error);
                    displayMessage("⚠️ حدث خطأ أثناء محاولة مغادرة الغرفة.", "error");
                });
        });
    }

    // مغادرة الغرفة عند إغلاق الصفحة أو تحديثها
    window.addEventListener("beforeunload", () => {
        // إذا كنت تستخدم setIntervals، تأكد من إزالتها
        // clearInterval(playersInterval);
        // clearInterval(statusInterval);
        const params = new URLSearchParams();
        params.append("room_id", roomId);
        navigator.sendBeacon("leave_room.php", params);
        // إغلاق اتصال SSE عند مغادرة الصفحة
        if (evtSource) {
            evtSource.close();
        }
    });

});

// هذه الدوال تم نقلها لداخل DOMContentLoaded أو تم تبسيط استدعائها
// window.loadPlayers = (typeof loadPlayers !== 'undefined') ? loadPlayers : window.loadPlayers;
// window.checkGameStart = (typeof checkGameStart !== 'undefined') ? checkGameStart : window.checkGameStart;

