<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/config/helpers.php';
require_once __DIR__ . '/../../../backend/services/bank_sampah_rules.php';

auth_require('admin');
$errors = [];
$editing = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($action === 'hapus' && $id) {
            try {
                $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
                $stmt->execute([':id' => $id]);
                set_flash('success', 'Kategori berhasil dihapus.');
            } catch (PDOException $e) {
                set_flash('danger', 'Kategori tidak dapat dihapus karena sudah digunakan dalam transaksi.');
            }
            header('Location: /admin/kategori/index.php');
            exit;
        }

        if ($action === 'simpan') {
            $nama = trim($_POST['nama_kategori'] ?? '');
            $harga = filter_var($_POST['harga_per_kg'] ?? null, FILTER_VALIDATE_FLOAT);
            $deskripsi = trim($_POST['deskripsi'] ?? '');

            $errors = category_validation_errors($nama, $_POST['harga_per_kg'] ?? null);

            if (!$errors) {
                if ($id) {
                    $stmt = $pdo->prepare('UPDATE categories SET nama_kategori = :nama, harga_per_kg = :harga, deskripsi = :deskripsi WHERE id = :id');
                    $stmt->execute([':nama' => $nama, ':harga' => $harga, ':deskripsi' => $deskripsi ?: null, ':id' => $id]);
                    set_flash('success', 'Kategori berhasil diperbarui.');
                } else {
                    $stmt = $pdo->prepare('INSERT INTO categories (nama_kategori, harga_per_kg, deskripsi) VALUES (:nama, :harga, :deskripsi)');
                    $stmt->execute([':nama' => $nama, ':harga' => $harga, ':deskripsi' => $deskripsi ?: null]);
                    set_flash('success', 'Kategori baru berhasil ditambahkan.');
                }
                header('Location: /admin/kategori/index.php');
                exit;
            }
        }
    }
}

if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id');
    $stmt->execute([':id' => $editId]);
    $editing = $stmt->fetch();
}
$categories = $pdo->query('SELECT * FROM categories ORDER BY nama_kategori')->fetchAll();
$pageTitle = 'Kategori Sampah';
require_once __DIR__ . '/../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="fw-bold mb-1">Kategori Sampah</h2><p class="text-muted mb-0">Atur jenis sampah dan tarif yang berlaku.</p></div>
    <a class="btn btn-outline-secondary" href="/admin/index.php"><i class="bi bi-arrow-left"></i> Dashboard</a>
</div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= htmlspecialchars($error); ?></div><?php endforeach; ?>
<div class="row g-4">
    <div class="col-lg-4">
        <section class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3"><?= $editing ? 'Ubah Kategori' : 'Tambah Kategori'; ?></h5>
            <form method="post">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="simpan">
                <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0); ?>">
                <div class="mb-3"><label class="form-label" for="nama_kategori">Nama kategori</label><input class="form-control" id="nama_kategori" name="nama_kategori" maxlength="100" required value="<?= htmlspecialchars($_POST['nama_kategori'] ?? $editing['nama_kategori'] ?? ''); ?>"></div>
                <div class="mb-3"><label class="form-label" for="harga_per_kg">Tarif per kg (Rp)</label><input class="form-control" id="harga_per_kg" name="harga_per_kg" type="number" min="0.01" step="0.01" required value="<?= htmlspecialchars($_POST['harga_per_kg'] ?? $editing['harga_per_kg'] ?? ''); ?>"></div>
                <div class="mb-3"><label class="form-label" for="deskripsi">Deskripsi</label><textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?= htmlspecialchars($_POST['deskripsi'] ?? $editing['deskripsi'] ?? ''); ?></textarea></div>
                <button class="btn btn-success" type="submit"><i class="bi bi-save"></i> Simpan</button>
                <?php if ($editing): ?><a class="btn btn-outline-secondary" href="/admin/kategori/index.php">Batal</a><?php endif; ?>
            </form>
        </section>
    </div>
    <div class="col-lg-8">
        <section class="card border-0 shadow-sm">
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th class="ps-3">Kategori</th><th>Tarif / kg</th><th>Deskripsi</th><th class="text-end pe-3">Aksi</th></tr></thead>
                <tbody>
                <?php if (!$categories): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada kategori sampah.</td></tr><?php endif; ?>
                <?php foreach ($categories as $category): ?>
                    <tr><td class="ps-3 fw-semibold"><?= htmlspecialchars($category['nama_kategori']); ?></td><td class="text-success fw-bold"><?= format_rupiah($category['harga_per_kg']); ?></td><td class="text-muted small"><?= htmlspecialchars($category['deskripsi'] ?: '-'); ?></td><td class="text-end pe-3"><a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$category['id']; ?>" aria-label="Ubah kategori"><i class="bi bi-pencil"></i></a> <form class="d-inline" method="post" onsubmit="return confirm('Hapus kategori ini? Kategori yang sudah dipakai tidak dapat dihapus.');"><?= csrf_field(); ?><input type="hidden" name="action" value="hapus"><input type="hidden" name="id" value="<?= (int)$category['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Hapus kategori"><i class="bi bi-trash"></i></button></form></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>