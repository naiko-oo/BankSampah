<?php
// admin/transaksi/index.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

auth_require('admin');

$jenis   = trim($_GET['jenis'] ?? '');
$search  = trim($_GET['search'] ?? '');
$tglAwal = trim($_GET['tgl_awal'] ?? '');
$tglAkhir= trim($_GET['tgl_akhir'] ?? '');

$sql = "
    SELECT t.*, u.nama_lengkap AS nama_nasabah, u.username AS username_nasabah, a.nama_lengkap AS nama_admin
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN users a ON t.admin_id = a.id
    WHERE 1=1
";
$params = [];

if (!empty($jenis) && in_array($jenis, ['setor', 'tarik'])) {
    $sql .= " AND t.jenis_transaksi = :jenis";
    $params[':jenis'] = $jenis;
}

if (!empty($search)) {
    $sql .= " AND (t.kode_transaksi LIKE :q OR u.nama_lengkap LIKE :q OR u.username LIKE :q)";
    $params[':q'] = "%{$search}%";
}

if (!empty($tglAwal)) {
    $sql .= " AND DATE(t.tanggal_transaksi) >= :tgl_awal";
    $params[':tgl_awal'] = $tglAwal;
}

if (!empty($tglAkhir)) {
    $sql .= " AND DATE(t.tanggal_transaksi) <= :tgl_akhir";
    $params[':tgl_akhir'] = $tglAkhir;
}

$sql .= " ORDER BY t.tanggal_transaksi DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$pageTitle = 'Riwayat Semua Transaksi';
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="/admin/index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Log Transaksi</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Riwayat Transaksi Bank Sampah</h2>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="/admin/transaksi/setor.php" class="btn btn-success shadow-sm">
            <i class="bi bi-box-arrow-in-down"></i> Catat Setoran
        </a>
        <a href="/admin/transaksi/tarik.php" class="btn btn-warning text-dark shadow-sm">
            <i class="bi bi-cash-stack"></i> Tarik Saldo
        </a>
    </div>
</div>

<!-- Filter Pencarian -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Pencarian</label>
                <input type="text" name="search" class="form-control" placeholder="Kode atau nama warga..." value="<?= htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Jenis Transaksi</label>
                <select name="jenis" class="form-select">
                    <option value="">Semua Jenis</option>
                    <option value="setor" <?= $jenis === 'setor' ? 'selected' : ''; ?>>Setor Sampah</option>
                    <option value="tarik" <?= $jenis === 'tarik' ? 'selected' : ''; ?>>Tarik Saldo</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Dari Tanggal</label>
                <input type="date" name="tgl_awal" class="form-control" value="<?= htmlspecialchars($tglAwal); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Sampai Tanggal</label>
                <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tglAkhir); ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-filter"></i> Filter</button>
                <a href="/admin/transaksi/index.php" class="btn btn-light"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
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
                    <th>Kode Transaksi</th>
                    <th>Nasabah</th>
                    <th>Jenis</th>
                    <th>Nominal</th>
                    <th>Petugas</th>
                    <th>Tanggal</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Tidak ada data transaksi yang sesuai filter.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($transactions as $t): ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-muted"><?= $no++; ?></td>
                            <td>
                                <span class="fw-bold text-dark font-monospace"><?= htmlspecialchars($t['kode_transaksi']); ?></span>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($t['nama_nasabah']); ?></div>
                                <small class="text-muted">@<?= htmlspecialchars($t['username_nasabah']); ?></small>
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
                            <td class="text-secondary small"><?= htmlspecialchars($t['nama_admin']); ?></td>
                            <td class="text-muted small"><?= format_tanggal($t['tanggal_transaksi']); ?></td>
                            <td class="text-end pe-4">
                                <a href="/admin/transaksi/detail.php?id=<?= $t['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-receipt"></i> Detail
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
