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

$error = '';
$products = [];

try {
    $products = $pdo->query(
        'SELECT id, sku, name, stock FROM products ORDER BY name ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $error = 'Data produk tidak dapat dimuat dari database.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $type = $_POST['type'] ?? '';
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $note = trim($_POST['note'] ?? '');
    $username = $_SESSION['app_username'] ?? '';

    if (!$productId || $productId < 1) {
        $error = 'Silakan pilih produk yang valid.';
    } elseif (!in_array($type, ['IN', 'OUT'], true)) {
        $error = 'Jenis transaksi tidak valid.';
    } elseif (!$quantity || $quantity < 1) {
        $error = 'Jumlah harus berupa bilangan bulat minimal 1.';
    } elseif (mb_strlen($note) > 255) {
        $error = 'Catatan maksimal 255 karakter.';
    } elseif ($username === '') {
        $error = 'Username sesi tidak ditemukan. Silakan keluar lalu login kembali.';
    } else {
        try {
            $pdo->beginTransaction();

            /*
             * PENTING:
             * stock_movements.user_id memiliki foreign key ke users.id,
             * sedangkan app_user_id berasal dari tabel app_users.
             * Maka cari ID pada tabel users menggunakan username sesi.
             */
            $userStmt = $pdo->prepare(
                'SELECT id FROM users WHERE username = ? LIMIT 1'
            );
            $userStmt->execute([$username]);
            $userId = $userStmt->fetchColumn();

            if ($userId === false) {
                throw new RuntimeException(
                    'Akun "' . $username . '" belum terdaftar pada tabel users. '
                    . 'Transaksi belum disimpan. Periksa data tabel users terlebih dahulu.'
                );
            }

            // Kunci baris produk selama transaksi agar stok tidak berubah bersamaan.
            $productStmt = $pdo->prepare(
                'SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE'
            );
            $productStmt->execute([$productId]);
            $product = $productStmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new RuntimeException('Produk tidak ditemukan.');
            }

            $currentStock = (int) $product['stock'];

            if ($type === 'OUT' && $quantity > $currentStock) {
                throw new RuntimeException(
                    'Stok tidak mencukupi. Stok saat ini: ' . $currentStock . '.'
                );
            }

            if ($type === 'IN') {
                $updateStmt = $pdo->prepare(
                    'UPDATE products SET stock = stock + ? WHERE id = ?'
                );
            } else {
                $updateStmt = $pdo->prepare(
                    'UPDATE products SET stock = stock - ? WHERE id = ?'
                );
            }
            $updateStmt->execute([$quantity, $productId]);

            $movementStmt = $pdo->prepare(
                'INSERT INTO stock_movements
                    (product_id, user_id, type, quantity, note)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $movementStmt->execute([
                $productId,
                (int) $userId,
                $type,
                $quantity,
                $note !== '' ? $note : null
            ]);

            $pdo->commit();

            header('Location: index.php?success=1');
            exit;

        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log($exception->getMessage());
            $error = 'Transaksi gagal disimpan. Periksa pesan error di log server jika masalah berlanjut.';
        }
    }
}

$usernameDisplay = $_SESSION['app_username'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catat Pergerakan Stok | TasyaShop</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 24px;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f1f5f9; color: #334155;
        }
        .container { max-width: 760px; margin: 0 auto; }
        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px; margin-bottom: 22px;
        }
        .brand { color: #344d68; font-weight: 800; font-size: 20px; text-decoration: none; }
        .muted { color: #64748b; font-size: 13px; }
        .card {
            padding: 28px; background: #fff; border: 1px solid #dce4ec;
            border-radius: 16px; box-shadow: 0 7px 22px rgba(52,70,90,.05);
        }
        h1 { margin: 0 0 8px; color: #263b53; font-size: 27px; }
        .intro { margin: 0 0 24px; color: #718096; font-size: 14px; line-height: 1.6; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 700; }
        input, select, textarea {
            width: 100%; padding: 12px 13px; border: 1px solid #cbd5e1;
            border-radius: 9px; background: #f8fafc; color: #334155;
            font: inherit; font-size: 14px;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: #647f9e;
            box-shadow: 0 0 0 3px rgba(100,127,158,.14);
        }
        textarea { min-height: 95px; resize: vertical; }
        .radio-row { display: flex; gap: 12px; flex-wrap: wrap; }
        .radio-option {
            display: flex; gap: 9px; align-items: center; flex: 1;
            min-width: 150px; padding: 13px; border: 1px solid #dce4ec;
            border-radius: 9px; background: #f8fafc; cursor: pointer;
        }
        .radio-option input { width: auto; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 22px; }
        .btn {
            display: inline-flex; justify-content: center; align-items: center;
            min-height: 43px; padding: 11px 16px; border: 0; border-radius: 9px;
            background: #344d68; color: #fff; text-decoration: none;
            font-size: 13px; font-weight: 700; cursor: pointer;
        }
        .btn:hover { background: #263b53; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; }
        .alert { padding: 13px 15px; border-radius: 9px; margin-bottom: 18px; font-size: 13px; line-height: 1.5; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .hint { margin: 7px 0 0; color: #718096; font-size: 12px; }
        @media(max-width:520px) { body { padding: 14px; } .card { padding: 20px; } h1 { font-size: 23px; } }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <a class="brand" href="../public/dashboard.php">▰ TasyaShop</a>
        <div class="muted">Login sebagai <?= e($usernameDisplay) ?></div>
    </div>

    <div class="card">
        <h1>Catat Pergerakan Stok</h1>
        <p class="intro">Catat barang masuk atau keluar. Stok produk akan diperbarui otomatis setelah transaksi berhasil disimpan.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="product_id">Pilih Produk</label>
                <select id="product_id" name="product_id" required>
                    <option value="">-- Pilih produk --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) $product['id'] ?>"
                            <?= isset($_POST['product_id']) && (string) $_POST['product_id'] === (string) $product['id'] ? 'selected' : '' ?>>
                            <?= e($product['name']) ?> (SKU: <?= e($product['sku']) ?> — stok: <?= (int) $product['stock'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Jenis Transaksi</label>
                <div class="radio-row">
                    <label class="radio-option">
                        <input type="radio" name="type" value="IN" required
                            <?= ($_POST['type'] ?? 'IN') === 'IN' ? 'checked' : '' ?>>
                        <span>＋ Barang Masuk</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="type" value="OUT" required
                            <?= ($_POST['type'] ?? '') === 'OUT' ? 'checked' : '' ?>>
                        <span>− Barang Keluar</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="quantity">Jumlah Barang</label>
                <input type="number" id="quantity" name="quantity" min="1" step="1"
                    value="<?= e($_POST['quantity'] ?? '1') ?>" required>
            </div>

            <div class="form-group">
                <label for="note">Catatan (opsional)</label>
                <textarea id="note" name="note" maxlength="255"
                    placeholder="Contoh: pembelian dari pemasok atau penjualan pelanggan"><?= e($_POST['note'] ?? '') ?></textarea>
                <p class="hint">Maksimal 255 karakter.</p>
            </div>

            <div class="actions">
                <button type="submit" class="btn">Simpan Transaksi</button>
                <a href="index.php" class="btn btn-secondary">Kembali ke Riwayat</a>
                <a href="../public/dashboard.php" class="btn btn-secondary">Dashboard</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
