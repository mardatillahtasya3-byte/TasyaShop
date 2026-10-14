
<?php
session_start();

if (!isset($_SESSION['app_user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$totalProducts = (int)$pdo->query(
    'SELECT COUNT(*) FROM products'
)->fetchColumn();

$totalCategories = (int)$pdo->query(
    'SELECT COUNT(*) FROM categories'
)->fetchColumn();

$totalStock = (int)$pdo->query(
    'SELECT COALESCE(SUM(stock), 0) FROM products'
)->fetchColumn();

$lowStock = (int)$pdo->query(
    'SELECT COUNT(*) FROM products
     WHERE stock <= minimum_stock'
)->fetchColumn();

$recentProducts = $pdo->query(
    'SELECT p.sku, p.name, p.price, p.stock, p.minimum_stock,
            c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     ORDER BY p.id DESC
     LIMIT 5'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Tasya Inventory</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
    <aside class="sidebar">
        <a class="brand" href="dashboard.php">
            <span class="brand-icon">P</span>
            <span>Tasya<span class="brand-light">Stock</span></span>
        </a>

        <p class="nav-label">MENU UTAMA</p>

        <a class="nav-link active" href="dashboard.php">
            <span>▦</span> Dashboard
        </a>
        <a class="nav-link" href="../products/index.php">
            <span>▤</span> Produk
        </a>

        <div class="sidebar-bottom">
            <div class="user-info">
                <div class="avatar">
                    <?= e(strtoupper(substr($_SESSION['app_username'], 0, 1))) ?>
                </div>
                <div>
                    <strong><?= e($_SESSION['app_username']) ?></strong>
                    <small>Administrator</small>
                </div>
            </div>
            <a class="nav-link logout-link" href="logout.php">
                ↪ Keluar
            </a>
        </div>
    </aside>

    <main class="dashboard-main">
        <header class="dashboard-header">
            <div>
                <p class="eyebrow">OVERVIEW</p>
                <h1>Dashboard</h1>
                <p>Selamat datang kembali,
                    <?= e($_SESSION['app_username']) ?>!</p>
            </div>
            <a href="../products/create.php" class="btn btn-primary">
                + Tambah Produk
            </a>
        </header>

        <section class="stats-grid">
            <article class="stat-card">
                <div class="stat-icon TasyaShop-icon">▤</div>
                <p>Total Produk</p>
                <h2><?= number_format($totalProducts) ?></h2>
                <small>Produk terdaftar</small>
            </article>

            <article class="stat-card">
                <div class="stat-icon pink-icon">◈</div>
                <p>Total Kategori</p>
                <h2><?= number_format($totalCategories) ?></h2>
                <small>Kategori produk</small>
            </article>

            <article class="stat-card">
                <div class="stat-icon blue-icon">▥</div>
                <p>Total Stok</p>
                <h2><?= number_format($totalStock) ?></h2>
                <small>Unit barang</small>
            </article>

            <article class="stat-card">
                <div class="stat-icon orange-icon">!</div>
                <p>Stok Menipis</p>
                <h2><?= number_format($lowStock) ?></h2>
                <small>Produk perlu diperiksa</small>
            </article>
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
                            <th>SKU</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$recentProducts): ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    Belum ada produk. Klik Tambah Produk
                                    untuk memulai.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentProducts as $product): ?>
                                <tr>
                                    <td><?= e($product['sku']) ?></td>
                                    <td><strong><?= e($product['name']) ?></strong></td>
                                    <td><?= e($product['category_name'] ?? '-') ?></td>
                                    <td>Rp <?= number_format((float)$product['price'], 0, ',', '.') ?></td>
                                    <td><?= number_format((int)$product['stock']) ?></td>
                                    <td>
                                        <?php if ((int)$product['stock'] <= (int)$product['minimum_stock']): ?>
                                            <span class="status low">Stok Menipis</span>
                                        <?php else: ?>
                                            <span class="status good">Tersedia</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <footer class="dashboard-footer">
            TasyaStock Inventory Management
        </footer>
    </main>
</body>
</html>
