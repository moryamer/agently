<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'php/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user_id'];

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        die("❌ نوع الملف غير مسموح");
    }

    if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
        die("❌ حجم الصورة كبير جدًا (الحد الأقصى 2MB)");
    }

    $newName = uniqid('avatar_') . "." . $ext;
    $uploadPath = __DIR__ . "/uploads/avatars/" . $newName;

    if (move_uploaded_file_safe($_FILES['avatar']['tmp_name'], $uploadPath)) {
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $newName, $userId);
        $stmt->execute();

        header("Location: profile.php");
        exit();
    } else {
        die("❌ فشل رفع الصورة");
    }
} else {
    die("❌ لم يتم اختيار أي صورة");
}
