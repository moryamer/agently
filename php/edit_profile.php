<?php
require_once __DIR__ . '/_safe_wrappers.php';
session_start();
require 'db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

try {
    $stmt = $conn->prepare("SELECT username, avatar FROM users WHERE id = ?");
    if ($stmt === false) {
        throw new Exception("خطأ في إعداد الاستعلام: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        session_unset();
        session_destroy();
        header("Location: login.php?error=user_not_found");
        exit("❌ لم يتم العثور على بيانات المستخدم في قاعدة البيانات. يرجى إعادة تسجيل الدخول.");
    }

    $user = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    die("❌ حدث خطأ غير متوقع: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>تعديل البروفايل</title>
        <link rel="icon" href="../img/logo.png" type="image/png" />

    <link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
    <style>
        body {
            overflow: hidden; /* يمنع التمرير (السكرول) */
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            color: white;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            direction: rtl;
        }

        .form-container {
            background: rgba(0, 0, 0, 0.85);
            padding: 30px 25px;
            border-radius: 20px;
            width: 100%;
            max-width: 400px;
            box-shadow:
                0 0 20px rgba(0, 245, 255, 0.3),
                0 0 40px rgba(0, 245, 255, 0.2),
                inset 0 0 15px rgba(0, 245, 255, 0.1);
            text-align: right;
        }

        h2 {
            font-weight: 700;
            font-size: 2rem;
            text-align: center;
            margin-bottom: 25px;
            color: #00f5ff;
            text-shadow: 0 0 10px #00f5ff;
            user-select: none;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 1.1rem;
            color: #a0eaff;
            user-select: none;
        }

        input[type="text"],
        input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            border-radius: 12px;
            border: none;
            background: #1f2937;
            color: white;
            font-size: 1.1rem;
            direction: rtl;
            box-shadow: inset 0 0 6px rgba(0, 245, 255, 0.4);
            transition: box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="file"]:focus {
            outline: none;
            box-shadow: 0 0 10px #00f5ff;
        }

        .avatar-preview {
            margin: 20px auto 10px;
            display: block;
            border-radius: 50%;
            border: 4px solid #00f5ff;
            box-shadow: 0 0 15px #00f5ff;
            width: 130px;
            height: 130px;
            object-fit: cover;
        }

        .avatar-caption {
            color: white;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
            user-select: none;
        }

        .no-avatar {
            text-align: center;
            color: #718096;
            font-size: 1rem;
            margin-bottom: 20px;
            user-select: none;
        }

        button[type="submit"] {
            width: 100%;
            background-color: #00f5ff;
            color: #002d3a;
            font-weight: 700;
            font-size: 1.2rem;
            padding: 14px;
            border-radius: 15px;
            cursor: pointer;
            box-shadow: 0 0 17px #00f5ff;
            transition: background-color 0.3s ease, transform 0.2s ease;
            user-select: none;
            border: none;
        }

        button[type="submit"]:hover,
        button[type="submit"]:focus {
            background-color: #00c3d6;
            transform: scale(1.05);
            outline: none;
        }

        .back-link {
            margin-top: 25px;
            text-align: center;
        }

        .back-link a {
            color: #00f5ff;
            font-weight: 600;
            font-size: 1.1rem;
            text-decoration: none;
            text-shadow: 0 0 8px #00f5ff;
            user-select: none;
            transition: color 0.3s ease;
        }

        .back-link a:hover,
        .back-link a:focus {
            color: #81e6d9;
            text-decoration: underline;
            outline: none;
        }

        /* Responsive */
        @media (max-width: 450px) {
            .form-container {
                margin-top: -60px;
                padding: 25px 20px;
                max-width: 100%;
            }

            h2 {
                font-size: 1.6rem;
            }

            input[type="text"],
            input[type="file"] {
                font-size: 1rem;
            }

            button[type="submit"] {
                font-size: 1.1rem;
            }
        }

        /* تأثير نيون للنص */
        .neon-text {
            text-shadow:
                0 0 5px #00f5ff,
                0 0 10px #00f5ff,
                0 0 15px #00d4e4;
        }
        .items-center {
            margin-top: 25px;
            align-items: center;
        }
        /* تحسين شكل زر اختيار الصورة الرمزية */
        label[for="avatar"] {
            display: inline-block;
            cursor: pointer;
            background: linear-gradient(45deg, #1f2937, #445455);
            color: white;
            font-weight: 700;
            padding: 14px 30px;
            border-radius: 15px;
            text-align: center;
            user-select: none;
            transition: background 0.3s ease, box-shadow 0.3s ease, transform 0.2s ease;
            font-family: 'Cairo', sans-serif;
            font-size: 1.1rem;
        }

        label[for="avatar"]:hover {
            box-shadow:
                0 0 0px #00f5ff,
                0 0 9px #00d0ff,
                inset 0 0 9px #00d9ff;
            transform: scale(1.05);
        }

    </style>
</head>

<body>

    <main class="form-container" role="main" aria-label="نموذج تعديل البروفايل">
        <h2 tabindex="0">تعديل البروفايل</h2>

        <form method="POST" action="save_profile.php" enctype="multipart/form-data" novalidate>
            <label for="username">الاسم:</label>
            <input type="text" id="username" name="username" required
                value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" aria-required="true" />

            <div class="flex flex-col items-center">
                <label for="avatar" class="cursor-pointer bg-blue-600 text-white font-semibold py-3 px-8 rounded-lg shadow-lg 
                hover:bg-blue-700 transition duration-300 ease-in-out
                neon-glow" style="text-align:center;">
                    اختر صورة رمزية
                </label>
                <input id="avatar" name="avatar" type="file" accept="image/*" class="hidden" />

            </div>

            <?php if (!empty($user['avatar'])): ?>
                <img src="avatars/<?php echo htmlspecialchars($user['avatar']); ?>" alt="الصورة الرمزية الحالية"
                    class="avatar-preview" />
                <p class="avatar-caption">الصورة الرمزية الحالية</p>
            <?php else: ?>
                <p class="no-avatar">لا توجد صورة رمزية حالية.</p>
            <?php endif; ?>

            <button type="submit" aria-label="حفظ التعديلات على البروفايل">
                حفظ التعديلات
            </button>
        </form>

        <div class="back-link">
            <a href="profile.php" aria-label="العودة إلى صفحة الملف الشخصي">العودة إلى البروفايل</a>
        </div>
    </main>

    <script>
        const inputFile = document.getElementById('avatar');
        const fileNameDisplay = document.getElementById('file-name');

        inputFile.addEventListener('change', () => {
            if (inputFile.files.length > 0) {
                fileNameDisplay.textContent = `تم اختيار الملف: ${inputFile.files[0].name}`;
            } else {
                fileNameDisplay.textContent = 'لم يتم اختيار ملف بعد.';
            }
        });
    </script>
    <script src="js/ws-client.js"></script>

</body>

</html>