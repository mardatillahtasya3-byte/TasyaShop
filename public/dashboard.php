
<?php
session_start();

if (!isset($_SESSION['app_user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$totalProducts = (int) $pdo->query(
    'SELECT COUNT(*) FROM products'
)->fetchColumn();

$totalCategories = (int) $pdo->query(
    'SELECT COUNT(*) FROM categories'
)->fetchColumn();

$totalStock = (int) $pdo->query(
    'SELECT COALESCE(SUM(stock), 0) FROM products'
)->fetchColumn();

$lowStock = (int) $pdo->query(
    'SELECT COUNT(*) FROM products WHERE stock <= minimum_stock'
)->fetchColumn();

$recentProducts = $pdo->query(
    'SELECT p.sku, p.name, p.price, p.stock, p.minimum_stock,
            c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     ORDER BY p.id DESC
     LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);

$lowStockProducts = $pdo->query(
    'SELECT sku, name, stock, minimum_stock
     FROM products
     WHERE stock <= minimum_stock
     ORDER BY stock ASC, name ASC
     LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);

$username = $_SESSION['app_username'] ?? 'Administrator';
$initial = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - TasyaShop</title>
<link rel="stylesheet" href="../assets/css/style.css">

<style>
:root {
    --blue-grey: #455a70;
    --blue-grey-dark: #34465a;
    --blue-grey-light: #eaf0f5;
    --page-bg: #f4f7fa;
    --text-main: #263746;
    --text-muted: #718096;
    --border-color: #dce4ec;
    --white: #fff;
}

* {
    box-sizing: border-box;
}

body.dashboard-page {
    margin: 0;
    min-height: 100vh;
    background: var(--page-bg);
    color: var(--text-main);
    font-family: "Segoe UI", Arial, sans-serif;
}

.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 10;
    display: flex;
    flex-direction: column;
    width: 250px;
    padding: 27px 18px 20px;
    background: var(--blue-grey-dark);
    color: var(--white);
    overflow-y: auto;
}

.brand {
    display: flex;
    align-items: center;
    gap: 11px;
    margin: 0 0 38px 4px;
    color: #fff;
    font-size: 21px;
    font-weight: 750;
    text-decoration: none;
    letter-spacing: -.5px;
}

.brand-icon {
    display: grid;
    place-items: center;
    width: 39px;
    height: 39px;
    flex-shrink: 0;
    border-radius: 12px;
    background: #dbe6ef;
    color: var(--blue-grey-dark);
    font-size: 20px;
    font-weight: 800;
}

.brand-light {
    color: #bdccda;
    font-weight: 400;
}

.nav-label {
    margin: 0 0 13px 10px;
    color: #b8c6d4;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.8px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 7px;
    padding: 13px 14px;
    border-radius: 10px;
    color: #e0e8ef;
    font-size: 14px;
    text-decoration: none;
    transition: background .2s, color .2s;
}

.nav-link > span {
    width: 20px;
    flex-shrink: 0;
    text-align: center;
    font-size: 18px;
}

.nav-link:hover,
.nav-link.active {
    background: #60778d;
    color: #fff;
}

.sidebar-bottom {
    margin-top: auto;
    padding-top: 20px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 11px;
    margin-bottom: 15px;
    padding: 16px 5px;
    border-top: 1px solid rgba(255,255,255,.15);
}

.avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 39px;
    height: 39px;
    border-radius: 50%;
    background: #dbe6ef;
    color: var(--blue-grey-dark);
    font-size: 15px;
    font-weight: 800;
}

.user-info strong {
    display: block;
    max-width: 145px;
    overflow: hidden;
    color: #fff;
    font-size: 13px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-info small {
    display: block;
    margin-top: 5px;
    color: #bdccda;
    font-size: 11px;
}

.logout-link {
    color: #e5caca;
}

.dashboard-main {
    min-height: 100vh;
    margin-left: 250px;
    padding: 36px;
}

.dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 30px;
}

.eyebrow {
    margin: 0 0 8px;
    color: var(--blue-grey);
    font-size: 11px;
    font-weight: 750;
    letter-spacing: 2px;
}

.dashboard-header h1 {
    margin: 0;
    color: var(--blue-grey-dark);
    font-size: clamp(26px, 3vw, 34px);
    letter-spacing: -.8px;
}

.dashboard-header p:not(.eyebrow) {
    margin: 10px 0 0;
    color: var(--text-muted);
    font-size: 14px;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    min-height: 43px;
    padding: 11px 17px;
    border-radius: 9px;
    background: var(--blue-grey);
    color: #fff;
    font-size: 13px;
    font-weight: 650;
    text-decoration: none;
    transition: background .2s, transform .2s;
}

