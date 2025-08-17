<?php
require_once __DIR__ . '/php/_safe_wrappers.php';
session_start();
require 'php/db.php';

$username = trim($_POST["username"]);
$password = $_POST["password"];

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        header("Location: lobby.php");
        exit();
    } else {
        echo "❌ كلمة المرور غير صحيحة!";
    }
} else {
    echo "❌ المستخدم غير موجود!";
}
?>
