
<?php
session_start();

if (!isset($_SESSION['app_user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Penghapusan hanya boleh dilakukan melalui formulir POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php?error=invalid');
    exit;
}

try {
    // Pastikan pemasok yang dipilih memang ada.
    $stmt = $pdo->prepare(
        'SELECT id, name FROM suppliers WHERE id = ?'
    );
    $stmt->execute([$id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$supplier) {
        header('Location: index.php?error=notfound');
        exit;
    }

    // Cegah penghapusan jika pemasok masih terhubung dengan produk.
    $check = $pdo->prepare(
        'SELECT COUNT(*) FROM product_suppliers WHERE supplier_id = ?'
    );
    $check->execute([$id]);

    if ((int) $check->fetchColumn() > 0) {
        header('Location: index.php?error=inuse');
        exit;
    }

    // Hapus hanya pemasok yang tidak sedang digunakan.
    $delete = $pdo->prepare(
        'DELETE FROM suppliers WHERE id = ?'
    );
    $delete->execute([$id]);

    header('Location: index.php?success=deleted');
    exit;

} catch (PDOException $exception) {
    error_log($exception->getMessage());

    header('Location: index.php?error=deletefailed');
    exit;
}
