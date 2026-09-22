<?php
// admin/laporan/index.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

auth_require('admin');

// Default filter bulan berjalan jika belum ditentukan
$tglAwal  = $_GET['tgl_awal'] ?? date('Y-m-01');
$tglAkhir = $_GET['tgl_akhir'] ?? date('Y-m-t');
$jenis    = trim($_GET['jenis'] ?? '');
$userId   = (int)($_GET['user_id'] ?? 0);

// Ambil daftar nasabah untuk opsi filter
$nasabahList = $pdo->query("SELECT id, nama_lengkap, username FROM users WHERE role = 'nasabah' ORDER BY nama_lengkap ASC")->fetchAll();

// Query Transaksi
$sql = "
    SELECT t.*, u.nama_lengkap AS nama_nasabah, a.nama_lengkap AS nama_admin
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN users a ON t.admin_id = a.id
    WHERE DATE(t.tanggal_transaksi) >= :tgl_awal 
      AND DATE(t.tanggal_transaksi) <= :tgl_akhir
";
$params = [
    ':tgl_awal'  => $tglAwal,
    ':tgl_akhir' => $tglAkhir
];

if (!empty($jenis) && in_array($jenis, ['setor', 'tarik'])) {
    $sql .= " AND t.jenis_transaksi = :jenis";
    $params[':jenis'] = $jenis;
}

if ($userId > 0) {
    $sql .= " AND t.user_id = :uid";
    $params[':uid'] = $userId;
}

$sql .= " ORDER BY t.tanggal_transaksi ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Hitung total ringkasan
$totalSetor = 0;
$totalTarik = 0;
foreach ($transactions as $t) {
    if ($t['jenis_transaksi'] === 'setor') {
        $totalSetor += (float)$t['total_nominal'];
    } else {
        $totalTarik += (float)$t['total_nominal'];
    }
}

// Rekapitulasi Berat per Kategori Sampah dalam periode ini
$sqlCat = "
    SELECT c.nama_kategori, 
           COALESCE(SUM(td.berat_kg), 0) AS total_berat, 
           COALESCE(SUM(td.subtotal), 0) AS total_nominal
    FROM categories c
    LEFT JOIN transaction_details td ON c.id = td.category_id
    LEFT JOIN transactions t ON td.transaction_id = t.id 
         AND DATE(t.tanggal_transaksi) >= :tgl_awal 
         AND DATE(t.tanggal_transaksi) <= :tgl_akhir
    GROUP BY c.id, c.nama_kategori
    ORDER BY total_berat DESC
";
$stmtCat = $pdo->prepare($sqlCat);
$stmtCat->execute([':tgl_awal' => $tglAwal, ':tgl_akhir' => $tglAkhir]);
$categorySummary = $stmtCat->fetchAll();

$totalBeratSemua = 0;
foreach ($categorySummary as $cs) {
    $totalBeratSemua += (float)$cs['total_berat'];
}

$pageTitle = 'Laporan Rekapitulasi Transaksi';
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 d-print-none">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="/admin/index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Laporan Rekapitulasi</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Laporan Rekapitulasi Transaksi</h2>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary shadow-sm">
            <i class="bi bi-printer me-1"></i> Cetak Laporan PDF / Print
        </button>
    </div>
</div>

