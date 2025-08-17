document.addEventListener("DOMContentLoaded", () => {
    const roomsList = document.getElementById("roomsList");
    const createForm = document.getElementById("createRoomForm");

    // === دوال مساعدة ===
    // دالة لعرض رسائل تنبيه مخصصة بدلاً من alert() الافتراضي
    function showCustomAlert(message) {
        // يمكنك هنا بناء واجهة مستخدم (UI) مخصصة لعرض الرسالة
        // مثل modal أو toast notification
        console.log("Custom Alert:", message);
        alert(message); // مؤقتاً، لا تزال تستخدم alert() ولكن يجب استبدالها بواجهة أفضل
    }


    const eventSource = new EventSource('./php/sse.php'); 

    // عند استقبال رسالة عامة (لو السيرفر بيبعت رسائل عامة بدون تحديد نوع)
    eventSource.onmessage = function(event) {
        console.log('رسالة SSE عامة:', event.data);
    };

    // عند استقبال حدث 'friend_requests_updated'
    eventSource.addEventListener('friend_requests_updated', function(event) {
        console.log('تحديث طلبات الصداقة:', event.data);
        try {
            const data = JSON.parse(event.data);
            const notifCountLobby = document.getElementById('notifCountLobby');
            if (notifCountLobby) {
                notifCountLobby.textContent = data.count;
                notifCountLobby.style.display = data.count > 0 ? 'inline' : 'none';
            }
        } catch (e) {
            console.error('خطأ في تحليل بيانات تحديث طلبات الصداقة:', e);
        }
    });

    // عند استقبال حدث 'rooms_updated'
    eventSource.addEventListener('rooms_updated', function(event) {
        console.log('تحديث الغرف:', event.data);
        // استدعي الدالة التي تقوم بتحميل وتحديث قائمة الغرف
        // ستقوم loadRooms() بعمل fetch جديد لضمان أحدث البيانات
        loadRooms(); 
    });

    // عند استقبال حدث 'chat_message'
    eventSource.addEventListener('chat_message', function(event) {
        console.log('رسالة دردشة جديدة:', event.data);
        // يمكنك هنا إضافة منطق لعرض رسالة الدردشة الجديدة
        // مثال: appendChatMessage(JSON.parse(event.data));
    });

    // عند استقبال حدث 'ping' (للحفاظ على الاتصال)
    eventSource.addEventListener('ping', function(event) {
        console.log('SSE Ping:', event.data);
    });

    // في حالة حدوث خطأ في اتصال SSE
    eventSource.onerror = function(err) {
        console.error('EventSource failed:', err);
        eventSource.close(); // إغلاق الاتصال الحالي
        // يمكنك هنا إضافة منطق لإعادة المحاولة بعد فترة (Exponential backoff)
    };

    // === وظائف تحميل الغرف والانضمام ===
    function loadRooms() {
        roomsList.innerHTML = "⏳ جاري تحميل الغرف...";

        fetch('php/get_rooms.php')
            .then(res => {
                // تحقق من حالة الاستجابة HTTP
                if (!res.ok) {
                    throw new Error(`HTTP Error: ${res.status} - ${res.statusText}`);
                }
                // استخدم .json() مباشرة لأن الخادم يرسل JSON
                return res.json();
            })
            .then(data => {
                console.log("Parsed JSON:", data); // للاختبار
                let html = '';
                if (data.length === 0) {
                    html = '❌ لا توجد غرف حالياً';
                } else {
                    data.forEach(room => {
                        html += `
                        <div class="room">
                            <strong>${room.room_name}</strong> 
                            (${room.current_players} / ${room.max_players})
                            <button onclick="joinRoom(${room.id})">انضم</button>
                        </div>
                        `;
                    });
                }
                roomsList.innerHTML = html;
            })
            .catch(error => {
                roomsList.innerHTML = "⚠️ خطأ في تحميل الغرف: " + error.message;
                console.error("خطأ في جلب الغرف:", error);
            });
    }

    // === وظائف إنشاء الغرفة ===
    createForm.addEventListener("submit", e => {
        e.preventDefault();
        const formData = new FormData();
        formData.append("room_name", document.getElementById("roomName").value);
        formData.append("max_players", document.getElementById("maxPlayers").value);
        formData.append("conspirators", document.getElementById("conspirators").value);

        fetch("php/create_room.php", { method: "POST", body: formData })
            .then(res => res.text())
            .then(data => {
                if (data.startsWith("success:")) {
                    let roomId = data.split(":")[1];
                    window.location.href = "room.php?room_id=" + roomId;
                } else {
                    showCustomAlert(data); // استخدام الدالة المخصصة
                }
            })
            .catch(error => {
                console.error("خطأ في إنشاء الغرفة:", error);
                showCustomAlert("⚠ حدث خطأ في الاتصال بالسيرفر عند إنشاء الغرفة."); // استخدام الدالة المخصصة
            });
    });

    // === وظائف الانضمام إلى الغرفة ===
    window.joinRoom = function (roomId) {
        const formData = new FormData();
        formData.append("room_id", roomId);

        fetch("php/join_room.php", { method: "POST", body: formData })
            .then(res => res.text())
            .then(msg => {
                if (msg.startsWith("success:")) {
                    let parts = msg.split(":");
                    let roomId = parts[1];
                    let page = parts[2];
                    window.location.href = page + ".php?room_id=" + roomId;
                } else {
                    showCustomAlert(msg); // استخدام الدالة المخصصة
                }
            })
            .catch(error => {
                console.error("خطأ في الانضمام للغرفة:", error);
                showCustomAlert("⚠ حدث خطأ في الاتصال بالسيرفر عند الانضمام للغرفة."); // استخدام الدالة المخصصة
            });
    };

    // === وظائف بدء اللعبة ===
    window.startGame = function (roomId) {
        const formData = new FormData();
        formData.append("room_id", roomId);

        fetch("php/start_game.php", { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showCustomAlert(data.message || "❌ حدث خطأ في بدء اللعبة"); // استخدام الدالة المخصصة
                }
            })
            .catch(error => {
                console.error("خطأ في بدء اللعبة:", error);
                showCustomAlert("⚠ حدث خطأ في الاتصال بالسيرفر عند بدء اللعبة."); // استخدام الدالة المخصصة
            });
    };

    // === وظائف Custom Select (تم نقلها كما هي) ===
    const selectWrapper = document.querySelector('.custom-select-wrapper');
    const trigger = selectWrapper.querySelector('.custom-select-trigger');
    const options = selectWrapper.querySelectorAll('.custom-option');
    const hiddenInput = selectWrapper.querySelector('input[type="hidden"]');

    trigger.addEventListener('click', () => {
        trigger.classList.toggle('active');
        selectWrapper.querySelector('.custom-options').style.display =
            selectWrapper.querySelector('.custom-options').style.display === 'block' ? 'none' : 'block';
    });

    options.forEach(option => {
        option.addEventListener('click', () => {
            options.forEach(o => o.classList.remove('selected'));
            option.classList.add('selected');
            trigger.textContent = "عدد المتورطين: " + option.textContent;
            hiddenInput.value = option.dataset.value;
            trigger.classList.remove('active');
            selectWrapper.querySelector('.custom-options').style.display = 'none';
        });
    });

    document.addEventListener('click', e => {
        if (!selectWrapper.contains(e.target)) {
            trigger.classList.remove('active');
            selectWrapper.querySelector('.custom-options').style.display = 'none';
        }
    });

    document.querySelectorAll('.custom-option').forEach(option => {
        option.addEventListener('click', () => {
            const value = option.getAttribute('data-value');
            document.getElementById('conspirators').value = value;
            document.querySelector('.custom-select-trigger').textContent = 'عدد المتورطين: ' + value;
            document.querySelectorAll('.custom-option').forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
        });
    });

    // === تحديث القائمة عند تحميل الصفحة فقط ===
    // استدعيها مرة واحدة لتحميل الغرف عند دخول اللوبي

    // استدعاء loadFriends (تفترض أنها معرفة في friend_requests_notif.js أو مكان آخر)
    // إذا كنت تحتاج لتحميل الأصدقاء مبدئياً، تأكد من تعريف هذه الدالة.
    // loadFriends(); 

    // إرسال إشعار عند الخروج لتسجيل الخروج من الغرفة
    window.addEventListener("beforeunload", () => {
        navigator.sendBeacon("php/leave_room.php");
    });
});

// هذا السطر يمكن إزالته إذا لم يكن هناك استخدام آخر لـ window.loadRooms خارج هذا الملف.
// window.loadRooms = (typeof loadRooms !== 'undefined') ? loadRooms : window.loadRooms;
