
<?php
require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';
$search = trim($_GET['search'] ?? '');
$categoryId = trim($_GET['category_id'] ?? '');

if (isset($_GET['deleted'])) {
    $success = 'Produk berhasil dihapus!';
} elseif (isset($_GET['updated'])) {
    $success = 'Produk berhasil diperbarui!';
} elseif (isset($_GET['error'])) {
    $error = 'Produk gagal dihapus atau tidak ditemukan.';
}

$products = [];
$categories = [];

try {
    // Mengambil daftar kategori untuk filter
    $categoryStmt = $pdo->query(
        'SELECT id, name FROM categories ORDER BY name ASC'
    );
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    // Mengambil produk sekaligus informasi pemasok dan harganya
    $sql = "SELECT
                p.id,
                p.sku,
                p.name,
                p.price,
                p.stock,
                p.minimum_stock,
                c.name AS category_name,
                COALESCE(sp.supplier_info, '-') AS supplier_info
            FROM products p
            LEFT JOIN categories c
                ON p.category_id = c.id
            LEFT JOIN (
                SELECT
                    ps.product_id,
                    GROUP_CONCAT(
                        CONCAT(
                            s.name,
                            ' (Rp ',
                            CASE
                                WHEN ps.supplier_price IS NULL
                                    THEN 'belum diisi'
                                ELSE REPLACE(
                                    FORMAT(ps.supplier_price, 0),
                                    ',',
                                    '.'
                                )
                            END,
                            ')'
                        )
                        ORDER BY s.name
                        SEPARATOR ', '
                    ) AS supplier_info
                FROM product_suppliers ps
                INNER JOIN suppliers s
                    ON s.id = ps.supplier_id
                GROUP BY ps.product_id
            ) sp ON sp.product_id = p.id
            WHERE 1 = 1";

    $params = [];

    // Pencarian berdasarkan nama produk atau SKU
    if ($search !== '') {
        $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
        $keyword = '%' . $search . '%';
        $params[] = $keyword;
        $params[] = $keyword;
    }

    // Filter berdasarkan kategori
    if (
        $categoryId !== ''
        && ctype_digit($categoryId)
        && (int) $categoryId > 0
    ) {
        $sql .= " AND p.category_id = ?";
        $params[] = (int) $categoryId;
    }

    $sql .= " ORDER BY p.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $products = [];
    $categories = [];
    $error = 'Gagal mengambil data dari database. Periksa struktur tabel.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Produk - TasyaShop</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #F1F5F9;
            color: #334155;
        }

        .product-container {
            width: 100%;
            max-width: 1600px;
            min-height: 100vh;
            margin: auto;
            padding: 35px;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 28px;
        }

        .page-label {
            margin: 0 0 8px;
            color: #526D8A;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .product-header h1 {
            margin: 0;
            color: #1E293B;
            font-size: 30px;
        }

        .page-description {
            margin: 9px 0 0;
            color: #64748B;
            font-size: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            color: #FFFFFF;
            text-decoration: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            transition: background .2s, transform .2s;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-add,
        .btn-filter {
            background: #344D68;
        }

        .btn-add:hover,
        .btn-filter:hover {
            background: #263B53;
        }

        .btn-edit {
            background: #64748B;
        }

        .btn-edit:hover {
            background: #475569;
        }

        .btn-delete {
            background: #DC2626;
        }

        .btn-delete:hover {
            background: #B91C1C;
        }

        .btn-reset {
            min-height: 42px;
            background: #E2E8F0;
            color: #334155;
        }

        .btn-reset:hover {
            background: #CBD5E1;
        }

        .alert {
            padding: 14px 17px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            color: #166534;
        }

        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
        }

        .filter-panel {
            display: flex;
            align-items: end;
            flex-wrap: wrap;
            gap: 12px;
            padding: 20px;
            margin-bottom: 18px;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
        }

        .filter-group {
            display: flex;
            flex: 1;
            min-width: 180px;
            flex-direction: column;
            gap: 7px;
        }

        .filter-group label {
            color: #334155;
            font-size: 12px;
            font-weight: bold;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            background: #F8FAFC;
            color: #334155;
            font-size: 13px;
            outline: none;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #647F9E;
            box-shadow: 0 0 0 3px rgba(100, 127, 158, .13);
        }

        .btn-filter {
            min-height: 42px;
        }

        .product-summary {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px 24px;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px 14px 0 0;
        }

        .summary-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            border-radius: 12px;
            background: #E2E8F0;
            color: #344D68;
            font-size: 22px;
        }

        .product-summary h2 {
            margin: 0 0 5px;
            color: #334155;
            font-size: 17px;
        }

        .product-summary p {
            margin: 0;
            color: #64748B;
            font-size: 12px;
        }

        .product-count {
            margin-left: auto;
            padding: 7px 12px;
            border-radius: 20px;
            background: #E8EEF5;
            color: #344D68;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-top: none;
            border-radius: 0 0 14px 14px;
            box-shadow: 0 6px 20px rgba(51, 65, 85, .05);
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th,
        .product-table td {
            padding: 16px 18px;
            border-bottom: 1px solid #E2E8F0;
            text-align: left;
            vertical-align: middle;
        }

        .product-table th {
            background: #344D68;
            color: #FFFFFF;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }

        .product-table td {
            color: #475569;
            font-size: 13px;
        }

        .product-table tbody tr:hover {
            background: #F8FAFC;
        }

        .product-table tbody tr:last-child td {
            border-bottom: none;
        }

        .product-name {
            color: #1E293B !important;
            font-weight: bold;
            min-width: 150px;
        }

        .sku-badge {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 6px;
            background: #F1F5F9;
            color: #475569;
            font-size: 12px;
            white-space: nowrap;
        }

        .price {
            color: #344D68 !important;
            font-weight: bold;
            white-space: nowrap;
        }

        .supplier-info {
            min-width: 210px;
            max-width: 320px;
            white-space: normal !important;
            line-height: 1.7;
        }

        .stock-badge {
            display: inline-block;
            min-width: 35px;
            padding: 6px 10px;
            border-radius: 20px;
            background: #DCFCE7;
            color: #166534;
            text-align: center;
            font-weight: bold;
        }

        .stock-badge.low {
            background: #FEF2F2;
            color: #B91C1C;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .action-buttons form {
            margin: 0;
        }

        .empty-data {
            padding: 45px 20px !important;
            color: #64748B !important;
            text-align: center !important;
            white-space: normal !important;
        }

        .product-footer {
            padding: 22px 0;
            color: #64748B;
            text-align: center;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .product-container {
                padding: 20px 12px;
            }

            .product-header h1 {
                font-size: 24px;
            }

            .product-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .product-summary {
                padding: 16px;
            }

            .product-table th,
            .product-table td {
                padding: 13px;
            }

            .filter-panel {
                align-items: stretch;
                flex-direction: column;
            }

            .filter-group {
                min-width: 0;
            }

            .filter-panel .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
<div class="product-container">

    <header class="product-header">
        <div>
            <p class="page-label">TasyaShop Inventory</p>
            <h1>Data Produk</h1>
            <p class="page-description">
                Kelola produk, pemasok, dan pantau persediaan toko kamu.
            </p>
        </div>

        <a href="create.php" class="btn btn-add">
            + Tambah Produk
        </a>
    </header>

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

    <form method="GET" action="index.php" class="filter-panel">

        <div class="filter-group">
            <label for="search">Cari Produk</label>
            <input
                type="text"
                id="search"
                name="search"
                placeholder="Nama produk atau SKU..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="filter-group">
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id">
                <option value="">Semua Kategori</option>

                <?php foreach ($categories as $category): ?>
                    <option
                        value="<?= (int) $category['id'] ?>"
                        <?= (
                            $categoryId !== ''
                            && ctype_digit($categoryId)
                            && (int) $categoryId === (int) $category['id']
                        ) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars(
                            $category['name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-filter">
            Cari
        </button>

        <a href="index.php" class="btn btn-reset">
            Reset
        </a>
    </form>

    <section class="product-summary">
        <div class="summary-icon">▦</div>

        <div>
            <h2>Daftar Produk</h2>
            <p>
                Informasi produk, pemasok, harga pemasok, dan persediaan.
            </p>
        </div>

        <span class="product-count">
            <?= count($products) ?> Produk
        </span>
    </section>

    <div class="table-wrapper">
        <table class="product-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga Jual</th>
                    <th>Pemasok &amp; Harga Pemasok</th>
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
                                <span class="sku-badge">
                                    <?= htmlspecialchars(
                                        $product['sku'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>

                            <td class="product-name">
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

                            <td class="price">
                                Rp <?= number_format(
                                    (float) $product['price'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td class="supplier-info">
                                <?= htmlspecialchars(
                                    $product['supplier_info'] ?? '-',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <span class="stock-badge <?= (
                                    (int) $product['stock']
                                    <= (int) $product['minimum_stock']
                                ) ? 'low' : '' ?>">
                                    <?= (int) $product['stock'] ?>
                                </span>
                            </td>

                            <td>
                                <?= (int) $product['minimum_stock'] ?>
                            </td>

                            <td>
                                <div class="action-buttons">

                                    <a
                                        href="edit.php?id=<?= (int) $product['id'] ?>"
                                        class="btn btn-edit"
                                    >
                                        Edit
                                    </a>

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
                        <td colspan="9" class="empty-data">
                            <?php if ($search !== '' || $categoryId !== ''): ?>
                                Produk tidak ditemukan. Coba ubah kata
                                pencarian atau pilih kategori lain.
                            <?php else: ?>
                                Belum ada data produk. Klik tombol
                                "Tambah Produk" untuk menambahkan
                                produk pertama.
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <footer class="product-footer">
        &copy; <?= date('Y') ?> TasyaShop — Inventory Management System
    </footer>

</div>
</body>
</html>
