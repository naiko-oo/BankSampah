<?php
// admin/transaksi/tarik.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

auth_require('admin');

// Ambil daftar nasabah aktif
$stmt = $pdo->query("SELECT id, nama_lengkap, username, saldo FROM users WHERE role = 'nasabah' ORDER BY nama_lengkap ASC");
$nasabahList = $stmt->fetchAll();

$error = '';
$selectedNasabahId = (int)($_GET['nasabah_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id  = (int)($_POST['user_id'] ?? 0);
    $nominal  = (float)($_POST['nominal'] ?? 0);
    $catatan  = trim($_POST['catatan'] ?? '');
    $admin_id = auth_user()['id'];

    if ($user_id <= 0) {
        $error = 'Silakan pilih nasabah yang akan melakukan penarikan saldo.';
    } elseif ($nominal <= 0) {
        $error = 'Nominal penarikan harus lebih besar dari Rp 0.';
    } else {
        try {
            $pdo->beginTransaction();

            // Kunci baris user untuk memeriksa saldo aktual (Concurrency / Data Integrity)
            $stmt = $pdo->prepare("SELECT id, nama_lengkap, saldo FROM users WHERE id = :id AND role = 'nasabah' FOR UPDATE");
            $stmt->execute([':id' => $user_id]);
            $nasabah = $stmt->fetch();

            if (!$nasabah) {
                throw new Exception('Data nasabah tidak ditemukan.');
            }

            $currentSaldo = (float)$nasabah['saldo'];

            // Validasi saldo mencukupi
            if ($nominal > $currentSaldo) {
                throw new Exception("Saldo nasabah tidak mencukupi! Saldo aktif: " . format_rupiah($currentSaldo) . ", penarikan diajukan: " . format_rupiah($nominal));
            }

            // 1. Simpan Transaksi Penarikan
            $kodeTransaksi = generate_kode_transaksi('tarik');
            $stmt = $pdo->prepare("
                INSERT INTO transactions (kode_transaksi, user_id, admin_id, jenis_transaksi, total_nominal, catatan)
                VALUES (:kode, :user_id, :admin_id, 'tarik', :total, :catatan)
            ");
            $stmt->execute([
                ':kode'     => $kodeTransaksi,
                ':user_id'  => $user_id,
                ':admin_id' => $admin_id,
                ':total'    => $nominal,
                ':catatan'  => $catatan
            ]);
            $transactionId = $pdo->lastInsertId();

            // 2. Kurangi Saldo Nasabah
            $stmtUser = $pdo->prepare("UPDATE users SET saldo = saldo - :nominal WHERE id = :id");
            $stmtUser->execute([
                ':nominal' => $nominal,
                ':id'      => $user_id
            ]);

            $pdo->commit();

            set_flash('success', "Penarikan saldo tunai sebesar <strong>" . format_rupiah($nominal) . "</strong> untuk nasabah <strong>" . htmlspecialchars($nasabah['nama_lengkap']) . "</strong> berhasil diproses.");
            header("Location: /admin/transaksi/detail.php?id={$transactionId}");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Pencairan Saldo Tunai';
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="fw-bold mb-0"><i class="bi bi-cash-stack text-warning me-2"></i>Pencairan Saldo Tunai</h4>
                    <small class="text-muted">Tarik uang tunai dari saldo tabungan nasabah</small>
                </div>
                <a href="/admin/transaksi/index.php" class="btn btn-sm btn-outline-secondary">Kembali</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="formTarik">
                <div class="mb-3">
                    <label for="user_id" class="form-label fw-semibold">Pilih Nasabah Warga <span class="text-danger">*</span></label>
                    <select class="form-select form-select-lg" id="user_id" name="user_id" required>
                        <option value="">-- Pilih Warga yang Menarik Dana --</option>
                        <?php foreach ($nasabahList as $n): ?>
                            <option value="<?= $n['id']; ?>" <?= ($selectedNasabahId === $n['id'] || (isset($_POST['user_id']) && (int)$_POST['user_id'] === $n['id'])) ? 'selected' : ''; ?> data-saldo="<?= $n['saldo']; ?>">
                                <?= htmlspecialchars($n['nama_lengkap']); ?> (@<?= htmlspecialchars($n['username']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Info Box Saldo Aktif -->
                <div class="card bg-light border-0 p-3 mb-3 d-none" id="infoSaldoBox">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted fw-semibold">Saldo Aktif Saat Ini:</span>
                        <h4 class="fw-bold text-success mb-0" id="textSaldoAktif">Rp 0</h4>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="nominal" class="form-label fw-semibold">Nominal Penarikan (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text">Rp</span>
                        <input type="number" step="1000" min="1000" class="form-control" id="nominal" name="nominal" placeholder="Contoh: 25000" required value="<?= htmlspecialchars($_POST['nominal'] ?? ''); ?>">
                    </div>
                    <div class="form-text text-danger d-none" id="errorExceeds">Nominal melebihi saldo aktif nasabah!</div>
                </div>

                <div class="mb-4">
                    <label for="catatan" class="form-label fw-semibold">Catatan / Alasan Penarikan (Opsional)</label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="2" placeholder="Contoh: Kebutuhan belanja bulanan"><?= htmlspecialchars($_POST['catatan'] ?? ''); ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="/admin/index.php" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-warning px-4 fw-bold text-dark" id="btnSubmitTarik">
                        <i class="bi bi-cash-stack me-1"></i> Proses Penarikan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectUser = document.getElementById('user_id');
    const infoBox = document.getElementById('infoSaldoBox');
    const textSaldo = document.getElementById('textSaldoAktif');
    const inputNominal = document.getElementById('nominal');
    const errorExceeds = document.getElementById('errorExceeds');
    const btnSubmit = document.getElementById('btnSubmitTarik');

    function updateSaldoInfo() {
        const option = selectUser.options[selectUser.selectedIndex];
        if (option && option.value) {
            const saldo = parseFloat(option.getAttribute('data-saldo') || 0);
            textSaldo.innerText = 'Rp ' + saldo.toLocaleString('id-ID');
            infoBox.classList.remove('d-none');
            checkNominal(saldo);
        } else {
            infoBox.classList.add('d-none');
            errorExceeds.classList.add('d-none');
            btnSubmit.disabled = false;
        }
    }

    function checkNominal(saldo) {
        const nominal = parseFloat(inputNominal.value || 0);
        if (nominal > saldo) {
            errorExceeds.classList.remove('d-none');
            inputNominal.classList.add('is-invalid');
            btnSubmit.disabled = true;
        } else {
            errorExceeds.classList.add('d-none');
            inputNominal.classList.remove('is-invalid');
            btnSubmit.disabled = false;
        }
    }

    selectUser.addEventListener('change', updateSaldoInfo);
    inputNominal.addEventListener('input', function() {
        const option = selectUser.options[selectUser.selectedIndex];
        const saldo = option ? parseFloat(option.getAttribute('data-saldo') || 0) : 0;
        checkNominal(saldo);
    });

    // Jalankan pengecekan di awal jika sudah terpilih
    updateSaldoInfo();
});
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
