<?php
session_start();

if (!isset($_SESSION['app_user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$error = '';
$success = isset($_GET['success']) ? 'Perubahan stok berhasil disimpan.' : '';

try {
    $stmt = $pdo->query(
        'SELECT sm.id, sm.type, sm.quantity, sm.note, sm.created_at,
                p.sku, p.name AS product_name
         FROM stock_movements sm
         LEFT JOIN products p ON p.id = sm.product_id
         ORDER BY sm.created_at DESC, sm.id DESC
         LIMIT 200'
    );
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $movements = [];
    $error = 'Riwayat stok gagal diambil dari database.';
}

$username = $_SESSION['app_username'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Stok | TasyaShop</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 28px; font-family: "Segoe UI", Arial, sans-serif; background: #f1f5f9; color: #334155; }
        .container { max-width: 1250px; margin: 0 auto; }
        .topbar { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:24px; }
        .brand { color:#344d68; font-size:21px; font-weight:800; text-decoration:none; }
        .top-actions { display:flex; gap:9px; flex-wrap:wrap; }
        .btn { display:inline-flex; align-items:center; justify-content:center; padding:11px 15px; min-height:42px; border:0; border-radius:9px; background:#344d68; color:#fff; text-decoration:none; font-size:13px; font-weight:700; cursor:pointer; }
        .btn:hover { background:#263b53; }
        .btn-secondary { background:#e2e8f0; color:#334155; }
        .btn-secondary:hover { background:#cbd5e1; }
        h1 { margin:0 0 8px; color:#263b53; font-size:30px; }
        .subtitle { margin:0 0 24px; color:#718096; font-size:14px; }
        .card { overflow:hidden; background:#fff; border:1px solid #dce4ec; border-radius:15px; box-shadow:0 6px 20px rgba(52,70,90,.04); }
        .card-heading { padding:20px 22px; border-bottom:1px solid #e8edf2; }
        .card-heading h2 { margin:0; color:#344d68; font-size:17px; }
        .card-heading p { margin:6px 0 0; color:#718096; font-size:12px; }
        .alert { padding:13px 15px; border-radius:9px; margin-bottom:18px; font-size:13px; }
        .alert-success { background:#e8f5ed; color:#31744d; border:1px solid #b9e2c8; }
        .alert-error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .table-scroll { width:100%; overflow-x:auto; }
        table { width:100%; border-collapse:collapse; white-space:nowrap; }
        th { padding:14px 17px; background:#f8fafc; color:#718096; font-size:10px; letter-spacing:.6px; text-transform:uppercase; text-align:left; }
        td { padding:15px 17px; border-top:1px solid #edf1f5; font-size:13px; color:#536477; }
        tbody tr:hover { background:#f9fbfd; }
        .pill { display:inline-flex; padding:6px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .pill-in { color:#267044; background:#e8f5ed; }
        .pill-out { color:#9a5b1a; background:#fff1df; }
        .note { max-width:260px; white-space:normal; }
        .empty { text-align:center; padding:34px 18px; color:#718096; }
        footer { margin-top:22px; color:#8996a4; text-align:center; font-size:11px; }
        @media(max-width:600px) { body { padding:15px; } h1 { font-size:25px; } td, th { padding:12px; } }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <a class="brand" href="../public/dashboard.php">▰ TasyaShop</a>
        <div class="top-actions">
            <a class="btn" href="create.php">＋ Catat Stok</a>
            <a class="btn btn-secondary" href="../public/dashboard.php">Dashboard</a>
        </div>
    </div>

    <h1>Riwayat Stok</h1>
    <p class="subtitle">Catatan barang masuk dan keluar yang tersimpan di TasyaShop.</p>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="card">
        <div class="card-heading">
            <h2>Daftar Pergerakan Stok</h2>
            <p>Menampilkan maksimal 200 transaksi terbaru.</p>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$movements): ?>
                    <tr><td colspan="6" class="empty">Belum ada riwayat stok. Klik “Catat Stok” untuk membuat transaksi pertama.</td></tr>
                <?php else: ?>
                    <?php foreach ($movements as $movement): ?>
                        <tr>
                            <td><?= e(date('d-m-Y H:i', strtotime($movement['created_at']))) ?></td>
                            <td><?= e($movement['sku'] ?? '-') ?></td>
                            <td><strong><?= e($movement['product_name'] ?? 'Produk dihapus') ?></strong></td>
                            <td>
                                <?php if ($movement['type'] === 'IN'): ?>
                                    <span class="pill pill-in">Barang Masuk</span>
                                <?php else: ?>
                                    <span class="pill pill-out">Barang Keluar</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format((int) $movement['quantity']) ?></td>
                            <td class="note"><?= e($movement['note'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <footer>&copy; <?= date('Y') ?> TasyaShop · Inventory Management System</footer>
</div>
</body>
</html>
