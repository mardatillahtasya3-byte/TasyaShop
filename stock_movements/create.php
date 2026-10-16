<?php
session_start();

if (empty($_SESSION['app_user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';
$selectedProduct = '';
$type = 'IN';
$quantity = '';
$note = '';

try {
    $productStmt = $pdo->query(
        'SELECT id, sku, name, stock
         FROM products
         ORDER BY name ASC'
    );
    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $products = [];
    $error = 'Gagal mengambil daftar produk.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $selectedProduct = trim($_POST['product_id'] ?? '');
    $type = $_POST['type'] ?? '';
    $quantity = trim($_POST['quantity'] ?? '');
    $note = trim($_POST['note'] ?? '');

    $productId = filter_var(
        $selectedProduct,
        FILTER_VALIDATE_INT
    );

    $quantityValue = filter_var(
        $quantity,
        FILTER_VALIDATE_INT
    );

    if (!$productId || $productId < 1) {
        $error = 'Silakan pilih produk.';
    } elseif (!in_array($type, ['IN', 'OUT'], true)) {
        $error = 'Jenis transaksi tidak valid.';
    } elseif (
        $quantityValue === false
        || $quantityValue < 1
    ) {
        $error = 'Jumlah barang minimal 1.';
    } elseif (strlen($note) > 255) {
        $error = 'Catatan maksimal 255 karakter.';
    } else {
        try {
            $pdo->beginTransaction();

            // Mengunci data produk selama transaksi berlangsung
            $stockStmt = $pdo->prepare(
                'SELECT stock FROM products
                 WHERE id = ? FOR UPDATE'
            );
            $stockStmt->execute([$productId]);
            $product = $stockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                $pdo->rollBack();
                $error = 'Produk tidak ditemukan.';
            } else {
                $currentStock = (int) $product['stock'];

                if (
                    $type === 'OUT'
                    && $quantityValue > $currentStock
                ) {
                    $pdo->rollBack();
                    $error = 'Stok tidak mencukupi. Stok tersedia: '
                        . $currentStock . '.';
                } else {
                    if ($type === 'IN') {
                        $newStock = $currentStock + $quantityValue;
                    } else {
                        $newStock = $currentStock - $quantityValue;
                    }

                    // Memperbarui stok produk
                    $updateStmt = $pdo->prepare(
                        'UPDATE products SET stock = ? WHERE id = ?'
                    );
                    $updateStmt->execute([$newStock, $productId]);

                    // Menyimpan riwayat pergerakan stok
                    $movementStmt = $pdo->prepare(
                        'INSERT INTO stock_movements
                            (product_id, user_id, type, quantity, note)
                         VALUES (?, ?, ?, ?, ?)'
                    );

                    $movementStmt->execute([
                        $productId,
                        (int) $_SESSION['app_user_id'],
                        $type,
                        $quantityValue,
                        $note !== '' ? $note : null
                    ]);

                    $pdo->commit();

                    header('Location: index.php?success=created');
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            $error = 'Transaksi gagal disimpan. Periksa kembali '
                . 'data database dan hubungan pengguna.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Stok - TasyaShop</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 25px;
            background: #F1F5F9;
            color: #334155;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 760px;
            margin: 20px auto;
        }

        .header { margin-bottom: 22px; }

        .label {
            color: #526D8A;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1.4px;
        }

        h1 { color: #1E293B; margin-bottom: 8px; }
        .description { color: #64748B; font-size: 14px; }

        .card {
            padding: 28px;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(51, 65, 85, .05);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
        }

        label { font-size: 13px; font-weight: bold; }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            background: #F8FAFC;
            color: #334155;
            font-size: 14px;
        }

        textarea { resize: vertical; }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #647F9E;
        }

        .buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 16px;
            border: 0;
            border-radius: 8px;
            background: #344D68;
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn:hover { background: #263B53; }

        .btn-back {
            background: #E2E8F0;
            color: #334155;
        }

        .btn-back:hover { background: #CBD5E1; }

        .alert {
            padding: 13px 15px;
            margin-bottom: 18px;
            border-radius: 8px;
            font-size: 13px;
        }

        .alert-error {
            color: #991B1B;
            background: #FEF2F2;
            border: 1px solid #FECACA;
        }

        .hint {
            margin-top: 6px;
            color: #64748B;
            font-size: 12px;
            line-height: 1.5;
        }

        @media (max-width: 600px) {
            body { padding: 12px; }
            .card { padding: 20px; }
        }
    </style>
</head>
<body>
<div class="container">

    <header class="header">
        <p class="label">TASYASHOP INVENTORY</p>
        <h1>Transaksi Stok</h1>
        <p class="description">
            Catat barang masuk atau barang keluar dari persediaan.
        </p>
    </header>

    <div class="card">

        <?php if ($error !== ''): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (empty($products)): ?>
            <p>Belum ada produk. Tambahkan produk terlebih dahulu.</p>
            <a class="btn btn-back" href="../products/index.php">
                Kembali ke Produk
            </a>
        <?php else: ?>

            <form method="POST" action="create.php">

                <div class="form-group">
                    <label for="product_id">Pilih Produk</label>
                    <select id="product_id" name="product_id" required>
                        <option value="">-- Pilih Produk --</option>

                        <?php foreach ($products as $product): ?>
                            <option
                                value="<?= (int) $product['id'] ?>"
                                <?= (string) $product['id'] === $selectedProduct
                                    ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $product['sku'] . ' - ' .
                                    $product['name'] .
                                    ' (Stok: ' . $product['stock'] . ')',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="type">Jenis Transaksi</label>
                    <select id="type" name="type" required>
                        <option value="IN" <?= $type === 'IN' ? 'selected' : '' ?>>
                            Stok Masuk
                        </option>
                        <option value="OUT" <?= $type === 'OUT' ? 'selected' : '' ?>>
                            Stok Keluar
                        </option>
                    </select>
                    <div class="hint">
                        Stok masuk menambah persediaan.
                        Stok keluar mengurangi persediaan.
                    </div>
                </div>

                <div class="form-group">
                    <label for="quantity">Jumlah Barang</label>
                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="1"
                        step="1"
                        value="<?= htmlspecialchars(
                            (string) $quantity,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="note">Catatan (Opsional)</label>
                    <textarea
                        id="note"
                        name="note"
                        rows="3"
                        maxlength="255"
                        placeholder="Contoh: Pembelian barang dari pemasok"
                    ><?= htmlspecialchars(
                        $note,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>
                </div>

                <div class="buttons">
                    <button type="submit" class="btn">
                        Simpan Transaksi
                    </button>

                    <a href="index.php" class="btn btn-back">
                        Kembali ke Riwayat
                    </a>
                </div>

            </form>
        <?php endif; ?>

    </div>
</div>
</body>
</html>