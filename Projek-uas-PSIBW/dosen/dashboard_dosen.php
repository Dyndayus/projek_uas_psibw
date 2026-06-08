<?php
require_once '../config/db.php';
requireRole(['dosen']);

$conn = getDB();
$session_email = $_SESSION['username'];

// 1. Ambil data profil dosen untuk Sidebar & Autentikasi
$query = "SELECT id_dosen, nama, foto FROM dosen WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $session_email);
$stmt->execute();
$result = $stmt->get_result();
$dosen = $result->fetch_assoc();
$stmt->close();

$id_dosen   = $dosen['id_dosen'] ?? 0;
$nama_dosen = !empty($dosen['nama']) ? $dosen['nama'] : 'Dosen SIAKAD';
$foto_path  = !empty($dosen['foto']) ? '../uploads/foto_dosen/' . $dosen['foto'] : 'https://via.placeholder.com/150';


// 2. HITUNG STATISTIK UTAMA (Disinkronkan Khusus Dosen yang Login)

// A. Menghitung Total Mata Kuliah yang BENAR-BENAR diampu oleh dosen ini di tabel kuliah
$q_matkul = "SELECT COUNT(*) AS total_matkul FROM kuliah WHERE id_dosen = ?";
$stmt_m = $conn->prepare($q_matkul);
$stmt_m->bind_param("i", $id_dosen);
$stmt_m->execute();
$row_matkul = $stmt_m->get_result()->fetch_assoc();
$total_matkul = $row_matkul['total_matkul'] ?? 0;
$stmt_m->close();

// B. PERBAIKAN: Menghitung Total Mahasiswa Kumulatif di Seluruh Kelas Dosen Ini
$q_mhs = "SELECT COUNT(n.id_mhs) AS total_mhs 
          FROM nilai n 
          JOIN kuliah k ON n.id_kuliah = k.id_kuliah 
          WHERE k.id_dosen = ?";
$stmt_mh = $conn->prepare($q_mhs);
$stmt_mh->bind_param("i", $id_dosen);
$stmt_mh->execute();
$row_mhs = $stmt_mh->get_result()->fetch_assoc();
$total_mhs = $row_mhs['total_mhs'] ?? 0;
$stmt_mh->close();


// 3. AMBIL JADWAL MENGAJAR HARI INI
$hari_inggris = date('l');
$daftar_hari = [
    'Sunday'    => 'Minggu',
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat',
    'Saturday'  => 'Sabtu'
];
$hari_ini = $daftar_hari[$hari_inggris];

