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

// B. Menghitung Total Mahasiswa unik yang diajar oleh dosen ini (berdasarkan mata kuliah yang diampunya)
$q_mhs = "SELECT COUNT(DISTINCT n.id_mhs) AS total_mhs 
          FROM nilai n 
          JOIN kuliah k ON n.id_kuliah = k.id_kuliah 
          WHERE k.id_dosen = ?";
$stmt_mh = $conn->prepare($q_mhs);
$stmt_mh->bind_param("i", $id_dosen);
$stmt_mh->execute();
$row_mhs = $stmt_mh->get_result()->fetch_assoc();
$total_mhs = $row_mhs['total_mhs'] ?? 0;
$stmt_mh->close();


// 3. AMBIL JADWAL MENGAJAR HARI INI (Otomatis Deteksi Hari Bahasa Indonesia)
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
// (Pastikan kolom 'hari' dan 'jam' sudah kamu tambahkan di tabel kuliah seperti langkah sebelumnya)
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
        html, body {
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

        /* NAVBAR PREMIUM */
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

        /* SIDEBAR: ROYAL BLUE CAMPUS THEME (SINKRON & SERAGAM)     */
        .sidebar {
            width: 260px;
            background-color: #1e3a8a; /* Biru Royal Kampus */
            border-right: 1px solid #1d4ed8;
            display: flex;
            flex-direction: column;
            height: 100%;
            flex-shrink: 0;
        }

        .sidebar .border-bottom {
            border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
        }

        .sidebar .text-dark {
            color: #ffffff !important;
        }

        /* Kita paksa semua jenis nav-link (termasuk class custom profil) agar warnanya sama */
        .sidebar .nav-link,
        .sidebar .nav-link-danger-custom {
            color: #bfdbfe !important; 
            font-size: 13.5px;
            font-weight: 600;
            padding: 12px 20px;
            margin: 3px 0;
            position: relative;
            transition: all 0.2s ease;
        }

        /* Efek hover untuk semua menu di sidebar */
        .sidebar .nav-link:hover,
        .sidebar .nav-item-normal:hover,
        .sidebar .nav-link-danger-custom:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

        .sidebar .nav-link.active {
            background-color: #172554; /* Biru dongker pekat */
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

        /* AKSEN MERAH MENYALA DI ATAS BACKGROUND BLUE ROYAL */
        .sidebar .nav-link.active-merah {
            background-color: #991b1b !important;
            color: #fecdd3 !important;
            font-weight: 700;
        }

        .sidebar .nav-link.active-merah::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
            background-color: #ef4444;
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

        /* CARD WELCOME */
        .card-welcome-premium {
            background: linear-gradient(135deg, #0f172a 0%, #115e59 100%);
            border: none;
            border-radius: 18px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(17, 94, 89, 0.3);
        }

        .welcome-watermark {
            position: absolute;
            right: 20px;
            bottom: -35px;
            font-size: 9rem;
            color: #2dd4bf;
            opacity: 0.15;
            pointer-events: none;
            transform: rotate(-12deg);
        }

        /* CARD STATISTIK */
        .card-stat-variant {
            border: none;
            border-radius: 18px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .card-stat-variant:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
        }

        .card-blue { border-top: 4px solid #2563eb; }
        .card-green { border-top: 4px solid #10b981; }
        .card-purple { border-top: 4px solid #7c3aed; }

        .watermark-icon-vibrant {
            position: absolute;
            right: -5px;
            bottom: -15px;
            font-size: 6.2rem;
            pointer-events: none;
            transform: scale(1.05);
        }
        .card-blue .watermark-icon-vibrant { color: rgba(37, 99, 235, 0.18); }
        .card-green .watermark-icon-vibrant { color: rgba(16, 185, 129, 0.18); }
        .card-purple .watermark-icon-vibrant { color: rgba(124, 58, 237, 0.18); }

        .bg-soft-primary { background-color: #dbeafe; color: #1d4ed8; }
        .bg-soft-success { background-color: #d1fae5; color: #065f46; }
        .bg-soft-purple { background-color: #ede9fe; color: #6d28d9; }

        /* CONTAINER JADWAL GLASSMORPHISM */
        .card-table-container-grid {
            background-color: rgba(30, 58, 138, 0.09);
            backdrop-filter: blur(14px); 
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            padding: 5px;
        }

        .table-header-minimalist {
            background-color: transparent;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table-custom-grid {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
            background-color: transparent !important;
        }

        .table-custom-grid, 
        .table-custom-grid th, 
        .table-custom-grid td {
            border: none !important; 
        }

        .table-custom-grid thead tr {
            background-color: rgba(30, 58, 138, 0.07) !important; 
        }

        .table-custom-grid th {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #1e3a8a; 
            padding: 14px 20px;
        }

        .table-custom-grid td {
            padding: 16px 20px;
            vertical-align: middle;
            color: #1e293b;
            background-color: transparent !important;
        }

        .table-custom-grid tbody tr:hover td {
            background-color: rgba(30, 58, 138, 0.05) !important; 
        }

        .icon-jam-vibrant { color: #f59e0b !important; }
        .icon-matkul-vibrant { color: #2563eb !important; }
        .icon-lab-vibrant { color: #10b981 !important; }
        .icon-gedung-vibrant { color: #8b5cf6 !important; }

        .badge-time-vibrant, .badge-room-vibrant-1 {
            background-color: rgba(255, 255, 255, 0.85);
            color: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.25);
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .class-pill-matchy {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .footer {
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #64748b;
            width: 100%;
            flex-shrink: 0;
        }

        @media (max-width: 767.98px) {
            html, body { overflow: auto; height: auto; }
            .main-wrapper { flex-direction: column; overflow: visible; }
            .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid #e2e8f0; }
            .right-layout { height: auto; overflow: visible; }
            .content-scrollable { overflow-y: visible; height: auto; }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark custom-navbar shadow-sm sticky-top" style="z-index: 1050;">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard_dosen.php">
                <img src="../assets/img/logo-unri.png" alt="Logo UNRI" class="logo-navbar me-2">
                <span class="d-flex flex-column">
                    <span class="text-white fw-bold mb-0" style="font-size: 15px; line-height: 1.2; letter-spacing: 0.3px;">SIAKAD Portal</span>
                    <span class="text-white-50" style="font-size: 11px; font-weight: 400; opacity: 0.85;">Universitas Riau</span>
                </span>
            </a>
            <div class="ms-auto">
                <a class="btn btn-sm btn-logout-custom px-3 py-1.5" href="../logout.php">
                    <i class="fa-solid fa-right-from-bracket me-1.5"></i> Keluar
                </a>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        
        <div class="sidebar py-3">
            <div class="text-center pb-4 px-3 border-bottom mb-3">
                <div class="position-relative d-inline-block mb-2">
                    <img src="<?= $foto_path ?>" class="rounded-circle border border-2 border-primary-subtle" style="width: 78px; height: 78px; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.08);">
                </div>
                <div class="fw-bold text-dark text-truncate small px-2" style="font-size: 14px; letter-spacing: -0.1px;"><?= htmlspecialchars($nama_dosen) ?></div>
            </div>

            <ul class="nav flex-column" style="flex: 1;">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard_dosen.php">
                        <i class="fa-solid fa-house-chimney me-2.5"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="matakuliah.php">
                        <i class="fa-solid fa-book-open me-2.5"></i> Daftar Mata Kuliah
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="jadwal.php">
                        <i class="fa-solid fa-calendar-check me-2.5"></i> Jadwal Mengajar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="input_nilai.php">
                        <i class="fa-solid fa-file-pen me-2.5"></i> Input Nilai
                    </a>
                </li>
                
                <li class="nav-item mt-2 border-top pt-2">
                    <a class="nav-link text-secondary nav-item-normal" href="edit_profil.php">
                        <i class="fa-solid fa-user-gear me-2.5"></i> Pengaturan Profil
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link nav-link-danger-custom" href="ganti_password.php">
                        <i class="fa-solid fa-lock-open me-2.5"></i> Ganti Password
                    </a>
                </li>
            </ul>
        </div>

        <div class="right-layout">
            
            <div class="content-scrollable px-4 py-4">

                <div class="card-welcome-premium p-4 mb-4">
                    <i class="fa-solid fa-graduation-cap welcome-watermark"></i>
                    <div style="position: relative; z-index: 2;">
                        <h4 class="fw-bold text-white mb-1" style="letter-spacing: -0.3px;">
                            Selamat Datang Kembali, <?= htmlspecialchars($nama_dosen) ?>.
                        </h4>
                        <p class="text-white-50 mb-0" style="font-size: 13.5px; max-width: 85%; font-weight: 400;">
                            Akses cepat manajemen akademik dan pengelolaan nilai mahasiswa Universitas Riau.
                        </p>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card card-stat-variant card-blue">
                            <i class="fa-solid fa-graduation-cap watermark-icon-vibrant"></i>
                            <div class="card-body p-3.5 d-flex align-items-center" style="position: relative; z-index: 2;">
                                <div class="p-3 rounded-3 bg-soft-primary me-3">
                                    <i class="fa-solid fa-graduation-cap fa-lg"></i>
                                </div>
                                <div>
                                    <div class="text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">MATA KULIAH DIAMPU</div>
                                    <h3 class="fw-bold text-dark mb-0 mt-0.5" style="letter-spacing: -0.5px;"><?= $total_matkul ?> <span class="fs-6 text-muted fw-normal">Kelas</span></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-stat-variant card-green">
                            <i class="fa-solid fa-user-graduate watermark-icon-vibrant"></i>
                            <div class="card-body p-3.5 d-flex align-items-center" style="position: relative; z-index: 2;">
                                <div class="p-3 rounded-3 bg-soft-success me-3">
                                    <i class="fa-solid fa-user-graduate fa-lg"></i>
                                </div>
                                <div>
                                    <div class="text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">MAHASISWA DIAJAR</div>
                                    <h3 class="fw-bold text-dark mb-0 mt-0.5" style="letter-spacing: -0.5px;"><?= $total_mhs ?> <span class="fs-6 text-muted fw-normal">Siswa</span></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-stat-variant card-purple">
                            <i class="fa-solid fa-circle-check watermark-icon-vibrant"></i>
                            <div class="card-body p-3.5 d-flex align-items-center" style="position: relative; z-index: 2;">
                                <div class="p-3 rounded-3 bg-soft-purple me-3">
                                    <i class="fa-solid fa-circle-check fa-lg"></i>
                                </div>
                                <div>
                                    <div class="text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">STATUS AKTIVITAS</div>
                                    <h4 class="fw-bold text-dark mb-0 mt-1" style="font-size: 14px;">Dosen Aktif Mengajar</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-table-container-grid">
                    <div class="table-header-minimalist">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center" style="font-size: 15px; color: #1e3a8a !important;">
                            <i class="fa-solid fa-calendar-day me-2.5 icon-matkul-vibrant"></i>Jadwal Perkuliahan Hari Ini (<?= $hari_ini ?>)
                        </h6>
                        <span class="badge bg-white text-primary border rounded-pill px-3 py-1.5 fw-bold" style="font-size: 11.5px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                            <?= date('d M Y') ?>
                        </span>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-custom-grid mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 25%;" class="text-center">Sesi Jam</th>
                                    <th style="width: 53%;">Mata Kuliah & Semester</th>
                                    <th style="width: 22%;" class="text-center">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 13.5px;">
                                <?php if ($result_jadwal->num_rows > 0): ?>
                                    <?php while ($row = $result_jadwal->fetch_assoc()): ?>
                                        <tr>
                                            <td class="text-center">
                                                <div class="badge-time-vibrant">
                                                    <i class="fa-regular fa-clock icon-jam-vibrant"></i> <?= htmlspecialchars($row['jam']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="class-pill-matchy"><?= htmlspecialchars($row['kode_mk']) ?></span>
                                                    <span class="fw-bold text-dark" style="font-size: 14.5px; letter-spacing: -0.2px;">
                                                        <i class="fa-solid fa-book-open-reader icon-matkul-vibrant me-1"></i> <?= htmlspecialchars($row['nama_mk']) ?>
                                                    </span>
                                                </div>
                                                <span class="text-secondary ms-1" style="font-size: 12px; font-weight: 500; padding-left: 20px; display: block;">
                                                    Semester <?= htmlspecialchars($row['semester']) ?> &bull; Reguler
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="badge-room-vibrant-1">
                                                    <i class="fa-solid fa-school icon-gedung-vibrant"></i> Ruang Kuliah
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-mug-hot d-block mb-2 fs-3 text-secondary" style="opacity: 0.5;"></i>
                                            <span class="fw-semibold d-block">Tidak Ada Jadwal Mengajar</span>
                                            Hari ini (<?= $hari_ini ?>) Anda tidak memiliki jadwal perkuliahan.
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