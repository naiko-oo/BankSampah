<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/config/helpers.php';
require_once __DIR__ . '/../../../backend/services/bank_sampah_rules.php';

auth_require('admin');
$errors = [];
$editing = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($action === 'hapus' && $id) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE user_id = :id');
            $stmt->execute([':id' => $id]);
            if ((int)$stmt->fetchColumn() > 0) {
                set_flash('warning', 'Nasabah memiliki riwayat transaksi, sehingga datanya tidak dapat dihapus.');
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'nasabah'");
                $stmt->execute([':id' => $id]);
                set_flash('success', 'Data nasabah berhasil dihapus.');
            }
            header('Location: /admin/nasabah/index.php');
            exit;
        }

        if ($action === 'simpan') {
            $nama = trim($_POST['nama_lengkap'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $telepon = trim($_POST['nomor_telepon'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');
            $password = $_POST['password'] ?? '';

            $errors = member_validation_errors($nama, $username, $password, !$id);

            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username AND id <> :id LIMIT 1');
            $stmt->execute([':username' => $username, ':id' => $id ?: 0]);
            if ($stmt->fetch()) $errors[] = 'Username sudah digunakan akun lain.';

            if (!$errors) {
                if ($id) {
                    $sql = 'UPDATE users SET nama_lengkap = :nama, username = :username, nomor_telepon = :telepon, alamat = :alamat';
                    $params = [':nama' => $nama, ':username' => $username, ':telepon' => $telepon ?: null, ':alamat' => $alamat ?: null, ':id' => $id];
                    if ($password !== '') { $sql .= ', password = :password'; $params[':password'] = password_hash($password, PASSWORD_DEFAULT); }
                    $sql .= " WHERE id = :id AND role = 'nasabah'";
                    $pdo->prepare($sql)->execute($params);
                    set_flash('success', 'Data nasabah berhasil diperbarui.');
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, nomor_telepon, alamat, role) VALUES (:username, :password, :nama, :telepon, :alamat, 'nasabah')");
                    $stmt->execute([':username' => $username, ':password' => password_hash($password, PASSWORD_DEFAULT), ':nama' => $nama, ':telepon' => $telepon ?: null, ':alamat' => $alamat ?: null]);
                    set_flash('success', 'Akun nasabah berhasil dibuat.');
                }
                header('Location: /admin/nasabah/index.php');
                exit;
            }
        }
    }
}

if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'nasabah'");
    $stmt->execute([':id' => $editId]);
    $editing = $stmt->fetch();
}
$nasabah = $pdo->query("SELECT * FROM users WHERE role = 'nasabah' ORDER BY nama_lengkap")->fetchAll();
$pageTitle = 'Data Nasabah';
require_once __DIR__ . '/../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="fw-bold mb-1">Data Nasabah</h2><p class="text-muted mb-0">Kelola akun dan informasi warga.</p></div><a class="btn btn-outline-secondary" href="/admin/index.php"><i class="bi bi-arrow-left"></i> Dashboard</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= htmlspecialchars($error); ?></div><?php endforeach; ?>
<div class="row g-4">
    <div class="col-lg-4"><section class="card border-0 shadow-sm p-4"><h5 class="fw-bold mb-3"><?= $editing ? 'Ubah Data Nasabah' : 'Daftarkan Nasabah'; ?></h5>
        <form method="post"><?= csrf_field(); ?><input type="hidden" name="action" value="simpan"><input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0); ?>">
            <div class="mb-3"><label class="form-label" for="nama_lengkap">Nama lengkap</label><input class="form-control" id="nama_lengkap" name="nama_lengkap" maxlength="100" required value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? $editing['nama_lengkap'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label" for="username">Username</label><input class="form-control" id="username" name="username" maxlength="50" required value="<?= htmlspecialchars($_POST['username'] ?? $editing['username'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label" for="nomor_telepon">Nomor telepon</label><input class="form-control" id="nomor_telepon" name="nomor_telepon" maxlength="20" value="<?= htmlspecialchars($_POST['nomor_telepon'] ?? $editing['nomor_telepon'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label" for="alamat">Alamat</label><textarea class="form-control" id="alamat" name="alamat" rows="2"><?= htmlspecialchars($_POST['alamat'] ?? $editing['alamat'] ?? ''); ?></textarea></div>
            <div class="mb-3"><label class="form-label" for="password">Kata sandi <?= $editing ? '(isi hanya jika ingin mengganti)' : ''; ?></label><input class="form-control" id="password" name="password" type="password" <?= $editing ? '' : 'required'; ?> minlength="8" autocomplete="new-password"></div>
            <button class="btn btn-success" type="submit"><i class="bi bi-save"></i> Simpan</button><?php if ($editing): ?> <a class="btn btn-outline-secondary" href="/admin/nasabah/index.php">Batal</a><?php endif; ?>
        </form>
    </section></div>
    <div class="col-lg-8"><section class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Nasabah</th><th>Kontak</th><th>Saldo</th><th class="text-end pe-3">Aksi</th></tr></thead><tbody>
        <?php if (!$nasabah): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada nasabah.</td></tr><?php endif; ?>
        <?php foreach ($nasabah as $person): ?><tr><td class="ps-3"><div class="fw-semibold"><?= htmlspecialchars($person['nama_lengkap']); ?></div><small class="text-muted">@<?= htmlspecialchars($person['username']); ?> · <?= htmlspecialchars($person['alamat'] ?: 'Alamat belum diisi'); ?></small></td><td><?= htmlspecialchars($person['nomor_telepon'] ?: '-'); ?></td><td class="fw-bold text-success"><?= format_rupiah($person['saldo']); ?></td><td class="text-end pe-3"><a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$person['id']; ?>" aria-label="Ubah nasabah"><i class="bi bi-pencil"></i></a> <form class="d-inline" method="post" onsubmit="return confirm('Hapus akun nasabah ini? Data dengan riwayat transaksi akan dipertahankan.');"><?= csrf_field(); ?><input type="hidden" name="action" value="hapus"><input type="hidden" name="id" value="<?= (int)$person['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Hapus nasabah"><i class="bi bi-trash"></i></button></form></td></tr><?php endforeach; ?>
    </tbody></table></div></section></div>
</div>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>