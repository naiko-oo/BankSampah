<?php
// admin/nasabah/edit.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

auth_require('admin');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'nasabah' LIMIT 1");
$stmt->execute([':id' => $id]);
$nasabah = $stmt->fetch();

if (!$nasabah) {
    set_flash('danger', 'Data nasabah tidak ditemukan.');
    header('Location: /admin/nasabah/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap  = trim($_POST['nama_lengkap'] ?? '');
    $nomor_telepon = trim($_POST['nomor_telepon'] ?? '');
    $alamat        = trim($_POST['alamat'] ?? '');
    $password_baru = $_POST['password_baru'] ?? '';

    if (empty($nama_lengkap)) {
        $error = 'Nama lengkap nasabah wajib diisi.';
    } else {
        try {
            if (!empty($password_baru)) {
                $hash = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET nama_lengkap = :nama, nomor_telepon = :telp, alamat = :alamat, password = :pwd
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nama'   => $nama_lengkap,
                    ':telp'   => $nomor_telepon,
                    ':alamat' => $alamat,
                    ':pwd'    => $hash,
                    ':id'     => $id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET nama_lengkap = :nama, nomor_telepon = :telp, alamat = :alamat
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nama'   => $nama_lengkap,
                    ':telp'   => $nomor_telepon,
                    ':alamat' => $alamat,
                    ':id'     => $id
                ]);
            }

            set_flash('success', "Profil nasabah <strong>" . htmlspecialchars($nama_lengkap) . "</strong> berhasil diperbarui.");
            header('Location: /admin/nasabah/index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui profil nasabah: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Data Nasabah';
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Edit Data Nasabah</h4>
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
                    <label class="form-label fw-semibold">Username Akun</label>
                    <input type="text" class="form-control bg-light" value="@<?= htmlspecialchars($nasabah['username']); ?>" readonly disabled>
                    <div class="form-text">Username bersifat permanen dan tidak dapat diubah.</div>
                </div>

                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label fw-semibold">Nama Lengkap Warga <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? $nasabah['nama_lengkap']); ?>">
                </div>

                <div class="mb-3">
                    <label for="nomor_telepon" class="form-label fw-semibold">Nomor WhatsApp / HP</label>
                    <input type="tel" class="form-control" id="nomor_telepon" name="nomor_telepon" value="<?= htmlspecialchars($_POST['nomor_telepon'] ?? $nasabah['nomor_telepon']); ?>">
                </div>

                <div class="mb-3">
                    <label for="alamat" class="form-label fw-semibold">Alamat Rumah (RT / RW)</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="2"><?= htmlspecialchars($_POST['alamat'] ?? $nasabah['alamat']); ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="password_baru" class="form-label fw-semibold">Ganti Kata Sandi (Opsional)</label>
                    <input type="password" class="form-control" id="password_baru" name="password_baru" placeholder="Kosongkan jika kata sandi tidak ingin diubah">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="/admin/nasabah/index.php" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
