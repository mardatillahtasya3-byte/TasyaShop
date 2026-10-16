
<?php
session_start();

if (empty($_SESSION['app_user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Mencegah teks dari database dianggap sebagai rumus Excel.
function csvSafe($value) {
    $value = (string) $value;

    if (preg_match('/^[\s]*[=+\-@\t\r]/', $value)) {
        return "'" . $value;
    }

    return $value;
}

$search = trim($_GET['search'] ?? '');
$categoryId = trim($_GET['category_id'] ?? '');
$isExport = isset($_GET['export']) && $_GET['export'] === 'excel';

$error = '';
$products = [];
$categories = [];

$totalProducts = 0;
$totalStock = 0;
$totalValue = 0;
$totalLowStock = 0;

try {
    // Mengambil kategori untuk filter.
    $categoryStmt = $pdo->query(
        'SELECT id, name FROM categories ORDER BY name ASC'
    );
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    // Mengambil data produk.
    $sql = "SELECT
                p.id,
                p.sku,
                p.name,
                p.price,
                p.stock,
                p.minimum_stock,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE 1 = 1";

    $params = [];

    // Pencarian nama produk atau SKU.
    if ($search !== '') {
        $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
        $keyword = '%' . $search . '%';
        $params[] = $keyword;
        $params[] = $keyword;
    }

    // Filter kategori.
    if (
        $categoryId !== ''
        && ctype_digit($categoryId)
        && (int) $categoryId > 0
    ) {
        $sql .= " AND p.category_id = ?";
        $params[] = (int) $categoryId;
    }

    $sql .= " ORDER BY p.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Menghitung ringkasan sesuai hasil pencarian dan filter.
    foreach ($products as $product) {
        $stock = (int) $product['stock'];
        $minimumStock = (int) $product['minimum_stock'];
        $price = (float) $product['price'];

        $totalProducts++;
        $totalStock += $stock;
        $totalValue += $price * $stock;

        if ($stock <= $minimumStock) {
            $totalLowStock++;
        }
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Gagal mengambil laporan dari database.';
    $products = [];
    $categories = [];
}

// EKSPOR DATA KE CSV YANG DAPAT DIBUKA DI MICROSOFT EXCEL.
if ($isExport) {
    if ($error !== '') {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $error;
        exit;
    }

    $filename = 'laporan_persediaan_tasyashop_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    // BOM agar karakter Bahasa Indonesia terbaca dengan baik di Excel.
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    // Membantu Excel mengenali pemisah kolom titik koma.
    fwrite($output, "sep=;\r\n");

    fputcsv($output, [
        'LAPORAN PERSEDIAAN TASYASHOP'
    ], ';');

    fputcsv($output, [
        'Tanggal Laporan',
        date('d-m-Y H:i:s')
    ], ';');

    fputcsv($output, [], ';');

    fputcsv($output, [
        'No.',
        'SKU',
        'Nama Produk',
        'Kategori',
        'Harga Jual (Rp)',
        'Stok',
        'Stok Minimum',
        'Nilai Persediaan (Rp)',
        'Status'
    ], ';');

    foreach ($products as $index => $product) {
        $stock = (int) $product['stock'];
        $minimumStock = (int) $product['minimum_stock'];
        $price = (float) $product['price'];
        $value = $price * $stock;

        $status = $stock <= $minimumStock
            ? 'Stok Menipis'
            : 'Tersedia';

        fputcsv($output, [
            $index + 1,
            csvSafe($product['sku'] ?? ''),
            csvSafe($product['name'] ?? ''),
            csvSafe($product['category_name'] ?? '-'),
            $price,
            $stock,
            $minimumStock,
            $value,
            $status
        ], ';');
    }

    fputcsv($output, [], ';');

    fputcsv($output, [
        'RINGKASAN',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        ''
    ], ';');

    fputcsv($output, [
        'Jumlah Jenis Produk',
        $totalProducts
    ], ';');

    fputcsv($output, [
        'Total Unit Stok',
        $totalStock
    ], ';');

    fputcsv($output, [
        'Perkiraan Nilai Persediaan (Rp)',
        $totalValue
    ], ';');

    fputcsv($output, [
        'Jumlah Produk Stok Menipis',
        $totalLowStock
    ], ';');

    fclose($output);
    exit;
}

$exportUrl = 'index.php?' . http_build_query([
    'export' => 'excel',
    'search' => $search,
    'category_id' => $categoryId
]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Persediaan - TasyaShop</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #F1F5F9;
            color: #334155;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .container {
            width: 100%;
            max-width: 1600px;
            min-height: 100vh;
            margin: auto;
            padding: 35px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 26px;
        }

        .eyebrow {
            margin: 0 0 8px;
            color: #526D8A;
            font-size: 12px;
            font-weight: 750;
            letter-spacing: 1.5px;
        }

        h1 {
            margin: 0;
            color: #34465A;
            font-size: 30px;
        }

        .description {
            margin: 9px 0 0;
            color: #718096;
            font-size: 14px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 11px 16px;
            border: none;
            border-radius: 8px;
            background: #455A70;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #34465A;
        }

        .btn-secondary {
            background: #E2E8F0;
            color: #334155;
        }

        .btn-secondary:hover {
            background: #CBD5E1;
        }

        .btn-excel {
            background: #217346;
        }

        .btn-excel:hover {
            background: #185C37;
        }

        .alert {
            padding: 14px 17px;
            margin-bottom: 20px;
            border-radius: 9px;
            font-size: 14px;
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
            margin-bottom: 20px;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            background: #fff;
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
            font-weight: 700;
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
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .summary-card {
            min-width: 0;
            padding: 21px;
            border: 1px solid #DCE4EC;
            border-radius: 13px;
            background: #fff;
            box-shadow: 0 5px 18px rgba(52, 70, 90, .035);
        }

        .summary-card p {
            margin: 0 0 12px;
            color: #718096;
            font-size: 12px;
        }

        .summary-card h2 {
            margin: 0;
            color: #34465A;
            font-size: clamp(20px, 2.2vw, 27px);
            overflow-wrap: anywhere;
        }

        .summary-card small {
            display: block;
            margin-top: 9px;
            color: #8996A4;
            font-size: 11px;
        }

        .report-card {
            overflow: hidden;
            border: 1px solid #DCE4EC;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 5px 18px rgba(52, 70, 90, .035);
        }

        .report-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 22px 24px;
            border-bottom: 1px solid #E2E8F0;
        }

        .report-heading h2 {
            margin: 0;
            color: #34465A;
            font-size: 18px;
        }

        .report-heading p {
            margin: 7px 0 0;
            color: #718096;
            font-size: 12px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px 16px;
            border-bottom: 1px solid #E2E8F0;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #344D68;
            color: #fff;
            font-size: 11px;
            letter-spacing: .4px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        td {
            color: #475569;
            font-size: 12px;
        }

        tbody tr:hover {
            background: #F8FAFC;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .sku {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 6px;
            background: #F1F5F9;
            white-space: nowrap;
        }

        .product-name {
            min-width: 150px;
            color: #1E293B;
            font-weight: 700;
        }

        .money {
            white-space: nowrap;
            font-weight: 650;
        }

        .stock-badge {
            display: inline-block;
            min-width: 35px;
            padding: 6px 10px;
            border-radius: 20px;
            background: #DCFCE7;
            color: #166534;
            text-align: center;
            font-weight: 700;
        }

        .stock-badge.low {
            background: #FEF2F2;
            color: #B91C1C;
        }

        .status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status.good {
            background: #E8F5ED;
            color: #31744D;
        }

        .status.low {
            background: #FFF1DF;
            color: #986321;
        }

        .empty {
            padding: 40px 20px;
            color: #718096;
            text-align: center;
        }

        .report-footer {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            padding: 20px 24px;
            background: #F8FAFC;
            color: #64748B;
            font-size: 12px;
        }

        .page-footer {
            padding: 22px 0;
            color: #8996A4;
            font-size: 11px;
            text-align: center;
        }

        @media (max-width: 1000px) {
            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px 12px;
            }

            h1 {
                font-size: 25px;
            }

            .header {
                align-items: flex-start;
                flex-direction: column;
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

            .summary-grid {
                gap: 10px;
            }

            .summary-card {
                padding: 15px;
            }

            th,
            td {
                padding: 12px;
            }
        }

        @media print {
            @page {
                size: landscape;
                margin: 12mm;
            }

            body {
                background: #fff;
                color: #000;
            }

            .container {
                max-width: none;
                padding: 0;
            }

            .no-print,
            .page-footer {
                display: none !important;
            }

            .header {
                margin-bottom: 15px;
            }

            .summary-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 8px;
            }

            .summary-card,
            .report-card {
                box-shadow: none;
                border: 1px solid #CBD5E1;
                break-inside: avoid;
            }

            .summary-card {
                padding: 12px;
            }

            .summary-card h2 {
                font-size: 17px;
            }

            .table-wrapper {
                overflow: visible;
            }

            th,
            td {
                padding: 7px;
            }

            th {
                background: #E2E8F0 !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-heading {
                padding: 12px;
            }

            .report-footer {
                padding: 10px 12px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <header class="header">
        <div>
            <p class="eyebrow">TASYASHOP INVENTORY</p>
            <h1>Laporan Persediaan</h1>
            <p class="description">
                Pantau jumlah stok dan perkiraan nilai persediaan produk.
            </p>
        </div>

        <div class="actions no-print">
            <a href="../public/dashboard.php" class="btn btn-secondary">
                Kembali ke Dashboard
            </a>

            <a href="<?= e($exportUrl) ?>" class="btn btn-excel">
                Ekspor Excel
            </a>

            <button
                type="button"
                class="btn"
                onclick="window.print()"
            >
                Cetak / Simpan PDF
            </button>
        </div>
    </header>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="index.php" class="filter-panel no-print">

        <div class="filter-group">
            <label for="search">Cari Produk</label>
            <input
                type="text"
                name="search"
                id="search"
                placeholder="Nama produk atau SKU..."
                value="<?= e($search) ?>"
            >
        </div>

        <div class="filter-group">
            <label for="category_id">Kategori</label>
            <select name="category_id" id="category_id">
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
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn">
            Terapkan Filter
        </button>

        <a href="index.php" class="btn btn-secondary">
            Reset
        </a>
    </form>

    <section class="summary-grid">

        <article class="summary-card">
            <p>Jumlah Jenis Produk</p>
            <h2><?= number_format($totalProducts, 0, ',', '.') ?></h2>
            <small>Produk dalam hasil laporan</small>
        </article>

        <article class="summary-card">
            <p>Total Unit Stok</p>
            <h2><?= number_format($totalStock, 0, ',', '.') ?></h2>
            <small>Jumlah seluruh unit barang</small>
        </article>

        <article class="summary-card">
            <p>Perkiraan Nilai Persediaan</p>
            <h2>
                Rp <?= number_format($totalValue, 0, ',', '.') ?>
            </h2>
            <small>Berdasarkan harga jual × jumlah stok</small>
        </article>

        <article class="summary-card">
            <p>Produk Stok Menipis</p>
            <h2><?= number_format($totalLowStock, 0, ',', '.') ?></h2>
            <small>Stok sama dengan atau di bawah minimum</small>
        </article>

    </section>

    <section class="report-card">

        <div class="report-heading">
            <div>
                <h2>Detail Persediaan Produk</h2>
                <p>
                    Tanggal laporan:
                    <?= date('d-m-Y H:i') ?>
                </p>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Stok Minimum</th>
                        <th>Nilai Persediaan</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($products)): ?>

                        <?php foreach ($products as $index => $product): ?>
                            <?php
                                $stock = (int) $product['stock'];
                                $minimumStock = (int) $product['minimum_stock'];
                                $price = (float) $product['price'];
                                $value = $price * $stock;
                                $isLow = $stock <= $minimumStock;
                            ?>

                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td>
                                    <span class="sku">
                                        <?= e($product['sku']) ?>
                                    </span>
                                </td>

                                <td class="product-name">
                                    <?= e($product['name']) ?>
                                </td>

                                <td>
                                    <?= e($product['category_name'] ?? '-') ?>
                                </td>

                                <td class="money">
                                    Rp <?= number_format($price, 0, ',', '.') ?>
                                </td>

                                <td>
                                    <span class="stock-badge <?= $isLow ? 'low' : '' ?>">
                                        <?= number_format($stock, 0, ',', '.') ?>
                                    </span>
                                </td>

                                <td>
                                    <?= number_format($minimumStock, 0, ',', '.') ?>
                                </td>

                                <td class="money">
                                    Rp <?= number_format($value, 0, ',', '.') ?>
                                </td>

                                <td>
                                    <?php if ($isLow): ?>
                                        <span class="status low">Stok Menipis</span>
                                    <?php else: ?>
                                        <span class="status good">Tersedia</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="empty">
                                <?php if ($search !== '' || $categoryId !== ''): ?>
                                    Tidak ada produk yang cocok dengan filter.
                                <?php else: ?>
                                    Belum ada produk untuk ditampilkan dalam laporan.
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="report-footer">
            <span>
                Total <?= number_format($totalProducts, 0, ',', '.') ?>
                jenis produk
            </span>
            <span>
                Nilai dihitung berdasarkan harga jual dan jumlah stok.
            </span>
        </div>

    </section>

    <footer class="page-footer">
        &copy; <?= date('Y') ?> TasyaShop — Inventory Management System
    </footer>

</div>
</body>
</html>
