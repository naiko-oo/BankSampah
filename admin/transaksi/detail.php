<?php
// admin/transaksi/detail.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

auth_require(); // Bisa diakses admin atau nasabah (dengan validasi kepemilikan)
$currentUser = auth_user();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT t.*, u.nama_lengkap AS nama_nasabah, u.username AS username_nasabah, 
           u.nomor_telepon AS telp_nasabah, u.alamat AS alamat_nasabah, u.saldo AS saldo_sekarang,
           a.nama_lengkap AS nama_admin
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN users a ON t.admin_id = a.id
    WHERE t.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$tx = $stmt->fetch();

if (!$tx) {
    set_flash('danger', 'Transaksi tidak ditemukan.');
    header('Location: /index.php');
    exit;
}

// Jika nasabah biasa, pastikan hanya bisa melihat transaksinya sendiri
if ($currentUser['role'] === 'nasabah' && $currentUser['id'] !== $tx['user_id']) {
    set_flash('danger', 'Anda tidak berhak melihat transaksi ini.');
    header('Location: /nasabah/riwayat.php');
    exit;
}

// Ambil item rincian jika jenis setor
$details = [];
if ($tx['jenis_transaksi'] === 'setor') {
    $stmtDetail = $pdo->prepare("
        SELECT td.*, c.nama_kategori
        FROM transaction_details td
        JOIN categories c ON td.category_id = c.id
        WHERE td.transaction_id = :tid
    ");
    $stmtDetail->execute([':tid' => $id]);
    $details = $stmtDetail->fetchAll();
}

$pageTitle = 'Struk Transaksi: ' . $tx['kode_transaksi'];
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-9 col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
            <a href="<?= $currentUser['role'] === 'admin' ? '/admin/transaksi/index.php' : '/nasabah/riwayat.php'; ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="bi bi-printer"></i> Cetak Struk Transaksi
            </button>
        </div>

        <!-- Kartu Struk Transaksi -->
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white printable-receipt">
            <!-- Header Struk -->
            <div class="text-center border-bottom pb-3 mb-4">
                <div class="text-success fs-2 mb-1"><i class="bi bi-recycle"></i></div>
                <h4 class="fw-bold mb-1">BANK SAMPAH DIGITAL RT/RW</h4>
                <div class="text-muted small">Sekretariat Pengelolaan Sampah Lingkungan Bersih & Sejahtera</div>
                <div class="mt-2">
                    <span class="badge bg-secondary font-monospace px-3 py-2 fs-6">
                        <?= htmlspecialchars($tx['kode_transaksi']); ?>
                    </span>
                </div>
            </div>

            <!-- Metadata Transaksi -->
            <div class="row g-3 mb-4 small">
                <div class="col-6">
                    <div class="text-muted">Tanggal Transaksi:</div>
                    <div class="fw-bold text-dark"><?= format_tanggal($tx['tanggal_transaksi']); ?></div>
                    <div class="text-muted mt-2">Petugas Pencatat:</div>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($tx['nama_admin']); ?></div>
                </div>
                <div class="col-6 text-end">
                    <div class="text-muted">Nama Nasabah:</div>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($tx['nama_nasabah']); ?></div>
                    <div class="text-muted mt-2">Alamat / RT RW:</div>
                    <div class="text-dark"><?= htmlspecialchars($tx['alamat_nasabah'] ?: '-'); ?></div>
                </div>
            </div>

            <!-- Status Jenis Transaksi -->
            <div class="alert <?= $tx['jenis_transaksi'] === 'setor' ? 'alert-success' : 'alert-warning'; ?> d-flex justify-content-between align-items-center mb-4">
                <div>
                    <strong>JENIS TRANSAKSI:</strong> 
                    <?= $tx['jenis_transaksi'] === 'setor' ? 'SETORAN SAMPAH ANORGANIK' : 'PENARIKAN SALDO TUNAI'; ?>
                </div>
                <div class="fs-5 fw-bold">
                    <?= $tx['jenis_transaksi'] === 'setor' ? '+' : '-'; ?><?= format_rupiah($tx['total_nominal']); ?>
                </div>
            </div>

            <!-- Rincian Sampah (Jika Setor) -->
            <?php if ($tx['jenis_transaksi'] === 'setor'): ?>
                <h6 class="fw-bold text-secondary mb-2">Rincian Timbangan Sampah:</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Kategori Sampah</th>
                                <th class="text-end">Tarif Satuan</th>
                                <th class="text-center">Berat</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($details as $d): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($d['nama_kategori']); ?></td>
                                    <td class="text-end"><?= format_rupiah($d['harga_per_kg']); ?>/kg</td>
                                    <td class="text-center"><?= format_berat($d['berat_kg']); ?></td>
                                    <td class="text-end fw-bold"><?= format_rupiah($d['subtotal']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="4" class="text-end">TOTAL DIKREDITKAN KE TABUNGAN:</th>
                                <th class="text-end text-success fs-5 fw-bold"><?= format_rupiah($tx['total_nominal']); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <!-- Informasi Penarikan -->
                <div class="card bg-light border-0 p-3 mb-4">
                    <div class="row">
                        <div class="col-6 text-muted">Jumlah Uang Ditarik:</div>
                        <div class="col-6 text-end fw-bold text-danger"><?= format_rupiah($tx['total_nominal']); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($tx['catatan'])): ?>
                <div class="mb-4 small">
                    <strong>Catatan:</strong> <?= htmlspecialchars($tx['catatan']); ?>
                </div>
            <?php endif; ?>

            <!-- Footer Tanda Tangan Struk -->
            <div class="row text-center mt-4 pt-3 border-top small">
                <div class="col-6">
                    <div class="text-muted mb-5">Nasabah Warga,</div>
                    <div class="fw-bold"><?= htmlspecialchars($tx['nama_nasabah']); ?></div>
                </div>
                <div class="col-6">
                    <div class="text-muted mb-5">Pengurus / Petugas,</div>
                    <div class="fw-bold"><?= htmlspecialchars($tx['nama_admin']); ?></div>
                </div>
            </div>

            <div class="text-center text-muted small mt-4 pt-2 border-top d-none d-print-block">
                Simpan struk ini sebagai bukti transaksi resmi Bank Sampah Digital RT/RW.
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body {
        background-color: #fff !important;
    }
    .navbar, footer, .d-print-none {
        display: none !important;
    }
    .printable-receipt {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        padding: 0 !important;
    }
}
</style>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
