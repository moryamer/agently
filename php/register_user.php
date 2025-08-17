<?php
require_once __DIR__ . '/_safe_wrappers.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require 'db.php';

if (!isset($_POST['username'], $_POST['password'])) {
    $_SESSION['register_error'] = "❌ البيانات غير مكتملة";
    header("Location: ../register.php");
    exit();
}

$username = trim($_POST['username']);
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);

// التحقق من وجود اسم المستخدم
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $_SESSION['register_error'] = "❌ اسم المستخدم موجود بالفعل";
    header("Location: ../register.php");
    exit();
}

// إدخال المستخدم
$stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $password);
if ($stmt->execute()) {
    $_SESSION['register_success'] = "✅ تم التسجيل بنجاح، يمكنك تسجيل الدخول الآن";
    header("Location: ../index.php");
    exit();
} else {
    $_SESSION['register_error'] = "❌ حدث خطأ أثناء التسجيل";
    header("Location: ../register.php");
    exit();
}
