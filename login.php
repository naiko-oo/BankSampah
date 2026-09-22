<?php
// login.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

// Jika sudah login, redirect sesuai peran
if (auth_check()) {
    $user = auth_user();
    header('Location: ' . ($user['role'] === 'admin' ? '/admin/index.php' : '/nasabah/index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Silakan isi username dan kata sandi Anda.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login Berhasil
            $_SESSION['user'] = [
                'id'            => $user['id'],
                'username'      => $user['username'],
                'nama_lengkap'  => $user['nama_lengkap'],
                'role'          => $user['role'],
                'nomor_telepon' => $user['nomor_telepon'],
                'alamat'        => $user['alamat'],
            ];

            set_flash('success', "Selamat datang kembali, <strong>" . htmlspecialchars($user['nama_lengkap']) . "</strong>!");
            
            if ($user['role'] === 'admin') {
                header('Location: /admin/index.php');
            } else {
                header('Location: /nasabah/index.php');
            }
            exit;
        } else {
            $error = 'Username atau kata sandi yang Anda masukkan salah.';
        }
    }
}

$pageTitle = 'Masuk ke Sistem';
require_once __DIR__ . '/templates/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="text-center mb-4">
                <div class="text-success display-5 mb-2">
                    <i class="bi bi-box-arrow-in-right"></i>
                </div>
                <h3 class="fw-bold">Masuk Sistem</h3>
                <p class="text-muted small">Silakan masuk menggunakan akun Admin atau Nasabah</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="/login.php">
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Kata Sandi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan kata sandi" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100 py-2 fw-semibold shadow-sm">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
            </form>

            <!-- Card Bantuan Demo untuk Dosen / Penguji -->
            <div class="mt-4 p-3 bg-light rounded-3 border">
                <div class="fw-bold text-secondary small mb-2"><i class="bi bi-info-circle"></i> Akun Uji Coba (Demo):</div>
                <div class="small text-muted">
                    <div><strong>Admin:</strong> <code>admin</code> / <code>admin123</code></div>
                    <div><strong>Nasabah:</strong> <code>budi</code> / <code>password123</code></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
