<?php
require_once __DIR__ . '/_safe_wrappers.php';
// php/notify_ws.php
// Helper to publish events to Node WebSocket bridge without breaking existing PHP logic.
function ws_publish($type, $payload = null, $roomId = null, $userId = null) {
    // Configure the HTTP endpoint of Node bridge
    require_once __DIR__ . '/ws_config.php';
    $url = WS_HTTP_ENDPOINT . '/publish';

    $data = array('type' => $type, 'payload' => $payload);
    if (!is_null($roomId)) $data['roomId'] = $roomId;
    if (!is_null($userId)) $data['userId'] = $userId;

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'timeout' => 1.2, // don't block PHP
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE)
        )
    );
    $context  = stream_context_create($options);
    @file_get_contents($url, false, $context); // suppress warnings, fire-and-forget
}
