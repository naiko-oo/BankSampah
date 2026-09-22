<?php
// templates/header.php
require_once __DIR__ . '/../config/helpers.php';
$currentUser = auth_user();
$pageTitle = $pageTitle ?? 'Bank Sampah Digital';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle); ?> - Bank Sampah Digital</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --bs-primary: #198754; /* Forest Green tema lingkungan */
            --bs-primary-rgb: 25, 135, 84;
        }
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .main-content {
            flex: 1 0 auto;
        }
        .card-stat {
            border: none;
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        .badge-role {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<!-- Navbar Navigasi -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $currentUser ? ($currentUser['role'] === 'admin' ? '/admin/index.php' : '/nasabah/index.php') : '/index.php'; ?>">
            <i class="bi bi-recycle fs-4"></i>
            <span>Bank Sampah RT/RW</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-dismiss="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <?php if ($currentUser): ?>
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-arrow-left-right"></i> Transaksi
                            </a>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item" href="/admin/transaksi/setor.php"><i class="bi bi-box-arrow-in-down text-success"></i> Catat Setor Sampah</a></li>
                                <li><a class="dropdown-item" href="/admin/transaksi/tarik.php"><i class="bi bi-cash-stack text-warning"></i> Tarik Saldo Tunai</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/admin/transaksi/index.php"><i class="bi bi-clock-history"></i> Riwayat Semua Transaksi</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-database"></i> Data Master
                            </a>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item" href="/admin/kategori/index.php"><i class="bi bi-tags"></i> Kategori Sampah & Tarif</a></li>
                                <li><a class="dropdown-item" href="/admin/nasabah/index.php"><i class="bi bi-people"></i> Data Warga (Nasabah)</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/laporan/index.php"><i class="bi bi-file-earmark-bar-graph"></i> Laporan</a>
                        </li>
                    </ul>
                <?php else: ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="/nasabah/index.php"><i class="bi bi-speedometer2"></i> Dashboard Saya</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/nasabah/riwayat.php"><i class="bi bi-clock-history"></i> Riwayat Transaksi</a>
                        </li>
                    </ul>
                <?php endif; ?>

                <div class="d-flex align-items-center gap-3">
                    <div class="text-white text-end d-none d-md-block">
                        <div class="fw-semibold small"><?= htmlspecialchars($currentUser['nama_lengkap']); ?></div>
                        <span class="badge bg-light text-success badge-role"><?= htmlspecialchars($currentUser['role']); ?></span>
                    </div>
                    <a href="/logout.php" class="btn btn-outline-light btn-sm" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                        <i class="bi bi-box-arrow-right"></i> Keluar
                    </a>
                </div>
            <?php else: ?>
                <div class="ms-auto">
                    <a href="/login.php" class="btn btn-light btn-sm px-3 fw-semibold text-success">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk Sistem
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Kontainer Utama -->
<main class="main-content py-4">
    <div class="container">
        <!-- Area Flash Message -->
        <?php display_flash(); ?>
