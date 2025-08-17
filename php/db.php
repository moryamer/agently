<?php
ini_set('display_errors', 1); // لا تنسى إزالة هذه السطور بعد تصحيح الأخطاء
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/_safe_wrappers.php';
// // ملف اتصال قاعدة البيانات: php/db.php
// $servername = "localhost";  // اسم السيرفر
// $username   = "root";       // يوزر قاعدة البيانات
// $password   = "";           // باسورد قاعدة البيانات
// $dbname     = "agent_killer_game"; // اسم قاعدة البيانات

// // ملف اتصال قاعدة البيانات: php/db.php
// $servername = "sql108.hstn.me";  // اسم السيرفر
// $username   = "mseet_39672618";       // يوزر قاعدة البيانات
// $password   = "mero13112020";           // باسورد قاعدة البيانات
// $dbname     = "mseet_39672618_agent_killer_game"; // اسم قاعدة البيانات

$servername = "sql8.freesqldatabase.com"; // الـ Host
$username   = "sql8794878";               // الـ Username
$password   = "ApzKYPlRz8";   // الـ Password
$dbname     = "sql8794878";               // Database Name
$port       = 3306;   
// إنشاء اتصال بقاعدة البيانات
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    // إذا فشل الاتصال، أوقف السكريبت وأظهر رسالة خطأ
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// تعيين ترميز الأحرف إلى UTF-8 لدعم اللغة العربية
$conn->set_charset("utf8mb4");

?>
