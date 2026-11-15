<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/config/helpers.php';

auth_require('admin');
$mulai = $_GET['mulai'] ?? date('Y-m-01');
$selesai = $_GET['selesai'] ?? date('Y-m-d');
$validDate = static function ($value) {
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
};
$errors = [];
$transactions = [];
$summary = ['jumlah' => 0, 'setoran' => 0, 'penarikan' => 0, 'berat' => 0];
if (!$validDate($mulai) || !$validDate($selesai) || $mulai > $selesai) {
    $errors[] = 'Rentang tanggal tidak valid. Pastikan tanggal awal tidak melewati tanggal akhir.';
} else {
    $conditions = 't.tanggal_transaksi >= :mulai AND t.tanggal_transaksi < DATE_ADD(:selesai, INTERVAL 1 DAY)';
    $params = [':mulai' => $mulai . ' 00:00:00', ':selesai' => $selesai];
    $stmt = $pdo->prepare("SELECT COUNT(*) AS jumlah, COALESCE(SUM(CASE WHEN jenis_transaksi = 'setor' THEN total_nominal ELSE 0 END), 0) AS setoran, COALESCE(SUM(CASE WHEN jenis_transaksi = 'tarik' THEN total_nominal ELSE 0 END), 0) AS penarikan FROM transactions t WHERE {$conditions}");
    $stmt->execute($params);
    $summary = $stmt->fetch();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(d.berat_kg), 0) FROM transaction_details d JOIN transactions t ON t.id = d.transaction_id WHERE {$conditions}");
    $stmt->execute($params);
    $summary['berat'] = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT t.*, u.nama_lengkap AS nama_nasabah, COALESCE(d.total_berat, 0) AS total_berat FROM transactions t JOIN users u ON u.id = t.user_id LEFT JOIN (SELECT transaction_id, SUM(berat_kg) AS total_berat FROM transaction_details GROUP BY transaction_id) d ON d.transaction_id = t.id WHERE {$conditions} ORDER BY t.tanggal_transaksi DESC, t.id DESC");
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
}
$pageTitle = 'Laporan Transaksi';
require_once __DIR__ . '/../../templates/header.php';
?>
<style>@media print { nav, footer, .no-print { display: none !important; } main { padding: 0 !important; } .card { box-shadow: none !important; border: 0 !important; } }</style>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="fw-bold mb-1">Laporan Transaksi</h2><p class="text-muted mb-0">Rekap setoran, penarikan, dan berat sampah menurut periode.</p></div><button class="btn btn-outline-success no-print" onclick="window.print()" type="button"><i class="bi bi-printer"></i> Cetak Laporan</button></div>
<form class="card card-body border-0 shadow-sm mb-3 no-print" method="get"><div class="row g-3 align-items-end"><div class="col-sm-5"><label class="form-label" for="mulai">Tanggal awal</label><input class="form-control" id="mulai" name="mulai" type="date" required value="<?= htmlspecialchars($mulai); ?>"></div><div class="col-sm-5"><label class="form-label" for="selesai">Tanggal akhir</label><input class="form-control" id="selesai" name="selesai" type="date" required value="<?= htmlspecialchars($selesai); ?>"></div><div class="col-sm-2"><button class="btn btn-success" type="submit"><i class="bi bi-funnel"></i> Tampilkan</button></div></div></form>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= htmlspecialchars($error); ?></div><?php endforeach; ?>
<?php if (!$errors): ?><p class="small text-muted">Periode <?= format_tanggal($mulai); ?> sampai <?= format_tanggal($selesai . ' 23:59:59'); ?></p>
<div class="row g-3 mb-4"><div class="col-sm-6 col-xl-3"><div class="card card-stat p-3"><small class="text-muted">Jumlah transaksi</small><strong class="fs-4"><?= number_format((int)$summary['jumlah']); ?></strong></div></div><div class="col-sm-6 col-xl-3"><div class="card card-stat p-3"><small class="text-muted">Total nilai setoran</small><strong class="fs-5 text-success"><?= format_rupiah($summary['setoran']); ?></strong></div></div><div class="col-sm-6 col-xl-3"><div class="card card-stat p-3"><small class="text-muted">Total penarikan</small><strong class="fs-5 text-warning-emphasis"><?= format_rupiah($summary['penarikan']); ?></strong></div></div><div class="col-sm-6 col-xl-3"><div class="card card-stat p-3"><small class="text-muted">Berat sampah terkumpul</small><strong class="fs-5"><?= format_berat($summary['berat']); ?></strong></div></div></div>
<section class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Tanggal</th><th>Kode</th><th>Nasabah</th><th>Jenis</th><th class="text-end">Berat</th><th class="text-end">Nominal</th></tr></thead><tbody>
<?php if (!$transactions): ?><tr><td colspan="6" class="text-center text-muted py-5">Tidak ada transaksi pada periode ini.</td></tr><?php endif; ?>
<?php foreach ($transactions as $tx): ?><tr><td class="ps-3 small"><?= format_tanggal($tx['tanggal_transaksi']); ?></td><td><a class="font-monospace text-decoration-none" href="/admin/transaksi/detail.php?id=<?= (int)$tx['id']; ?>"><?= htmlspecialchars($tx['kode_transaksi']); ?></a></td><td><?= htmlspecialchars($tx['nama_nasabah']); ?></td><td><?= $tx['jenis_transaksi'] === 'setor' ? '<span class="badge bg-success-subtle text-success">Setor</span>' : '<span class="badge bg-warning-subtle text-warning-emphasis">Tarik</span>'; ?></td><td class="text-end"><?= $tx['jenis_transaksi'] === 'setor' ? format_berat($tx['total_berat']) : '-'; ?></td><td class="text-end fw-semibold <?= $tx['jenis_transaksi'] === 'setor' ? 'text-success' : 'text-danger'; ?>"><?= format_rupiah($tx['total_nominal']); ?></td></tr><?php endforeach; ?>
</tbody></table></div></section><?php endif; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>