<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/config/helpers.php';

if (!auth_check()) {
    set_flash('warning', 'Silakan login untuk melihat detail transaksi.');
    header('Location: /login.php');
    exit;
}
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT t.*, u.nama_lengkap AS nama_nasabah, u.username, a.nama_lengkap AS nama_admin FROM transactions t JOIN users u ON u.id = t.user_id JOIN users a ON a.id = t.admin_id WHERE t.id = :id');
$stmt->execute([':id' => $id ?: 0]);
$transaction = $stmt->fetch();
if (!$transaction || (auth_user()['role'] !== 'admin' && (int)$transaction['user_id'] !== (int)auth_user()['id'])) {
    http_response_code(404);
    exit('Transaksi tidak ditemukan.');
}
$details = [];
if ($transaction['jenis_transaksi'] === 'setor') {
    $stmt = $pdo->prepare('SELECT d.*, c.nama_kategori FROM transaction_details d JOIN categories c ON c.id = d.category_id WHERE d.transaction_id = :id ORDER BY d.id');
    $stmt->execute([':id' => $id]);
    $details = $stmt->fetchAll();
}
$pageTitle = 'Detail Transaksi';
require_once __DIR__ . '/../../templates/header.php';
?>
<style>@media print { nav, footer, .no-print { display: none !important; } main { padding: 0 !important; } .receipt { box-shadow: none !important; } }</style>
<div class="d-flex justify-content-between align-items-center mb-3 no-print"><a class="btn btn-outline-secondary" href="<?= auth_user()['role'] === 'admin' ? '/admin/transaksi/index.php' : '/nasabah/riwayat.php'; ?>"><i class="bi bi-arrow-left"></i> Kembali</a><button class="btn btn-success" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Struk</button></div>
<section class="card border-0 shadow-sm p-4 receipt" style="max-width: 800px; margin: auto"><div class="text-center border-bottom pb-3 mb-3"><i class="bi bi-recycle text-success fs-1"></i><h3 class="fw-bold mb-1">Bank Sampah Digital RT/RW</h3><div class="text-muted">Bukti Transaksi</div></div>
<div class="row g-3 mb-4"><div class="col-sm-6"><small class="text-muted">Kode transaksi</small><div class="fw-bold font-monospace"><?= htmlspecialchars($transaction['kode_transaksi']); ?></div></div><div class="col-sm-6"><small class="text-muted">Tanggal</small><div class="fw-semibold"><?= format_tanggal($transaction['tanggal_transaksi']); ?></div></div><div class="col-sm-6"><small class="text-muted">Nasabah</small><div class="fw-semibold"><?= htmlspecialchars($transaction['nama_nasabah']); ?> (@<?= htmlspecialchars($transaction['username']); ?>)</div></div><div class="col-sm-6"><small class="text-muted">Dicatat oleh</small><div class="fw-semibold"><?= htmlspecialchars($transaction['nama_admin']); ?></div></div><div class="col-sm-6"><small class="text-muted">Jenis</small><div><span class="badge <?= $transaction['jenis_transaksi'] === 'setor' ? 'bg-success' : 'bg-warning text-dark'; ?>"><?= $transaction['jenis_transaksi'] === 'setor' ? 'Setoran' : 'Penarikan'; ?></span></div></div></div>
<?php if ($details): ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Kategori</th><th class="text-end">Berat</th><th class="text-end">Tarif / kg</th><th class="text-end">Subtotal</th></tr></thead><tbody><?php foreach ($details as $detail): ?><tr><td><?= htmlspecialchars($detail['nama_kategori']); ?></td><td class="text-end"><?= format_berat($detail['berat_kg']); ?></td><td class="text-end"><?= format_rupiah($detail['harga_per_kg']); ?></td><td class="text-end fw-semibold"><?= format_rupiah($detail['subtotal']); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<div class="border-top pt-3 d-flex justify-content-between fs-5"><span class="fw-semibold">Total <?= $transaction['jenis_transaksi'] === 'setor' ? 'setoran' : 'penarikan'; ?></span><strong class="<?= $transaction['jenis_transaksi'] === 'setor' ? 'text-success' : 'text-warning-emphasis'; ?>"><?= format_rupiah($transaction['total_nominal']); ?></strong></div>
<?php if ($transaction['catatan']): ?><div class="mt-3"><small class="text-muted">Catatan</small><div><?= nl2br(htmlspecialchars($transaction['catatan'])); ?></div></div><?php endif; ?></section>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>