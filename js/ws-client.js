// ws-client.js - drop-in client that augments existing polling without removing it
(function(){
  // عنوان WebSocket الجديد (8080 من server.js)
  const WS_URL = window.WS_URL || (
    location.protocol === 'https:'
      ? 'wss://' + location.hostname + ':8081/ws'
      : 'ws://' + location.hostname + ':8081/ws'
  );


  let roomId = (typeof window.CURRENT_ROOM_ID !== 'undefined') ? window.CURRENT_ROOM_ID : null;
  let userId = (typeof window.CURRENT_USER_ID !== 'undefined') ? window.CURRENT_USER_ID : null;

  function safe(fn){ try{ fn(); } catch(e){ console.warn('[ws-client] handler error', e); } }

  function connect(){
    const ws = new WebSocket(WS_URL);
    ws.onopen = () => {
      try {
        ws.send(JSON.stringify({ type: 'identify', userId, roomId }));
      } catch(e){}
    };
    ws.onmessage = (e) => {
      let msg; try { msg = JSON.parse(e.data); } catch { return; }
      switch(msg.type){
        case 'rooms_updated':
          safe(()=> window.loadRooms && window.loadRooms());
          break;
        case 'friend_requests_updated':
          safe(()=> window.updateNotifCount && window.updateNotifCount());
          break;
        case 'room_players_updated':
          if (msg.roomId && roomId && String(msg.roomId) !== String(roomId)) break;
          safe(()=> window.loadPlayers && window.loadPlayers());
          break;
        case 'game_started':
          if (msg.roomId && roomId && String(msg.roomId) !== String(roomId)) break;
          if (typeof window.checkGameStart === 'function') { safe(()=> window.checkGameStart()); }
          else if (roomId) { location.href = 'game.php?room_id=' + roomId; }
          break;
        case 'chat_message':
          if (typeof window.appendChatMessage === 'function') safe(()=> window.appendChatMessage(msg.payload));
          break;
      }
    };
    ws.onclose = () => {
      // إعادة الاتصال بعد ثانية ونصف
      setTimeout(connect, 1500);
    };
  }

  // دالة لتغيير الـ ID أثناء التشغيل
  window.wsIdentify = function({user, room}){
    userId = user || userId;
    roomId = room || roomId;
  };

  connect();
})();
