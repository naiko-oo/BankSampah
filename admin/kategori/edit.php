<?php
// admin/kategori/edit.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

auth_require('admin');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$category = $stmt->fetch();

if (!$category) {
    set_flash('danger', 'Kategori sampah tidak ditemukan.');
    header('Location: /admin/kategori/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $harga_per_kg  = (float)($_POST['harga_per_kg'] ?? 0);
    $deskripsi     = trim($_POST['deskripsi'] ?? '');

    if (empty($nama_kategori)) {
        $error = 'Nama kategori sampah wajib diisi.';
    } elseif ($harga_per_kg <= 0) {
        $error = 'Tarif per kilogram harus lebih besar dari 0.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET nama_kategori = :nama, harga_per_kg = :harga, deskripsi = :deskripsi WHERE id = :id");
            $stmt->execute([
                ':nama'      => $nama_kategori,
                ':harga'     => $harga_per_kg,
                ':deskripsi' => $deskripsi,
                ':id'        => $id
            ]);

            set_flash('success', "Kategori <strong>" . htmlspecialchars($nama_kategori) . "</strong> berhasil diperbarui.");
            header('Location: /admin/kategori/index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui kategori: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Kategori Sampah';
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Edit Kategori Sampah</h4>
                <a href="/admin/kategori/index.php" class="btn btn-sm btn-outline-secondary">Kembali</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="nama_kategori" class="form-label fw-semibold">Nama Kategori Sampah <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required value="<?= htmlspecialchars($_POST['nama_kategori'] ?? $category['nama_kategori']); ?>">
                </div>

                <div class="mb-3">
                    <label for="harga_per_kg" class="form-label fw-semibold">Tarif Beli per Kg (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" step="100" min="100" class="form-control" id="harga_per_kg" name="harga_per_kg" required value="<?= htmlspecialchars($_POST['harga_per_kg'] ?? $category['harga_per_kg']); ?>">
                        <span class="input-group-text">/ kg</span>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="deskripsi" class="form-label fw-semibold">Deskripsi / Syarat Penyetoran</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?= htmlspecialchars($_POST['deskripsi'] ?? $category['deskripsi']); ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="/admin/kategori/index.php" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
