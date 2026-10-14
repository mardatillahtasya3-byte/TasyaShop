
<?php
require_once __DIR__ . '/../config/database.php';

/* Hanya izinkan penghapusan melalui POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

/* Periksa ID produk */
$id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php?error=invalid');
    exit;
}

try {
    /* Hapus produk berdasarkan ID */
    $stmt = $pdo->prepare(
        'DELETE FROM products WHERE id = ?'
    );

    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        header('Location: index.php?deleted=1');
    } else {
        header('Location: index.php?error=notfound');
    }

    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());

    header('Location: index.php?error=deletefailed');
    exit;
}
