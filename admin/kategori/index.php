<?php
// admin/kategori/index.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

auth_require('admin');

$stmt = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori ASC");
$categories = $stmt->fetchAll();

$pageTitle = 'Kelola Kategori Sampah';
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="/admin/index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Kategori Sampah</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Kategori Sampah & Tarif</h2>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="/admin/kategori/tambah.php" class="btn btn-success shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Tambah Kategori
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">No</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi / Syarat</th>
                    <th>Tarif / Kg</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Belum ada data kategori sampah.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($categories as $cat): ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-muted"><?= $no++; ?></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($cat['nama_kategori']); ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($cat['deskripsi'] ?? '-'); ?></td>
                            <td>
                                <span class="badge bg-success-subtle text-success fs-6 fw-semibold px-2 py-1">
                                    <?= format_rupiah($cat['harga_per_kg']); ?> / kg
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="/admin/kategori/edit.php?id=<?= $cat['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <a href="/admin/kategori/hapus.php?id=<?= $cat['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">
                                    <i class="bi bi-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
