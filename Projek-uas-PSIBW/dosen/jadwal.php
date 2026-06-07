<?php
require_once '../config/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
requireRole(['dosen']);

$conn = getDB();
$session_email = $_SESSION['username'];

// 1. Ambil data dosen pendukung sidebar
$dosen_q = "SELECT id_dosen, nama, foto FROM dosen WHERE email = ?";
$stmt_d = $conn->prepare($dosen_q);
$stmt_d->bind_param("s", $session_email);
$stmt_d->execute();
$dosen_res = $stmt_d->get_result()->fetch_assoc();
$stmt_d->close();

$id_dosen   = $dosen_res['id_dosen'] ?? 0;
$nama_dosen = !empty($dosen_res['nama']) ? $dosen_res['nama'] : 'Dosen SIAKAD';
$foto_path  = !empty($dosen_res['foto']) ? '../uploads/foto_dosen/' . $dosen_res['foto'] : 'https://via.placeholder.com/150';

// 2. Ambil data mata kuliah berdasarkan dosen yang login
$jadwal_list = [];
if ($id_dosen > 0) {
    $kuliah_q = "SELECT 
                    kode_mk, 
                    nama_mk, 
                    sks, 
                    hari, 
                    jam AS jam_mulai 
                 FROM kuliah 
                 WHERE id_dosen = ? 
                 ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), jam_mulai ASC";

    $stmt_k = $conn->prepare($kuliah_q);
    $stmt_k->bind_param("i", $id_dosen);
    $stmt_k->execute();
    $kuliah_result = $stmt_k->get_result();

    while ($row = $kuliah_result->fetch_assoc()) {
        $jam_mulai = $row['jam_mulai'];
        $sks_matkul = (int)($row['sks'] ?? 2);

        // Menghitung jam selesai secara otomatis (1 SKS = 50 Menit)
        $durasi_menit = $sks_matkul * 50;
        $jam_selesai = date('H:i', strtotime("+$durasi_menit minutes", strtotime($jam_mulai)));

        $jadwal_list[$row['hari']][] = [
            'kode_mk'     => $row['kode_mk'],
            'nama_mk'     => $row['nama_mk'],
            'sks'         => $row['sks'],
            'jam_mulai'   => $jam_mulai,
            'jam_selesai' => $jam_selesai,
            'ruangan'     => 'Ruang Kuliah Utama' // Sesuai mockup sebelumnya
        ];
    }
    $stmt_k->close();
}
$conn->close();

