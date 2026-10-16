
<?php
require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';
$categories = [];
$suppliers = [];

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT id, name FROM suppliers ORDER BY name");
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Data kategori atau pemasok gagal dimuat. Periksa database.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $categoryId = filter_var(
        $_POST['category_id'] ?? '',
        FILTER_VALIDATE_INT
    );
    $price = filter_var(
        $_POST['price'] ?? '',
        FILTER_VALIDATE_FLOAT
    );
    $stock = filter_var(
        $_POST['stock'] ?? '',
        FILTER_VALIDATE_INT
    );
    $minimumStock = filter_var(
        $_POST['minimum_stock'] ?? '',
        FILTER_VALIDATE_INT
    );

    $supplierInput = trim($_POST['supplier_id'] ?? '');
    $supplierPriceInput = trim($_POST['supplier_price'] ?? '');

    $supplierId = null;
    $supplierPrice = null;

    if ($supplierInput !== '') {
        $supplierId = filter_var($supplierInput, FILTER_VALIDATE_INT);
    }

    if ($supplierPriceInput !== '') {
        $supplierPrice = filter_var(
            $supplierPriceInput,
            FILTER_VALIDATE_FLOAT
        );
    }

    if (
        $sku === '' ||
        $name === '' ||
        $categoryId === false ||
        $categoryId === null ||
        $categoryId <= 0 ||
        $price === false ||
        $price === null ||
        $price < 0 ||
        $stock === false ||
        $stock === null ||
        $stock < 0 ||
        $minimumStock === false ||
        $minimumStock === null ||
        $minimumStock < 0
    ) {
        $error = 'Lengkapi semua data produk dengan benar.';
    } elseif (
        $supplierInput !== '' &&
        ($supplierId === false || $supplierId <= 0)
    ) {
        $error = 'Pemasok yang dipilih tidak valid.';
    } elseif (
        $supplierInput !== '' &&
        (
            $supplierPriceInput === '' ||
            $supplierPrice === false ||
            $supplierPrice === null ||
            $supplierPrice < 0
        )
    ) {
        $error = 'Masukkan harga pemasok berupa angka nol atau lebih.';
    } elseif (
        $supplierInput === '' &&
        $supplierPriceInput !== ''
    ) {
        $error = 'Pilih pemasok terlebih dahulu.';
    } else {
        try {
            $check = $pdo->prepare(
                "SELECT COUNT(*) FROM categories WHERE id = ?"
            );
            $check->execute([$categoryId]);

            if ((int) $check->fetchColumn() === 0) {
                throw new InvalidArgumentException(
                    'Kategori tidak ditemukan.'
                );
            }

            if ($supplierId !== null) {
                $check = $pdo->prepare(
                    "SELECT COUNT(*) FROM suppliers WHERE id = ?"
                );
                $check->execute([$supplierId]);

                if ((int) $check->fetchColumn() === 0) {
                    throw new InvalidArgumentException(
                        'Pemasok tidak ditemukan.'
                    );
                }
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO products
                (category_id, sku, name, price, stock, minimum_stock)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $categoryId,
                $sku,
                $name,
                $price,
                $stock,
                $minimumStock
            ]);

            $productId = (int) $pdo->lastInsertId();

            if ($supplierId !== null) {
                $stmt = $pdo->prepare(
                    "INSERT INTO product_suppliers
                    (product_id, supplier_id, supplier_price)
                    VALUES (?, ?, ?)"
                );

                $stmt->execute([
                    $productId,
                    $supplierId,
                    $supplierPrice
                ]);
            }

            $pdo->commit();

            header('Location: index.php?created=1');
            exit;
        } catch (InvalidArgumentException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            if ($e->getCode() === '23000') {
                $error = 'SKU sudah digunakan atau data terkait tidak valid.';
            } else {
                $error = 'Produk gagal disimpan. Periksa struktur tabel database.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Produk - TasyaShop</title>

    <style>
        :root {
            --blue: #455a70;
            --blue-dark: #34465a;
            --page: #f4f7fa;
            --text: #263746;
            --muted: #718096;
            --border: #dce4ec;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--page);
            color: var(--text);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .container {
            width: min(1000px, calc(100% - 32px));
            margin: 38px auto;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
        }

        .eyebrow {
            color: var(--blue);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
        }

        h1 {
            margin: 8px 0;
            color: var(--blue-dark);
            font-size: clamp(26px, 4vw, 34px);
        }

        .muted {
            color: var(--muted);
            line-height: 1.6;
        }

        .card {
            padding: 30px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(52, 70, 90, .06);
        }

        h2 {
            margin-top: 0;
            color: var(--blue-dark);
            font-size: 21px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .field label {
            font-size: 14px;
            font-weight: 650;
        }

        input, select {
            width: 100%;
            min-height: 45px;
            padding: 11px 13px;
            border: 1px solid #ccd7e2;
            border-radius: 9px;
            background: #fbfcfe;
            color: var(--text);
            font: inherit;
        }

        input:focus, select:focus {
            outline: 2px solid #aabaca;
            border-color: var(--blue);
        }

        .section-title {
            grid-column: 1 / -1;
            margin: 6px 0 -5px;
            padding-top: 18px;
            border-top: 1px solid #edf1f5;
            color: var(--blue-dark);
        }

        .alert {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 10px;
            line-height: 1.6;
        }

        .error {
            color: #923b42;
            background: #fff0f0;
            border: 1px solid #f1c7ca;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #edf1f5;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 43px;
            padding: 11px 18px;
            border: 1px solid #ccd7e2;
            border-radius: 9px;
            background: white;
            color: var(--blue-dark);
            text-decoration: none;
            font: inherit;
            font-size: 14px;
            font-weight: 650;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--blue);
            border-color: var(--blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--blue-dark);
        }

        .help {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 650px) {
            .container {
                margin: 22px auto;
            }

            .header {
                align-items: flex-start;
                flex-direction: column;
            }

            .card {
                padding: 21px 16px;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .actions {
                flex-direction: column-reverse;
            }

            .actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<main class="container">
    <header class="header">
        <div>
            <p class="eyebrow">TASYASHOP · INVENTORY</p>
            <h1>Tambah Produk</h1>
            <p class="muted">
                Tambahkan produk baru ke dalam inventaris toko.
            </p>
        </div>

        <a href="index.php" class="btn">← Kembali ke Produk</a>
    </header>

    <?php if ($error !== ''): ?>
        <div class="alert error" role="alert">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!$categories): ?>
        <div class="alert error">
            Belum ada kategori. Tambahkan kategori terlebih dahulu.
        </div>
    <?php endif; ?>

    <section class="card">
        <h2>Informasi Produk</h2>
        <p class="muted">
            Isi data produk. Kolom bertanda * wajib diisi.
        </p>

        <form method="POST" action="">
            <div class="grid">
                <div class="field">
                    <label for="sku">SKU / Kode Produk *</label>
                    <input
                        type="text"
                        id="sku"
                        name="sku"
                        maxlength="50"
                        placeholder="Contoh: TSY-001"
                        value="<?= e($_POST['sku'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="name">Nama Produk *</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="150"
                        placeholder="Masukkan nama produk"
                        value="<?= e($_POST['name'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="category_id">Kategori *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Pilih kategori --</option>
                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= (int) $category['id'] ?>"
                                <?= (string)($_POST['category_id'] ?? '') ===
                                    (string)$category['id'] ? 'selected' : '' ?>
                            >
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="price">Harga Jual (Rp) *</label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        placeholder="Contoh: 50000"
                        value="<?= e($_POST['price'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="stock">Jumlah Stok *</label>
                    <input
                        type="number"
                        id="stock"
                        name="stock"
                        min="0"
                        step="1"
                        placeholder="Contoh: 10"
                        value="<?= e($_POST['stock'] ?? '0') ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="minimum_stock">Batas Minimum Stok *</label>
                    <input
                        type="number"
                        id="minimum_stock"
                        name="minimum_stock"
                        min="0"
                        step="1"
                        placeholder="Contoh: 3"
                        value="<?= e($_POST['minimum_stock'] ?? '0') ?>"
                        required
                    >
                </div>

                <h3 class="section-title">Informasi Pemasok (Opsional)</h3>

                <div class="field">
                    <label for="supplier_id">Pemasok</label>
                    <select id="supplier_id" name="supplier_id">
                        <option value="">-- Tanpa pemasok --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option
                                value="<?= (int) $supplier['id'] ?>"
                                <?= (string)($_POST['supplier_id'] ?? '') ===
                                    (string)$supplier['id'] ? 'selected' : '' ?>
                            >
                                <?= e($supplier['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="help">Boleh dikosongkan jika belum ada pemasok.</p>
                </div>

                <div class="field">
                    <label for="supplier_price">Harga dari Pemasok (Rp)</label>
                    <input
                        type="number"
                        id="supplier_price"
                        name="supplier_price"
                        min="0"
                        step="0.01"
                        placeholder="Contoh: 30000"
                        value="<?= e($_POST['supplier_price'] ?? '') ?>"
                    >
                    <p class="help">
                        Isi jika pemasok dipilih.
                    </p>
                </div>
            </div>

            <div class="actions">
                <a href="index.php" class="btn">Batal</a>
                <button
                    type="submit"
                    class="btn btn-primary"
                    <?= !$categories ? 'disabled' : '' ?>
                >
                    Simpan Produk
                </button>
            </div>
        </form>
    </section>
</main>

<script>
const supplierSelect = document.getElementById('supplier_id');
const supplierPrice = document.getElementById('supplier_price');

function updateSupplierPrice() {
    const selected = supplierSelect.value !== '';
    supplierPrice.disabled = !selected;

    if (!selected) {
        supplierPrice.value = '';
    }
}

supplierSelect.addEventListener('change', updateSupplierPrice);
updateSupplierPrice();
</script>
</body>
</html>
