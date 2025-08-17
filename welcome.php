<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>مرحبًا بك - العميل والقاتل</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="icon" href="img/logo.png" type="image/png" />

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@700&display=swap');

        /* Reset */
        * {
            margin: 0; padding: 0; box-sizing: border-box;
        }

        body, html {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Cairo', sans-serif;
            color: #00f5ff;
            overflow: hidden;
        }

        .container {
            margin-top: -50px;
            text-align: center;
            animation: fadeInUp 1.5s ease forwards;
        }

        /* اللوجو (ممكن صورة) */
        .logo {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            background: url('img/logo.png') no-repeat center center;
            background-size: contain;
            animation: rotateScale 3s linear infinite;
        }

        /* نص by Omar Amer */
        .by-text {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 15px;
            color: white;

            animation: neonPulse 3s ease-in-out infinite;
        }


        .logo {
        animation: rapidMoveThenGrow 3s ease-in-out forwards;
        }

        @keyframes rapidMoveThenGrow {
            0% {
                transform: translate(0, 0) scale(1);
                filter: drop-shadow(0 0 5px rgba(0, 245, 255, 0.15));
            }
            10% {
                transform: translate(15px, -10px) scale(1);
            }
            20% {
                transform: translate(-15px, 10px) scale(1);
            }
            30% {
                transform: translate(20px, 15px) scale(1);
            }
            40% {
                transform: translate(-20px, -15px) scale(1);
            }
            50% {
                transform: translate(10px, 20px) scale(1);
            }
            60% {
                transform: translate(-10px, -20px) scale(1);
            }
            70% {
                transform: translate(5px, 5px) scale(1);
            }
            80% {
                transform: translate(-5px, -5px) scale(1);
            }
            90% {
                transform: translate(0, 0) scale(1);
            }
            100% {
                transform: translate(0, 0) scale(1.3);
                filter: drop-shadow(0 0 15px rgba(0, 245, 255, 0.5));
            }
        }


        .neon-text {
        animation: neonRisePulse 4s ease-in-out forwards;
        }

        @keyframes neonRisePulse {
            0% {
                transform: translateY(30px); /* يبدأ تحت */
                opacity: 0;
                text-shadow: none;
                color: transparent;
            }
            40% {
                transform: translateY(0);
                opacity: 1;
                color: #00f5ff;
                text-shadow:
                0 0 5px #00f5ff,
                0 0 10px #00f5ff,
                0 0 20px #00ffff,
                0 0 40px #00ffff;
            }
            60% {
                /* يبدأ النيون يتوهج */
                text-shadow:
                0 0 10px #00ffff,
                0 0 20px #00ffff,
                0 0 30px #00f5ff,
                0 0 60px #00f5ff;
                color: #a0ffff;
            }
            80% {
                /* توهج أكثر */
                text-shadow:
                0 0 5px #00f5ff,
                0 0 15px #00ffff,
                0 0 25px #00f5ff,
                0 0 50px #00ffff;
                color: #00f5ff;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
                text-shadow:
                0 0 5px #00f5ff,
                0 0 10px #00f5ff,
                0 0 20px #00ffff,
                0 0 40px #00ffff;
                color: #00f5ff;
            }
        }





        /* دخول من الاسفل */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        // بعد 6 ثواني يتم الانتقال للصفحة الرئيسية (index.php)
        setTimeout(() => {
            window.location.href = 'lobby.php';
        }, 4500);
    </script>
</head>
<body>
    <div class="container">
        <div class="logo"></div>
        <div class="by-text">by Omar Amer</div>
    </div>
</body>
</html>