.btn-primary:hover {
    transform: translateY(-1px);
    background: var(--blue-grey-dark);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 24px;
}

.stat-card {
    min-width: 0;
    padding: 22px 20px;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 5px 18px rgba(52,70,90,.035);
}

.stat-icon {
    display: grid;
    place-items: center;
    width: 43px;
    height: 43px;
    margin-bottom: 19px;
    border-radius: 12px;
    background: var(--blue-grey-light);
    color: var(--blue-grey-dark);
    font-size: 21px;
    font-weight: 700;
}

.pink-icon {
    background: #f2eaf0;
    color: #89617e;
}

.blue-icon {
    background: #e4eef7;
    color: #4b6e91;
}

.orange-icon {
    background: #fff1df;
    color: #a56d2c;
}

.stat-card > p {
    margin: 0 0 9px;
    color: var(--text-muted);
    font-size: 13px;
}

.stat-card h2 {
    margin: 0;
    overflow-wrap: anywhere;
    color: var(--blue-grey-dark);
    font-size: clamp(24px, 2.2vw, 31px);
    letter-spacing: -.7px;
}

.stat-card > small {
    display: block;
    margin-top: 10px;
    color: #8996a4;
    font-size: 11px;
}

.content-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr);
    gap: 20px;
    margin-bottom: 22px;
}

.table-card {
    overflow: hidden;
    margin-bottom: 22px;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 5px 18px rgba(52,70,90,.035);
}

.content-grid .table-card {
    margin-bottom: 0;
}

.table-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 22px 24px;
    border-bottom: 1px solid #edf1f5;
}

.table-heading h2 {
    margin: 0;
    color: var(--blue-grey-dark);
    font-size: 18px;
}

.table-heading p {
    margin: 7px 0 0;
    color: var(--text-muted);
    font-size: 12px;
}

.text-link {
    color: var(--blue-grey);
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
}

.text-link:hover {
    text-decoration: underline;
}

.table-scroll {
    overflow-x: auto;
    width: 100%;
}

.inventory-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    white-space: nowrap;
}

.inventory-table th {
    padding: 14px 15px;
    background: #f8fafc;
    color: #718096;
    font-size: 10px;
    font-weight: 750;
    letter-spacing: .7px;
    text-transform: uppercase;
}

.inventory-table td {
    padding: 15px;
    border-top: 1px solid #edf1f5;
    color: #536477;
    font-size: 12px;
}

.inventory-table tbody tr:hover {
    background: #f9fbfd;
}

.inventory-table td strong {
    color: var(--text-main);
    font-weight: 650;
}

.status {
    display: inline-flex;
    align-items: center;
    padding: 6px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
}

.status.low {
    background: #fff1df;
    color: #986321;
}

.status.good {
    background: #e8f5ed;
    color: #31744d;
}

.empty-state {
    padding: 30px 20px !important;
    color: var(--text-muted) !important;
    text-align: center;
    white-space: normal;
}

.dashboard-footer {
    margin-top: 25px;
    padding: 15px 0;
    color: #8996a4;
    font-size: 11px;
    text-align: center;
}

@media (max-width: 1200px) {
    .dashboard-main {
        padding: 28px 24px;
    }

    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 760px) {
    .sidebar {
        position: relative;
        width: 100%;
        padding: 16px;
    }

    .brand {
        margin: 0 0 18px;
    }

    .nav-label {
        margin-bottom: 8px;
    }

    .nav-link {
        margin-bottom: 5px;
    }

    .sidebar-bottom {
        margin-top: 10px;
    }

    .user-info {
        margin-bottom: 5px;
        padding: 12px 5px;
    }

    .dashboard-main {
        margin-left: 0;
        padding: 23px 15px;
    }

    .dashboard-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .stats-grid {
        gap: 12px;
    }

    .stat-card {
        padding: 17px 14px;
    }

    .table-heading {
        align-items: flex-start;
        padding: 19px 16px;
    }

    .inventory-table th,
    .inventory-table td {
        padding: 12px;
    }
}

@media (max-width: 420px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .stat-card {
        padding: 18px;
    }
}
</style>
</head>

<body class="dashboard-page">

