<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/config/helpers.php';
require_once __DIR__ . '/../../../backend/services/bank_sampah_rules.php';

auth_require('admin');
$errors = [];
$nasabah = $pdo->query("SELECT id, nama_lengkap, username FROM users WHERE role = 'nasabah' ORDER BY nama_lengkap")->fetchAll();
$categories = $pdo->query('SELECT id, nama_kategori, harga_per_kg FROM categories ORDER BY nama_kategori')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    $categoryIds = $_POST['category_id'] ?? [];
    $weights = $_POST['berat_kg'] ?? [];
    $note = trim($_POST['catatan'] ?? '');

    if (!csrf_valid($_POST['csrf_token'] ?? null)) $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    if (!$userId) $errors[] = 'Pilih nasabah yang melakukan setoran.';
    if (!is_array($categoryIds) || !is_array($weights) || count($categoryIds) !== count($weights) || count($categoryIds) === 0) $errors[] = 'Tambahkan minimal satu rincian kategori dan berat.';

    $details = [];
    if (!$errors) {
        foreach ($categoryIds as $index => $categoryIdValue) {
            $categoryId = filter_var($categoryIdValue, FILTER_VALIDATE_INT);
            $weight = filter_var($weights[$index] ?? null, FILTER_VALIDATE_FLOAT);
            if (!$categoryId || $weight === false || !is_finite((float)$weight) || round((float)$weight, 2) < 0.01 || round((float)$weight, 2) > 999999.99) {
                $errors[] = 'Setiap rincian harus memiliki kategori dan berat lebih besar dari nol.';
                break;
            }
            $details[] = ['category_id' => $categoryId, 'weight' => round($weight, 2)];
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'nasabah' FOR UPDATE");
            $stmt->execute([':id' => $userId]);
            if (!$stmt->fetch()) throw new RuntimeException('Nasabah yang dipilih tidak ditemukan.');

            $preparedDetails = [];
            $total = 0;
            foreach ($details as $detail) {
                $stmt = $pdo->prepare('SELECT nama_kategori, harga_per_kg FROM categories WHERE id = :id');
                $stmt->execute([':id' => $detail['category_id']]);
                $category = $stmt->fetch();
                if (!$category) throw new RuntimeException('Salah satu kategori sudah tidak tersedia.');
                $subtotal = calculate_deposit_subtotal($detail['weight'], $category['harga_per_kg']);
                $total = round($total + $subtotal, 2);
                if (!is_finite($total) || $total > 9999999999.99) {
                    throw new RuntimeException('Total setoran melebihi batas nominal sistem.');
                }
                $preparedDetails[] = ['category_id' => $detail['category_id'], 'weight' => $detail['weight'], 'price' => $category['harga_per_kg'], 'subtotal' => $subtotal];
            }

            $stmt = $pdo->prepare("INSERT INTO transactions (kode_transaksi, user_id, admin_id, jenis_transaksi, total_nominal, catatan) VALUES (:kode, :user, :admin, 'setor', :total, :catatan)");
            $stmt->execute([':kode' => generate_kode_transaksi('setor'), ':user' => $userId, ':admin' => auth_user()['id'], ':total' => $total, ':catatan' => $note ?: null]);
            $transactionId = (int)$pdo->lastInsertId();
            $detailStmt = $pdo->prepare('INSERT INTO transaction_details (transaction_id, category_id, berat_kg, harga_per_kg, subtotal) VALUES (:transaction_id, :category_id, :weight, :price, :subtotal)');
            foreach ($preparedDetails as $detail) {
                $detailStmt->execute([':transaction_id' => $transactionId, ':category_id' => $detail['category_id'], ':weight' => $detail['weight'], ':price' => $detail['price'], ':subtotal' => $detail['subtotal']]);
            }
            $stmt = $pdo->prepare('UPDATE users SET saldo = saldo + :total WHERE id = :id');
            $stmt->execute([':total' => $total, ':id' => $userId]);
            $pdo->commit();
            set_flash('success', 'Setoran berhasil dicatat dan saldo nasabah telah diperbarui.');
            header('Location: /admin/transaksi/detail.php?id=' . $transactionId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Setoran gagal disimpan. Silakan coba kembali.';
        }
    }
}

