<?php
require_once '../config/db.php';
requireRole(['dosen']);

$conn = getDB();
$session_email = $_SESSION['username'];

// 1. Ambil data dosen pendukung sidebar & ID Dosen untuk filter matkul
$dosen_q = "SELECT id_dosen, nama, foto FROM dosen WHERE email = ?";
$stmt_d = $conn->prepare($dosen_q);
$stmt_d->bind_param("s", $session_email);
$stmt_d->execute();
$dosen_res = $stmt_d->get_result()->fetch_assoc();
$stmt_d->close();

$id_dosen   = $dosen_res['id_dosen'] ?? 0;
$nama_dosen = $dosen_res['nama'] ?? 'Dosen SIAKAD';
$foto_path  = !empty($dosen_res['foto']) ? '../uploads/foto_dosen/' . $dosen_res['foto'] : 'https://via.placeholder.com/150';

// 2. QUERY UTAMA FIX: Hanya ambil mata kuliah yang diampu oleh DOSEN YANG LOGIN
$kuliah_q = "SELECT id_kuliah, kode_mk, nama_mk, sks FROM kuliah WHERE id_dosen = ? ORDER BY nama_mk ASC";
$stmt_k = $conn->prepare($kuliah_q);
$stmt_k->bind_param("i", $id_dosen);
$stmt_k->execute();
$kuliah_result = $stmt_k->get_result();
// Jangan ditutup dulu $stmt_k atau $conn di sini, biarkan loop di bawah menyelesaikannya, atau tutup di paling bawah file HTML.
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Mata Kuliah - SIAKAD UNRI</title>
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
            color: #334155;
            background-color: #f8fafc;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        /* NAVBAR SINKRON */
        .custom-navbar {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            color: #bfdbfe !important; /* Biru muda pudar premium */
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

        /* HERO HEADER JADWAL / MATKUL */
        .form-hero-header {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            padding: 24px 28px;
            border-bottom: 1px solid #bfdbfe;
            border-left: 5px solid #2563eb;
            border-radius: 12px 12px 0 0;
        }

        .header-icon-box {
            width: 44px;
            height: 44px;
            background-color: #ffffff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1d4ed8;
            font-size: 18px;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.06);
        }

        /* TABEL CUSTOM MODERN */
        .table-custom {
            font-size: 13.5px;
            margin-bottom: 0;
        }

        .table-custom thead th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11.5px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            padding: 14px 16px;
        }

        .table-custom tbody tr {
            transition: background-color 0.15s ease;
        }

        .table-custom tbody tr:hover {
            background-color: #f8fafc;
        }

        .table-custom tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        .sks-badge {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            font-weight: 600;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
        }

        /* FOOTER */
        .footer {
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #64748b;
            width: 100%;
        }   

        @media (max-width: 767.98px) {
            html, body { overflow: auto; height: auto; }
            .main-wrapper { flex-direction: column; overflow: visible; }
            .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid #e2e8f0; }
            .right-layout { height: auto; overflow: visible; }
            .content-scrollable { overflow-y: visible; height: auto; }
            .form-hero-header { border-radius: 0; }
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
                    <a class="nav-link" href="dashboard_dosen.php">
                        <i class="fa-solid fa-house-chimney me-2.5"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="matakuliah.php">
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
                    <a class="nav-link" href="edit_profil.php">
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
                
                <div class="card shadow-sm border-0 mx-auto" style="max-width: 1000px; border-radius: 12px; overflow: hidden;">
                    <div class="form-hero-header d-flex align-items-center gap-3">
                        <div class="header-icon-box d-none d-sm-flex">
                            <i class="fa-solid fa-book"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-primary mb-1" style="letter-spacing: -0.3px; color: #1e3a8a !important;">Mata Kuliah Yang Diampu</h5>
                            <p class="text-secondary mb-0" style="font-size: 12.5px; line-height: 1.4; color: #475569 !important;">
                                Silakan pilih salah satu mata kuliah aktif di bawah ini untuk memulai pengisian bobot nilai mahasiswa.
                            </p>
                        </div>
                    </div>

                    <div class="card-body p-0 bg-white">
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;" class="text-center">No</th>
                                        <th style="width: 160px;">Kode MK</th>
                                        <th>Nama Mata Kuliah</th>
                                        <th style="width: 130px;" class="text-center">Bobot SKS</th>
                                        <th style="width: 160px;" class="text-center">Aksi Manajemen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    if ($kuliah_result && $kuliah_result->num_rows > 0):
                                        while ($row = $kuliah_result->fetch_assoc()):
                                    ?>
                                            <tr>
                                                <td class="text-center text-muted fw-medium"><?= $no++ ?></td>
                                                <td><span class="badge bg-slate border text-dark font-monospace px-2 py-1.5" style="background-color: #f1f5f9; border-color: #cbd5e1 !important;"><?= htmlspecialchars($row['kode_mk']) ?></span></td>
                                                <td><span class="fw-semibold text-dark" style="font-size: 14.5pxিলেন"><?= htmlspecialchars($row['nama_mk']) ?></span></td>
                                                <td class="text-center">
                                                    <span class="sks-badge"><?= htmlspecialchars($row['sks'] ?? '3') ?> SKS</span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="input_nilai.php?id_kuliah=<?= $row['id_kuliah'] ?>" class="btn btn-sm btn-primary rounded-3 fw-medium px-3 py-1.5 shadow-sm" style="font-size: 12px; transition: all 0.2s;">
                                                        <i class="fa-solid fa-file-signature me-1.5"></i> Input Nilai
                                                    </a>
                                                </td>
                                            </tr>
                                    <?php
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="5" class="text-center p-5 text-muted">
                                                <i class="fa-solid fa-folder-open d-block fs-2 mb-3" style="color: #cbd5e1;"></i>
                                                Belum ada penugasan mata kuliah yang terdata untuk Anda di database.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
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
<?php 
$stmt_k->close();
$conn->close(); 
?>