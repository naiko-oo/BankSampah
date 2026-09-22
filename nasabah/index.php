<?php
// nasabah/index.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

auth_require('nasabah');
$user = auth_user();
$userId = $user['id'];

// Ambil data nasabah terkini dari database (termasuk saldo terupdate)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$profile = $stmt->fetch();

// Statistik nasabah
$stmt = $pdo->prepare("
    SELECT 
        COUNT(t.id) AS total_transaksi,
        SUM(CASE WHEN t.jenis_transaksi = 'setor' THEN t.total_nominal ELSE 0 END) AS total_disetor,
        SUM(CASE WHEN t.jenis_transaksi = 'tarik' THEN t.total_nominal ELSE 0 END) AS total_ditarik,
        COALESCE(SUM(td.berat_kg), 0) AS total_kg
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    WHERE t.user_id = :uid
");
$stmt->execute([':uid' => $userId]);
$stat = $stmt->fetch();

// 5 Transaksi terakhir
$stmt = $pdo->prepare("
    SELECT t.*, a.nama_lengkap AS nama_admin
    FROM transactions t
    JOIN users a ON t.admin_id = a.id
    WHERE t.user_id = :uid
    ORDER BY t.tanggal_transaksi DESC
    LIMIT 5
");
$stmt->execute([':uid' => $userId]);
$recentTransactions = $stmt->fetchAll();

// Daftar tarif terkini
$categories = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori ASC")->fetchAll();

$pageTitle = 'Dashboard Nasabah';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Halo, <?= htmlspecialchars($profile['nama_lengkap']); ?>! 👋</h2>
        <p class="text-muted mb-0">Selamat datang di Buku Tabungan Sampah Digital RT/RW Anda</p>
    </div>
    <div class="mt-2 mt-md-0">
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($profile['alamat'] ?: 'Warga RT/RW'); ?>
        </span>
    </div>
</div>

<!-- Kartu Saldo & Metrik Warga -->
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-white bg-success h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-white-50 small fw-semibold text-uppercase">Saldo Tabungan Anda</span>
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
                <h1 class="display-6 fw-bold mb-1"><?= format_rupiah($profile['saldo']); ?></h1>
                <small class="text-white-50">Dapat dicairkan tunai melalui pengurus bank sampah</small>
            </div>
            <div class="pt-3 border-top border-white-50 mt-3 d-flex justify-content-between">
                <span class="small text-white-50">Username Akun</span>
                <span class="small fw-semibold">@<?= htmlspecialchars($profile['username']); ?></span>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="row g-3 h-100">
            <div class="col-sm-6">
                <div class="card card-stat shadow-sm p-3 bg-white h-100 d-flex flex-column justify-content-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-success-subtle text-success p-3 rounded-3 me-3 fs-3">
                            <i class="bi bi-recycle"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold">Total Sampah Disetor</span>
                            <h4 class="fw-bold mb-0 text-success"><?= format_berat($stat['total_kg'] ?? 0); ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="card card-stat shadow-sm p-3 bg-white h-100 d-flex flex-column justify-content-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary-subtle text-primary p-3 rounded-3 me-3 fs-3">
                            <i class="bi bi-piggy-bank"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold">Total Nilai Tabungan</span>
                            <h4 class="fw-bold mb-0 text-primary"><?= format_rupiah($stat['total_disetor'] ?? 0); ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="card card-stat shadow-sm p-3 bg-white h-100 d-flex flex-column justify-content-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning-subtle text-warning p-3 rounded-3 me-3 fs-3">
                            <i class="bi bi-cash"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold">Total Saldo Ditarik</span>
                            <h4 class="fw-bold mb-0 text-dark"><?= format_rupiah($stat['total_ditarik'] ?? 0); ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="card card-stat shadow-sm p-3 bg-white h-100 d-flex flex-column justify-content-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-info-subtle text-info p-3 rounded-3 me-3 fs-3">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold">Total Frekuensi Transaksi</span>
                            <h4 class="fw-bold mb-0 text-dark"><?= (int)($stat['total_transaksi'] ?? 0); ?> kali</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Riwayat Terakhir & Tarif -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-success"></i> Aktivitas Terakhir Saya</h5>
                <a href="/nasabah/riwayat.php" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Jenis</th>
                            <th>Nominal</th>
                            <th>Tanggal</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTransactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat transaksi tabungan sampah.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentTransactions as $tx): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold font-monospace small"><?= htmlspecialchars($tx['kode_transaksi']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($tx['jenis_transaksi'] === 'setor'): ?>
                                            <span class="badge bg-success-subtle text-success">Setor</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis">Tarik</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold <?= $tx['jenis_transaksi'] === 'setor' ? 'text-success' : 'text-danger'; ?>">
                                        <?= ($tx['jenis_transaksi'] === 'setor' ? '+' : '-') . format_rupiah($tx['total_nominal']); ?>
                                    </td>
                                    <td class="text-muted small"><?= format_tanggal($tx['tanggal_transaksi']); ?></td>
                                    <td class="text-end">
                                        <a href="/admin/transaksi/detail.php?id=<?= $tx['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-receipt"></i> Struk
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-tags me-2 text-success"></i> Tarif Sampah Hari Ini</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Kategori</th>
                            <th class="text-end">Harga / kg</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c): ?>
                            <tr>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($c['nama_kategori']); ?></td>
                                <td class="text-end fw-bold text-success"><?= format_rupiah($c['harga_per_kg']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