$pageTitle = 'Catat Setoran';
require_once __DIR__ . '/../../templates/header.php';
?>
<div class="mb-4"><h2 class="fw-bold mb-1">Catat Setoran Sampah</h2><p class="text-muted mb-0">Nilai setoran dihitung dari berat dan tarif kategori saat ini.</p></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= htmlspecialchars($error); ?></div><?php endforeach; ?>
<?php if (!$nasabah): ?><div class="alert alert-warning">Belum ada akun nasabah. <a href="/admin/nasabah/index.php">Daftarkan nasabah</a> terlebih dahulu.</div><?php endif; ?>
<form method="post" id="form-setoran" class="card border-0 shadow-sm p-4">
    <?= csrf_field(); ?>
    <div class="row g-3 mb-4"><div class="col-md-6"><label class="form-label" for="user_id">Nasabah</label><select class="form-select" id="user_id" name="user_id" required><option value="">Pilih nasabah</option><?php foreach ($nasabah as $person): ?><option value="<?= (int)$person['id']; ?>" <?= (string)($_POST['user_id'] ?? '') === (string)$person['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($person['nama_lengkap'] . ' (@' . $person['username'] . ')'); ?></option><?php endforeach; ?></select></div></div>
    <h5 class="fw-bold">Rincian setoran</h5>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Kategori</th><th style="min-width: 150px">Berat (kg)</th><th>Perkiraan subtotal</th><th></th></tr></thead><tbody id="detail-rows">
    <?php $formRows = $_POST['category_id'] ?? ['']; $formWeights = $_POST['berat_kg'] ?? ['']; foreach ($formRows as $i => $selectedCategory): ?><tr class="detail-row"><td><select class="form-select category-select" name="category_id[]" required><option value="">Pilih kategori</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id']; ?>" data-price="<?= htmlspecialchars($category['harga_per_kg']); ?>" <?= (string)$selectedCategory === (string)$category['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($category['nama_kategori']); ?> · <?= format_rupiah($category['harga_per_kg']); ?>/kg</option><?php endforeach; ?></select></td><td><input class="form-control weight-input" name="berat_kg[]" type="number" min="0.01" step="0.01" required value="<?= htmlspecialchars($formWeights[$i] ?? ''); ?>"></td><td class="subtotal fw-semibold">Rp 0</td><td><button class="btn btn-sm btn-outline-danger remove-row" type="button" aria-label="Hapus rincian"><i class="bi bi-x-lg"></i></button></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3"><button class="btn btn-outline-success" id="add-row" type="button"><i class="bi bi-plus-lg"></i> Tambah kategori</button><div class="fs-5">Total setoran: <strong id="grand-total" class="text-success">Rp 0</strong></div></div>
    <div class="mt-3"><label class="form-label" for="catatan">Catatan (opsional)</label><textarea class="form-control" id="catatan" name="catatan" rows="2"><?= htmlspecialchars($_POST['catatan'] ?? ''); ?></textarea></div>
    <div class="mt-4"><button class="btn btn-success" type="submit" <?= !$nasabah || !$categories ? 'disabled' : ''; ?>><i class="bi bi-check-lg"></i> Simpan Setoran</button> <a class="btn btn-outline-secondary" href="/admin/index.php">Batal</a></div>
</form>
<script>
(() => {
    const rows = document.getElementById('detail-rows');
    const categories = <?= json_encode(array_map(static fn($category) => ['id' => (int)$category['id'], 'name' => $category['nama_kategori'], 'price' => (float)$category['harga_per_kg']], $categories), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    const update = () => {
        let total = 0;
        rows.querySelectorAll('.detail-row').forEach(row => {
            const option = row.querySelector('.category-select').selectedOptions[0];
            const weight = Number(row.querySelector('.weight-input').value) || 0;
            const subtotal = option && option.value ? Number(option.dataset.price) * weight : 0;
            row.querySelector('.subtotal').textContent = rupiah.format(subtotal);
            total += subtotal;
        });
        document.getElementById('grand-total').textContent = rupiah.format(total);
    };
    const addRow = () => {
        const row = document.createElement('tr');
        row.className = 'detail-row';
        const select = document.createElement('select');
        select.className = 'form-select category-select'; select.name = 'category_id[]'; select.required = true;
        select.add(new Option('Pilih kategori', ''));
        categories.forEach(category => { const option = new Option(`${category.name} · ${rupiah.format(category.price)}/kg`, category.id); option.dataset.price = category.price; select.add(option); });
        const categoryCell = document.createElement('td'); categoryCell.append(select);
        const weightCell = document.createElement('td'); const weight = document.createElement('input'); weight.className = 'form-control weight-input'; weight.name = 'berat_kg[]'; weight.type = 'number'; weight.min = '0.01'; weight.step = '0.01'; weight.required = true; weightCell.append(weight);
        const subtotalCell = document.createElement('td'); subtotalCell.className = 'subtotal fw-semibold'; subtotalCell.textContent = 'Rp 0';
        const actionCell = document.createElement('td'); actionCell.innerHTML = '<button class="btn btn-sm btn-outline-danger remove-row" type="button" aria-label="Hapus rincian"><i class="bi bi-x-lg"></i></button>';
        row.append(categoryCell, weightCell, subtotalCell, actionCell); rows.append(row); update();
    };
    document.getElementById('add-row').addEventListener('click', addRow);
    rows.addEventListener('input', update); rows.addEventListener('change', update);
    rows.addEventListener('click', event => { if (event.target.closest('.remove-row')) { if (rows.querySelectorAll('.detail-row').length > 1) event.target.closest('.detail-row').remove(); update(); } });
    update();
})();
</script>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>