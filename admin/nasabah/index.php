<?php
// admin/nasabah/index.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

auth_require('admin');

$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE role = 'nasabah' AND (nama_lengkap LIKE :q OR username LIKE :q OR nomor_telepon LIKE :q)
        ORDER BY nama_lengkap ASC
    ");
    $stmt->execute([':q' => "%{$search}%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'nasabah' ORDER BY nama_lengkap ASC");
}
$nasabahList = $stmt->fetchAll();

$pageTitle = 'Kelola Data Nasabah';
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="/admin/index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Data Nasabah</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Daftar Nasabah (Warga RT/RW)</h2>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="/admin/nasabah/tambah.php" class="btn btn-success shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i> Registrasi Nasabah Baru
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2">
            <div class="col-md-9">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari berdasarkan nama warga, username, atau no. telepon..." value="<?= htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Cari</button>
                <?php if (!empty($search)): ?>
                    <a href="/admin/nasabah/index.php" class="btn btn-light"><i class="bi bi-x-circle"></i> Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">No</th>
                    <th>Nama Lengkap & Username</th>
                    <th>No. Telepon</th>
                    <th>Alamat / RT RW</th>
                    <th>Saldo Tabungan</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($nasabahList)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Tidak ditemukan data nasabah warga.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($nasabahList as $nasabah): ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-muted"><?= $no++; ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($nasabah['nama_lengkap']); ?></div>
                                <small class="text-muted"><i class="bi bi-person"></i> @<?= htmlspecialchars($nasabah['username']); ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($nasabah['nomor_telepon'] ?: '-'); ?>
                            </td>
                            <td class="text-muted small">
                                <?= htmlspecialchars($nasabah['alamat'] ?: '-'); ?>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary fs-6 fw-bold px-3 py-2">
                                    <?= format_rupiah($nasabah['saldo']); ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="/admin/nasabah/detail.php?id=<?= $nasabah['id']; ?>" class="btn btn-sm btn-outline-info me-1" title="Lihat Riwayat Transaksi">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                <a href="/admin/nasabah/edit.php?id=<?= $nasabah['id']; ?>" class="btn btn-sm btn-outline-primary" title="Ubah Profil">
                                    <i class="bi bi-pencil-square"></i> Edit
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
