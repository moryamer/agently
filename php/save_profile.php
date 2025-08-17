<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start(); // بدء الجلسة
require 'db.php'; // تضمين ملف الاتصال بقاعدة البيانات

// التحقق من وجود user_id في الجلسة
if (!isset($_SESSION["user_id"])) {
    // إذا لم يتم العثور على user_id، أعد التوجيه إلى صفحة تسجيل الدخول
    header("Location: login.php"); // تأكد من المسار الصحيح لصفحة تسجيل الدخول
    exit(); // إنهاء تنفيذ السكريبت
}

$user_id = $_SESSION["user_id"]; // جلب user_id من الجلسة
$response_message = ""; // لرسائل النجاح أو الخطأ

// التحقق مما إذا كان الطلب من نوع POST (أي أن النموذج تم إرساله)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. تحديث اسم المستخدم
    $new_username = trim($_POST["username"]);
    if (!empty($new_username)) {
        try {
            $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("خطأ في إعداد استعلام تحديث اسم المستخدم: " . $conn->error);
            }
            $stmt->bind_param("si", $new_username, $user_id);
            if ($stmt->execute()) {
                $_SESSION["username"] = $new_username; // تحديث اسم المستخدم في الجلسة أيضاً
            } else {
                throw new Exception("خطأ في تنفيذ استعلام تحديث اسم المستخدم: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $response_message .= "❌ خطأ في تحديث الاسم: " . $e->getMessage() . ". ";
        }
    } else {
        $response_message .= "❌ لا يمكن أن يكون اسم المستخدم فارغاً. ";
    }

    // 2. معالجة تحميل الصورة الرمزية (الأفاتار)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "avatars/"; // دليل حفظ الصور الرمزية
        // التأكد من أن الدليل موجود وقابل للكتابة
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // إنشاء الدليل إذا لم يكن موجوداً
        }

        $file_name = basename($_FILES["avatar"]["name"]);
        $imageFileType = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = uniqid('avatar_', true) . '.' . $imageFileType; // اسم فريد للملف
        $target_file = $target_dir . $new_file_name;

        // التحقق من نوع الملف (صورة)
        $check = getimagesize($_FILES["avatar"]["tmp_name"]);
        if ($check === false) {
            $response_message .= "❌ الملف المحمل ليس صورة. ";
        } else {
            // التحقق من حجم الملف (يمكن تعديل الحد الأقصى للحجم بالبايت)
            if ($_FILES["avatar"]["size"] > 5000000) { // 5MB
                $response_message .= "❌ حجم الصورة كبير جداً (الحد الأقصى 5MB). ";
            } elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
                // السماح بأنواع معينة من الملفات
                $response_message .= "❌ يسمح فقط بملفات JPG, JPEG, PNG & GIF. ";
            } else {
                // نقل الملف المحمل
                if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {

                    // تحديث اسم الصورة الرمزية في قاعدة البيانات
                    try {
                        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                        if ($stmt === false) {
                            throw new Exception("خطأ في إعداد استعلام تحديث الصورة الرمزية: " . $conn->error);
                        }
                        $stmt->bind_param("si", $new_file_name, $user_id);
                        if ($stmt->execute()) {
                            // قبل تحديث الصورة الجديدة، احذف الصورة القديمة إذا كانت موجودة
                            // (يمكنك إضافة منطق لجلب اسم الصورة القديمة وحذفها من المجلد)
                        } else {
                            throw new Exception("خطأ في تنفيذ استعلام تحديث الصورة الرمزية: " . $stmt->error);
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        $response_message .= "❌ خطأ في تحديث الصورة الرمزية في قاعدة البيانات: " . $e->getMessage() . ". ";
                    }
                } else {
                    $response_message .= "❌ حدث خطأ أثناء تحميل ملف الصورة. ";
                }
            }
        }
    } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        // التعامل مع أخطاء التحميل الأخرى بخلاف عدم وجود ملف
        $response_message .= "❌ خطأ في تحميل الصورة: " . $_FILES['avatar']['error'] . ". ";
    }
} else {
    // إذا لم يكن الطلب من نوع POST، أعد التوجيه إلى صفحة تعديل البروفايل
    header("Location: edit_profile.php");
    exit();
}

// بعد الانتهاء من جميع العمليات، أعد التوجيه إلى صفحة الملف الشخصي
// يمكنك تضمين رسالة النجاح/الخطأ في عنوان URL إذا أردت عرضها في profile.php
// (تحتاج إلى قراءة $_GET في profile.php إذا اخترت هذه الطريقة)
header("Location: profile.php?message=" . urlencode($response_message));
exit();

?>