<aside class="sidebar">

    <a class="brand" href="dashboard.php">
        <span class="brand-icon">T</span>
        <span>Tasya<span class="brand-light">Shop</span></span>
    </a>

    <p class="nav-label">MENU UTAMA</p>

    <a class="nav-link active" href="dashboard.php">
        <span aria-hidden="true">▦</span>Dashboard
    </a>

    <a class="nav-link" href="../products/index.php">
        <span aria-hidden="true">▤</span>Produk
    </a>

    <a class="nav-link" href="../stock_movements/index.php">
        <span aria-hidden="true">↕</span>Riwayat Stok
    </a>

    <a class="nav-link" href="../stock_movements/create.php">
        <span aria-hidden="true">＋</span>Catat Stok
    </a>

    <!-- MENU BARU: LAPORAN PERSEDIAAN -->
    <a class="nav-link" href="../reports/index.php">
        <span aria-hidden="true">▧</span>Laporan Persediaan
    </a>

    <div class="sidebar-bottom">

        <div class="user-info">
            <div class="avatar"><?= e($initial) ?></div>
            <div>
                <strong><?= e($username) ?></strong>
                <small>Administrator</small>
            </div>
        </div>

        <a class="nav-link logout-link" href="logout.php">
            <span aria-hidden="true">↪</span>Keluar
        </a>

    </div>
</aside>

<main class="dashboard-main">

    <header class="dashboard-header">
        <div>
            <p class="eyebrow">RINGKASAN INVENTARIS</p>
            <h1>Dashboard</h1>
            <p>
                Selamat datang kembali, <?= e($username) ?>!
                Berikut ringkasan inventaris toko kamu.
            </p>
        </div>

        <a href="../products/create.php" class="btn-primary">
            + Tambah Produk
        </a>
    </header>

    <section class="stats-grid">

        <article class="stat-card">
            <div class="stat-icon" aria-hidden="true">▤</div>
            <p>Total Produk</p>
            <h2><?= number_format($totalProducts) ?></h2>
            <small>Produk terdaftar</small>
        </article>

        <article class="stat-card">
            <div class="stat-icon pink-icon" aria-hidden="true">◈</div>
            <p>Total Kategori</p>
            <h2><?= number_format($totalCategories) ?></h2>
            <small>Kategori produk</small>
        </article>

        <article class="stat-card">
            <div class="stat-icon blue-icon" aria-hidden="true">▥</div>
            <p>Total Stok</p>
            <h2><?= number_format($totalStock) ?></h2>
            <small>Total unit barang</small>
        </article>

        <article class="stat-card">
            <div class="stat-icon orange-icon" aria-hidden="true">!</div>
            <p>Stok Menipis</p>
            <h2><?= number_format($lowStock) ?></h2>
            <small>Produk perlu diperiksa</small>
        </article>

    </section>

    <div class="content-grid">

        <section class="table-card">

            <div class="table-heading">
                <div>
                    <h2>⚠ Peringatan Stok Menipis</h2>
                    <p>
                        Produk dengan stok sama dengan atau di bawah batas minimum.
                    </p>
                </div>

                <a href="../products/index.php" class="text-link">
                    Lihat produk →
                </a>
            </div>

            <div class="table-scroll">
                <table class="inventory-table">

                    <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Produk</th>
                        <th>Stok</th>
                        <th>Minimum</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if (!$lowStockProducts): ?>

                        <tr>
                            <td colspan="4" class="empty-state">
                                Tidak ada produk dengan stok menipis.
                                Semua aman!
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td><?= e($product['sku']) ?></td>

                                <td>
                                    <strong><?= e($product['name']) ?></strong>
                                </td>

                                <td>
                                    <span class="status low">
                                        <?= number_format((int) $product['stock']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= number_format((int) $product['minimum_stock']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>
                    </tbody>

                </table>
            </div>

        </section>

        <section class="table-card">

            <div class="table-heading">
                <div>
                    <h2>Produk Terbaru</h2>
                    <p>Lima produk terakhir yang ditambahkan.</p>
                </div>

                <a href="../products/index.php" class="text-link">
                    Lihat semua →
                </a>
            </div>

            <div class="table-scroll">
                <table class="inventory-table">

                    <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Stok</th>
                        <th>Status</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if (!$recentProducts): ?>

                        <tr>
                            <td colspan="3" class="empty-state">
                                Belum ada produk. Klik Tambah Produk
                                untuk mulai mengisi inventaris.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($recentProducts as $product): ?>
                            <tr>
                                <td>
                                    <strong><?= e($product['name']) ?></strong>
                                    <br>
                                    <small><?= e($product['sku']) ?></small>
                                </td>

                                <td>
                                    <?= number_format((int) $product['stock']) ?>
                                </td>

                                <td>
                                    <?php if (
                                        (int) $product['stock']
                                        <= (int) $product['minimum_stock']
                                    ): ?>

                                        <span class="status low">
                                            Stok Menipis
                                        </span>

                                    <?php else: ?>

                                        <span class="status good">
                                            Tersedia
                                        </span>

                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>
                    </tbody>

                </table>
            </div>

        </section>

    </div>

    <footer class="dashboard-footer">
        &copy; <?= date('Y') ?> TasyaShop · Inventory Management System
    </footer>

</main>

</body>
</html>
