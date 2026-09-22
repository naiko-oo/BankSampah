<?php
// config/helpers.php
// Kumpulan fungsi pembantu (helper) untuk sistem

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mendapatkan base URL aplikasi
 */
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    
    // Normalisasi base path jika berada di subfolder
    $root = rtrim($script_dir, '/');
    // Jika kita menggunakan php -S localhost:8000, root biasanya kosong
    return $protocol . $host . '/' . ltrim($path, '/');
}

/**
 * Format angka ke mata uang Rupiah
 */
function format_rupiah($nominal) {
    return 'Rp ' . number_format((float)$nominal, 0, ',', '.');
}

/**
 * Format berat sampah dalam Kilogram
 */
function format_berat($kg) {
    return number_format((float)$kg, 2, ',', '.') . ' kg';
}

/**
 * Format tanggal Indonesia
 */
function format_tanggal($datetime) {
    if (!$datetime) return '-';
    $timestamp = strtotime($datetime);
    $bulanIndo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $d = date('d', $timestamp);
    $m = (int)date('m', $timestamp);
    $y = date('Y', $timestamp);
    $h = date('H:i', $timestamp);
    return "$d {$bulanIndo[$m]} $y, $h WIB";
}

/**
 * Cek apakah user sedang login
 */
function auth_check() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Mendapatkan data user yang sedang login
 */
function auth_user() {
    return $_SESSION['user'] ?? null;
}

/**
 * Proteksi akses halaman berdasarkan role
 */
function auth_require($role = null) {
    if (!auth_check()) {
        set_flash('warning', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
        header('Location: /login.php');
        exit;
    }

    if ($role !== null) {
        $userRole = $_SESSION['user']['role'] ?? '';
        if ($userRole !== $role) {
            set_flash('danger', 'Akses ditolak! Anda tidak memiliki izin untuk membuka halaman tersebut.');
            if ($userRole === 'admin') {
                header('Location: /admin/index.php');
            } else {
                header('Location: /nasabah/index.php');
            }
            exit;
        }
    }
}

/**
 * Menyimpan pesan flash (notifikasi sementara)
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Menampilkan dan membersihkan notifikasi flash
 */
function display_flash() {
    if (isset($_SESSION['flash'])) {
        $type = htmlspecialchars($_SESSION['flash']['type']);
        $message = htmlspecialchars($_SESSION['flash']['message']);
        unset($_SESSION['flash']);
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}

/**
 * Sanitasi input teks
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate Kode Transaksi Unik
 */
function generate_kode_transaksi($jenis = 'setor') {
    $prefix = ($jenis === 'setor') ? 'TRX-SET' : 'TRX-TRK';
    $datePart = date('Ymd');
    $randomPart = strtoupper(substr(uniqid(), -4));
    return "{$prefix}-{$datePart}-{$randomPart}";
}
