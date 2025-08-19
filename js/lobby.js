document.addEventListener("DOMContentLoaded", () => {
    const roomsList = document.getElementById("roomsList");
    const createForm = document.getElementById("createRoomForm");

    function showCustomAlert(message) {
        console.log("Custom Alert:", message);
        alert(message);
    }

    // === Firebase Integration ===
    const firebaseConfig = {
      apiKey: "YOUR_API_KEY",
      authDomain: "secret-agent-284a4.firebaseapp.com",
      databaseURL: "https://secret-agent-284a4-default-rtdb.firebaseio.com",
      projectId: "secret-agent-284a4",
      storageBucket: "secret-agent-284a4.appspot.com",
      messagingSenderId: "YOUR_SENDER_ID",
      appId: "YOUR_APP_ID"
    };

    const app = firebase.initializeApp(firebaseConfig);
    const db = firebase.database();

    // === مراقبة الغرف في الوقت الفعلي ===
    function listenRooms() {
        roomsList.innerHTML = "⏳ جاري تحميل الغرف...";

        db.ref("rooms").on("value", snapshot => {
            const data = snapshot.val();
            let html = '';
            if (!data) {
                html = '❌ لا توجد غرف حالياً';
            } else {
                Object.entries(data).forEach(([id, room]) => {
                    html += `<div class="room">
                        <strong>${room.room_name}</strong> 
                        (${room.current_players || 0} / ${room.max_players})
                        <button onclick="joinRoom('${id}')">انضم</button>
                    </div>`;
                });
            }
            roomsList.innerHTML = html;
        });
    }

    listenRooms(); // تحميل أولي + تحديث تلقائي

    // === إنشاء الغرفة (باستخدام PHP كالمعتاد) ===
    createForm.addEventListener("submit", e => {
        e.preventDefault();
        const formData = new FormData(createForm);
        fetch("php/create_room.php", { method: "POST", body: formData })
            .then(res => res.text())
            .then(data => {
                if (data.startsWith("success:")) {
                    const roomId = data.split(":")[1];
                    // هنا PHP لازم يسجل الغرفة الجديدة في Firebase
                    window.location.href = "room.php?room_id=" + roomId;
                } else showCustomAlert(data);
            })
            .catch(() => showCustomAlert("⚠ حدث خطأ في الاتصال بالسيرفر عند إنشاء الغرفة."));
    });

    // === الانضمام للغرفة ===
    window.joinRoom = function(roomId) {
        const formData = new FormData();
        formData.append("room_id", roomId);
        fetch("php/join_room.php", { method: "POST", body: formData })
            .then(res => res.text())
            .then(msg => {
                if (msg.startsWith("success:")) {
                    const [_, id, page] = msg.split(":");
                    // PHP لازم يعدّل عدد اللاعبين في Firebase
                    window.location.href = `${page}.php?room_id=${id}`;
                } else showCustomAlert(msg);
            })
            .catch(() => showCustomAlert("⚠ حدث خطأ في الاتصال بالسيرفر عند الانضمام للغرفة."));
    };

    // === بدء اللعبة ===
    window.startGame = function(roomId) {
        const formData = new FormData();
        formData.append("room_id", roomId);
        fetch("php/start_game.php", { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // PHP لازم يغير حالة الغرفة في Firebase → { status: "started" }
                    window.location.href = data.redirect;
                } else showCustomAlert(data.message || "❌ حدث خطأ في بدء اللعبة");
            })
            .catch(() => showCustomAlert("⚠ حدث خطأ في الاتصال بالسيرفر عند بدء اللعبة."));
    };

    // === إشعارات الطلبات (Realtime من Firebase) ===
    const currentUserId = window.CURRENT_USER_ID;
    const notifRef = db.ref(`friend_requests_count/${currentUserId}`);
    notifRef.on("value", snapshot => {
        const count = snapshot.val() || 0;
        const notif = document.getElementById('notifCountLobby');
        if (notif) {
            notif.textContent = count;
            notif.style.display = count > 0 ? 'inline' : 'none';
        }
    });

    // === رسائل الدردشة في اللوبي (اختياري) ===
    const chatRef = db.ref("lobby_chat");
    chatRef.on("child_added", snapshot => {
        const message = snapshot.val();
        window.appendChatMessage(message);
    });

    window.appendChatMessage = function(message) {
        const chatBox = document.getElementById('chatBox');
        if (!chatBox) return;
        const div = document.createElement('div');
        div.textContent = `${message.sender}: ${message.text}`;
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    };

    // === إرسال إشعار عند الخروج (ممكن يفضل PHP) ===
    window.addEventListener("beforeunload", () => {
        navigator.sendBeacon("php/leave_room.php");
    });
});
