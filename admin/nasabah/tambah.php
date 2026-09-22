<?php
// admin/nasabah/tambah.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

auth_require('admin');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap  = trim($_POST['nama_lengkap'] ?? '');
    $username      = strtolower(trim($_POST['username'] ?? ''));
    $password      = $_POST['password'] ?? '';
    $nomor_telepon = trim($_POST['nomor_telepon'] ?? '');
    $alamat        = trim($_POST['alamat'] ?? '');
    $saldo_awal    = (float)($_POST['saldo_awal'] ?? 0);

    if (empty($nama_lengkap) || empty($username) || empty($password)) {
        $error = 'Nama lengkap, username, dan kata sandi wajib diisi.';
    } elseif ($saldo_awal < 0) {
        $error = 'Saldo awal tidak boleh bernilai negatif.';
    } else {
        // Cek username unik
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username '{$username}' sudah digunakan. Silakan pilih username lain.";
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password, nama_lengkap, nomor_telepon, alamat, role, saldo)
                    VALUES (:username, :password, :nama, :telp, :alamat, 'nasabah', :saldo)
                ");
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $hash,
                    ':nama'     => $nama_lengkap,
                    ':telp'     => $nomor_telepon,
                    ':alamat'   => $alamat,
                    ':saldo'    => $saldo_awal
                ]);

                set_flash('success', "Akun nasabah <strong>" . htmlspecialchars($nama_lengkap) . "</strong> berhasil didaftarkan.");
                header('Location: /admin/nasabah/index.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal mendaftarkan nasabah: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Registrasi Nasabah Baru';
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Registrasi Nasabah Baru</h4>
                <a href="/admin/nasabah/index.php" class="btn btn-sm btn-outline-secondary">Kembali</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label fw-semibold">Nama Lengkap Warga <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" placeholder="Contoh: Joko Widodo" required value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label fw-semibold">Username Login <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Contoh: jokow" required value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>">
                        <div class="form-text">Gunakan huruf kecil tanpa spasi.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label fw-semibold">Kata Sandi Awal <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="nomor_telepon" class="form-label fw-semibold">Nomor WhatsApp / HP</label>
                    <input type="tel" class="form-control" id="nomor_telepon" name="nomor_telepon" placeholder="081234567890" value="<?= htmlspecialchars($_POST['nomor_telepon'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label for="alamat" class="form-label fw-semibold">Alamat Rumah (RT / RW)</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="2" placeholder="Contoh: Jl. Delima No. 4, RT 02 / RW 05"><?= htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="saldo_awal" class="form-label fw-semibold">Saldo Awal (Opsional)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" step="1000" min="0" class="form-control" id="saldo_awal" name="saldo_awal" value="<?= htmlspecialchars($_POST['saldo_awal'] ?? '0'); ?>">
                    </div>
                    <div class="form-text">Biarkan 0 jika nasabah belum memiliki saldo simpanan.</div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="/admin/nasabah/index.php" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-success px-4">Daftarkan Warga</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
