<?php
session_start();

if (empty($_SESSION['app_user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';
$movements = [];

if (isset($_GET['success']) && $_GET['success'] === 'created') {
    $success = 'Transaksi stok berhasil disimpan dan stok telah diperbarui.';
}

try {
    $sql = "SELECT
                sm.id,
                sm.product_id,
                sm.type,
                sm.quantity,
                sm.note,
                sm.created_at,
                p.sku,
                p.name AS product_name
            FROM stock_movements sm
            LEFT JOIN products p ON p.id = sm.product_id
            ORDER BY sm.created_at DESC, sm.id DESC
            LIMIT 200";

    $stmt = $pdo->query($sql);
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Gagal mengambil riwayat transaksi dari database.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Stok - TasyaShop</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #F1F5F9;
            color: #334155;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            max-width: 1450px;
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

        .label {
            margin: 0 0 8px;
            color: #526D8A;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1.5px;
        }

        h1 {
            margin: 0;
            color: #1E293B;
            font-size: 30px;
        }

        .description {
            margin-top: 9px;
            color: #64748B;
            font-size: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 15px;
            border: none;
            border-radius: 8px;
            background: #344D68;
            color: #FFFFFF;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn:hover { background: #263B53; }

        .btn-secondary {
            background: #E2E8F0;
            color: #334155;
        }

        .btn-secondary:hover { background: #CBD5E1; }

        .alert {
            padding: 14px 17px;
            margin-bottom: 20px;
            border-radius: 9px;
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

        .summary {
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
            border-radius: 12px;
            background: #E2E8F0;
            color: #344D68;
            font-size: 22px;
        }

        .summary h2 {
            margin: 0 0 5px;
            font-size: 17px;
        }

        .summary p {
            margin: 0;
            color: #64748B;
            font-size: 12px;
        }

        .count {
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 16px 18px;
            border-bottom: 1px solid #E2E8F0;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #344D68;
            color: #FFFFFF;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }

        td {
            color: #475569;
            font-size: 13px;
        }

        tbody tr:hover { background: #F8FAFC; }
        tbody tr:last-child td { border-bottom: none; }

        .product-name {
            min-width: 150px;
            color: #1E293B;
            font-weight: bold;
        }

        .sku {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 6px;
            background: #F1F5F9;
            white-space: nowrap;
        }

        .type {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }

        .type-in {
            background: #DCFCE7;
            color: #166534;
        }

        .type-out {
            background: #FEF2F2;
            color: #B91C1C;
        }

        .quantity {
            font-weight: bold;
            white-space: nowrap;
        }

        .note {
            min-width: 180px;
            max-width: 280px;
            white-space: normal;
            line-height: 1.5;
        }

        .empty {
            padding: 45px 20px;
            color: #64748B;
            text-align: center;
        }

        .footer {
            padding: 22px 0;
            color: #64748B;
            text-align: center;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .container { padding: 20px 12px; }
            .header {
                align-items: flex-start;
                flex-direction: column;
            }
            h1 { font-size: 24px; }
            th, td { padding: 13px; }
        }
    </style>
</head>

<body>
<div class="container">

    <header class="header">
        <div>
            <p class="label">TASYASHOP INVENTORY</p>
            <h1>Riwayat Stok</h1>
            <p class="description">
                Pantau setiap transaksi barang masuk dan barang keluar.
            </p>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="create.php" class="btn">
                + Transaksi Stok
            </a>

            <a href="../products/index.php" class="btn btn-secondary">
                Kembali ke Produk
            </a>
        </div>
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

    <section class="summary">
        <div class="summary-icon">↕</div>

        <div>
            <h2>Daftar Transaksi</h2>
            <p>Menampilkan maksimal 200 transaksi terbaru.</p>
        </div>

        <span class="count">
            <?= count($movements) ?> Transaksi
        </span>
    </section>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Tanggal</th>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Catatan</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($movements)): ?>

                    <?php foreach ($movements as $index => $movement): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>

                            <td>
                                <?= htmlspecialchars(
                                    date(
                                        'd-m-Y H:i',
                                        strtotime($movement['created_at'])
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <span class="sku">
                                    <?= htmlspecialchars(
                                        $movement['sku'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>

                            <td class="product-name">
                                <?= htmlspecialchars(
                                    $movement['product_name'] ?? 'Produk tidak tersedia',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?php if ($movement['type'] === 'IN'): ?>
                                    <span class="type type-in">
                                        Stok Masuk
                                    </span>
                                <?php else: ?>
                                    <span class="type type-out">
                                        Stok Keluar
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="quantity">
                                <?= (int) $movement['quantity'] ?> unit
                            </td>

                            <td class="note">
                                <?= htmlspecialchars(
                                    $movement['note'] ?? '-',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty">
                            Belum ada riwayat transaksi stok.
                            Klik tombol "Transaksi Stok" untuk memulai.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <footer class="footer">
        &copy; <?= date('Y') ?> TasyaShop — Inventory Management System
    </footer>

</div>
</body>
</html>