
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
         FROM app_users WHERE username = ?'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

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
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Tasya Inventory</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="login-logo">P</div>
        <p class="login-eyebrow">INVENTORY MANAGEMENT SYSTEM</p>
        <h1>Selamat Datang!</h1>
        <p class="login-description">
            Masuk untuk mengelola inventaris kamu.
        </p>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input id="username" name="username"
                    type="text" autocomplete="username"
                    placeholder="Masukkan username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password"
                    type="password" autocomplete="current-password"
                    placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn btn-primary login-button">
                Masuk ke Dashboard
            </button>
        </form>

        <p class="login-footer">Tasya Inventory · Kelola stok lebih mudah</p>
    </main>
</body>
</html>
