<?php
// Safe wrapper functions to comply with hosting rules

function base64_decode_safe($data) {
    return base64_decode($data, true); // strict mode
}

function exec_safe($cmd, &$output=null, &$return_var=null) {
    // disabled or logged - avoid remote shell execution
    return null;
}

function shell_exec_safe($cmd) {
    return null;
}

function move_uploaded_file_safe($from, $to) {
    $allowed_dir = __DIR__ . '/uploads';
    if (!is_dir($allowed_dir)) mkdir($allowed_dir, 0755);
    if (strpos(realpath($to), realpath($allowed_dir)) === 0) {
        return move_uploaded_file($from, $to);
    }
    return false;
}

function safe_remote_get($url) {
    static $cacheDir;
    if (!$cacheDir) {
        $cacheDir = __DIR__ . '/cache';
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0755);
    }
    $key = md5($url);
    $file = "$cacheDir/$key";
    if (file_exists($file) && (time() - filemtime($file) < 300)) {
        return file_get_contents($file);
    }
    $context = stream_context_create(['http' => ['timeout' => 3]]);
    $data = @file_get_contents($url, false, $context);
    if ($data !== false) {
        file_put_contents($file, $data);
    }
    return $data;
}
?>