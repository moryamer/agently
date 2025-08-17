<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'php/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['room_id'])) {
    header("Location: index.php");
    exit();
}

$roomId = intval($_GET['room_id']);
$userId = $_SESSION['user_id'];
$update = $conn->prepare("UPDATE room_players SET is_online = 1 WHERE room_id = ? AND user_id = ?");

// تحقق إذا كان اللاعب عضو في الروم
$check = $conn->prepare("SELECT * FROM room_players WHERE room_id = ? AND user_id = ?");
$check->bind_param("ii", $roomId, $userId);
$check->execute();
$checkResult = $check->get_result();

// تحقق إذا كانت الغرفة موجودة أولاً
$roomCheck = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$roomCheck->bind_param("i", $roomId);
$roomCheck->execute();
$roomResult = $roomCheck->get_result();

if ($roomResult->num_rows === 0) {
    // الغرفة مش موجودة، رجعه للـ Lobby
    header("Location: lobby.php?error=room_not_found");
    exit();
}


if ($checkResult->num_rows === 0) {
    // اللاعب مش منضم للروم، امنعه من الدخول
    header("Location: lobby.php");
    exit();
}

// جلب اسم اللاعب وحالته
$res = $conn->prepare("SELECT u.username, rp.last_status
                       FROM room_players rp
                       JOIN users u ON rp.user_id = u.id
                       WHERE rp.room_id = ? AND rp.user_id = ?");
$res->bind_param("ii", $roomId, $userId);
$res->execute();
$data = $res->get_result()->fetch_assoc();
$username = $data['username'];
$lastStatus = $data['last_status'] ?? 'in';

// تحديث حالة اللاعب ليكون Online
$conn->query("UPDATE room_players SET last_seen = NOW() WHERE room_id = $roomId AND user_id = $userId");

// لو كان خارج ورجع ← سجل رسالة دخول
if ($lastStatus === 'out') {
    $msg = "✅ اللاعب {$username} عاد إلى اللعبة";
    $stmt = $conn->prepare("INSERT INTO system_messages (room_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $roomId, $msg);
    $stmt->execute();
}

// تحديث الحالة إلى داخل
$conn->query("UPDATE room_players SET last_status = 'in' WHERE room_id = $roomId AND user_id = $userId");

// جلب الدور
$roleRes = $conn->prepare("SELECT role FROM player_roles WHERE room_id = ? AND user_id = ?");
$roleRes->bind_param("ii", $roomId, $userId);
$roleRes->execute();
$result = $roleRes->get_result();
$role = ($result->num_rows > 0) ? $result->fetch_assoc()['role'] : null;

// جلب حالة المهمات وحالة التصويت من قاعدة البيانات مباشرة
$progressStmt = $conn->prepare("SELECT killer_done, conspirator_done, voting_started, mission_sent FROM rooms WHERE id = ?");
$progressStmt->bind_param("i", $roomId);
$progressStmt->execute();
$progressData = $progressStmt->get_result()->fetch_assoc();
$killerDone = $progressData['killer_done'];
$conspiratorDone = $progressData['conspirator_done'];
$votingStarted = $progressData['voting_started'];
$missionSent = $progressData['mission_sent'];
$progressStmt->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="img/logo.png" type="image/png" />
    <meta charset="UTF-8">
    <title>العميل والقاتل - اللعبة</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #2c3e50;
            /* Darker background */
            color: #ecf0f1;
            /* Light text */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            background: #34495e;
            /* Slightly lighter dark background for container */
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            padding: 30px;
            width: 100%;
            max-width: 600px;
            text-align: center;
            border: 1px solid #4a627d;
            /* Subtle border */
        }

        h2,
        h3 {
            color: #ecf0f1;
            margin-bottom: 20px;
        }

        #systemChat {
            background: #283747;
            /* Even darker for chat background */
            border: 1px solid #4a627d;
            height: 250px;
            /* Increased height */
            overflow-y: auto;
            padding: 15px;
            font-size: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: right;
            /* Align chat text to the right for RTL */
        }

        .sys-msg {
            background: #3c526d;
            /* Background for system messages */
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 8px;
            font-style: italic;
            color: #e0e0e0;
            /* Slightly darker white for message text */
            text-align: right;
        }

        /* Voting Section Styling */
        #votingUI {
            background: #283747;
            border: 1px solid #4a627d;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            text-align: right;
            /* Align content to the right for RTL */
        }

        #votingUI h3 {
            color: #ecf0f1;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        #votingUI label {
            display: block;
            margin-bottom: 10px;
            font-size: 1.1em;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        #votingUI label:hover {
            background-color: #4a627d;
        }

        #votingUI input[type="radio"] {
            margin-left: 10px;
            /* Space between radio button and text */
            transform: scale(1.2);
            /* Slightly larger radio buttons */
        }

        #votingUI button {
            background-color: #28a745;
            /* Green for voting button */
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 15px;
        }

        #votingUI button:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        /* General Button Styling */
        button {
            background-color: #007bff;
            /* Blue for general buttons */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 10px;
        }

        button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        #leaveRoomButton {
            background-color: #dc3545;
            /* Red for leave button */
        }

        #leaveRoomButton:hover {
            background-color: #c82333;
        }

        textarea {
            width: calc(100% - 20px);
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #4a627d;
            background-color: #283747;
            color: #ecf0f1;
            resize: vertical;
            min-height: 80px;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            background: #3c526d;
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 6px;
        }

        strong {
            color: #ecf0f1;
        }

        .sys-msg {
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>

<body>
    <div class="container" id="gameContainer">
        <h2>🎯 اللعبة بدأت!</h2>

        <!-- شات رسائل النظام -->
        <div id="systemChat"></div>

        <?php if (!$role): ?>
            <p id="waitingMsg">⏳ انتظر توزيع الأدوار... سيتم بدء اللعبة قريبًا.</p>
        <?php else: ?>
            <h3>دورك: <span style="color:red;"><?php echo $role; ?></span></h3>

            <?php if ($role == "العميل" && $missionSent == 0): ?>

                <div id="missionSection">
                    <p>✍ اكتب الحكم الذي سينفذه القاتل:</p>
                    <textarea id="mission" placeholder="اكتب المهمة هنا"></textarea>
                    <button onclick="sendMission()">إرسال المهمة</button>
                </div>

            <?php elseif ($role == "القاتل"): ?>
                <div id="missionDisplay">
                    <?php if ($killerDone): ?>
                        <p style="text-decoration:line-through;color:green;">📜 المهمة اكتملت ✅</p>
                    <?php else: ?>
                        📜 انتظر استلام المهمة من العميل...
                    <?php endif; ?>
                </div>

            <?php elseif ($role == "متورط"): ?>
                <div id="missionDisplay">
                    <?php if ($conspiratorDone): ?>
                        <p style="text-decoration:line-through;color:green;">📜 المهمة اكتملت ✅</p>
                    <?php else: ?>
                        📜 انتظر استلام المهمة من العميل...
                    <?php endif; ?>
                </div>

            <?php elseif ($role == "المراقب"): ?>

                <h3>📋 لوحة تحكم المراقب</h3>

                <?php if (!$killerDone): ?>
                    <button id="markKillerTaskBtn" onclick="markTask('killer')">✅ القاتل أكمل المهمة</button>
                <?php else: ?>
                    <p>✅ تم تأكيد مهمة القاتل.</p>
                <?php endif; ?>

                <?php if (!$conspiratorDone): ?>
                    <button id="markConspiratorTaskBtn" onclick="markTask('conspirator')">✅ المتورط أكمل المهمة</button>
                <?php else: ?>
                    <p>✅ تم تأكيد مهمة المتورط.</p>
                <?php endif; ?>

                <div id="voteSection"
                    style="margin-top:20px; display:<?php echo ($killerDone && $conspiratorDone && !$votingStarted) ? 'block' : 'none'; ?>;">
                    <button id="startVotingBtn" onclick="startVoting()">🗳 بدء التصويت</button>
                </div>

            <?php else: ?>

                <p>👀 شاهد اللعبة وحاول اكتشاف القاتل!</p>
            <?php endif; ?>
        <?php endif; ?>

        <!-- New Voting UI Section -->
        <div id="votingUI" style="display: none;">
            <h3>🗳 اختر اللاعب المشتبه به:</h3>
            <div id="playerOptions">
                <!-- Player options will be loaded here by JavaScript -->
            </div>
            <button onclick="sendVote()">إرسال التصويت</button>
        </div>

        <button id="leaveRoomButton"
            style="margin-top:15px;padding:10px 20px;background:#ff4444;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            🚪 مغادرة اللعبة
        </button>
    </div>

    <script>
        const role = "<?php echo $role ?? ''; ?>";
        const roomId = <?php echo $roomId; ?>;
        const userId = <?php echo $userId; ?>; // Pass current user ID to JS
        const username = "<?php echo $username; ?>";

        // Function to replace alert with a custom modal/message box
        function showMessage(message) {
            const messageBox = document.createElement('div');
            messageBox.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background-color: #34495e;
                color: #ecf0f1;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.5);
                z-index: 1000;
                font-size: 1.1em;
                text-align: center;
                border: 1px solid #4a627d;
            `;
            messageBox.innerHTML = `
                <p>${message}</p>
                <button style="margin-top: 15px; background-color: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer;" onclick="this.parentNode.remove()">حسناً</button>
            `;
            document.body.appendChild(messageBox);
        }

        // Override alert to use our custom message box
        window.alert = showMessage;


        let lastMessageCount = 0; // نحتفظ بعدد الرسائل السابق

        function loadSystemMessages() {
            fetch(`php/get_system_messages.php?room_id=${roomId}`)
                .then(res => res.json())
                .then(data => {
                    const chat = document.getElementById("systemChat");
                    const isNearBottom = (chat.scrollHeight - chat.scrollTop - chat.clientHeight) < 10;
                    const oldCount = lastMessageCount;
                    lastMessageCount = data.length;

                    // ما نمسحش الرسائل قبل ما نقارن
                    chat.innerHTML = "";

                    if (data.length === 0) {
                        const div = document.createElement("div");
                        div.className = "sys-msg";
                        div.textContent = "📢 لا توجد رسائل بعد...";
                        chat.appendChild(div);
                    } else {
                        data.forEach(msg => {
                            const div = document.createElement("div");
                            div.className = "sys-msg";
                            div.innerHTML = msg.message.replace(/\n/g, "<br>"); // ← هنا التعديل
                            chat.appendChild(div);
                        });

                    }

                    // ننزل لتحت فقط إذا كنا تحت أصلاً أو جات رسائل جديدة
                    if (isNearBottom || data.length > oldCount) {
                        chat.scrollTop = chat.scrollHeight;
                    }
                })
                .catch(err => console.error('Error loading system messages:', err));
        }

        setInterval(loadSystemMessages, 2000);
        loadSystemMessages();


        function sendMission() {
            const mission = document.getElementById("mission").value.trim();
            if (!mission) { alert("⚠ من فضلك اكتب المهمة أولاً"); return; }
            fetch("php/set_mission.php", {
                method: "POST",
                body: new URLSearchParams({ room_id: roomId, mission })
            })
                .then(res => res.text())
                .then(response => {
                    alert(response);
                    document.getElementById("missionSection").innerHTML = `<p>📜 المهمة التي أرسلتها: ${mission}</p>`;
                })
                .catch(err => console.error('Error sending mission:', err));
        }

        function markTask(type) {
            fetch("php/mark_task.php", {
                method: "POST",
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ room_id: roomId, type })
            })
                .then(res => res.json()) // Expect JSON response
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        // Instead of full reload, fetch check_voting.php again to update UI based on latest state
                        checkVotingStatusAndRenderUI();
                    }
                })
                .catch(err => console.error('Error marking task:', err));
        }

        function startVoting() {
            fetch("php/start_voting.php", {
                method: "POST",
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ room_id: roomId })
            })
                .then(res => res.text())
                .then(msg => {
                    alert(msg);
                    // Fetch check_voting.php again to update UI based on latest state
                    checkVotingStatusAndRenderUI();
                })
                .catch(err => console.error('Error starting voting:', err));
        }


        if (role === "القاتل" || role === "متورط") {
            setInterval(() => {
                fetch(`php/get_mission.php?room_id=${roomId}&t=${Date.now()}`)
                    .then(res => res.json())
                    .then(data => {
                        const missionDisplay = document.getElementById("missionDisplay");
                        if (data.mission) {
                            let text = "📜 المهمة: " + data.mission;
                            if (role === "القاتل" && data.killer_done == 1) {
                                text = `<del>${text}</del> ✅`;
                            } else if (role === "متورط" && data.conspirator_done == 1) {
                                text = `<del>${text}</del> ✅`;
                            }
                            missionDisplay.innerHTML = text;
                        } else {
                            missionDisplay.textContent = "📜 انتظر استلام المهمة من العميل...";
                        }
                    })
                    .catch(err => console.error('Error getting mission:', err));
            }, 1000);
        }

        if (!role) {
            setInterval(() => {
                fetch(`php/get_role.php?room_id=${roomId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.role) {
                            location.reload();
                        }
                    })
                    .catch(err => console.error('Error getting role:', err));
            }, 3000);
        }

        let votingUIInitialized = false; // Flag to prevent re-initializing voting UI

        // New function to handle checking voting status and rendering UI
        function checkVotingStatusAndRenderUI() {
            fetch(`php/check_voting.php?room_id=${roomId}`)
                .then(res => res.json())
                .then(data => {
                    const votingUI = document.getElementById("votingUI");
                    const playerOptionsDiv = document.getElementById("playerOptions");
                    const startVotingBtn = document.getElementById("startVotingBtn");
                    const voteSection = document.getElementById("voteSection");

                    // Handle visibility of "Mark Task" buttons for Observer
                    const markKillerTaskBtn = document.getElementById("markKillerTaskBtn");
                    const markConspiratorTaskBtn = document.getElementById("markConspiratorTaskBtn");

                    if (role === 'المراقب') {
                        if (markKillerTaskBtn) {
                            markKillerTaskBtn.style.display = data.killer_done ? 'none' : 'block';
                            // Also update the paragraph if task is done and the paragraph is not there
                            if (data.killer_done && (!markKillerTaskBtn.nextElementSibling || markKillerTaskBtn.nextElementSibling.tagName !== 'P')) {
                                // Remove old paragraph if it exists and is not the correct one
                                if (markKillerTaskBtn.nextElementSibling && markKillerTaskBtn.nextElementSibling.tagName === 'P') {
                                    markKillerTaskBtn.nextElementSibling.remove();
                                }
                                markKillerTaskBtn.insertAdjacentHTML('afterend', "<p>✅ تم تأكيد مهمة القاتل.</p>");
                            }
                        }
                        if (markConspiratorTaskBtn) {
                            markConspiratorTaskBtn.style.display = data.conspirator_done ? 'none' : 'block';
                            // Also update the paragraph if task is done and the paragraph is not there
                            if (data.conspirator_done && (!markConspiratorTaskBtn.nextElementSibling || markConspiratorTaskBtn.nextElementSibling.tagName !== 'P')) {
                                // Remove old paragraph if it exists and is not the correct one
                                if (markConspiratorTaskBtn.nextElementSibling && markConspiratorTaskBtn.nextElementSibling.tagName === 'P') {
                                    markConspiratorTaskBtn.nextElementSibling.remove();
                                }
                                markConspiratorTaskBtn.insertAdjacentHTML('afterend', "<p>✅ تم تأكيد مهمة المتورط.</p>");
                            }
                        }

                        // Handle visibility of "Start Voting" button for Observer
                        if (data.killer_done && data.conspirator_done && !data.voting_started) {
                            if (voteSection) {
                                voteSection.style.display = 'block';
                            }
                        } else {
                            if (voteSection) {
                                voteSection.style.display = 'none';
                            }
                        }
                    }


                    if (data.voting_started) {
                        // If voting started and current user hasn't voted and is not observer
                        if (!data.has_voted && role !== "المراقب") {
                            votingUI.style.display = "block";
                            if (!votingUIInitialized) { // Initialize only once
                                let html = '';
                                // Filter players: cannot vote for self
                                data.players.filter(p => p.id != userId).forEach(p => {
                                    html += `<label>
                                        <input type="radio" name="voteTarget" value="${p.id}">
                                        ${p.username}
                                    </label>`;
                                });
                                playerOptionsDiv.innerHTML = html;
                                votingUIInitialized = true;
                            }
                        } else {
                            // If voting started but user already voted or is observer, hide UI
                            votingUI.style.display = "none";
                            votingUIInitialized = false; // Reset if UI is hidden
                        }
                    } else {
                        // If voting is not started, hide UI and reset flag
                        votingUI.style.display = "none";
                        votingUIInitialized = false;
                    }
                })
                .catch(err => console.error('Error checking voting status:', err));
        }

        // Call the new function initially and in the interval
        setInterval(checkVotingStatusAndRenderUI, 3000);
        checkVotingStatusAndRenderUI();


        function sendVote() {
            const selectedRadio = document.querySelector("input[name='voteTarget']:checked");

            if (!selectedRadio) {
                alert("⚠ من فضلك اختر لاعب للتصويت");
                return;
            }

            const targetId = selectedRadio.value;

            fetch("php/send_vote.php", {
                method: "POST",
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ room_id: roomId, target_id: targetId })
            })
                .then(res => res.json()) // Expect JSON response
                .then(data => {
                    alert(data.message); // Show message from server
                    if (data.status === "success") {
                        document.getElementById("votingUI").style.display = "none"; // Hide UI on success
                        votingUIInitialized = false; // Reset flag to allow re-initialization if voting starts again
                        loadSystemMessages(); // Force refresh chat to see the "X has voted" message

                        // NEW: Check if all players have voted and trigger end_voting.php
                        if (data.all_voted) {
                            fetch("php/end_voting.php", {
                                method: "POST",
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: new URLSearchParams({ room_id: roomId })
                            })
                                .then(endVoteRes => endVoteRes.json())
                                .then(endVoteData => {
                                    console.log("End voting response:", endVoteData);
                                    // The system messages will be updated by the interval loadSystemMessages
                                    // No need for alert here as end_voting.php already inserts a detailed message.
                                    loadSystemMessages(); // Load messages again to show final results
                                    // Optionally, trigger a page reload to reset state after game ends
                                    // location.reload(); // Might be too aggressive, depends on full game flow
                                })
                                .catch(endVoteErr => {
                                    console.error('Error ending voting:', endVoteErr);
                                    alert('⚠️ خطأ في إنهاء التصويت.');
                                });
                        }
                    }
                })
                .catch(err => {
                    console.error('Error sending vote:', err);
                    alert('⚠️ خطأ في الاتصال بالخادم أو في إرسال صوتك.');
                });
        }


        function copyRoomLink() {
            const url = new URL(window.location.href);
            const roomId = url.searchParams.get("room_id");

            if (roomId) {
                const shareLink = `${window.location.origin}/game.php?room_id=${roomId}`;
                // Using document.execCommand for better iframe compatibility
                const tempInput = document.createElement('input');
                document.body.appendChild(tempInput);
                tempInput.value = shareLink;
                tempInput.select();
                document.execCommand('copy');
                tempInput.remove();
                alert("✅ تم نسخ رابط الغرفة!");
            } else {
                alert("⚠️ لا يمكن تحديد رقم الغرفة.");
            }
        }

        document.getElementById('leaveRoomButton').addEventListener('click', () => {
            fetch('leave_room.php', {
                method: "POST",
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ room_id: roomId }) // Use JS roomId
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // نجاح، نرجع للّوبي
                        window.location.href = 'lobby.php';
                    } else {
                        alert('❌ حدث خطأ أثناء مغادرة الغرفة: ' + data.message);
                    }
                })
                .catch(err => {
                    alert('⚠️ خطأ في الاتصال بالخادم');
                    console.error(err);
                });
        });
    </script>
    <script src="js/ws-client.js"></script>

</body>

</html>