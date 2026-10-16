
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

$suppliers = $pdo->query(
    'SELECT id, name, phone, address FROM suppliers ORDER BY id DESC'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Pemasok - TasyaShop</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 35px;
            background: #f0f4f8;
            color: #263746;
            font-family: "Segoe UI", Arial, sans-serif;
        }
        .container { max-width: 1200px; margin: auto; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }
        .eyebrow {
            color: #54718e;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        h1 { margin: 8px 0; color: #344b63; }
        .subtitle { color: #718096; }
        .button {
            display: inline-block;
            padding: 12px 17px;
            border: none;
            border-radius: 9px;
            background: #344e68;
            color: white;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }
        .button:hover { background: #263b50; }
        .button-light { background: #e1e8ef; color: #344b63; }
        .button-edit { background: #647991; }
        .button-delete { background: #d94343; }
        .card {
            overflow: hidden;
            border: 1px solid #dce5ee;
            border-radius: 15px;
            background: white;
            box-shadow: 0 6px 20px rgba(40, 60, 80, .04);
        }
        .card-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 22px 24px;
        }
        .card-heading h2 { margin: 0; font-size: 19px; }
        .card-heading p { margin: 7px 0 0; color: #718096; font-size: 13px; }
        .count {
            padding: 8px 13px;
            border-radius: 20px;
            background: #e8eef5;
            color: #344b63;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th {
            padding: 15px;
            background: #344e68;
            color: white;
            font-size: 12px;
            text-transform: uppercase;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #edf1f5;
            font-size: 13px;
            vertical-align: middle;
        }
        tbody tr:hover { background: #f8fafc; }
        .actions { display: flex; gap: 7px; }
        .actions .button { padding: 8px 11px; font-size: 12px; }
        .empty { padding: 35px; color: #718096; text-align: center; }
        .footer { margin-top: 25px; color: #8996a4; font-size: 12px; text-align: center; }
        @media (max-width: 650px) {
            body { padding: 20px 12px; }
            .header { align-items: flex-start; flex-direction: column; }
            .card-heading { padding: 18px; }
            th, td { padding: 12px; }
        }
    </style>
</head>
<body>
<div class="container">
    <header class="header">
        <div>
            <div class="eyebrow">TASYASHOP INVENTORY</div>
            <h1>Data Pemasok</h1>
            <p class="subtitle">Kelola informasi pemasok barang toko kamu.</p>
        </div>
        <a class="button" href="create.php">+ Tambah Pemasok</a>
    </header>

    <section class="card">
        <div class="card-heading">
            <div>
                <h2>Daftar Pemasok</h2>
                <p>Informasi pemasok yang tersimpan di database.</p>
            </div>
            <span class="count"><?= count($suppliers) ?> Pemasok</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Pemasok</th>
                        <th>Telepon</th>
                        <th>Alamat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="5" class="empty">
                            Belum ada data pemasok. Klik Tambah Pemasok untuk memulai.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $i => $supplier): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= e($supplier['name']) ?></strong></td>
                            <td><?= e($supplier['phone'] ?: '-') ?></td>
                            <td><?= nl2br(e($supplier['address'] ?: '-')) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="button button-edit"
                                       href="edit.php?id=<?= (int) $supplier['id'] ?>">
                                        Edit
                                    </a>
                                    <form action="delete.php" method="post"
                                          onsubmit="return confirm('Yakin ingin menghapus pemasok ini?');">
                                        <input type="hidden" name="id"
                                               value="<?= (int) $supplier['id'] ?>">
                                        <button class="button button-delete" type="submit">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <p style="margin-top:20px">
        <a class="button button-light" href="../public/dashboard.php">
            &larr; Kembali ke Dashboard
        </a>
    </p>

    <footer class="footer">
        &copy; <?= date('Y') ?> TasyaShop — Inventory Management System
    </footer>
</div>
</body>
</html>
