<?php
// admin/index.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// Pastikan hanya admin yang bisa mengakses
auth_require('admin');

// 1. Ambil Statistik Dashboard
// Total Nasabah
$stmt = $pdo->query("SELECT COUNT(*) AS total_nasabah, COALESCE(SUM(saldo), 0) AS total_saldo FROM users WHERE role = 'nasabah'");
$nasabahStat = $stmt->fetch();

// Total Sampah Terkumpul (Kg)
$stmt = $pdo->query("SELECT COALESCE(SUM(berat_kg), 0) AS total_kg FROM transaction_details");
$sampahStat = $stmt->fetch();

// Total Transaksi
$stmt = $pdo->query("SELECT COUNT(*) AS total_transaksi FROM transactions");
$transStat = $stmt->fetch();

// 2. Ambil 5 Transaksi Terakhir
$stmt = $pdo->query("
    SELECT t.*, u.nama_lengkap AS nama_nasabah, a.nama_lengkap AS nama_admin
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN users a ON t.admin_id = a.id
    ORDER BY t.tanggal_transaksi DESC
    LIMIT 5
");
$recentTransactions = $stmt->fetchAll();

$pageTitle = 'Dashboard Admin';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Dashboard Pengurus</h2>
        <p class="text-muted mb-0">Ringkasan operasional dan aktivitas pencatatan Bank Sampah RT/RW</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="/admin/transaksi/setor.php" class="btn btn-success shadow-sm">
            <i class="bi bi-box-arrow-in-down"></i> Setor Sampah
        </a>
        <a href="/admin/transaksi/tarik.php" class="btn btn-warning text-dark shadow-sm">
            <i class="bi bi-cash-stack"></i> Tarik Saldo
        </a>
    </div>
</div>

<!-- Kartu Statistik (Metric Cards) -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat shadow-sm p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="bg-success-subtle text-success p-3 rounded-3 me-3 fs-3">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Total Nasabah Warga</span>
                    <h3 class="fw-bold mb-0"><?= number_format($nasabahStat['total_nasabah']); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat shadow-sm p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="bg-primary-subtle text-primary p-3 rounded-3 me-3 fs-3">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Total Saldo Beredar</span>
                    <h4 class="fw-bold mb-0 text-truncate"><?= format_rupiah($nasabahStat['total_saldo']); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat shadow-sm p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="bg-info-subtle text-info p-3 rounded-3 me-3 fs-3">
                    <i class="bi bi-recycle"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Total Sampah Terkumpul</span>
                    <h3 class="fw-bold mb-0"><?= format_berat($sampahStat['total_kg']); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat shadow-sm p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="bg-warning-subtle text-warning p-3 rounded-3 me-3 fs-3">
                    <i class="bi bi-receipt"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Total Log Transaksi</span>
                    <h3 class="fw-bold mb-0"><?= number_format($transStat['total_transaksi']); ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaksi Terakhir & Aksi Cepat -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-success"></i> Transaksi Terbaru</h5>
                <a href="/admin/transaksi/index.php" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nasabah</th>
                            <th>Jenis</th>
                            <th>Nominal</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTransactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi yang tercatat.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentTransactions as $tx): ?>
                                <tr>
                                    <td>
                                        <a href="/admin/transaksi/detail.php?id=<?= $tx['id']; ?>" class="fw-semibold text-decoration-none">
                                            <?= htmlspecialchars($tx['kode_transaksi']); ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($tx['nama_nasabah']); ?></td>
                                    <td>
                                        <?php if ($tx['jenis_transaksi'] === 'setor'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Setor</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Tarik</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold <?= $tx['jenis_transaksi'] === 'setor' ? 'text-success' : 'text-danger'; ?>">
                                        <?= ($tx['jenis_transaksi'] === 'setor' ? '+' : '-') . format_rupiah($tx['total_nominal']); ?>
                                    </td>
                                    <td class="text-muted small"><?= format_tanggal($tx['tanggal_transaksi']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Panel Pintasan Menu -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <h5 class="fw-bold mb-3"><i class="bi bi-grid-fill me-2 text-success"></i> Menu Akses Cepat</h5>
            <div class="list-group list-group-flush">
                <a href="/admin/transaksi/setor.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-box-arrow-in-down fs-4 text-success me-3"></i>
                        <div>
                            <div class="fw-bold">Catat Setoran Sampah</div>
                            <small class="text-muted">Kalkulasi bobot & tambah saldo</small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="/admin/transaksi/tarik.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-cash-stack fs-4 text-warning me-3"></i>
                        <div>
                            <div class="fw-bold">Pencairan Saldo Tunai</div>
                            <small class="text-muted">Tarik uang tunai nasabah</small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="/admin/nasabah/tambah.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-plus fs-4 text-primary me-3"></i>
                        <div>
                            <div class="fw-bold">Daftarkan Warga Baru</div>
                            <small class="text-muted">Registrasi akun nasabah</small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="/admin/kategori/index.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-tags fs-4 text-info me-3"></i>
                        <div>
                            <div class="fw-bold">Kelola Tarif Sampah</div>
                            <small class="text-muted">Update harga per kg</small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="/admin/laporan/index.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-printer fs-4 text-secondary me-3"></i>
                        <div>
                            <div class="fw-bold">Cetak Rekapitulasi</div>
                            <small class="text-muted">Laporan berkala untuk evaluasi</small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
