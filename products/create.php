
<?php
require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

$stmt = $pdo->query(
    'SELECT id, name FROM categories ORDER BY name'
);
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $categoryId = filter_input(
        INPUT_POST, 'category_id', FILTER_VALIDATE_INT
    );
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $minimumStock = filter_input(
        INPUT_POST, 'minimum_stock', FILTER_VALIDATE_INT
    );

    if (
        $sku === '' || $name === '' || !$categoryId ||
        $price === false || $price === null || $price < 0 ||
        $stock === false || $stock === null || $stock < 0 ||
        $minimumStock === false || $minimumStock === null ||
        $minimumStock < 0
    ) {
        $error = 'Periksa kembali semua data yang kamu masukkan.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO products
                (category_id, sku, name, price, stock, minimum_stock)
                VALUES (?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $categoryId, $sku, $name, $price,
                $stock, $minimumStock
            ]);

            $success = 'Produk berhasil ditambahkan!';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'SKU sudah digunakan atau kategori tidak valid.';
            } else {
                error_log($e->getMessage());
                $error = 'Produk gagal disimpan. Silakan coba lagi.';
            }
        }
    }
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
    <title>Tambah Produk - Inventory App</title>
    <link rel="stylesheet" href="/inventory_app/assets/css/style.css">
</head>
<body>
    <main class="container">
        <header class="page-header">
            <div>
                <p class="eyebrow">INVENTORY MANAGEMENT</p>
                <h1>Tambah Produk</h1>
                <p>Masukkan informasi produk baru.</p>
            </div>
            <a href="index.php" class="btn btn-light">Kembali</a>
        </header>

        <?php if ($success): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!$categories): ?>
            <div class="alert error">
                Belum ada kategori. Tambahkan kategori terlebih dahulu
                melalui phpMyAdmin.
            </div>
        <?php endif; ?>

        <section class="card">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="sku">SKU / Kode Produk</label>
                        <input id="sku" name="sku" type="text"
                            maxlength="50" required
                            value="<?= e($_POST['sku'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="name">Nama Produk</label>
                        <input id="name" name="name" type="text"
                            maxlength="150" required
                            value="<?= e($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="category_id">Kategori</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Pilih kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e($category['id']) ?>"
                                    <?= (string)($_POST['category_id'] ?? '') ===
                                        (string)$category['id'] ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="price">Harga (Rp)</label>
                        <input id="price" name="price" type="number"
                            min="0" step="0.01" required
                            value="<?= e($_POST['price'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="stock">Stok Awal</label>
                        <input id="stock" name="stock" type="number"
                            min="0" step="1" required
                            value="<?= e($_POST['stock'] ?? '0') ?>">
                    </div>

                    <div class="form-group">
                        <label for="minimum_stock">Minimum Stok</label>
                        <input id="minimum_stock" name="minimum_stock"
                            type="number" min="0" step="1" required
                            value="<?= e($_POST['minimum_stock'] ?? '5') ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary"
                        <?= !$categories ? 'disabled' : '' ?>>
                        Simpan Produk
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
