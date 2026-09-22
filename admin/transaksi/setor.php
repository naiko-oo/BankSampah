<?php
// admin/transaksi/setor.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

auth_require('admin');

// Ambil daftar nasabah aktif
$stmt = $pdo->query("SELECT id, nama_lengkap, username, saldo FROM users WHERE role = 'nasabah' ORDER BY nama_lengkap ASC");
$nasabahList = $stmt->fetchAll();

// Ambil daftar kategori sampah
$stmt = $pdo->query("SELECT id, nama_kategori, harga_per_kg FROM categories ORDER BY nama_kategori ASC");
$categories = $stmt->fetchAll();

$error = '';
$selectedNasabahId = (int)($_GET['nasabah_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id   = (int)($_POST['user_id'] ?? 0);
    $catatan   = trim($_POST['catatan'] ?? '');
    $items     = $_POST['items'] ?? []; // array of ['category_id' => x, 'berat_kg' => y]
    $admin_id  = auth_user()['id'];

    if ($user_id <= 0) {
        $error = 'Silakan pilih nasabah warga yang menyetor sampah.';
    } elseif (empty($items) || !is_array($items)) {
        $error = 'Minimal masukkan satu jenis sampah yang disetor.';
    } else {
        // Validasi dan hitung rincian
        $validItems = [];
        $totalNominal = 0;

        // Ambil data harga terkini dari DB untuk memastikan akurasi
        $catMap = [];
        foreach ($categories as $c) {
            $catMap[$c['id']] = (float)$c['harga_per_kg'];
        }

        foreach ($items as $item) {
            $catId = (int)($item['category_id'] ?? 0);
            $berat = (float)($item['berat_kg'] ?? 0);

            if ($catId > 0 && isset($catMap[$catId]) && $berat > 0) {
                $tarif = $catMap[$catId];
                $subtotal = round($berat * $tarif, 2);
                $validItems[] = [
                    'category_id'  => $catId,
                    'berat_kg'     => $berat,
                    'harga_per_kg' => $tarif,
                    'subtotal'     => $subtotal
                ];
                $totalNominal += $subtotal;
            }
        }

        if (empty($validItems) || $totalNominal <= 0) {
            $error = 'Rincian sampah belum valid. Masukkan jenis sampah dan bobot yang lebih dari 0 kg.';
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Simpan Transaksi Induk
                $kodeTransaksi = generate_kode_transaksi('setor');
                $stmt = $pdo->prepare("
                    INSERT INTO transactions (kode_transaksi, user_id, admin_id, jenis_transaksi, total_nominal, catatan)
                    VALUES (:kode, :user_id, :admin_id, 'setor', :total, :catatan)
                ");
                $stmt->execute([
                    ':kode'     => $kodeTransaksi,
                    ':user_id'  => $user_id,
                    ':admin_id' => $admin_id,
                    ':total'    => $totalNominal,
                    ':catatan'  => $catatan
                ]);
                $transactionId = $pdo->lastInsertId();

                // 2. Simpan Rincian Sampah
                $stmtDetail = $pdo->prepare("
                    INSERT INTO transaction_details (transaction_id, category_id, berat_kg, harga_per_kg, subtotal)
                    VALUES (:trans_id, :cat_id, :berat, :harga, :subtotal)
                ");
                foreach ($validItems as $v) {
                    $stmtDetail->execute([
                        ':trans_id' => $transactionId,
                        ':cat_id'   => $v['category_id'],
                        ':berat'    => $v['berat_kg'],
                        ':harga'    => $v['harga_per_kg'],
                        ':subtotal' => $v['subtotal']
                    ]);
                }

                // 3. Tambahkan Saldo Nasabah
                $stmtUser = $pdo->prepare("UPDATE users SET saldo = saldo + :total WHERE id = :id");
                $stmtUser->execute([
                    ':total' => $totalNominal,
                    ':id'    => $user_id
                ]);

                $pdo->commit();

                set_flash('success', "Transaksi setoran sampah <strong>{$kodeTransaksi}</strong> sebesar " . format_rupiah($totalNominal) . " berhasil dicatat.");
                header("Location: /admin/transaksi/detail.php?id={$transactionId}");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal memproses transaksi setoran: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Catat Setor Sampah';
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="fw-bold mb-0"><i class="bi bi-box-arrow-in-down text-success me-2"></i>Catat Setoran Sampah</h3>
                <small class="text-muted">Pencatatan timbangan sampah warga & penambahan saldo otomatis</small>
            </div>
            <a href="/admin/transaksi/index.php" class="btn btn-outline-secondary btn-sm">Daftar Transaksi</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-octagon-fill me-2"></i> <?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="formSetor">
            <div class="card border-0 shadow-sm rounded-3 p-4 mb-4">
                <h5 class="fw-bold mb-3 text-secondary border-bottom pb-2">1. Data Nasabah Warga</h5>
                <div class="row g-3">
                    <div class="col-md-7">
                        <label for="user_id" class="form-label fw-semibold">Pilih Nasabah Warga <span class="text-danger">*</span></label>
                        <select class="form-select form-select-lg" id="user_id" name="user_id" required>
                            <option value="">-- Pilih Warga Penyetor --</option>
                            <?php foreach ($nasabahList as $n): ?>
                                <option value="<?= $n['id']; ?>" <?= ($selectedNasabahId === $n['id'] || (isset($_POST['user_id']) && (int)$_POST['user_id'] === $n['id'])) ? 'selected' : ''; ?> data-saldo="<?= $n['saldo']; ?>">
                                    <?= htmlspecialchars($n['nama_lengkap']); ?> (@<?= htmlspecialchars($n['username']); ?>) - Saldo: <?= format_rupiah($n['saldo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="catatan" class="form-label fw-semibold">Catatan / Keterangan (Opsional)</label>
                        <input type="text" class="form-control form-control-lg" id="catatan" name="catatan" placeholder="Contoh: Setoran rutin mingguan" value="<?= htmlspecialchars($_POST['catatan'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Rincian Sampah Dinamis -->
            <div class="card border-0 shadow-sm rounded-3 p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h5 class="fw-bold mb-0 text-secondary">2. Rincian Timbangan Sampah</h5>
                    <button type="button" class="btn btn-outline-success btn-sm" id="btnAddRow">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Baris Jenis Sampah
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 45%;">Jenis / Kategori Sampah</th>
                                <th style="width: 25%;">Berat Timbangan (Kg)</th>
                                <th style="width: 25%;">Subtotal (Rp)</th>
                                <th style="width: 5%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <!-- Baris pertama -->
                            <tr class="item-row">
                                <td>
                                    <select class="form-select select-category" name="items[0][category_id]" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= $c['id']; ?>" data-harga="<?= $c['harga_per_kg']; ?>">
                                                <?= htmlspecialchars($c['nama_kategori']); ?> (<?= format_rupiah($c['harga_per_kg']); ?>/kg)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="number" step="0.05" min="0.05" class="form-control input-berat" name="items[0][berat_kg]" placeholder="0.0" required>
                                        <span class="input-group-text">kg</span>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control bg-light fw-bold text-end input-subtotal" readonly value="Rp 0">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row" disabled>
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <th colspan="2" class="text-end fs-5">TOTAL ESTIMASI TABUNGAN:</th>
                                <th class="text-end fs-5 text-success fw-bold" id="totalDisplay">Rp 0</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3">
                <a href="/admin/index.php" class="btn btn-light btn-lg px-4">Batal</a>
                <button type="submit" class="btn btn-success btn-lg px-5 fw-bold shadow">
                    <i class="bi bi-check-circle me-1"></i> Simpan Transaksi Setoran
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsBody = document.getElementById('itemsBody');
    const btnAddRow = document.getElementById('btnAddRow');
    const totalDisplay = document.getElementById('totalDisplay');
    let rowIndex = 1;

    // Hitung subtotal tiap baris dan total keseluruhan
    function recalculate() {
        let grandTotal = 0;
        const rows = itemsBody.querySelectorAll('.item-row');
        
        rows.forEach(row => {
            const selectCat = row.querySelector('.select-category');
            const inputBerat = row.querySelector('.input-berat');
            const inputSubtotal = row.querySelector('.input-subtotal');

            const selectedOption = selectCat.options[selectCat.selectedIndex];
            const harga = selectedOption ? parseFloat(selectedOption.getAttribute('data-harga') || 0) : 0;
            const berat = parseFloat(inputBerat.value || 0);

            const subtotal = Math.round(harga * berat);
            inputSubtotal.value = 'Rp ' + subtotal.toLocaleString('id-ID');
            grandTotal += subtotal;
        });

        totalDisplay.innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');

        // Atur tombol hapus (hanya bisa hapus jika baris > 1)
        const removeBtns = itemsBody.querySelectorAll('.btn-remove-row');
        removeBtns.forEach(btn => btn.disabled = (rows.length <= 1));
    }

    // Tambah baris baru
    btnAddRow.addEventListener('click', function() {
        const firstRow = itemsBody.querySelector('.item-row');
        const newRow = firstRow.cloneNode(true);
        
        // Bersihkan nilai baris baru
        newRow.querySelector('.select-category').name = `items[${rowIndex}][category_id]`;
        newRow.querySelector('.select-category').selectedIndex = 0;
        newRow.querySelector('.input-berat').name = `items[${rowIndex}][berat_kg]`;
        newRow.querySelector('.input-berat').value = '';
        newRow.querySelector('.input-subtotal').value = 'Rp 0';
        newRow.querySelector('.btn-remove-row').disabled = false;

        itemsBody.appendChild(newRow);
        rowIndex++;
        recalculate();
    });

    // Event delegation untuk perubahan input & hapus baris
    itemsBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('input-berat')) {
            recalculate();
        }
    });

    itemsBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('select-category')) {
            recalculate();
        }
    });

    itemsBody.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.btn-remove-row');
        if (removeBtn && !removeBtn.disabled) {
            const row = removeBtn.closest('.item-row');
            row.remove();
            recalculate();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
