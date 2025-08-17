<?php
require_once __DIR__ . '/php/_safe_wrappers.php';
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: welcome.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول - العميل والقاتل</title>
    <link rel="icon" href="img/logo.png" type="image/png" />
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/style.css">
    <style>
        /* تحسينات مخصصة لصفحة الدخول */

        html, body {
            margin: 0;
            padding: 0;
            height: 100vh;  /* طول الشاشة بالكامل */
            overflow: hidden; /* يمنع التمرير (السكرول) */
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            font-family: 'Cairo', sans-serif;
            color: white;
        }

        .login-container {
            background: rgba(0, 0, 0, 0.85);
            padding: 30px 25px;
            border-radius: 20px;
            width: 100%;
            max-width: 400px;
            box-shadow:
                0 0 20px rgba(0, 245, 255, 0.3),
                0 0 40px rgba(0, 245, 255, 0.2),
                inset 0 0 15px rgba(0, 245, 255, 0.1);
            color: white;
            text-align: center;
            font-family: 'Cairo', sans-serif;
            user-select: none;
            animation: fadeIn 0.7s ease-in-out;

            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
        }

        .login-container h2 {
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 2rem;
            color: #00f5ff;
            text-shadow: 0 0 8px #00f5ff;
            user-select: text;
        }

        .login-container form {
            width: 100%;
        }

        .login-container form input {
            width: 100%;
            padding: 14px 15px;
            margin: 12px 0;
            border-radius: 15px;
            border: none;
            outline: none;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            box-shadow:
                inset 0 2px 6px rgba(0, 0, 0, 0.3),
                0 0 8px rgba(0, 245, 255, 0.2);
            transition: background 0.3s ease, transform 0.2s ease;
            user-select: text;
        }

        .login-container form input::placeholder {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 600;
        }

        .login-container form input:focus {
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 12px #00f5ff;
            transform: scale(1.02);
        }

        .login-container form button {
            background: linear-gradient(45deg, #00f5ff, #00d4e4);
            color: black;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 14px 0;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            box-shadow: 0 6px 15px rgba(0, 245, 255, 0.4);
            margin-top: 15px;
            letter-spacing: 0.5px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            user-select: none;
            width: 100%;
        }

        .login-container form button:hover {
            background: linear-gradient(45deg, #00c4cc, #00a8b0);
            box-shadow: 0 8px 20px rgba(0, 245, 255, 0.6);
            transform: translateY(-2px);
        }

        .login-container form button:active {
            transform: translateY(1px);
        }

        .login-container p {
            margin-top: 20px;
            font-size: 1rem;
            user-select: text;
        }

        .login-container p a {
            color: #00f5ff;
            text-decoration: none;
            transition: color 0.3s;
            user-select: none;
        }

        .login-container p a:hover {
            color: #a0ffff;
        }

        /* رسائل الخطأ والنجاح */
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1rem;
            user-select: none;
        }

        .message.error {
            background-color: rgba(255, 0, 0, 0.25);
            color: #ff4c4c;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.4);
        }

        .message.success {
            background-color: rgba(0, 255, 0, 0.25);
            color: #6aff6a;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.4);
        }
        .register-img {
            margin-top: 20px;
            display: block;
            width: 153px;
            margin-bottom: -188px;
        }


        /* Responsive */
        @media (max-width: 450px) {
            .login-container {
                margin: 20px 15px;
                padding: 25px 20px;
                border-radius: 20px;
                margin-top: -13%;
                box-shadow:
                    0 0 15px rgba(0, 245, 255, 0.3),
                    0 0 30px rgba(0, 245, 255, 0.2),
                    inset 0 0 12px rgba(0, 245, 255, 0.1);
                min-height: 600px;
                display: flex;
                flex-direction: column;
                justify-content: flex-start; /* يوزع العناصر من فوق */
                align-items: center;
            }

            .login-container h2 {
                font-size: 1.7rem;
                margin-bottom: 25px; /* مسافة تحت العنوان */
            }

            .login-container form {
                width: 100%;
                flex-grow: 1; /* يشغل المساحة المتاحة */
                display: flex;
                flex-direction: column;
                justify-content: space-between; /* يوزع الحقول والأزرار متساوي */
            }

            .login-container form input {
                margin: 6px 0; /* قللت المسافة بين الحقول */
                padding: 14px 15px;
                font-size: 1rem;
                border-radius: 15px;
                box-shadow:
                    inset 0 2px 6px rgba(0, 0, 0, 0.3),
                    0 0 8px rgba(0, 245, 255, 0.2);
                transition: background 0.3s ease, transform 0.2s ease;
            }

            .login-container .user {
                margin-top: 230px;
            }


            .login-container form button {
                margin-top: 10px; /* بدل 20px خليها 10 */
                padding: 14px 0;
                border-radius: 25px;
                width: 100%;
                font-size: 1rem;
                box-shadow: 0 6px 15px rgba(0, 245, 255, 0.4);
                cursor: pointer;
                user-select: none;
                transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            }


            .login-container p {
                font-size: 0.95rem;
                margin-top: 15px;
            }
        }


        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.85);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

    </style>
</head>

<body>
    <div class="login-container">
        <h2>تسجيل الدخول</h2>

        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="message error">
                <?= htmlspecialchars($_SESSION['login_error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['login_error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['register_success'])): ?>
            <div class="message success">
                <?= htmlspecialchars($_SESSION['register_success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['register_success']); ?>
            </div>
        <?php endif; ?>
        <img src="./img/logo.png" alt="صورة تسجيل" class="register-img" />
        <form action="login.php" method="POST" autocomplete="off" novalidate>
            <input type="text" class="user" name="username" placeholder="اسم المستخدم" required autofocus>
            <input type="password" class="pass" name="password" placeholder="كلمة المرور" required>
            <button type="submit">دخول</button>
        </form>

        <p>ليس لديك حساب؟ <a href="register.php">إنشاء حساب</a></p>
    </div>
</body>

</html>
