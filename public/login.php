
<?php
session_start();

if (isset($_SESSION['app_user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT id, username, password_hash
         FROM app_users
         WHERE username = ?'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['app_user_id'] = $user['id'];
        $_SESSION['app_username'] = $user['username'];

        header('Location: dashboard.php');
        exit;
    }

    $error = 'Username atau password salah.';
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | TasyaShop</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body.login-page {
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e9eef5, #d5dfeb, #edf1f6);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 460px;
            padding: 38px 34px;
            border: 1px solid #e1e7ef;
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 18px 50px rgba(39, 61, 86, 0.13);
        }

        .login-logo {
            width: 82px;
            height: 82px;
            margin: 0 auto 23px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 24px;
            background: linear-gradient(135deg, #344d68, #627f9e);
            color: #ffffff;
            font-size: 43px;
            font-weight: 800;
            box-shadow: 0 8px 20px rgba(52, 77, 104, 0.25);
        }

        .login-eyebrow {
            margin: 0 0 11px;
            color: #718096;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .login-card h1 {
            margin: 0;
            color: #26394f;
            text-align: center;
            font-size: 30px;
        }

        .login-description {
            margin: 13px 0 27px;
            color: #778497;
            text-align: center;
            font-size: 14px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 19px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #34465b;
            font-size: 13px;
            font-weight: 700;
        }

        .form-group input {
            width: 100%;
            min-height: 47px;
            padding: 12px 14px;
            border: 1px solid #d5deea;
            border-radius: 10px;
            background: #f9fbfd;
            color: #293b50;
            font-size: 14px;
            outline: none;
        }

        .form-group input:focus {
            border-color: #607f9f;
            box-shadow: 0 0 0 3px rgba(96, 127, 159, 0.15);
            background: #ffffff;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 82px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #526d8a;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .login-button {
            width: 100%;
            min-height: 48px;
            margin-top: 5px;
            border: none;
            border-radius: 10px;
            background: #344d68;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .login-button:hover {
            background: #263b53;
        }

        .alert-error {
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid #f2caca;
            border-radius: 9px;
            background: #fff1f1;
            color: #a83232;
            font-size: 13px;
        }

        .login-footer {
            margin: 25px 0 0;
            padding-top: 18px;
            border-top: 1px solid #edf0f4;
            color: #8793a3;
            text-align: center;
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 22px;
            }

            .login-card h1 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body class="login-page">
    <main class="login-card">

        <div class="login-logo">T</div>

        <p class="login-eyebrow">
            INVENTORY MANAGEMENT SYSTEM
        </p>

        <h1>Selamat Datang!</h1>

        <p class="login-description">
            Masuk ke TasyaShop untuk mengelola inventaris kamu.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert-error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    autocomplete="username"
                    placeholder="Masukkan username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <div class="password-wrapper">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Masukkan password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        id="togglePassword"
                    >
                        Lihat
                    </button>
                </div>
            </div>

            <button type="submit" class="login-button">
                Masuk ke Dashboard
            </button>
        </form>

        <p class="login-footer">
            TasyaShop &copy; <?= date('Y') ?> · Kelola stok lebih mudah
        </p>

    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword.addEventListener('click', function () {
            const hidden = passwordInput.type === 'password';

            passwordInput.type = hidden ? 'text' : 'password';
            togglePassword.textContent = hidden ? 'Sembunyi' : 'Lihat';
        });
    </script>
</body>
</html>
