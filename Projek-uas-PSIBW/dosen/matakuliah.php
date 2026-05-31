<?php
require_once '../config/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
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

// 2. QUERY UTAMA: Hanya ambil mata kuliah yang diampu oleh DOSEN YANG LOGIN
$kuliah_q = "SELECT id_kuliah, kode_mk, nama_mk, sks FROM kuliah WHERE id_dosen = ? ORDER BY nama_mk ASC";
$stmt_k = $conn->prepare($kuliah_q);
$stmt_k->bind_param("i", $id_dosen);
$stmt_k->execute();
$kuliah_result = $stmt_k->get_result();
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

        /* NAVBAR KONSISTEN */
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

        .btn-logout-custom:hover {
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

        /* SIDEBAR KONSISTEN */
        .sidebar {
            width: 260px;
            background-color: #1e3a8a;
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

        .sidebar .text-dosen-nama {
            color: #ffffff !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            letter-spacing: -0.1px;
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
        }

        .sidebar .nav-link:hover,
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

        /* CARD CLEAN DAN GRADASI HEADER UTAMA */
        .profile-clean-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .colorful-header-block {
            background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);
            padding: 24px 30px;
            color: #ffffff;
            border-bottom: 4px solid #38bdf8;
        }

        .header-badge-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #ffffff;
        }

        /* ======================================================== */
        /* IMPLEMENTASI CSS GRID AGAR SELARAS TOTAL                 */
        /* ======================================================== */
        .grid-table-container {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        /* Cetakan kolom dikunci mutlak berurutan */
        .grid-table-header,
        .grid-table-row {
            display: grid !important;
            grid-template-columns: 0.8fr 1.5fr 5fr 1.2fr 1.5fr; /* Rasio proporsi kolom pakem */
            align-items: center;
            padding: 14px 16px;
        }

        /* Header Style */
        .grid-table-header {
            background-color: #1e3a8a !important;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 13.5px;
            border-bottom: none;
        }

        /* Row Style */
        .grid-table-row {
            border-bottom: 1px solid #e2e8f0;
            background-color: #ffffff;
            font-size: 13.5px;
            transition: background-color 0.15s ease;
        }

        .grid-table-row:hover {
            background-color: #f8fafc;
        }

        .grid-table-row:last-child {
            border-bottom: none;
        }

        /* Alignment Konten Kolom Grid */
        .col-center-align {
            text-align: center;
            justify-self: center;
        }

        .col-nama {
            white-space: normal !important;
            word-break: break-word;
            padding-right: 15px;
        }

        /* Komponen Badge & Teks Khusus */
        .badge-code-custom {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-family: var(--bs-font-monospace);
            font-weight: 600;
            font-size: 12.5px;
            padding: 5px 10px;
            border-radius: 6px;
            display: inline-block;
        }

        .badge-sks-custom {
            background-color: #e0f2fe;
            border: 1px solid #bae6fd;
            color: #0369a1;
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
            font-size: 12px;
        }

        /* FOOTER */
        .footer {
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #64748b;
            width: 100%;
            flex-shrink: 0;
        }

        /* Responsivitas Layar Seluler */
        @media (max-width: 767.98px) {
            html, body { overflow: auto; height: auto; }
            .main-wrapper { flex-direction: column; overflow: visible; }
            .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid #e2e8f0; }
            .right-layout { height: auto; overflow: visible; }
            .content-scrollable { overflow-y: visible; height: auto; }

            .grid-table-header { display: none !important; }
            .grid-table-row {
                display: flex !important; /* Kembali ke susunan tumpuk vertikal di HP */
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                padding: 14px;
            }
            .col-center-align { text-align: left; justify-self: flex-start; }
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
                <div class="text-dosen-nama text-truncate small px-2"><?= htmlspecialchars($nama_dosen) ?></div>
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

            <div class="content-scrollable px-3 px-sm-4 py-4">

                <div class="profile-clean-card mx-auto" style="max-width: 1100px;">

                    <div class="colorful-header-block d-flex align-items-center gap-3">
                        <div class="header-badge-icon flex-shrink-0">
                            <i class="fa-solid fa-book"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1" style="letter-spacing: -0.5px;">Mata Kuliah Yang Diampu</h4>
                            <p class="mb-0 text-white-50" style="font-size: 12.5px;">Silakan pilih salah satu mata kuliah aktif di bawah ini untuk memulai pengisian atau kelola bobot nilai mahasiswa.</p>
                        </div>
                    </div>

                    <div class="p-4 bg-white">

                        <?php if (!($kuliah_result && $kuliah_result->num_rows > 0)): ?>
                            <div class="text-center py-5 border rounded-3 bg-light-subtle">
                                <i class="fa-solid fa-folder-open text-muted mb-3" style="font-size: 36px;"></i>
                                <h6 class="fw-bold text-dark mb-1">Belum Ada Penugasan</h6>
                                <p class="text-muted small mb-0">Belum ada penugasan mata kuliah yang terdata untuk Anda di database.</p>
                            </div>
                        <?php else: ?>

                            <div class="grid-table-container">

                                <div class="grid-table-header">
                                    <div class="col-center-align">No</div>
                                    <div>Kode MK</div>
                                    <div>Nama Mata Kuliah</div>
                                    <div class="col-center-align">Bobot SKS</div>
                                    <div class="col-center-align">Aksi Manajemen</div>
                                </div>

                                <?php 
                                $no = 1;
                                while ($row = $kuliah_result->fetch_assoc()): 
                                ?>
                                    <div class="grid-table-row">
                                        
                                        <div class="col-center-align text-muted fw-semibold">
                                            <?= $no++ ?>
                                        </div>
                                        
                                        <div>
                                            <span class="badge-code-custom"><?= htmlspecialchars($row['kode_mk']) ?></span>
                                        </div>
                                        
                                        <div class="col-nama">
                                            <span class="fw-bold text-dark" style="font-size: 14.5px;">
                                                <?= htmlspecialchars($row['nama_mk']) ?>
                                            </span>
                                        </div>
                                        
                                        <div class="col-center-align">
                                            <span class="badge-sks-custom"><?= htmlspecialchars($row['sks'] ?? '3') ?> SKS</span>
                                        </div>
                                        
                                        <div class="col-center-align">
                                            <a href="input_nilai.php?id_kuliah=<?= $row['id_kuliah'] ?>" class="btn btn-sm btn-primary rounded-3 fw-medium px-3 py-1.5 shadow-sm d-inline-flex align-items-center" style="font-size: 12px; transition: all 0.2s;">
                                                <i class="fa-solid fa-file-signature me-1.5"></i> Input Nilai
                                            </a>
                                        </div>

                                    </div>
                                <?php endwhile; ?>

                            </div>

                        <?php endif; ?>

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