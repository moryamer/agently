<?php
require_once __DIR__ . '/php/_safe_wrappers.php';
session_start();
session_destroy();
header("Location: index.php");