<!-- Filter Panel -->
<div class="card border-0 shadow-sm rounded-3 mb-4 d-print-none">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Dari Tanggal</label>
                <input type="date" name="tgl_awal" class="form-control" value="<?= htmlspecialchars($tglAwal); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Sampai Tanggal</label>
                <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tglAkhir); ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Jenis Transaksi</label>
                <select name="jenis" class="form-select">
                    <option value="">Semua Jenis</option>
                    <option value="setor" <?= $jenis === 'setor' ? 'selected' : ''; ?>>Setoran Saja</option>
                    <option value="tarik" <?= $jenis === 'tarik' ? 'selected' : ''; ?>>Penarikan Saja</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Nasabah (Warga)</label>
                <select name="user_id" class="form-select">
                    <option value="0">Semua Warga</option>
                    <?php foreach ($nasabahList as $n): ?>
                        <option value="<?= $n['id']; ?>" <?= $userId === $n['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($n['nama_lengkap']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Terapkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Laporan Print Container -->
<div class="printable-report">
    <!-- Kop Laporan -->
    <div class="text-center mb-4 border-bottom pb-3">
        <h3 class="fw-bold mb-1">REKAPITULASI TRANSAKSI BANK SAMPAH RT/RW</h3>
        <p class="text-muted mb-0">Periode: <strong><?= date('d/m/Y', strtotime($tglAwal)); ?></strong> s/d <strong><?= date('d/m/Y', strtotime($tglAkhir)); ?></strong></p>
    </div>

    <!-- Ringkasan Angka Periode -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border shadow-sm p-3 bg-white text-center">
                <div class="text-muted small fw-semibold">Total Setoran Sampah (Masuk)</div>
                <h3 class="fw-bold text-success mt-1 mb-0"><?= format_rupiah($totalSetor); ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border shadow-sm p-3 bg-white text-center">
                <div class="text-muted small fw-semibold">Total Penarikan Saldo (Keluar)</div>
                <h3 class="fw-bold text-danger mt-1 mb-0"><?= format_rupiah($totalTarik); ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border shadow-sm p-3 bg-white text-center">
                <div class="text-muted small fw-semibold">Total Bobot Sampah Dikelola</div>
                <h3 class="fw-bold text-primary mt-1 mb-0"><?= format_berat($totalBeratSemua); ?></h3>
            </div>
        </div>
    </div>

    <!-- Tabel Rekap Sampah per Kategori -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-light py-2">
            <h6 class="fw-bold mb-0 text-secondary">A. Rekapitulasi Berdasarkan Kategori Sampah</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Kategori Sampah</th>
                        <th class="text-center">Total Berat Terkumpul</th>
                        <th class="text-end">Nilai Nominal Tabungan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($categorySummary as $cs): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($cs['nama_kategori']); ?></td>
                            <td class="text-center fw-bold"><?= format_berat($cs['total_berat']); ?></td>
                            <td class="text-end"><?= format_rupiah($cs['total_nominal']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="2" class="text-end">JUMLAH KESELURUHAN:</th>
                        <th class="text-center fs-6 text-primary fw-bold"><?= format_berat($totalBeratSemua); ?></th>
                        <th class="text-end fs-6 text-success fw-bold"><?= format_rupiah($totalSetor); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Tabel Rincian Semua Transaksi Periode Ini -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-light py-2">
            <h6 class="fw-bold mb-0 text-secondary">B. Daftar Rincian Log Transaksi</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Kode Transaksi</th>
                        <th>Tanggal</th>
                        <th>Nama Nasabah</th>
                        <th>Jenis</th>
                        <th class="text-end">Nominal</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-3 text-muted">Tidak ada transaksi tercatat pada rentang tanggal ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="font-monospace fw-semibold"><?= htmlspecialchars($t['kode_transaksi']); ?></td>
                                <td><?= format_tanggal($t['tanggal_transaksi']); ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($t['nama_nasabah']); ?></td>
                                <td>
                                    <?php if ($t['jenis_transaksi'] === 'setor'): ?>
                                        <span class="badge bg-success">Setor</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Tarik</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold <?= $t['jenis_transaksi'] === 'setor' ? 'text-success' : 'text-danger'; ?>">
                                    <?= ($t['jenis_transaksi'] === 'setor' ? '+' : '-') . format_rupiah($t['total_nominal']); ?>
                                </td>
                                <td><?= htmlspecialchars($t['nama_admin']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Kolom Tanda Tangan untuk Laporan Resmi -->
    <div class="row text-center mt-5 pt-4 border-top small">
        <div class="col-4">
            <div class="text-muted mb-5">Ketua RT/RW,</div>
            <div class="fw-bold">(&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;)</div>
        </div>
        <div class="col-4">
            <div class="text-muted mb-5">Bendahara Bank Sampah,</div>
            <div class="fw-bold">(&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;)</div>
        </div>
        <div class="col-4">
            <div class="text-muted mb-5">Penanggung Jawab Sistem,</div>
            <div class="fw-bold"><?= htmlspecialchars(auth_user()['nama_lengkap']); ?></div>
        </div>
    </div>
</div>

<style>
@media print {
    body {
        background-color: #fff !important;
        font-size: 12px;
    }
    .navbar, footer, .d-print-none {
        display: none !important;
    }
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
    .table {
        font-size: 11px;
    }
}
</style>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
