<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/config/helpers.php';

auth_require('admin');
$errors = [];
$nasabah = $pdo->query("SELECT id, nama_lengkap, username, saldo FROM users WHERE role = 'nasabah' ORDER BY nama_lengkap")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    $amount = filter_var($_POST['total_nominal'] ?? null, FILTER_VALIDATE_FLOAT);
    $note = trim($_POST['catatan'] ?? '');
    if (!csrf_valid($_POST['csrf_token'] ?? null)) $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    if (!$userId) $errors[] = 'Pilih nasabah yang melakukan penarikan.';
    if ($amount === false || !is_finite((float)$amount) || round((float)$amount, 2) <= 0) $errors[] = 'Nominal penarikan harus lebih besar dari nol.';
    else $amount = round((float)$amount, 2);

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT saldo FROM users WHERE id = :id AND role = 'nasabah' FOR UPDATE");
            $stmt->execute([':id' => $userId]);
            $person = $stmt->fetch();
            if (!$person) throw new RuntimeException('Nasabah yang dipilih tidak ditemukan.');
            if ((float)$amount > (float)$person['saldo']) throw new RuntimeException('Saldo nasabah tidak mencukupi untuk penarikan tersebut.');

            $stmt = $pdo->prepare("INSERT INTO transactions (kode_transaksi, user_id, admin_id, jenis_transaksi, total_nominal, catatan) VALUES (:kode, :user, :admin, 'tarik', :total, :catatan)");
            $stmt->execute([':kode' => generate_kode_transaksi('tarik'), ':user' => $userId, ':admin' => auth_user()['id'], ':total' => $amount, ':catatan' => $note ?: null]);
            $transactionId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare('UPDATE users SET saldo = saldo - :total WHERE id = :id AND saldo >= :minimum');
            $stmt->execute([':total' => $amount, ':id' => $userId, ':minimum' => $amount]);
            if ($stmt->rowCount() !== 1) throw new RuntimeException('Saldo berubah atau tidak mencukupi. Silakan ulangi transaksi.');
            $pdo->commit();
            set_flash('success', 'Penarikan berhasil dicatat.');
            header('Location: /admin/transaksi/detail.php?id=' . $transactionId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Penarikan gagal disimpan. Silakan coba kembali.';
        }
    }
}

$pageTitle = 'Tarik Saldo';
require_once __DIR__ . '/../../templates/header.php';
?>
<div class="mb-4"><h2 class="fw-bold mb-1">Penarikan Saldo</h2><p class="text-muted mb-0">Saldo diperiksa kembali saat transaksi disimpan.</p></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= htmlspecialchars($error); ?></div><?php endforeach; ?>
<form method="post" class="card border-0 shadow-sm p-4" style="max-width: 760px">
    <?= csrf_field(); ?>
    <div class="mb-3"><label class="form-label" for="user_id">Nasabah</label><select class="form-select" name="user_id" id="user_id" required><option value="">Pilih nasabah</option><?php foreach ($nasabah as $person): ?><option value="<?= (int)$person['id']; ?>" <?= (string)($_POST['user_id'] ?? '') === (string)$person['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($person['nama_lengkap'] . ' (@' . $person['username'] . ') · ' . format_rupiah($person['saldo'])); ?></option><?php endforeach; ?></select></div>
    <div class="mb-3"><label class="form-label" for="total_nominal">Nominal penarikan (Rp)</label><input class="form-control" id="total_nominal" name="total_nominal" type="number" min="0.01" step="0.01" required value="<?= htmlspecialchars($_POST['total_nominal'] ?? ''); ?>"></div>
    <div class="mb-3"><label class="form-label" for="catatan">Catatan (opsional)</label><textarea class="form-control" id="catatan" name="catatan" rows="3"><?= htmlspecialchars($_POST['catatan'] ?? ''); ?></textarea></div>
    <div><button class="btn btn-warning" type="submit" <?= !$nasabah ? 'disabled' : ''; ?>><i class="bi bi-cash-stack"></i> Catat Penarikan</button> <a class="btn btn-outline-secondary" href="/admin/index.php">Batal</a></div>
</form>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>