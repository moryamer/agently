// server.js - PHP + WebSocket + Proxy (CommonJS)

const { exec } = require('child_process');
const express = require('express');
const { createProxyServer } = require('http-proxy');
const { WebSocketServer } = require('ws');
const http = require('http');

// ====== إعداد ======
const PHP_PORT = 8000;     // صفحات PHP
const WS_PORT = 8080;      // WebSocket داخلي
const PROXY_PORT = 8081;   // Proxy endpoint

// ====== شغل PHP ======
exec(`php -S 0.0.0.0:${PHP_PORT} -t .`, (err, stdout, stderr) => {
  if (err) console.error(err);
  if (stdout) console.log(stdout);
  if (stderr) console.error(stderr);
});
console.log(`[PHP] running on http://0.0.0.0:${PHP_PORT}`);

// ====== WebSocket Server ======
const wss = new WebSocketServer({ port: WS_PORT });
console.log(`[WS] listening on ${WS_PORT}`);

wss.on('connection', (ws) => {
  ws.send(JSON.stringify({ type: 'hello', ts: Date.now() }));
  ws.on('message', (raw) => {
    let msg;
    try { msg = JSON.parse(raw); } catch { return; }
    console.log('[WS message]', msg);
  });
});

// ====== Proxy Server على نفس بورت PHP ======
const app = express();
const proxy = createProxyServer({ target: `ws://localhost:${WS_PORT}`, ws: true });

// Endpoint HTTP للنشر من PHP → WebSocket
app.use('/publish', express.json(), (req, res) => {
  const { type, payload } = req.body || {};
  if (!type) return res.status(400).json({ error: 'type required' });
  wss.clients.forEach(c => {
    if (c.readyState === 1) c.send(JSON.stringify({ type, payload }));
  });
  res.json({ ok: true });
});

// أي اتصال WebSocket على /ws يروح لـ WS server
const server = http.createServer(app);
server.on('upgrade', (req, socket, head) => {
  if (req.url === '/ws') {
    proxy.ws(req, socket, head);
  } else {
    socket.destroy();
  }
});

server.listen(PROXY_PORT, () => {
  console.log(`[Proxy] listening on ${PROXY_PORT} for /ws`);
});
