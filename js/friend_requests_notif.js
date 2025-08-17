document.addEventListener("DOMContentLoaded", () => {
    updateNotifCount();
});

function updateNotifCount() {
    fetch('/php/get_friend_requests_count.php') // المسار من الجذر
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! Status: ${res.status}`);
            }
            return res.text(); // ناخده نص أولًا
        })
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error(`JSON parse error: ${e.message} | Raw response: ${text}`);
            }

            const count = data.count || 0;

            const notifCountProfile = document.getElementById("notifCount");
            const notifCountLobby = document.getElementById("notifCountLobby");

            if (notifCountProfile) {
                notifCountProfile.style.display = count > 0 ? "inline-block" : "none";
                notifCountProfile.textContent = count;
            }

            if (notifCountLobby) {
                notifCountLobby.style.display = count > 0 ? "inline-block" : "none";
                notifCountLobby.textContent = count;
            }
        })
        .catch(err => {
            console.error("خطأ في جلب عدد الطلبات:", err);
        });
}



// Expose functions for ws-client.js to call without changing existing behavior
window.updateNotifCount = updateNotifCount;


// === SSE Integration ===

const evtSource = new EventSource('sse.php');
evtSource.addEventListener('friend_requests_updated', function(e) {
    const data = JSON.parse(e.data);
    if (typeof window.updateNotifCount === 'function') {
        window.updateNotifCount(data.count);
    }
});
