<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();

if (!isset($_SESSION['sse_stats'])) {
    $_SESSION['sse_stats'] = [
        'events_sent' => 0,
        'bandwidth_bytes' => 0,
        'last_reset' => time()
    ];
}

if (isset($_GET['size'])) {
    $_SESSION['sse_stats']['events_sent']++;
    $_SESSION['sse_stats']['bandwidth_bytes'] += (int)$_GET['size'];
    exit;
}

if (isset($_GET['show'])) {
    $uptime = time() - $_SESSION['sse_stats']['last_reset'];
    echo "<h1>SSE Stats</h1>";
    echo "Events Sent: " . $_SESSION['sse_stats']['events_sent'] . "<br>";
    echo "Bandwidth: " . round($_SESSION['sse_stats']['bandwidth_bytes'] / 1024, 2) . " KB<br>";
    echo "Uptime: {$uptime} seconds<br>";
    exit;
}

if (isset($_GET['reset'])) {
    $_SESSION['sse_stats'] = [
        'events_sent' => 0,
        'bandwidth_bytes' => 0,
        'last_reset' => time()
    ];
    echo "Stats reset.";
}
?>