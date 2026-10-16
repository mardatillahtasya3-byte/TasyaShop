
<?php
session_start();

if (!isset($_SESSION['app_user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$name = '';
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        $errors[] = 'Nama pemasok wajib diisi.';
    }

    if (strlen($name) > 150) {
        $errors[] = 'Nama pemasok maksimal 150 karakter.';
    }

    if (strlen($phone) > 30) {
        $errors[] = 'Nomor telepon maksimal 30 karakter.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO suppliers (name, phone, address)
                 VALUES (:name, :phone, :address)'
            );

            $stmt->execute([
                ':name' => $name,
                ':phone' => $phone !== '' ? $phone : null,
                ':address' => $address !== '' ? $address : null,
            ]);

            header('Location: index.php?success=created');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Data pemasok gagal disimpan. Silakan periksa database.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Pemasok - TasyaShop</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 35px 15px;
            background: #f0f4f8;
            color: #263746;
            font-family: "Segoe UI", Arial, sans-serif;
        }
        .container { max-width: 760px; margin: auto; }
        .brand { margin-bottom: 24px; color: #344e68; font-size: 23px; font-weight: 800; }
        .brand span { color: #71869b; font-weight: 400; }
        .card {
            padding: 32px;
            border: 1px solid #dce5ee;
            border-radius: 16px;
            background: white;
            box-shadow: 0 8px 25px rgba(40, 60, 80, .05);
        }
        h1 { margin: 0 0 10px; color: #344b63; font-size: 28px; }
        .subtitle { margin: 0 0 27px; color: #718096; line-height: 1.6; }
        .field { margin-bottom: 21px; }
        label { display: block; margin-bottom: 9px; font-size: 14px; font-weight: 650; }
        input, textarea {
            display: block;
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d1dce7;
            border-radius: 9px;
            outline: none;
            background: #f8fafc;
            color: #263746;
            font: inherit;
        }
        input:focus, textarea:focus {
            border-color: #607d98;
            box-shadow: 0 0 0 3px rgba(96, 125, 152, .13);
        }
        textarea { min-height: 115px; resize: vertical; }
        .hint { display: block; margin-top: 7px; color: #8794a3; font-size: 12px; }
        .error {
            margin-bottom: 20px;
            padding: 14px 17px;
            border: 1px solid #ffcaca;
            border-radius: 9px;
            background: #fff2f2;
            color: #b42323;
            font-size: 14px;
        }
        .error ul { margin: 8px 0 0; padding-left: 20px; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 28px; }
        .button {
            display: inline-block;
            padding: 12px 18px;
            border: 0;
            border-radius: 9px;
            background: #344e68;
            color: white;
            font: inherit;
            font-size: 14px;
            font-weight: 650;
            text-decoration: none;
            cursor: pointer;
        }
        .button:hover { background: #263b50; }
        .button-light { background: #e5ebf2; color: #344b63; }
        .button-light:hover { background: #d7e0e9; }
        @media (max-width: 500px) {
            body { padding: 20px 12px; }
            .card { padding: 22px 18px; }
            h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="brand">Tasya<span>Shop</span></div>

    <main class="card">
        <h1>Tambah Pemasok</h1>
        <p class="subtitle">
            Masukkan informasi pemasok yang menyediakan barang untuk toko kamu.
        </p>

        <?php if ($errors): ?>
            <div class="error">
                <strong>Data belum dapat disimpan:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="field">
                <label for="name">Nama Pemasok *</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="150"
                    value="<?= e($name) ?>"
                    placeholder="Contoh: PT Sumber Makmur"
                    required
                    autofocus
                >
            </div>

            <div class="field">
                <label for="phone">Nomor Telepon</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    maxlength="30"
                    value="<?= e($phone) ?>"
                    placeholder="Contoh: 081234567890"
                >
                <small class="hint">Boleh dikosongkan jika belum tersedia.</small>
            </div>

            <div class="field">
                <label for="address">Alamat Pemasok</label>
                <textarea
                    id="address"
                    name="address"
                    placeholder="Masukkan alamat pemasok..."
                ><?= e($address) ?></textarea>
            </div>

            <div class="actions">
                <button type="submit" class="button">Simpan Pemasok</button>
                <a href="index.php" class="button button-light">Batal</a>
            </div>
        </form>
    </main>
</div>
</body>
</html>