$urutan_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Mengajar Dosen - SIAKAD UNRI</title>
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

        /* NAVBAR PREMIUM (Sama Persis Edit Profil) */
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

        /* SIDEBAR KONSISTEN & SERAGAM (Sama Persis Edit Profil) */
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

        /* CARD CONTENT STYLING */
        .profile-clean-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
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

        /* TABS HARI MINGGUAN (Agar rapi saat sidebar mengecil) */
        .academic-tabs-line {
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 20px;
            flex-wrap: nowrap;
            overflow-x: auto;
            scrollbar-width: none; 
        }

        .academic-tabs-line::-webkit-scrollbar {
            display: none;
        }

        .academic-tabs-line .nav-item {
            white-space: nowrap;
        }

        .academic-tabs-line .nav-link {
            color: #64748b;
            font-weight: 600;
            font-size: 13.5px;
            padding: 10px 16px;
            border: none;
            border-bottom: 3px solid transparent;
            background: none;
            border-radius: 0;
            margin-bottom: -2px;
            transition: all 0.15s ease-in-out;
        }

        .academic-tabs-line .nav-link:hover:not(.disabled) {
            color: #1e3a8a;
            border-bottom-color: #cbd5e1;
        }

        .academic-tabs-line .nav-link.active {
            color: #1e3a8a !important;
            font-weight: 700;
            background: none !important;
            border-bottom: 3px solid #1e3a8a !important;
        }

        .academic-tabs-line .badge-count {
            font-size: 11px;
            background-color: #f1f5f9;
            color: #475569;
            padding: 1px 6px;
            border-radius: 4px;
            margin-left: 5px;
            font-weight: 500;
            border: 1px solid #e2e8f0;
        }

        .academic-tabs-line .nav-link.active .badge-count {
            background-color: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd;
        }

        /* GRID LAYOUT JADWAL UTAMA (Desktop & HP Fleksibel) */
        .grid-table-container {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .grid-table-header, 
        .grid-table-row {
            display: grid !important;
            grid-template-columns: 1.6fr 1.2fr 4.6fr 1.2fr 1.4fr;
            align-items: center;
            padding: 12px 16px;
        }

        .grid-table-header {
            background-color: #1e3a8a !important;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 13.5px;
        }

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

        /* BADGES & TYPOGRAPHY JADWAL */
        .badge-waktu-custom {
            background-color: #f0f4f8;
            border: 1px solid #d9e2ec;
            color: #102a43;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
            font-size: 12px;
        }

        .badge-sks-custom {
            background-color: #e0f2fe;
            border: 1px solid #bae6fd;
            color: #0369a1;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
            font-size: 11.5px;
        }

        .txt-code {
            font-family: var(--bs-font-monospace);
            font-size: 13px;
            color: #475569;
            font-weight: 600;
        }

        .txt-ruangan {
            font-weight: 600;
            color: #334155;
            font-size: 13px;
        }

        /* CARD JADWAL KHUSUS MOBILE VIEW (< 768px) */
        .mobile-jadwal-card {
            display: none;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .footer {
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #64748b;
            width: 100%;
            flex-shrink: 0;
        }

        /* MEDIA QUERIES UNTUK TAMPILAN GADGET (Sama Persis Edit Profil) */
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
            
            .sidebar .sidebar-profile-img {
                width: 40px !important;
                height: 40px !important;
            }
        }

        /* Responsivitas Khusus Komponen Tabel Jadwal */
        @media (max-width: 767.98px) {
            .grid-table-container {
                display: none !important;
            }
            .mobile-jadwal-card {
                display: block;
            }
        }

        @media (max-width: 575.98px) {
            .colorful-header-block {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 12px !important;
                padding: 20px !important;
            }
            .profile-clean-card {
                border-radius: 12px;
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

        <div class="sidebar py-3">
            <div class="text-center pb-4 px-3 border-bottom mb-3">
                <div class="position-relative d-inline-block mb-2">
                    <img src="<?= $foto_path ?>" class="sidebar-profile-img img-fluid rounded-circle border border-2 border-primary-subtle" style="width: 78px; height: 78px; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.08); transition: all 0.2s ease-in-out;">
                </div>
                <div class="text-dosen-nama text-truncate small px-2"><?= htmlspecialchars($nama_dosen) ?></div>
            </div>

            <ul class="nav flex-column" style="flex: 1;">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard_dosen.php">
                        <i class="fa-solid fa-house-chimney me-2.5"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="matakuliah.php">
                        <i class="fa-solid fa-book-open me-2.5"></i> <span>Daftar Mata Kuliah</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="jadwal.php">
                        <i class="fa-solid fa-calendar-check me-2.5"></i> <span>Jadwal Mengajar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="input_nilai.php">
                        <i class="fa-solid fa-file-pen me-2.5"></i> <span>Input Nilai</span>
                    </a>
                </li>

                <li class="nav-item mt-2 border-top pt-2">
                    <a class="nav-link" href="edit_profil.php">
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

                <div class="profile-clean-card mx-auto" style="max-width: 1100px;">
                    
                    <div class="colorful-header-block d-flex align-items-center gap-3">
                        <div class="header-badge-icon flex-shrink-0">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1" style="letter-spacing: -0.5px;">Jadwal Mengajar Mingguan</h4>
                            <p class="mb-0 text-white-50" style="font-size: 12.5px;">Pantau agenda perkuliahan rutin dan alokasi ruang kelas Anda pada semester berjalan.</p>
                        </div>
                    </div>

                    <div class="p-3 p-sm-4 bg-white">
                        
                        <ul class="nav nav-tabs academic-tabs-line" id="jadwalTab" role="tablist">
                            <?php
                            $hari_aktif_pertama = '';
                            foreach ($urutan_hari as $hari) {
                                if (isset($jadwal_list[$hari])) {
                                    $hari_aktif_pertama = $hari;
                                    break;
                                }
                            }
                            if (empty($hari_aktif_pertama)) $hari_aktif_pertama = 'Senin';

                            foreach ($urutan_hari as $hari):
                                $has_jadwal = isset($jadwal_list[$hari]);
                                $isActive = ($hari === $hari_aktif_pertama) ? 'active' : '';
                                $isDisabled = !$has_jadwal ? 'disabled' : '';
                            ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $isActive ?> <?= $isDisabled ?>"
                                        id="tab-<?= $hari ?>"
                                        data-bs-toggle="tab"
                                        data-bs-target="#panel-<?= $hari ?>"
                                        type="button"
                                        role="tab"
                                        <?= !$has_jadwal ? 'disabled' : '' ?>>
                                        <?= $hari ?>
                                        <?php if ($has_jadwal): ?>
                                            <span class="badge-count"><?= count($jadwal_list[$hari]) ?></span>
                                        <?php endif; ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php if (empty($jadwal_list)): ?>
                            <div class="text-center py-5 border rounded-3 bg-light-subtle">
                                <i class="fa-solid fa-calendar-xmark text-muted mb-3" style="font-size: 36px;"></i>
                                <h6 class="fw-bold text-dark mb-1">Tidak Ada Jadwal Mengajar</h6>
                                <p class="text-muted small mb-0">Anda tidak memiliki agenda perkuliahan aktif pada semester ini.</p>
                            </div>
                        <?php else: ?>

                            <div class="tab-content" id="jadwalTabContent">
                                <?php
                                foreach ($urutan_hari as $hari):
                                    if (isset($jadwal_list[$hari])):
                                        $isActivePanel = ($hari === $hari_aktif_pertama) ? 'show active' : '';
                                ?>
                                        <div class="tab-pane fade <?= $isActivePanel ?>" id="panel-<?= $hari ?>" role="tabpanel">
                                            
                                            <div class="grid-table-container">
                                                <div class="grid-table-header">
                                                    <div class="text-center">Waktu</div>
                                                    <div>Kode MK</div>
                                                    <div>Nama Mata Kuliah</div>
                                                    <div class="text-center">Bobot</div>
                                                    <div>Ruangan</div>
                                                </div>

                                                <?php foreach ($jadwal_list[$hari] as $item): ?>
                                                    <div class="grid-table-row">
                                                        <div class="text-center">
                                                            <div class="badge-waktu-custom">
                                                                <i class="fa-regular fa-clock me-1 text-primary"></i>
                                                                <?= date('H:i', strtotime($item['jam_mulai'])) ?> - <?= date('H:i', strtotime($item['jam_selesai'])) ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <span class="txt-code"><?= htmlspecialchars($item['kode_mk']) ?></span>
                                                        </div>
                                                        <div>
                                                            <span class="fw-bold text-dark"><?= htmlspecialchars($item['nama_mk']) ?></span>
                                                        </div>
                                                        <div class="text-center">
                                                            <span class="badge-sks-custom"><?= htmlspecialchars($item['sks']) ?> SKS</span>
                                                        </div>
                                                        <div>
                                                            <div class="txt-ruangan">
                                                                <i class="fa-solid fa-location-dot text-danger me-1"></i><?= htmlspecialchars($item['ruangan']) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <?php foreach ($jadwal_list[$hari] as $item): ?>
                                                <div class="mobile-jadwal-card">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <span class="txt-code bg-light px-2 py-1 rounded border"><?= htmlspecialchars($item['kode_mk']) ?></span>
                                                        <span class="badge-sks-custom"><?= htmlspecialchars($item['sks']) ?> SKS</span>
                                                    </div>
                                                    <h6 class="fw-bold text-dark mb-3" style="line-height: 1.4;"><?= htmlspecialchars($item['nama_mk']) ?></h6>
                                                    <hr class="text-muted my-2 opacity-25">
                                                    <div class="d-flex flex-column gap-2 pt-1">
                                                        <div class="small d-flex align-items-center">
                                                            <i class="fa-regular fa-clock text-primary me-2" style="width: 16px;"></i>
                                                            <span class="fw-semibold text-dark"><?= date('H:i', strtotime($item['jam_mulai'])) ?> - <?= date('H:i', strtotime($item['jam_selesai'])) ?> WIB</span>
                                                        </div>
                                                        <div class="small d-flex align-items-center">
                                                            <i class="fa-solid fa-location-dot text-danger me-2" style="width: 16px;"></i>
                                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($item['ruangan']) ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>

                                        </div>
                                <?php
                                    endif;
                                endforeach;
                                ?>
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