document.addEventListener("DOMContentLoaded", () => {
    const roomsList = document.getElementById("roomsList");
    const createForm = document.getElementById("createRoomForm");

    function showCustomAlert(message) {
        console.log("Custom Alert:", message);
        alert(message);
    }

    // === تحميل الغرف كل 3 ثواني ===
    function loadRooms() {
        roomsList.innerHTML = "⏳ جاري تحميل الغرف...";

        fetch('php/fetch_rooms.php') // استعملنا الملف الجديد بدل get_rooms.php
            .then(res => {
                if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
                return res.json();
            })
            .then(data => {
                let html = '';
                if (!data.success || data.rooms.length === 0) {
                    html = '❌ لا توجد غرف حالياً';
                } else {
                    data.rooms.forEach(room => {
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

    // استدعاء أولي + كل 3 ثواني
    loadRooms();
    setInterval(loadRooms, 3000);

    // === إنشاء غرفة ===
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
                    showCustomAlert(data);
                }
            })
            .catch(error => {
                console.error("خطأ في إنشاء الغرفة:", error);
                showCustomAlert("⚠ حدث خطأ في الاتصال بالسيرفر عند إنشاء الغرفة.");
            });
    });

    // === الانضمام إلى غرفة ===
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
                    showCustomAlert(msg);
                }
            })
            .catch(error => {
                console.error("خطأ في الانضمام للغرفة:", error);
                showCustomAlert("⚠ حدث خطأ في الاتصال بالسيرفر عند الانضمام للغرفة.");
            });
    };

    // === بدء اللعبة ===
    window.startGame = function (roomId) {
        const formData = new FormData();
        formData.append("room_id", roomId);

        fetch("php/start_game.php", { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showCustomAlert(data.message || "❌ حدث خطأ في بدء اللعبة");
                }
            })
            .catch(error => {
                console.error("خطأ في بدء اللعبة:", error);
                showCustomAlert("⚠ حدث خطأ في الاتصال بالسيرفر عند بدء اللعبة.");
            });
    };

    // === باقي كود custom select زي ما هو ===
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

    // إشعار عند الخروج
    window.addEventListener("beforeunload", () => {
        navigator.sendBeacon("php/leave_room.php");
    });
});