// Kueri mengambil jadwal kuliah khusus untuk dosen ini dan hari ini saja
$q_jadwal = "SELECT kode_mk, nama_mk, jam, semester FROM kuliah WHERE id_dosen = ? AND hari = ? ORDER BY jam ASC";
$stmt_j = $conn->prepare($q_jadwal);
$stmt_j->bind_param("is", $id_dosen, $hari_ini);
$stmt_j->execute();
$result_jadwal = $stmt_j->get_result();
$stmt_j->close();

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
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #1e293b;
            background-color: #f1f5f9;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        .custom-navbar {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
        }

        .logo-navbar {
            height: 38px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0px 2px 4px rgba(0, 0, 0, 0.15));
        }

        .btn-logout-custom {
            color: rgba(255, 255, 255, 0.85);
            font-weight: 600;
            font-size: 12.5px;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .btn-logout-custom:hover,
        .btn-logout-custom:focus {
            background-color: #e11d48 !important;
            color: #ffffff !important;
            border-color: #e11d48 !important;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.4);
        }

        .main-wrapper {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .sidebar {
            width: 260px;
            background-color: #1e3a8a;
            border-right: 1px solid #1d4ed8;
            display: flex;
            flex-direction: column;
            height: 100%;
            flex-shrink: 0;
            transition: width 0.2s ease-in-out;
        }

        .sidebar .border-bottom {
            border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
        }

        .sidebar .text-dark {
            color: #ffffff !important;
        }

        .sidebar .nav-link,
        .sidebar .nav-link-danger-custom {
            color: #bfdbfe !important;
            font-size: 13.5px;
            font-weight: 600;
            padding: 12px 20px;
            margin: 3px 0;
            position: relative;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-item-normal:hover,
        .sidebar .nav-link-danger-custom:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

        .sidebar .nav-link.active {
            background-color: #172554;
            color: #ffffff !important;
            font-weight: 700;
        }

        .sidebar .nav-link.active::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
            background-color: #60a5fa;
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
        }

        .right-layout {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        .content-scrollable {
            flex: 1;
            overflow-y: auto;
            background-color: #f8fafc;
        }

        .card-welcome-premium {
            background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);
            border: none;
            border-radius: 18px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.25);
            border-bottom: 4px solid #38bdf8;
        }

        .welcome-watermark {
            position: absolute;
            right: 20px;
            bottom: -35px;
            font-size: 9rem;
            color: #ffffff;
            opacity: 0.12;
            pointer-events: none;
            transform: rotate(-12deg);
        }

        .card-stat-variant {
            border-radius: 18px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
            height: 100%;
        }

        .card-stat-variant:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
        }

        .card-merah {
            background: linear-gradient(180deg, #fff5f5 0%, #ffe3e3 100%);
            border-top: 4px solid #e11d48;
            border-left: 1px solid #ffc9c9;
            border-right: 1px solid #ffc9c9;
            border-bottom: 1px solid #ffc9c9;
        }

        .card-merah .watermark-icon-vibrant {
            color: rgba(225, 29, 72, 0.04);
        }

        .bg-soft-merah {
            background-color: #ffffff;
            color: #e11d48;
            border: 1px solid #ffc9c9;
        }

        .card-kuning {
            background: linear-gradient(180deg, #fefce8 0%, #fef08a 100%);
            border-top: 4px solid #ca8a04;
            border-left: 1px solid #fef08a;
            border-right: 1px solid #fef08a;
            border-bottom: 1px solid #fef08a;
        }

        .card-kuning .watermark-icon-vibrant {
            color: rgba(202, 138, 44, 0.05);
        }

        .bg-soft-kuning {
            background-color: #ffffff;
            color: #ca8a04;
            border: 1px solid #fde047;
        }

        .card-hijau {
            background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%);
            border-top: 4px solid #16a34a;
            border-left: 1px solid #bbf7d0;
            border-right: 1px solid #bbf7d0;
            border-bottom: 1px solid #bbf7d0;
        }

        .card-hijau .watermark-icon-vibrant {
            color: rgba(22, 163, 74, 0.04);
        }

        .bg-soft-hijau {
            background-color: #ffffff;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .watermark-icon-vibrant {
            position: absolute;
            right: -5px;
            bottom: -15px;
            font-size: 6.2rem;
            pointer-events: none;
            transform: scale(1.05);
        }

        .card-table-container-grid {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .table-header-minimalist {
            background-color: #ffffff;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #e2e8f0;
        }

        .table-custom-grid {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .table-custom-grid thead tr {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%) !important;
        }

        .table-custom-grid th {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #ffffff !important;
            padding: 16px 24px;
            border: none;
        }

        .table-custom-grid td {
            padding: 18px 24px;
            vertical-align: middle;
            color: #334155;
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        .table-custom-grid tbody tr:hover td {
            background-color: #f8fafc !important;
        }

        .table-custom-grid tbody tr:last-child td {
            border-bottom: none !important;
        }

        .badge-time-formal {
            background-color: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: monospace;
        }

        .badge-room-formal {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .class-code-pill {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            letter-spacing: 0.3px;
        }

        .empty-state-icon {
            color: #0284c7;
            background-color: #e0f2fe;
            width: 64px;
            height: 64px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-bottom: 16px;
        }

        .footer {
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #64748b;
            width: 100%;
            flex-shrink: 0;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                width: 70px;
            }

            .sidebar .text-truncate,
            .sidebar .nav-link span {
                display: none !important;
            }

            .sidebar .nav-link {
                text-align: center;
                padding: 15px 0;
            }

            .sidebar .nav-link i {
                margin-right: 0 !important;
                font-size: 16px;
            }

            .sidebar .profile-section img {
                width: 42px !important;
                height: 42px !important;
            }

            .welcome-watermark {
                font-size: 6rem;
                bottom: -15px;
            }

            .card-stat-variant h3 {
                font-size: 15px !important;
            }

            .card-stat-variant h4 {
                font-size: 11px !important;
            }

            .card-stat-variant .fw-bold {
                font-size: 8.5px !important;
            }

            .card-stat-variant .fs-6 {
                font-size: 10px !important;
            }

            .card-stat-variant .card-body {
                padding: 10px 6px !important;
                flex-direction: column;
                text-align: center;
            }

            .card-stat-variant .card-body div[class*="bg-soft-"] {
                margin-right: 0 !important;
                margin-bottom: 8px;
                padding: 8px !important;
            }

            .watermark-icon-vibrant {
                display: none;
            }
        }

        @media (max-width: 575.98px) {
            .table-header-minimalist {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .card-welcome-premium {
                border-radius: 12px;
                padding: 20px !important;
            }

            .table-custom-grid th,
            .table-custom-grid td {
                padding: 12px 16px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark custom-navbar shadow-sm sticky-top" style="z-index: 1050;">
        <div class="container-fluid px-3 px-sm-4">
            <a class="navbar-brand fw-bold d-flex align-items-center me-auto" href="dashboard_dosen.php">
                <img src="../assets/img/logo-unri.png" alt="Logo UNRI" class="logo-navbar me-2">
                <span class="d-flex flex-column">
                    <span class="text-white fw-bold mb-0" style="font-size: 15px; line-height: 1.2; letter-spacing: 0.3px;">SIAKAD</span>
                    <span class="text-white-50" style="font-size: 11px; font-weight: 400; opacity: 0.85;">Universitas Riau</span>
                </span>
            </a>
            <div class="ms-auto">
                <a class="btn btn-sm btn-logout-custom px-2 px-sm-3 py-1.5" href="../logout.php">
                    <i class="fa-solid fa-right-from-bracket me-1.5"></i> <span class="d-none d-sm-inline">Keluar</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">

        <div class="sidebar py-3" id="sidebarMenu">
            <div class="text-center pb-4 px-3 border-bottom mb-3 profile-section">
                <div class="position-relative d-inline-block mb-2">
                    <img src="<?= $foto_path ?>" class="rounded-circle border border-2 border-primary-subtle" style="width: 78px; height: 78px; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.08);">
                </div>
                <div class="fw-bold text-dark text-truncate small px-2" style="font-size: 14px; letter-spacing: -0.1px;"><?= htmlspecialchars($nama_dosen) ?></div>
            </div>

            <ul class="nav flex-column" style="flex: 1;">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard_dosen.php">
                        <i class="fa-solid fa-house-chimney me-2.5"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="matakuliah.php">
                        <i class="fa-solid fa-book-open me-2.5"></i> <span>Daftar Mata Kuliah</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="jadwal.php">
                        <i class="fa-solid fa-calendar-check me-2.5"></i> <span>Jadwal Mengajar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="input_nilai.php">
                        <i class="fa-solid fa-file-pen me-2.5"></i> <span>Input Nilai</span>
                    </a>
                </li>
                <li class="nav-item mt-2 border-top pt-2">
                    <a class="nav-link text-secondary nav-item-normal" href="edit_profil.php">
                        <i class="fa-solid fa-user-gear me-2.5"></i> <span>Pengaturan Profil</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-danger-custom" href="ganti_password.php">
                        <i class="fa-solid fa-lock-open me-2.5"></i> <span>Ganti Password</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="right-layout">

            <div class="content-scrollable px-3 px-sm-4 py-4">

                <div class="card-welcome-premium p-4 mb-4">
                    <i class="fa-solid fa-graduation-cap welcome-watermark"></i>
                    <div style="position: relative; z-index: 2;">
                        <h4 class="fw-bold text-white mb-1" style="letter-spacing: -0.3px; font-size: calc(1.2rem + 0.3vw);">
                            Selamat Datang Kembali, <?= htmlspecialchars($nama_dosen) ?>.
                        </h4>
                        <p class="text-white-50 mb-0" style="font-size: 13.5px; max-width: 85%; font-weight: 400;">
                            Akses cepat manajemen akademik dan pengelolaan nilai mahasiswa Universitas Riau.
                        </p>
                    </div>
                </div>

                <div class="row g-2 g-sm-3 mb-4">
                    <div class="col-4">
                        <div class="card card-stat-variant card-merah">
                            <i class="fa-solid fa-graduation-cap watermark-icon-vibrant"></i>
                            <div class="card-body p-3 d-flex align-items-center" style="position: relative; z-index: 2;">
                                <div class="p-3 rounded-3 bg-soft-merah me-3">
                                    <i class="fa-solid fa-graduation-cap fa-lg"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size: 11px; letter-spacing: 0.5px; color: #b91c1c !important;">MATA KULIAH DIAMPU</div>
                                    <h3 class="fw-bold text-dark mb-0 mt-0.5" style="letter-spacing: -0.5px; font-size: calc(1.3rem + 0.4vw);"><?= $total_matkul ?> <span class="fs-6 text-muted fw-normal">Kelas</span></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="card card-stat-variant card-kuning">
                            <i class="fa-solid fa-user-graduate watermark-icon-vibrant"></i>
                            <div class="card-body p-3 d-flex align-items-center" style="position: relative; z-index: 2;">
                                <div class="p-3 rounded-3 bg-soft-kuning me-3">
                                    <i class="fa-solid fa-user-graduate fa-lg"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size: 11px; letter-spacing: 0.5px; color: #a16207 !important;">MAHASISWA DIAJAR</div>
                                    <h3 class="fw-bold text-dark mb-0 mt-0.5" style="letter-spacing: -0.5px; font-size: calc(1.3rem + 0.4vw);"><?= $total_mhs ?> <span class="fs-6 text-muted fw-normal">Siswa</span></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="card card-stat-variant card-hijau">
                            <i class="fa-solid fa-circle-check watermark-icon-vibrant"></i>
                            <div class="card-body p-3 d-flex align-items-center" style="position: relative; z-index: 2;">
                                <div class="p-3 rounded-3 bg-soft-hijau me-3">
                                    <i class="fa-solid fa-circle-check fa-lg"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size: 11px; letter-spacing: 0.5px; color: #15803d !important;">STATUS AKTIVITAS</div>
                                    <h4 class="fw-bold text-dark mb-0 mt-1" style="font-size: 14px;">Dosen Aktif Mengajar</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-table-container-grid">
                    <div class="table-header-minimalist">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center" style="font-size: 14.5px; color: #0f172a !important;">
                            Jadwal Perkuliahan Hari Ini (<?= $hari_ini ?>)
                        </h6>
                        <span class="badge bg-light border text-dark rounded px-3 py-1.5 fw-bold" style="font-size: 12px; font-family: monospace;">
                            <?= date('d-m-Y') ?>
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom-grid mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 20%;" class="text-center">Sesi Jam</th>
                                    <th style="width: 55%;">Mata Kuliah & Semester</th>
                                    <th style="width: 25%;" class="text-center">Keterangan / Lokasi</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 13.5px;">
                                <?php if ($result_jadwal->num_rows > 0): ?>
                                    <?php while ($row = $result_jadwal->fetch_assoc()): ?>
                                        <tr>
                                            <td class="text-center">
                                                <div class="badge-time-formal">
                                                    <i class="fa-regular fa-clock text-secondary me-1"></i> <?= htmlspecialchars($row['jam']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                    <span class="class-code-pill"><?= htmlspecialchars($row['kode_mk']) ?></span>
                                                    <span class="fw-bold text-dark" style="font-size: 14px;">
                                                        <?= htmlspecialchars($row['nama_mk']) ?>
                                                    </span>
                                                </div>
                                                <span class="text-muted" style="font-size: 12px; font-weight: 500; display: block;">
                                                    Program Studi S1 &bull; Semester <?= htmlspecialchars($row['semester']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="badge-room-formal">
                                                    <i class="fa-solid fa-location-dot me-1"></i> Ruang Kuliah
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 bg-white">
                                            <div class="empty-state-icon">
                                                <i class="fa-solid fa-mug-hot fs-4"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1" style="font-size: 14px;">Tidak Ada Jadwal Mengajar Hari Ini</h6>
                                            <p class="text-muted small mb-0 px-3">Hari ini (<?= $hari_ini ?>) tidak terdapat agenda perkuliahan tatap muka yang dijadwalkan.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <footer class="footer py-3">
                <div class="container-fluid px-4">
                    <div class="row align-items-center justify-content-between flex-column flex-sm-row">
                        <div class="col-auto text-center text-sm-start mb-2 mb-sm-0">
                            <span class="fw-semibold text-secondary">SIAKAD Universitas Riau</span> &copy; <?= date('Y'); ?>. Seluruh Hak Cipta Dilindungi.
                        </div>
                        <div class="col-auto text-center text-sm-end">
                            <span class="me-3" style="font-size: 12px; font-weight: 500;"><i class="fa-solid fa-circle-shield text-success me-1"></i> Sesi Dosen Aman</span>
                            <span class="text-muted" style="font-size: 11px;">v2.2.0</span>
                        </div>
                    </div>
                </div>
            </footer>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>