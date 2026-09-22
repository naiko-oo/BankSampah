<?php
// index.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

// Jika sudah login, langsung arahkan ke dashboard masing-masing
if (auth_check()) {
    $user = auth_user();
    if ($user['role'] === 'admin') {
        header('Location: /admin/index.php');
    } else {
        header('Location: /nasabah/index.php');
    }
    exit;
}

// Ambil daftar kategori dan tarif sampah untuk informasi umum warga
$stmt = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori ASC");
$categories = $stmt->fetchAll();

$pageTitle = 'Beranda';
require_once __DIR__ . '/templates/header.php';
?>

<!-- Hero Section -->
<div class="row align-items-center mb-5 py-4">
    <div class="col-lg-7 text-center text-lg-start">
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill mb-3">
            <i class="bi bi-shield-check"></i> Lingkungan Bersih, Tabungan Bertambah
        </span>
        <h1 class="display-5 fw-bold text-dark mb-3">
            Sistem Informasi Bank Sampah Digital RT/RW
        </h1>
        <p class="lead text-secondary mb-4">
            Kelola setoran sampah anorganik Anda secara transparan. Pantau saldo tabungan, cek riwayat transaksi, dan ketahui tarif sampah terkini secara real-time.
        </p>
        <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start">
            <a href="/login.php" class="btn btn-success btn-lg px-4 gap-3 shadow-sm">
                <i class="bi bi-box-arrow-in-right"></i> Masuk Sekarang
            </a>
            <a href="#kategori-sampah" class="btn btn-outline-secondary btn-lg px-4">
                <i class="bi bi-tag"></i> Lihat Daftar Tarif Sampah
            </a>
        </div>
    </div>
    <div class="col-lg-5 text-center mt-4 mt-lg-0">
        <div class="card border-0 shadow-lg p-4 bg-white rounded-4">
            <div class="text-success display-1 mb-2">
                <i class="bi bi-recycle"></i>
            </div>
            <h5 class="fw-bold">Peduli Lingkungan & Nilai Ekonomi</h5>
            <p class="text-muted small">
                Pilah sampah dari rumah Anda, setorkan ke pengurus bank sampah RT/RW, dan jadikan saldo tabungan yang bisa ditarik kapan saja.
            </p>
            <div class="row text-center mt-3 g-2 border-top pt-3">
                <div class="col-6 border-end">
                    <h4 class="fw-bold text-success mb-0"><?= count($categories); ?>+</h4>
                    <span class="text-muted small">Kategori Diterima</span>
                </div>
                <div class="col-6">
                    <h4 class="fw-bold text-primary mb-0">100%</h4>
                    <span class="text-muted small">Transparan</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daftar Tarif Sampah Terkini -->
<div class="row" id="kategori-sampah">
    <div class="col-12 text-center mb-4">
        <h3 class="fw-bold">Daftar Kategori & Tarif Sampah Terkini</h3>
        <p class="text-muted">Tarif resmi yang berlaku untuk setiap kilogram ($kg$) sampah yang disetorkan warga</p>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No</th>
                            <th>Jenis / Kategori Sampah</th>
                            <th>Deskripsi / Syarat Penyetoran</th>
                            <th class="text-end pe-4">Tarif per Kg</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Belum ada data kategori sampah.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($categories as $cat): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-muted"><?= $no++; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($cat['nama_kategori']); ?></div>
                                    </td>
                                    <td class="text-muted small">
                                        <?= htmlspecialchars($cat['deskripsi'] ?? 'Tidak ada catatan khusus'); ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <span class="badge bg-success-subtle text-success fs-6 fw-bold px-3 py-2">
                                            <?= format_rupiah($cat['harga_per_kg']); ?> / kg
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
