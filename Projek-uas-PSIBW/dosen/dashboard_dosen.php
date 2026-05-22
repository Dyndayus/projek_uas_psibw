<?php
require_once '../config/db.php';
requireRole(['dosen']);

$conn = getDB();
$session_email = $_SESSION['username'];

// 1. Ambil data profil dosen untuk Sidebar
$query = "SELECT id_dosen, nama, foto FROM dosen WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $session_email);
$stmt->execute();
$result = $stmt->get_result();
$dosen = $result->fetch_assoc();
$stmt->close();

$id_dosen = $dosen['id_dosen'] ?? 0;
$nama_dosen = $dosen['nama'] ?? 'Dosen SIAKAD';
$foto_path = !empty($dosen['foto']) ? '../uploads/foto_dosen/' . $dosen['foto'] : 'https://via.placeholder.com/150';

// 2. HITUNG STATISTIK UTAMA
$q_matkul = "SELECT COUNT(DISTINCT id_kuliah) AS total_matkul FROM nilai WHERE id_mhs IN (SELECT id_mhs FROM mhs)";
$res_matkul = $conn->query($q_matkul);
$row_matkul = $res_matkul->fetch_assoc();
$total_matkul = $row_matkul['total_matkul'] ?? 0;

$q_mhs = "SELECT COUNT(DISTINCT id_mhs) AS total_mhs FROM nilai";
$res_mhs = $conn->query($q_mhs);
$row_mhs = $res_mhs->fetch_assoc();
$total_mhs = $row_mhs['total_mhs'] ?? 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen - SIAKAD UNRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Mengunci total layar utama agar browser tidak memunculkan scrollbar ganda */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #333333;
            background-color: #f4f6f9;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        .logo-navbar {
            height: 35px;
            width: auto;
            object-fit: contain;
        }

        /* Pembungkus luar di bawah navbar yang membagi layar kiri (sidebar) dan kanan (konten) */
        .main-wrapper {
            display: flex;
            flex: 1;
            overflow: hidden; /* Menjaga sidebar tetap mati */
        }

        /* SIDEBAR KIRI: Terkunci permanen */
        .sidebar {
            width: 16.666667%; /* Setara col-lg-2 secara visual */
            min-width: 240px;
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            height: 100%;
            flex-shrink: 0;
        }

        .sidebar .nav-link {
            color: #4a5568;
            font-size: 13.5px;
            font-weight: 500;
            padding: 10px 18px;
            border-radius: 6px;
            margin: 2px 12px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #f1f5f9;
            color: #0d6efd;
        }

        /* AREA KANAN: Berisi Konten Utama dan Footer */
        .right-layout {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        /* AREA SCROLL INDEPENDEN: Hanya elemen ini yang boleh memunculkan scrollbar */
        .content-scrollable {
            flex: 1;
            overflow-y: auto;
            background-color: #f4f6f9;
        }

        .card-stat {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background-color: #ffffff;
        }

        .table-custom th {
            font-size: 12px;
            letter-spacing: 0.5px;
            color: #718096;
            background-color: #f8fafc !important;
        }

        /* FOOTER TETAP (FIXED DI BAWAH AREA KANAN) */
        .footer {
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #64748b;
            width: 100%;
            flex-shrink: 0; /* Mencegah footer gepeng/mengecil */
        }

        /* Responsivitas untuk mobile / layar sentuh kecil */
        @media (max-width: 767.98px) {
            html, body {
                overflow: auto;
                height: auto;
            }
            .main-wrapper {
                flex-direction: column;
                overflow: visible;
            }
            .sidebar {
                width: 100%;
                height: auto;
                min-width: 100%;
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
            }
            .right-layout {
                height: auto;
                overflow: visible;
            }
            .content-scrollable {
                overflow-y: visible;
                height: auto;
            }
            .footer {
                position: relative;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top" style="z-index: 1050;">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard_dosen.php">
                <img src="https://unri.ac.id/wp-content/uploads/2016/05/cropped-LogoUR-1-1.png" alt="Logo UNRI" class="logo-navbar me-2">
                Universitas Riau
            </a>
            <div class="ms-auto">
                <a class="btn btn-light btn-sm text-danger fw-bold rounded px-3" href="../logout.php" style="font-size: 12px;">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        
        <div class="sidebar">
            <div class="text-center p-4 border-bottom mb-3" style="background-color: #fafafa;">
                <img src="<?= $foto_path ?>" class="rounded-circle img-thumbnail mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                <div class="fw-bold text-dark text-truncate small px-1"><?= htmlspecialchars($nama_dosen) ?></div>
                <a href="edit_profil.php" class="btn btn-sm btn-light border rounded mt-2" style="font-size: 11px; padding: 2px 8px;">
                    <i class="fa-solid fa-user-gear me-1"></i> Lihat Profil
                </a>
            </div>

            <ul class="nav flex-column" style="flex: 1;">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard_dosen.php">
                        <i class="fa-solid fa-house me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="matakuliah.php">
                        <i class="fa-solid fa-book me-2"></i> Daftar Mata Kuliah
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="jadwal.php">
                        <i class="fa-solid fa-calendar-days me-2"></i> Jadwal Mengajar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="input_nilai.php">
                        <i class="fa-solid fa-pen-to-square me-2"></i> Input Nilai
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="ganti_password.php">
                        <i class="fa-solid fa-key me-2"></i> Ganti Password
                    </a>
                </li>
            </ul>
        </div>

        <div class="right-layout">
            
            <div class="content-scrollable px-md-4 py-4">

                <div class="card border-0 rounded-3 p-4 mb-4" style="background-color: #ffffff; border: 1px solid #e2e8f0 !important;">
                    <h5 class="fw-bold text-dark mb-1">Selamat Datang, <?= htmlspecialchars($nama_dosen) ?>.</h5>
                    <p class="text-muted mb-0" style="font-size: 13px;">Sistem Informasi Akademik khusus dosen untuk pengelolaan data nilai mahasiswa.</p>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card card-stat shadow-sm">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="p-3 rounded bg-light text-primary me-3">
                                    <i class="fa-solid fa-book-bookmark fa-xl"></i>
                                </div>
                                <div>
                                    <div class="text-muted fw-semibold" style="font-size: 11px;">MATA KULIAH DIAMPU</div>
                                    <h4 class="fw-bold text-dark mb-0 mt-1"><?= $total_matkul ?> <span class="fs-6 text-muted fw-normal">Matkul</span></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-stat shadow-sm">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="p-3 rounded bg-light text-success me-3">
                                    <i class="fa-solid fa-users fa-xl"></i>
                                </div>
                                <div>
                                    <div class="text-muted fw-semibold" style="font-size: 11px;">TOTAL MAHASISWA DIAJAR</div>
                                    <h4 class="fw-bold text-dark mb-0 mt-1"><?= $total_mhs ?> <span class="fs-6 text-muted fw-normal">Orang</span></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-stat shadow-sm">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="p-3 rounded bg-light text-secondary me-3">
                                    <i class="fa-solid fa-circle-check fa-xl"></i>
                                </div>
                                <div>
                                    <div class="text-muted fw-semibold" style="font-size: 11px;">STATUS SEKARANG</div>
                                    <h4 class="fw-bold text-dark mb-0 mt-1" style="font-size: 16px;">Dosen Aktif</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3" style="border: 1px solid #e2e8f0 !important;">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-calendar-day text-secondary me-2"></i>Jadwal Mengajar Hari Ini</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-custom mb-0">
                                <thead class="text-uppercase">
                                    <tr>
                                        <th style="width: 25%;">Waktu / Jam</th>
                                        <th style="width: 50%;">Mata Kuliah</th>
                                        <th style="width: 25%;">Ruangan</th>
                                    </tr>
                                </thead>
                                <tbody style="font-size: 13px;">
                                    <tr>
                                        <td class="fw-bold text-dark">08:00 - 10:30</td>
                                        <td>
                                            <span class="fw-semibold text-dark d-block">Algoritma dan Pemrograman</span>
                                            <span class="text-muted" style="font-size: 11px;">TI-101 • Teknik Informatika</span>
                                        </td>
                                        <td><span class="text-secondary"><i class="fa-solid fa-location-dot me-1 text-muted"></i> Lab Komputer 2</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-dark">13:30 - 15:10</td>
                                        <td>
                                            <span class="fw-semibold text-dark d-block">Struktur Data</span>
                                            <span class="text-muted" style="font-size: 11px;">TI-104 • Sistem Informasi</span>
                                        </td>
                                        <td><span class="text-secondary"><i class="fa-solid fa-location-dot me-1 text-muted"></i> Gedung Thariq 3</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3" style="border: 1px solid #e2e8f0 !important;">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-calendar-day text-secondary me-2"></i>Jadwal Mengajar Hari Ini</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-custom mb-0">
                                <thead class="text-uppercase">
                                    <tr>
                                        <th style="width: 25%;">Waktu / Jam</th>
                                        <th style="width: 50%;">Mata Kuliah</th>
                                        <th style="width: 25%;">Ruangan</th>
                                    </tr>
                                </thead>
                                <tbody style="font-size: 13px;">
                                    <tr>
                                        <td class="fw-bold text-dark">08:00 - 10:30</td>
                                        <td>
                                            <span class="fw-semibold text-dark d-block">Algoritma dan Pemrograman</span>
                                            <span class="text-muted" style="font-size: 11px;">TI-101 • Teknik Informatika</span>
                                        </td>
                                        <td><span class="text-secondary"><i class="fa-solid fa-location-dot me-1 text-muted"></i> Lab Komputer 2</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-dark">13:30 - 15:10</td>
                                        <td>
                                            <span class="fw-semibold text-dark d-block">Struktur Data</span>
                                            <span class="text-muted" style="font-size: 11px;">TI-104 • Sistem Informasi</span>
                                        </td>
                                        <td><span class="text-secondary"><i class="fa-solid fa-location-dot me-1 text-muted"></i> Gedung Thariq 3</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <footer class="footer py-3">
                <div class="container-fluid px-4">
                    <div class="row align-items-center justify-content-between flex-column flex-sm-row">
                        <div class="col-auto text-center text-sm-start mb-2 mb-sm-0">
                            <span class="fw-semibold text-dark">SIAKAD</span> Universitas Riau &copy; <?= date('Y'); ?>. All Rights Reserved.
                        </div>
                        <div class="col-auto text-center text-sm-end">
                            <span class="me-3"><i class="fa-solid fa-shield-halved text-primary me-1"></i> Area Dosen Aman</span>
                            <span class="text-muted" style="font-size: 11px;">v2.1.0</span>
                        </div>
                    </div>
                </div>
            </footer>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>