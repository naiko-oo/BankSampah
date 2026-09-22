<?php
// admin/nasabah/detail.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

auth_require('admin');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'nasabah' LIMIT 1");
$stmt->execute([':id' => $id]);
$nasabah = $stmt->fetch();

if (!$nasabah) {
    set_flash('danger', 'Data nasabah tidak ditemukan.');
    header('Location: /admin/nasabah/index.php');
    exit;
}

// Ambil seluruh riwayat transaksi nasabah ini
$stmt = $pdo->prepare("
    SELECT t.*, a.nama_lengkap AS nama_admin
    FROM transactions t
    JOIN users a ON t.admin_id = a.id
    WHERE t.user_id = :user_id
    ORDER BY t.tanggal_transaksi DESC
");
$stmt->execute([':user_id' => $id]);
$transactions = $stmt->fetchAll();

// Statistik khusus nasabah ini
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN t.jenis_transaksi = 'setor' THEN t.total_nominal ELSE 0 END) AS total_disetor,
        SUM(CASE WHEN t.jenis_transaksi = 'tarik' THEN t.total_nominal ELSE 0 END) AS total_ditarik,
        COALESCE(SUM(td.berat_kg), 0) AS total_kg
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    WHERE t.user_id = :user_id
");
$stmt->execute([':user_id' => $id]);
$stat = $stmt->fetch();

$pageTitle = 'Detail Nasabah: ' . $nasabah['nama_lengkap'];
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="/admin/index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/admin/nasabah/index.php" class="text-decoration-none">Data Nasabah</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detail</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0"><?= htmlspecialchars($nasabah['nama_lengkap']); ?></h2>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="/admin/transaksi/setor.php?nasabah_id=<?= $nasabah['id']; ?>" class="btn btn-success btn-sm">
            <i class="bi bi-box-arrow-in-down"></i> Catat Setoran
        </a>
        <a href="/admin/transaksi/tarik.php?nasabah_id=<?= $nasabah['id']; ?>" class="btn btn-warning btn-sm text-dark">
            <i class="bi bi-cash-stack"></i> Tarik Saldo
        </a>
        <a href="/admin/nasabah/edit.php?id=<?= $nasabah['id']; ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil-square"></i> Edit Profil
        </a>
    </div>
</div>

<!-- Kartu Informasi & Statistik Nasabah -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <h6 class="text-muted fw-bold text-uppercase small mb-3">Profil Warga</h6>
            <div class="mb-2"><strong>Username:</strong> @<?= htmlspecialchars($nasabah['username']); ?></div>
            <div class="mb-2"><strong>No. Telepon:</strong> <?= htmlspecialchars($nasabah['nomor_telepon'] ?: '-'); ?></div>
            <div class="mb-2"><strong>Alamat / RT RW:</strong> <?= htmlspecialchars($nasabah['alamat'] ?: '-'); ?></div>
            <div><strong>Terdaftar Sejak:</strong> <?= format_tanggal($nasabah['created_at']); ?></div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row g-3 h-100">
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-primary text-white h-100 d-flex flex-column justify-content-center">
                    <span class="small text-white-50 fw-semibold">Saldo Aktif Saat Ini</span>
                    <h3 class="fw-bold mt-1 mb-0"><?= format_rupiah($nasabah['saldo']); ?></h3>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 d-flex flex-column justify-content-center">
                    <span class="small text-muted fw-semibold">Total Sampah Disetor</span>
                    <h3 class="fw-bold mt-1 mb-0 text-success"><?= format_berat($stat['total_kg'] ?? 0); ?></h3>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 d-flex flex-column justify-content-center">
                    <span class="small text-muted fw-semibold">Total Uang Pernah Ditarik</span>
                    <h3 class="fw-bold mt-1 mb-0 text-secondary"><?= format_rupiah($stat['total_ditarik'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Riwayat Transaksi Nasabah Ini -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-success"></i> Riwayat Transaksi Nasabah</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Kode Transaksi</th>
                    <th>Jenis</th>
                    <th>Nominal</th>
                    <th>Petugas Pencatat</th>
                    <th>Tanggal</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat transaksi untuk nasabah ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-dark">
                                <?= htmlspecialchars($t['kode_transaksi']); ?>
                            </td>
                            <td>
                                <?php if ($t['jenis_transaksi'] === 'setor'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Setor Sampah</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Tarik Saldo</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold <?= $t['jenis_transaksi'] === 'setor' ? 'text-success' : 'text-danger'; ?>">
                                <?= ($t['jenis_transaksi'] === 'setor' ? '+' : '-') . format_rupiah($t['total_nominal']); ?>
                            </td>
                            <td><?= htmlspecialchars($t['nama_admin']); ?></td>
                            <td class="text-muted small"><?= format_tanggal($t['tanggal_transaksi']); ?></td>
                            <td class="text-end pe-4">
                                <a href="/admin/transaksi/detail.php?id=<?= $t['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-receipt"></i> Struk / Rincian
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
