```php
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

/* Ambil kategori dan data produk */
try {
    $categoryStmt = $pdo->query(
        'SELECT id, name FROM categories ORDER BY name ASC'
    );
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

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

            if ($e->getCode() === '23000') {
                $error = 'SKU sudah digunakan oleh produk lain atau data kategori tidak valid.';
            } else {
                $error = 'Gagal menyimpan perubahan. Silakan coba lagi.';
            }
        }
    }
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
    <title>Edit Produk - TasyaShop</title>

    <link rel="stylesheet" href="/inventory_app/assets/css/style.css">

    <style>
        :root {
            --blue-grey: #455a70;
            --blue-grey-dark: #34465a;
            --blue-grey-light: #eaf0f5;
            --page-bg: #f4f7fa;
            --text-main: #263746;
            --text-muted: #718096;
            --border-color: #dce4ec;
            --white: #ffffff;
        }

        body {
            margin: 0;
            background: var(--page-bg);
            color: var(--text-main);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .edit-page {
            width: min(100% - 36px, 1000px);
            margin: 38px auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 26px;
        }

        .eyebrow {
            margin: 0 0 8px;
            color: var(--blue-grey);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .page-header h1 {
            margin: 0;
            color: var(--blue-grey-dark);
            font-size: clamp(25px, 4vw, 32px);
        }

        .page-description {
            margin: 9px 0 0;
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .edit-card {
            padding: 30px;
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(52, 70, 90, 0.06);
        }

        .form-heading {
            margin: 0 0 6px;
            color: var(--blue-grey-dark);
            font-size: 20px;
        }

        .form-subheading {
            margin: 0 0 26px;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 23px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 9px;
            min-width: 0;
        }

        .form-group label {
            color: #405365;
            font-size: 13px;
            font-weight: 650;
        }

        .form-group input,
        .form-group select {
            box-sizing: border-box;
            width: 100%;
            min-height: 46px;
            padding: 11px 13px;
            border: 1px solid #ccd7e2;
            border-radius: 9px;
            background: #fbfcfe;
            color: var(--text-main);
            font: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--blue-grey);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(69, 90, 112, 0.12);
        }

        .field-help {
            margin: -3px 0 0;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .form-note {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-top: 24px;
            padding: 13px 15px;
            border-radius: 9px;
            background: #f2f6f9;
            color: #536779;
            font-size: 12px;
            line-height: 1.7;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 30px;
            padding-top: 23px;
            border-top: 1px solid #edf1f5;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            min-height: 42px;
            padding: 10px 18px;
            border: 1px solid transparent;
            border-radius: 9px;
            font: inherit;
            font-size: 13px;
            font-weight: 650;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-back,
        .btn-cancel {
            color: var(--blue-grey-dark);
            background: var(--white);
            border-color: #ccd7e2;
        }

        .btn-back:hover,
        .btn-cancel:hover {
            background: var(--blue-grey-light);
        }

        .btn-save {
            color: var(--white);
            background: var(--blue-grey);
        }

        .btn-save:hover {
            background: var(--blue-grey-dark);
        }

        .alert-error {
            margin-bottom: 20px;
            padding: 14px 16px;
            border: 1px solid #f1c7ca;
            border-radius: 10px;
            background: #fff0f0;
            color: #923b42;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 650px) {
            .edit-page {
                width: calc(100% - 24px);
                margin: 22px auto;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .edit-card {
                padding: 20px 16px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 19px;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
<main class="edit-page">
    <header class="page-header">
        <div>
            <p class="eyebrow">TASYASHOP · INVENTORY</p>
            <h1>Edit Produk</h1>
            <p class="page-description">
                Perbarui informasi produk yang tersimpan dalam inventaris.
            </p>
        </div>

        <a href="index.php" class="btn btn-back">
            &larr; Kembali ke Produk
        </a>
    </header>

    <?php if ($error !== ''): ?>
        <div class="alert-error" role="alert">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <section class="edit-card">
        <h2 class="form-heading">Informasi Produk</h2>
        <p class="form-subheading">
            Ubah informasi yang diperlukan, kemudian simpan perubahan.
        </p>

        <form method="POST" action="edit.php?id=<?= (int) $id ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label for="sku">SKU / Kode Produk *</label>
                    <input
                        type="text"
                        id="sku"
                        name="sku"
                        maxlength="50"
                        autocomplete="off"
                        value="<?= e($product['sku']) ?>"
                        required
                    >
                    <p class="field-help">
                        Kode unik yang digunakan untuk mengenali produk.
                    </p>
                </div>

                <div class="form-group">
                    <label for="name">Nama Produk *</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="150"
                        value="<?= e($product['name']) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="category_id">Kategori *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Pilih kategori</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= (int) $category['id'] ?>"
                                <?= (string) $product['category_id'] ===
                                    (string) $category['id'] ? 'selected' : '' ?>
                            >
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Harga Produk (Rp) *</label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="any"
                        value="<?= e($product['price']) ?>"
                        required
                    >
                    <p class="field-help">
                        Harga tidak boleh kurang dari nol.
                    </p>
                </div>

                <div class="form-group">
                    <label for="stock">Stok Saat Ini *</label>
                    <input
                        type="number"
                        id="stock"
                        name="stock"
                        min="0"
                        step="1"
                        value="<?= e($product['stock']) ?>"
                        required
                    >
                    <p class="field-help">
                        Masukkan jumlah barang yang tersedia.
                    </p>
                </div>

                <div class="form-group">
                    <label for="minimum_stock">Minimum Stok *</label>
                    <input
                        type="number"
                        id="minimum_stock"
                        name="minimum_stock"
                        min="0"
                        step="1"
                        value="<?= e($product['minimum_stock']) ?>"
                        required
                    >
                    <p class="field-help">
                        Batas minimum untuk pemantauan persediaan.
                    </p>
                </div>
            </div>

            <div class="form-note">
                <span aria-hidden="true">&#9432;</span>
                <span>
                    Periksa kembali perubahan sebelum menyimpan.
                    SKU harus unik, sedangkan stok dan minimum stok harus
                    berupa bilangan bulat nol atau lebih.
                </span>
            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-cancel">
                    Batal
                </a>

                <button type="submit" class="btn btn-save">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </section>
</main>
</body>
</html>
```