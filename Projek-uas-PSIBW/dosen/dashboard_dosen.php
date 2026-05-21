<?php
// Hubungkan ke file konfigurasi untuk menggunakan fungsi pengaman
require_once '../config/db.php';

// Proteksi halaman: Hanya user yang memiliki role 'dosen' yang bisa masuk!
// Jika bukan dosen mencoba akses, otomatis ditolak via JSON/die oleh fungsi ini
requireRole(['dosen']);

// Mengambil data dosen dari session (dibuat saat login sukses)
$id_user = $_SESSION['id_user'];
$username = $_SESSION['username'] ?? 'Dosen';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen - SIAKAD UNRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">SIAKAD DOSEN</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="#">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-warning fw-semibold" href="../logout.php">Logout (<?= htmlspecialchars($username) ?>)</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5">
    <div class="row">
        <div class="col-10">
            <h3>Selamat Datang, Bapak/Ibu Dosen!</h3>
            <p class="text-muted">Gunakan sistem ini untuk mengelola nilai mahasiswa dan memperbarui profil Anda.</p>
        </div>
    </div>

    <div class="row mt-4 g-4">
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-primary">Input & Kelola Nilai</h5>
                    <p class="card-text text-muted">Berikan nilai, ubah nilai, atau lihat daftar mahasiswa berdasarkan mata kuliah yang Anda ampu.</p>
                    <a href="#" class="btn btn-outline-primary">Masuk Fitur Nilai</a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-success">Edit Profil Saya</h5>
                    <p class="card-text text-muted">Perbarui data diri Anda seperti nama, email, nomor telepon, atau foto profil resmi.</p>
                    <a href="edit_profil.php" class="btn btn-outline-success">Ubah Profil</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>