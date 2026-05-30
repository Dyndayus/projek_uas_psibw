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

        /* ======================================================== */
        /* RE-DESIGN AREA: REAL CAMPUS PORTAL STYLE                 */
        /* ======================================================== */
        .academic-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 20px 24px;
        }

        .academic-tabs .nav-link {
            color: #64748b;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 8px;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .academic-tabs .nav-link:hover:not(.disabled) {
            color: #1e3a8a;
            background-color: #f1f5f9;
        }

        .academic-tabs .nav-link.active {
            background-color: #1e3a8a !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
        }

        .academic-tabs .nav-link.disabled {
            color: #cbd5e1;
            cursor: not-allowed;
            background-color: #f8fafc;
        }

        .table-academic {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
        }

        /* Menggunakan sistem table-layout auto agar kolom fleksibel dan sejajar sempurna */
        .table-academic table {
            table-layout: auto !important;
            width: 100% !important;
            border-collapse: collapse;
        }

        .table-academic thead {
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        /* Padding disamakan persis (16px atas-bawah, 24px kiri-kanan) agar titik mulai teks lurus vertikal */
        .table-academic th {
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            padding: 16px 24px !important;
            vertical-align: middle;
            text-align: left;
            /* Memastikan semua header rata kiri */
        }

        .table-academic td {
            padding: 16px 24px !important;
            /* Wajib sama dengan padding th */
            vertical-align: middle;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            white-space: nowrap;
            text-align: left;
            /* Memastikan semua isi data rata kiri */
        }

        .table-academic tbody tr:hover {
            background-color: #f8fafc;
        }

        .badge-sks {
            background-color: #e0f2fe;
            color: #0369a1;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
        }

        .badge-time {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 600;
            border: 1px solid #e2e8f0;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 13px;
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

            .table-academic table {
                table-layout: auto;
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

            <div class="academic-header">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-calendar-week text-primary fs-5"></i>
                    <h5 class="fw-bold text-dark mb-0" style="letter-spacing: -0.3px;">Jadwal Mengajar Mingguan</h5>
                </div>

                <ul class="nav nav-pills academic-tabs gap-1" id="jadwalTab" role="tablist">
                    <?php
                    // Cari hari pertama yang punya jadwal untuk dijadikan tab active otomatis
                    $hari_aktif_pertama = '';
                    foreach ($urutan_hari as $hari) {
                        if (isset($jadwal_list[$hari])) {
                            $hari_aktif_pertama = $hari;
                            break;
                        }
                    }
                    // Jika sama sekali tidak ada jadwal, default ke Senin
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
                                    <span class="badge bg-white text-dark ms-1 rounded-circle" style="font-size: 10px; padding: 3px 6px;"><?= count($jadwal_list[$hari]) ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="content-scrollable px-4 py-4">
                <div class="mx-auto" style="max-width: 1000px;">

                    <?php if (empty($jadwal_list)): ?>
                        <div class="card border-0 shadow-sm text-center py-5" style="border-radius: 8px;">
                            <div class="card-body">
                                <i class="fa-solid fa-calendar-xmark text-muted mb-3" style="font-size: 40px;"></i>
                                <h6 class="fw-bold text-dark mb-1">Tidak Ada Jadwal Mengajar</h6>
                                <p class="text-muted small mb-0">Anda tidak memiliki agenda perkuliahan aktif pada semester ini.</p>
                            </div>
                        </div>
                    <?php else: ?>

                        <div class="tab-content" id="jadwalTabContent">
                            <?php
                            foreach ($urutan_hari as $hari):
                                if (isset($jadwal_list[$hari])):
                                    $isActivePanel = ($hari === $hari_aktif_pertama) ? 'show active' : '';
                            ?>
                                    <div class="tab-pane fade <?= $isActivePanel ?>" id="panel-<?= $hari ?>" role="tabpanel">
                                        <div class="table-responsive table-academic shadow-sm bg-white">
                                            <table class="table mb-0 align-middle">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 20%; text-align: left;">Waktu</th>
                                                        <th style="width: 12%; text-align: left;">Kode MK</th>
                                                        <th style="width: 33%; text-align: left;">Nama Mata Kuliah</th>
                                                        <th style="width: 10%; text-align: left;">SKS</th>
                                                        <th style="width: 10%; text-align: left;">Kelas</th>
                                                        <th style="width: 15%; text-align: left;">Ruangan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($jadwal_list[$hari] as $item): ?>
                                                        <tr>
                                                            <td style="text-align: left;">
                                                                <span class="badge badge-time d-inline-block m-0">
                                                                    <i class="fa-regular fa-clock me-1.5 text-secondary"></i>
                                                                    <?= date('H:i', strtotime($item['jam_mulai'])) ?> - <?= date('H:i', strtotime($item['jam_selesai'])) ?>
                                                                </span>
                                                            </td>
                                                            <td class="font-monospace text-secondary small fw-bold" style="text-align: left;">
                                                                <?= htmlspecialchars($item['kode_mk']) ?>
                                                            </td>
                                                            <td style="text-align: left;">
                                                                <div class="fw-bold text-dark text-truncate" style="font-size: 14px;" title="<?= htmlspecialchars($item['nama_mk']) ?>">
                                                                    <?= htmlspecialchars($item['nama_mk']) ?>
                                                                </div>
                                                            </td>
                                                            <td style="text-align: left;">
                                                                <span class="badge badge-sks d-inline-block m-0"><?= htmlspecialchars($item['sks']) ?> SKS</span>
                                                            </td>
                                                            <td style="text-align: left;">
                                                                <span class="text-secondary fw-medium small"><?= htmlspecialchars($item['kelas']) ?></span>
                                                            </td>
                                                            <td style="text-align: left;">
                                                                <span class="text-dark small fw-semibold text-truncate d-block" title="<?= htmlspecialchars($item['ruangan']) ?>">
                                                                    <i class="fa-solid fa-location-dot text-danger me-1.5 small"></i>
                                                                    <?= htmlspecialchars($item['ruangan']) ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>

                    <?php endif; ?>

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