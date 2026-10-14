
<?php
require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

/* Pesan setelah proses hapus atau edit */
if (isset($_GET['deleted'])) {
    $success = 'Produk berhasil dihapus!';
} elseif (isset($_GET['error'])) {
    $error = 'Produk gagal dihapus atau tidak ditemukan.';
} elseif (isset($_GET['updated'])) {
    $success = 'Produk berhasil diperbarui!';
}

/* Ambil daftar produk dari database */
try {
    $sql = "SELECT
                p.id,
                p.sku,
                p.name,
                p.price,
                p.stock,
                p.minimum_stock,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.id DESC";

    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $products = [];
    $error = 'Gagal mengambil data produk dari database.';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Produk - TasyaShop</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <style>
        .product-container {
            padding: 25px;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .product-header h1 {
            margin: 0;
            color: #4b267b;
        }

        .btn {
            display: inline-block;
            border: none;
            border-radius: 6px;
            padding: 9px 14px;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-add {
            background: #6c3bb5;
        }

        .btn-edit {
            background: #3182ce;
        }

        .btn-delete {
            background: #dc3545;
        }

        .btn:hover {
            opacity: 0.85;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            background: white;
            border-radius: 8px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th,
        .product-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
            white-space: nowrap;
        }

        .product-table th {
            background: #6c3bb5;
            color: white;
        }

        .product-table tr:hover {
            background: #f8f5fc;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-buttons form {
            margin: 0;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        .stock-low {
            color: #dc3545;
            font-weight: bold;
        }

        .empty-data {
            text-align: center;
            padding: 25px;
        }
    </style>
</head>

<body>

<div class="product-container">

    <div class="product-header">
        <h1>Data Produk TasyaShop</h1>

        <a href="create.php" class="btn btn-add">
            + Tambah Produk
        </a>
    </div>

    <!-- Pesan berhasil atau gagal -->
    <?php if ($success !== ''): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- Tabel produk -->
    <div class="table-wrapper">
        <table class="product-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Stok Minimum</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($products)): ?>

                    <?php foreach ($products as $index => $product): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>

                            <td>
                                <?= htmlspecialchars(
                                    $product['sku'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $product['name'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $product['category_name'] ?? '-',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                Rp <?= number_format(
                                    (float) $product['price'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td class="<?= (
                                (int) $product['stock']
                                <= (int) $product['minimum_stock']
                            ) ? 'stock-low' : '' ?>">
                                <?= (int) $product['stock'] ?>
                            </td>

                            <td>
                                <?= (int) $product['minimum_stock'] ?>
                            </td>

                            <td>
                                <div class="action-buttons">

                                    <!-- Tombol Edit -->
                                    <a
                                        href="edit.php?id=<?= (int) $product['id'] ?>"
                                        class="btn btn-edit"
                                    >
                                        Edit
                                    </a>

                                    <!-- Tombol Hapus -->
                                    <form
                                        method="POST"
                                        action="delete.php"
                                        onsubmit="return confirm('Yakin ingin menghapus produk ini?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="delete_id"
                                            value="<?= (int) $product['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-delete"
                                        >
                                            Hapus
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="8" class="empty-data">
                            Belum ada data produk.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
