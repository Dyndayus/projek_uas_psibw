<?php
require_once '../config/db.php';
requireRole(['dosen']);

$conn = getDB();
$session_email = $_SESSION['username'];

// 1. Ambil data dosen untuk sidebar
$dosen_q = "SELECT * FROM dosen WHERE email = ?";
$stmt_d = $conn->prepare($dosen_q);
$stmt_d->bind_param("s", $session_email);
$stmt_d->execute();
$dosen_res = $stmt_d->get_result()->fetch_assoc();
$stmt_d->close();

$id_dosen = $dosen_res['id_dosen'] ?? $dosen_res['id'] ?? 0;
$nama_dosen = $dosen_res['nama'] ?? 'Dosen SIAKAD';
$foto_path = !empty($dosen_res['foto']) ? '../uploads/foto_dosen/' . $dosen_res['foto'] : 'https://via.placeholder.com/150';

// ========================================================================
// LOGIKA BARU: MENGAMBIL MATA KULIAH YANG HANYA DIAMPUI OLEH DOSEN INI
// ========================================================================
$kuliah_q = "SELECT id_kuliah, nama_mk, kode_mk FROM kuliah WHERE id_dosen = '$id_dosen' ORDER BY nama_mk ASC";
$kuliah_result = mysqli_query($conn, $kuliah_q);

// Ambil id_kuliah yang sedang dipilih dari form filter
$id_kuliah_terpilih = "";
if (isset($_REQUEST['id_kuliah'])) {
    $id_kuliah_terpilih = mysqli_real_escape_string($conn, $_REQUEST['id_kuliah']);
}

// ========================================================================
// LOGIKA BARU: AMBIL DAFTAR MAHASISWA YANG HANYA MENGAMBIL MATAKULIAH TERPILIH
// ========================================================================
$mahasiswa_list = [];
if (!empty($id_kuliah_terpilih)) {
    // Mengambil data dari tabel nilai yang sudah dijodohkan oleh admin sebelumnya
    $mhs_q = "SELECT n.id_nilai, n.nilai_angka, n.nilai_huruf, n.tahun_ajaran, m.id_mhs, m.nim, m.nama 
              FROM nilai n
              JOIN mhs m ON n.id_mhs = m.id_mhs
              WHERE n.id_kuliah = '$id_kuliah_terpilih'
              ORDER BY m.nim ASC";
    $mhs_result = mysqli_query($conn, $mhs_q);
    while ($row = mysqli_fetch_assoc($mhs_result)) {
        $mahasiswa_list[] = $row;
    }
}

// ========================================================================
// PROSES SIMPAN NILAI SECARA MASSAL (ARRAY FOREACH LOOP)
// ========================================================================
$notif_status = "";
$notif_pesan = "";

