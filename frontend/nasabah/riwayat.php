<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/config/helpers.php';

auth_require('nasabah');
$user = auth_user();
$jenis = $_GET['jenis'] ?? '';
$mulai = trim($_GET['mulai'] ?? '');
$selesai = trim($_GET['selesai'] ?? '');
$where = ['t.user_id = :user_id'];
$params = [':user_id' => $user['id']];
if (in_array($jenis, ['setor', 'tarik'], true)) { $where[] = 't.jenis_transaksi = :jenis'; $params[':jenis'] = $jenis; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $mulai)) { $where[] = 't.tanggal_transaksi >= :mulai'; $params[':mulai'] = $mulai . ' 00:00:00'; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $selesai)) { $where[] = 't.tanggal_transaksi < DATE_ADD(:selesai, INTERVAL 1 DAY)'; $params[':selesai'] = $selesai; }
$stmt = $pdo->prepare('SELECT t.* FROM transactions t WHERE ' . implode(' AND ', $where) . ' ORDER BY t.tanggal_transaksi DESC, t.id DESC');
$stmt->execute($params);
$transactions = $stmt->fetchAll();
$pageTitle = 'Riwayat Transaksi Saya';
require_once __DIR__ . '/../templates/header.php';
?>
<div class="mb-4"><h2 class="fw-bold mb-1">Riwayat Transaksi</h2><p class="text-muted mb-0">Catatan setoran dan penarikan saldo Anda.</p></div>
<form class="card card-body border-0 shadow-sm mb-3" method="get"><div class="row g-2 align-items-end"><div class="col-sm-6 col-lg-3"><label class="form-label" for="jenis">Jenis transaksi</label><select class="form-select" id="jenis" name="jenis"><option value="">Semua jenis</option><option value="setor" <?= $jenis === 'setor' ? 'selected' : ''; ?>>Setoran</option><option value="tarik" <?= $jenis === 'tarik' ? 'selected' : ''; ?>>Penarikan</option></select></div><div class="col-sm-6 col-lg-3"><label class="form-label" for="mulai">Dari tanggal</label><input class="form-control" type="date" id="mulai" name="mulai" value="<?= htmlspecialchars($mulai); ?>"></div><div class="col-sm-6 col-lg-3"><label class="form-label" for="selesai">Sampai tanggal</label><input class="form-control" type="date" id="selesai" name="selesai" value="<?= htmlspecialchars($selesai); ?>"></div><div class="col-sm-6 col-lg-3"><button class="btn btn-success" type="submit"><i class="bi bi-funnel"></i> Terapkan</button> <a class="btn btn-outline-secondary" href="/nasabah/riwayat.php">Atur ulang</a></div></div></form>
<section class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Kode</th><th>Jenis</th><th>Nominal</th><th>Tanggal</th><th class="text-end pe-3">Struk</th></tr></thead><tbody>
<?php if (!$transactions): ?><tr><td colspan="5" class="text-center text-muted py-5">Belum ada transaksi yang sesuai.</td></tr><?php endif; ?>
<?php foreach ($transactions as $tx): ?><tr><td class="ps-3 fw-semibold font-monospace"><?= htmlspecialchars($tx['kode_transaksi']); ?></td><td><?php if ($tx['jenis_transaksi'] === 'setor'): ?><span class="badge bg-success-subtle text-success">Setor</span><?php else: ?><span class="badge bg-warning-subtle text-warning-emphasis">Tarik</span><?php endif; ?></td><td class="fw-bold <?= $tx['jenis_transaksi'] === 'setor' ? 'text-success' : 'text-danger'; ?>"><?= ($tx['jenis_transaksi'] === 'setor' ? '+' : '-') . format_rupiah($tx['total_nominal']); ?></td><td class="small text-muted"><?= format_tanggal($tx['tanggal_transaksi']); ?></td><td class="text-end pe-3"><a class="btn btn-sm btn-outline-secondary" href="/admin/transaksi/detail.php?id=<?= (int)$tx['id']; ?>"><i class="bi bi-receipt"></i> Struk</a></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>