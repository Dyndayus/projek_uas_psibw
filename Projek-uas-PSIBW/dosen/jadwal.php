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
$nama_dosen = $dosen_res['nama'] ?? 'Dosen SIAKAD';
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
            'kelas'       => 'Reguler A',
            'ruangan'     => 'Ruang Kuliah Utama'
        ];
    }
    $stmt_k->close();
}
$conn->close();

// Array urutan hari untuk memastikan sorting tampilan tetap berurutan dari Senin dst.
$urutan_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function getHariClass($hari)
{
    switch (ucfirst(strtolower($hari))) {
        case 'Senin':
            return 'card-senin';
        case 'Selasa':
            return 'card-selasa';
        case 'Rabu':
            return 'card-senin'; // Mengikuti style asli kamu
        case 'Kamis':
            return 'card-kamis';
        case 'Jumat':
            return 'card-jumat';
        default:
            return 'card-jumat';
    }
}
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

        /* ======================================================== */
        /* SIDEBAR: ROYAL BLUE CAMPUS THEME (SINKRON & SERAGAM)     */
        /* ======================================================== */
        .sidebar {
            width: 260px;
            background-color: #1e3a8a;
            /* Biru Royal Kampus */
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
            /* Biru muda pudar premium */
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
            background-color: #172554;
            /* Biru dongker pekat */
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

        /* HERO HEADER JADWAL */
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

        /* CARD JADWAL */
        .jadwal-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            overflow: hidden;
        }

        .jadwal-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }

        .day-strip {
            background-color: #f1f5f9;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #334155;
            font-size: 14px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            min-height: 100%;
            padding: 15px;
        }

        /* VARIASI WARNA STRIP HARI */
        .card-senin {
            border-left: 5px solid #f43f5e;
        }
        .card-senin .day-strip {
            background: linear-gradient(180deg, #fff1f2 0%, #ffe4e6 100%);
            color: #be123c;
            border-right-color: #fecdd3;
        }

        .card-selasa {
            border-left: 5px solid #eab308;
        }
        .card-selasa .day-strip {
            background: linear-gradient(180deg, #fefce8 0%, #fef9c3 100%);
            color: #a16207;
            border-right-color: #fef08a;
        }

        .card-rabu {
            border-left: 5px solid #10b981; 
        }
        .card-rabu .day-strip {
            background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%);
            color: #047857;
            border-right-color: #a7f3d0;
        }

        .card-kamis {
            border-left: 5px solid #f97316; 
        }
        .card-kamis .day-strip {
            background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
            color: #c2410c;
            border-right-color: #fed7aa;
        }

        .card-jumat {
            border-left: 5px solid #ec4899; 
        }
        .card-jumat .day-strip {
            background: linear-gradient(180deg, #fdf2f8 0%, #fce7f3 100%);
            color: #be185d;
            border-right-color: #fbcfe8;
        }

        .time-badge {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            font-weight: 600;
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
        }

        .meta-info-badge {
            font-size: 12px;
            font-weight: 500;
            color: #64748b;
            background-color: #f8fafc;
            border: 1px solid #f1f5f9;
            padding: 3px 10px;
            border-radius: 20px;
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

            html,
            body {
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

            .day-strip {
                padding: 12px;
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
                flex-direction: row;
                gap: 8px;
            }

            .form-hero-header {
                border-radius: 0;
            }
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
                    <a class="nav-link" href="matakuliah.php">
                        <i class="fa-solid fa-book-open me-2.5"></i> Daftar Mata Kuliah
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="jadwal.php">
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
                <div class="card shadow-sm border-0 mx-auto mb-4" style="max-width: 1000px; border-radius: 12px; overflow: hidden;">
                    <div class="form-hero-header d-flex align-items-center gap-3">
                        <div class="header-icon-box d-none d-sm-flex">
                            <i class="fa-solid fa-calendar-week"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-primary mb-1" style="letter-spacing: -0.3px; color: #1e3a8a !important;">Jadwal Perkuliahan Mingguan</h5>
                            <p class="text-secondary mb-0" style="font-size: 12.5px; line-height: 1.4; color: #475569 !important;">
                                Daftar waktu dan lokasi pelaksanaan kelas tatap muka akademik yang ditugaskan resmi kepada Anda.
                            </p>
                        </div>
                    </div>

                    <div class="p-4 bg-white d-flex flex-column gap-3">
                        <?php
                        if (empty($jadwal_list)):
                        ?>
                            <div class="alert alert-info text-center py-4 border-0 shadow-sm" style="border-radius: 10px; background-color: #f0f9ff;">
                                <i class="fa-solid fa-calendar-xmark text-primary mb-2" style="font-size: 24px;"></i>
                                <h6 class="fw-bold text-dark mb-1">Belum Ada Jadwal Mengajar</h6>
                                <p class="text-muted small mb-0">Anda tidak memiliki agenda kelas tatap muka yang terplot pada semester ini.</p>
                            </div>
                            <?php
                        else:
                            // LOGIKA TERBARU: Loop hanya hari yang memiliki jadwal aktif
                            foreach ($urutan_hari as $hari):
                                if (isset($jadwal_list[$hari])):
                                    foreach ($jadwal_list[$hari] as $item):
                                        $bgClass = getHariClass($hari);
                            ?>
                                        <div class="jadwal-card <?= $bgClass ?>">
                                            <div class="row g-0 align-items-stretch">
                                                <div class="col-md-2 d-none d-md-flex day-strip">
                                                    <i class="fa-solid fa-calendar-day mb-1 opacity-75"></i>
                                                    <span><?= $hari ?></span>
                                                </div>
                                                <div class="col-12 col-md-10 p-3">
                                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                                        <div>
                                                            <span class="d-md-none badge bg-primary mb-2"><?= $hari ?></span>
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <h6 class="fw-bold text-dark mb-0" style="font-size: 16px;"><?= htmlspecialchars($item['nama_mk']) ?></h6>
                                                                <span class="meta-info-badge"><?= htmlspecialchars($item['sks']) ?> SKS</span>
                                                            </div>
                                                            <p class="text-muted mb-0 small fw-medium">
                                                                <i class="fa-solid fa-layer-group me-1"></i> <?= htmlspecialchars($item['kode_mk']) ?> &bull; <?= htmlspecialchars($item['kelas']) ?>
                                                            </p>
                                                        </div>
                                                        <div class="text-md-end d-flex flex-column align-items-start align-items-md-end">
                                                            <div class="time-badge mb-2">
                                                                <i class="fa-regular fa-clock me-2 text-primary fw-bold"></i><?= date('H:i', strtotime($item['jam_mulai'])) ?> - <?= date('H:i', strtotime($item['jam_selesai'])) ?>
                                                            </div>
                                                            <div class="text-secondary small fw-medium">
                                                                <i class="fa-solid fa-location-dot me-1.5 text-danger"></i><?= htmlspecialchars($item['ruangan']) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                        <?php
                                    endforeach;
                                endif;
                            endforeach;
                        endif;
                        ?>
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