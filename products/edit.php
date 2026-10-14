
<?php
require_once __DIR__ . '/../config/database.php';

$error = '';
$product = null;

/* Ambil ID produk dari URL */
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php');
    exit;
}

/* Ambil daftar kategori */
try {
    $categoryStmt = $pdo->query(
        'SELECT id, name FROM categories ORDER BY name ASC'
    );
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    /* Ambil data produk yang akan diedit */
    $stmt = $pdo->prepare(
        'SELECT id, category_id, sku, name, price, stock, minimum_stock
         FROM products
         WHERE id = ?'
    );
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: index.php?error=notfound');
        exit;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Gagal mengambil data produk. Periksa database Anda.');
}

/* Proses menyimpan perubahan */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $categoryId = filter_input(
        INPUT_POST,
        'category_id',
        FILTER_VALIDATE_INT
    );
    $priceInput = $_POST['price'] ?? '';
    $stockInput = $_POST['stock'] ?? '';
    $minimumInput = $_POST['minimum_stock'] ?? '';

    /* Pertahankan input jika validasi gagal */
    $product['sku'] = $sku;
    $product['name'] = $name;
    $product['category_id'] = $categoryId ?: '';
    $product['price'] = $priceInput;
    $product['stock'] = $stockInput;
    $product['minimum_stock'] = $minimumInput;

    if (
        $sku === '' ||
        $name === '' ||
        !$categoryId ||
        !is_numeric($priceInput) ||
        !is_numeric($stockInput) ||
        !is_numeric($minimumInput) ||
        (float) $priceInput < 0 ||
        (float) $stockInput < 0 ||
        (float) $minimumInput < 0 ||
        (float) $stockInput != (int) $stockInput ||
        (float) $minimumInput != (int) $minimumInput
    ) {
        $error = 'Isi semua kolom dengan benar. Harga tidak boleh negatif dan stok harus berupa bilangan bulat.';
    } else {
        try {
            /* Pastikan kategori tersedia */
            $checkCategory = $pdo->prepare(
                'SELECT id FROM categories WHERE id = ?'
            );
            $checkCategory->execute([$categoryId]);

            if (!$checkCategory->fetch()) {
                $error = 'Kategori yang dipilih tidak ditemukan.';
            } else {
                /* Perbarui data produk */
                $updateStmt = $pdo->prepare(
                    'UPDATE products
                     SET category_id = ?,
                         sku = ?,
                         name = ?,
                         price = ?,
                         stock = ?,
                         minimum_stock = ?
                     WHERE id = ?'
                );

                $updateStmt->execute([
                    $categoryId,
                    $sku,
                    $name,
                    $priceInput,
                    (int) $stockInput,
                    (int) $minimumInput,
                    $id
                ]);

                header('Location: index.php?updated=1');
                exit;
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Gagal menyimpan perubahan. Periksa apakah SKU sudah digunakan produk lain.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - TasyaShop</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .edit-container {
            max-width: 650px;
            margin: 35px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .edit-container h1 {
            color: #4b267b;
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 22px;
        }

        .btn {
            display: inline-block;
            border: none;
            border-radius: 6px;
            padding: 10px 16px;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-save {
            background: #6c3bb5;
        }

        .btn-cancel {
            background: #6c757d;
        }

        .alert-error {
            padding: 12px;
            margin-bottom: 15px;
            background: #f8d7da;
            color: #721c24;
            border-radius: 6px;
        }
    </style>
</head>

<body>
<div class="edit-container">
    <h1>Edit Produk</h1>

    <?php if ($error !== ''): ?>
        <div class="alert-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?= (int) $id ?>">
        <div class="form-group">
            <label for="sku">SKU</label>
            <input
                type="text"
                id="sku"
                name="sku"
                value="<?= htmlspecialchars((string) $product['sku'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="name">Nama Produk</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id" required>
                <option value="">-- Pilih Kategori --</option>

                <?php foreach ($categories as $category): ?>
                    <option
                        value="<?= (int) $category['id'] ?>"
                        <?= (string) $product['category_id'] === (string) $category['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="price">Harga</label>
            <input
                type="number"
                id="price"
                name="price"
                min="0"
                step="any"
                value="<?= htmlspecialchars((string) $product['price'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="stock">Stok</label>
            <input
                type="number"
                id="stock"
                name="stock"
                min="0"
                step="1"
                value="<?= htmlspecialchars((string) $product['stock'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="minimum_stock">Stok Minimum</label>
            <input
                type="number"
                id="minimum_stock"
                name="minimum_stock"
                min="0"
                step="1"
                value="<?= htmlspecialchars((string) $product['minimum_stock'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-save">
                Simpan Perubahan
            </button>

            <a href="index.php" class="btn btn-cancel">
                Batal
            </a>
        </div>
    </form>
</div>
</body>
</html>
