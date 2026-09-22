<?php
// admin/kategori/hapus.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

auth_require('admin');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'ID kategori tidak valid.');
    header('Location: /admin/kategori/index.php');
    exit;
}

try {
    // Cek apakah kategori sudah pernah digunakan dalam transaksi
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM transaction_details WHERE category_id = :id");
    $checkStmt->execute([':id' => $id]);
    $usageCount = $checkStmt->fetchColumn();

    if ($usageCount > 0) {
        set_flash('danger', 'Kategori ini tidak dapat dihapus karena sudah memiliki riwayat transaksi setoran sampah.');
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        set_flash('success', 'Kategori sampah berhasil dihapus.');
    }
} catch (PDOException $e) {
    set_flash('danger', 'Terjadi kesalahan saat menghapus: ' . $e->getMessage());
}

header('Location: /admin/kategori/index.php');
exit;