if (isset($_POST['proses_simpan_nilai_massal'])) {
    if (isset($_POST['id_nilai']) && is_array($_POST['id_nilai'])) {
        $array_id_nilai   = $_POST['id_nilai'];
        $array_nilai_angka = $_POST['nilai_angka'];
        $array_thn_ajaran  = $_POST['tahun_ajaran'];

        $sukses_update = true;

        foreach ($array_id_nilai as $index => $id_nilai_raw) {
            $id_nilai_input = intval($id_nilai_raw);
            $n_angka        = floatval($array_nilai_angka[$index]);
            $thn_ajaran     = mysqli_real_escape_string($conn, $array_thn_ajaran[$index]);

            // Hitung nilai huruf otomatis berdasarkan kriteria kamu
            $n_huruf = 'E';
            if ($n_angka >= 85) $n_huruf = 'A';
            elseif ($n_angka >= 75) $n_huruf = 'B+';
            elseif ($n_angka >= 65) $n_huruf = 'B';
            elseif ($n_angka >= 55) $n_huruf = 'C+';
            elseif ($n_angka >= 45) $n_huruf = 'C';

            // Eksekusi UPDATE langsung ke baris data nilai mahasiswa bersangkutan
            $sql_update = "UPDATE nilai 
                           SET nilai_angka = '$n_angka', nilai_huruf = '$n_huruf', tahun_ajaran = '$thn_ajaran' 
                           WHERE id_nilai = '$id_nilai_input'";

            if (!mysqli_query($conn, $sql_update)) {
                $sukses_update = false;
            }
        }

        if ($sukses_update) {
            $notif_status = "success";
            $notif_pesan = "Berhasil: Semua nilai mahasiswa pada kelas ini telah diperbarui!";

            // Refresh data mahasiswa di layar setelah berhasil disimpan
            $mahasiswa_list = [];
            $mhs_q = "SELECT n.id_nilai, n.nilai_angka, n.nilai_huruf, n.tahun_ajaran, m.id_mhs, m.nim, m.nama 
                      FROM nilai n
                      JOIN mhs m ON n.id_mhs = m.id_mhs
                      WHERE n.id_kuliah = '$id_kuliah_terpilih'
                      ORDER BY m.nim ASC";
            $mhs_result = mysqli_query($conn, $mhs_q);
            while ($row = mysqli_fetch_assoc($mhs_result)) {
                $mahasiswa_list[] = $row;
            }
        } else {
            $notif_status = "danger";
            $notif_pesan = "Gagal: Terjadi kesalahan database saat menyimpan beberapa nilai.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai Mahasiswa - SIAKAD UNRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo-navbar {
            height: 38px;
            width: auto;
            object-fit: contain;
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
            width: 100%;
        }

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

        .card-filter-box {
            background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);
            border: none;
            border-radius: 14px;
            box-shadow: 0 10px 25px -5px rgba(30, 58, 138, 0.3);
            position: relative;
            overflow: hidden;
        }

        .card-filter-box::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -15%;
            width: 280px;
            height: 280px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
            pointer-events: none;
        }

        .card-table-box {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }

        .table-theme {
            margin-top: 0 !important;
            /* Mencegah margin ganda atas tabel */
        }

        .table-theme tragedies {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
        }

        .table-theme th {
            color: #ffffff !important;
            background: #1e3a8a !important;
            /* Penyeragaman warna latar header */
            font-weight: 600;
            text-transform: none;
            font-size: 13px;
            border-bottom: none;
            padding: 10px 16px !important;
            /* Memadatkan jarak vertikal header */
        }

        .table-theme tbody tr td {
            padding: 8px 16px !important;
            /* Memadatkan jarak baris antar mahasiswa */
        }

        .table-theme tbody tr:nth-of-type(even) {
            background-color: #f8fafc;
        }

        .table-theme tbody tr:hover {
            background-color: #f1f5f9 !important;
        }

        .form-control-nilai {
            border-radius: 8px;
            font-weight: 700;
            padding: 5px 10px;
            border: 1.5px solid #cbd5e1;
            text-align: center;
            color: #0f172a;
            transition: all 0.2s ease-in-out;
            background-color: #ffffff;
        }

        .form-control-nilai:hover {
            border-color: #94a3b8;
        }

        .form-control-nilai:focus {
            color: #0f172a;
            background-color: #fff;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2);
            transform: scale(1.02);
        }

        .badge-grade {
            font-size: 12.5px;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 8px;
            letter-spacing: 0.3px;
            display: inline-block;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .bg-grade-a {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .bg-grade-b {
            background-color: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .bg-grade-c {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .bg-grade-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .btn-simpan-custom {
            border-radius: 10px;
            background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);
            border: none;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
            transition: all 0.2s ease-in-out;
        }

        .btn-simpan-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.45);
            background: linear-gradient(135deg, #172554 0%, #1e3a8a 100%);
        }

        .btn-simpan-custom:active {
            transform: translateY(0);
        }

        .state-empty-box {
            border-radius: 14px;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.02);
            transition: all 0.3s ease;
        }

        .state-empty-box:hover {
            border-color: #0284c7;
            background: rgba(240, 247, 255, 0.5);
        }

        .icon-circle-box {
            color: #0284c7;
            background-color: #f0f7ff;
            width: 64px;
            height: 64px;
            line-height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 8px 16px rgba(2, 132, 199, 0.1);
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
            }

            .sidebar {
                width: 100% !important;
                height: auto;
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
                    <a class="nav-link" href="jadwal.php">
                        <i class="fa-solid fa-calendar-check me-2.5"></i> Jadwal Mengajar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="input_nilai.php">
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

                <?php if (!empty($notif_pesan)): ?>
                    <div class="alert alert-<?= $notif_status ?> alert-dismissible fade show shadow-sm py-3 px-4 mb-4" role="alert" style="border-radius: 10px;">
                        <i class="fa-solid <?= $notif_status == 'success' ? 'fa-circle-check text-success' : 'fa-circle-exclamation text-danger' ?> me-2 fs-5"></i>
                        <span class="fw-semibold"><?= $notif_pesan ?></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card card-filter-box p-4 mb-4">
                    <div class="row align-items-center position-relative" style="z-index: 2;">
                        <div class="col-lg-7 mb-3 mb-lg-0">
                            <div class="d-flex align-items-center gap-3">
                                <div class="flex-shrink-0" style="width: 48px; height: 48px; background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                    <i class="fa-solid fa-file-pen text-white"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 text-white fw-bold" style="letter-spacing: -0.5px; line-height: 1.2;">Lembar Evaluasi & Input Nilai</h5>
                                    <p class="mb-0 text-white-50" style="font-size: 12.5px; opacity: 0.9;">Pilih salah satu kelas mata kuliah yang Anda ampu untuk mengelola nilai mahasiswa secara kolektif.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <form action="input_nilai.php" method="GET" id="formPilihMK">
                                <label class="form-label mb-1.5 fw-semibold text-white small" style="opacity: 0.95;">Mata Kuliah Anda</label>
                                <select name="id_kuliah" class="form-select fw-bold py-2.5 text-dark shadow-sm" onchange="document.getElementById('formPilihMK').submit();" required style="border-radius: 8px; border: none; background-color: #ffffff;">
                                    <option value="" class="fw-normal">-- Pilih Kelas Mata Kuliah --</option>
                                    <?php
                                    mysqli_data_seek($kuliah_result, 0); // Reset pointer
                                    while ($row = mysqli_fetch_assoc($kuliah_result)):
                                        $selected = ($row['id_kuliah'] == $id_kuliah_terpilih) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $row['id_kuliah'] ?>" <?= $selected ?>>
                                            <?= htmlspecialchars($row['kode_mk'] . ' - ' . $row['nama_mk']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                <?php if (!empty($id_kuliah_terpilih)): ?>
                    <form action="input_nilai.php?id_kuliah=<?= $id_kuliah_terpilih ?>" method="POST">
                        <div class="card card-table-box">
                            <div class="bg-light px-4 py-2 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <h6 class="fw-bold text-primary mb-0 d-flex align-items-center" style="color: #1e3a8a !important; font-size: 13.5px;">
                                    <i class="fa-solid fa-users me-2 text-primary" style="color: #1e3a8a !important;"></i>Daftar Peserta Kelas & Komponen Nilai
                                </h6>
                                <span class="badge px-3 py-1.5 rounded-pill fw-bold" style="font-size: 11px; background-color: #1e3a8a; color: #ffffff; box-shadow: 0 2px 6px rgba(30,58,138,0.25)">
                                    Total: <?= count($mahasiswa_list) ?> Mahasiswa
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover table-theme align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 6%;">No</th>
                                            <th style="width: 15%;">NIM</th>
                                            <th style="width: 35%;">Nama Mahasiswa</th>
                                            <th class="text-center" style="width: 18%;">Nilai Angka (0-100)</th>
                                            <th class="text-center" style="width: 11%;">Grade</th>
                                            <th class="text-center" style="width: 15%;">Tahun Ajaran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($mahasiswa_list) > 0): ?>
                                            <?php foreach ($mahasiswa_list as $index => $mhs): ?>
                                                <tr>
                                                    <td class="text-center fw-bold text-muted"><?= $index + 1 ?></td>
                                                    <td class="fw-bold text-dark" style="letter-spacing: 0.3px;"><?= htmlspecialchars($mhs['nim']) ?></td>
                                                    <td class="fw-semibold text-secondary"><?= htmlspecialchars($mhs['nama']) ?></td>

                                                    <td class="text-center">
                                                        <input type="hidden" name="id_nilai[]" value="<?= $mhs['id_nilai'] ?>">
                                                        <input type="number" name="nilai_angka[]" class="form-control form-control-nilai mx-auto" min="0" max="100" step="0.01" value="<?= floatval($mhs['nilai_angka']) ?>" placeholder="0.00" style="max-width: 110px;" required>
                                                    </td>

                                                    <td class="text-center">
                                                        <?php
                                                        $grade = $mhs['nilai_huruf'] ?? '-';
                                                        $badge_class = 'bg-secondary text-white';

                                                        if ($grade == 'A') $badge_class = 'bg-grade-a';
                                                        elseif (in_array($grade, ['B+', 'B'])) $badge_class = 'bg-grade-b';
                                                        elseif (in_array($grade, ['C+', 'C'])) $badge_class = 'bg-grade-c';
                                                        elseif (in_array($grade, ['D', 'E'])) $badge_class = 'bg-grade-danger';
                                                        ?>
                                                        <span class="badge-grade <?= $badge_class ?>"><?= htmlspecialchars($grade) ?></span>
                                                    </td>

                                                    <td>
                                                        <input type="text" name="tahun_ajaran[]" class="form-control form-control-nilai text-center mx-auto" value="<?= htmlspecialchars($mhs['tahun_ajaran'] ?? '2025/2026') ?>" placeholder="Contoh: 2025/2026" style="max-width: 130px; font-weight:600;" required>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="fa-solid fa-folder-open fs-2 d-block mb-3 text-secondary" style="opacity: 0.4;"></i>
                                                    <span class="fw-bold d-block text-dark mb-1">Belum Ada Mahasiswa Di Kelas Ini</span>
                                                    Silakan koordinasi dengan Admin untuk mendaftarkan mahasiswa ke mata kuliah Anda.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if (count($mahasiswa_list) > 0): ?>
                            <div class="d-flex justify-content-end mt-4 mb-2">
                                <button type="submit" name="proses_simpan_nilai_massal" class="btn btn-success btn-simpan-custom fw-bold px-4 py-2.5 text-white">
                                    <i class="fa-solid fa-cloud-arrow-up me-2"></i>Simpan Seluruh Nilai Kelas
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <div class="text-center py-5 px-4 state-empty-box mt-2">
                        <div class="icon-circle-box">
                            <i class="fa-solid fa-arrow-pointer fs-3"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Mata Kuliah Belum Dipilih</h6>
                        <p class="text-muted small mb-0 mx-auto" style="max-width: 480px;">
                            Silakan pilih salah satu kelas mata kuliah di sudut kanan atas panel filter untuk memuat lembar penilaian mahasiswa.
                        </p>
                    </div>
                <?php endif; ?>

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