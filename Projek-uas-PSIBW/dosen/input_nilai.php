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

// 2. KEMBALIKAN KE ALL MK: Mengambil SELURUH mata kuliah di kampus agar tidak ada error kosong
$kuliah_q = "SELECT id_kuliah, nama_mk, kode_mk FROM kuliah ORDER BY nama_mk ASC";
$kuliah_result = mysqli_query($conn, $kuliah_q);

// 3. Ambil id_kuliah yang sedang dipilih dari form
$id_kuliah_terpilih = "";
if (isset($_REQUEST['id_kuliah'])) {
    // Kita biarkan tipenya fleksibel sesuai input database kamu
    $id_kuliah_terpilih = mysqli_real_escape_string($conn, $_REQUEST['id_kuliah']);
}

// 4. Ambil daftar seluruh mahasiswa untuk diinput nilainya
$mahasiswa_list = [];
$mhs_q = "SELECT id_mhs, nim, nama FROM mhs ORDER BY nim ASC";
$mhs_result = mysqli_query($conn, $mhs_q);
while ($row = mysqli_fetch_assoc($mhs_result)) {
    $mahasiswa_list[] = $row;
}

// 5. PROSES SIMPAN NILAI (LOGIKA FIX SESTEI DI SCREENSHOT DATABASE)
$notif_status = "";
$notif_pesan = "";

if (isset($_POST['proses_simpan_nilai'])) {
    $id_mhs_input = isset($_POST['id_mhs']) ? intval($_POST['id_mhs']) : 0;
    $id_kuliah_proses = isset($_POST['id_kuliah']) ? mysqli_real_escape_string($conn, $_POST['id_kuliah']) : $id_kuliah_terpilih;
    $nilai_angka = isset($_POST['nilai_angka']) ? floatval($_POST['nilai_angka']) : 0;
    $tahun_ajaran = isset($_POST['tahun_ajaran']) ? mysqli_real_escape_string($conn, $_POST['tahun_ajaran']) : '';

    // Konversi nilai angka ke huruf otomatis
    $nilai_huruf = 'E';
    if ($nilai_angka >= 85) $nilai_huruf = 'A';
    elseif ($nilai_angka >= 75) $nilai_huruf = 'B+';
    elseif ($nilai_angka >= 65) $nilai_huruf = 'B';
    elseif ($nilai_angka >= 55) $nilai_huruf = 'C+';
    elseif ($nilai_angka >= 45) $nilai_huruf = 'C';

    if (empty($id_kuliah_proses) || $id_kuliah_proses == "0") {
        $notif_status = "danger";
        $notif_pesan = "Gagal: Anda belum memilih mata kuliah.";
    } elseif ($id_mhs_input <= 0) {
        $notif_status = "danger";
        $notif_pesan = "Gagal: Anda belum memilih mahasiswa.";
    } else {
        // Cek data lama di tabel nilai
        $cek_query = "SELECT id_nilai FROM nilai WHERE id_mhs = '$id_mhs_input' AND id_kuliah = '$id_kuliah_proses'";
        $eksekusi_cek = mysqli_query($conn, $cek_query);

        if (mysqli_num_rows($eksekusi_cek) > 0) {
            // JIKA SUDAH ADA, UPDATE
            $sql_aksi = "UPDATE nilai 
                         SET nilai_angka = '$nilai_angka', nilai_huruf = '$nilai_huruf', tahun_ajaran = '$tahun_ajaran' 
                         WHERE id_mhs = '$id_mhs_input' AND id_kuliah = '$id_kuliah_proses'";
        } else {
            // JIKA BELUM ADA, INSERT NEW
            $sql_aksi = "INSERT INTO nilai (id_mhs, id_kuliah, nilai_angka, nilai_huruf, tahun_ajaran) 
                         VALUES ('$id_mhs_input', '$id_kuliah_proses', '$nilai_angka', '$nilai_huruf', '$tahun_ajaran')";
        }

        if (mysqli_query($conn, $sql_aksi)) {
            $notif_status = "success";
            $notif_pesan = "Nilai berhasil disimpan!";
            $id_kuliah_terpilih = $id_kuliah_proses;
        } else {
            // Jika crash, muntahkan error MySQL asli
            die("<div style='color:red; padding:20px; background:#ffebee; border:2px solid red; font-family:sans-serif;'>
                    <h3>🚨 DATABASE ERROR ON INSERT/UPDATE!</h3>
                    <p><b>Pesan Error:</b> " . mysqli_error($conn) . "</p>
                    <p><b>Kueri SQL:</b> $sql_aksi</p>
                    <a href='input_nilai.php'>Kembali</a>
                 </div>");
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
            color: #2d3748;
            background-color: #f8fafc;
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

        /* ======================================================== */
        /* SIDEBAR: ROYAL BLUE CAMPUS THEME (SINKRON & SERAGAM)     */
        /* ======================================================== */
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

        .card-nilai-container {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
        }

        .form-hero-header {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            padding: 24px 28px;
            border-left: 5px solid #2563eb;
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: #ffffff;
            border: none;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 6px;
        }

        /* FOOTER (SINKRON 100% DASHBOARD) */
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
                <img src="https://unri.ac.id/wp-content/uploads/2016/05/cropped-LogoUR-1-1.png" alt="Logo UNRI" class="logo-navbar me-2">
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
                <div class="card-nilai-container mx-auto" style="max-width: 720px; margin-top: 15px;">
                    <div class="form-hero-header d-flex align-items-center gap-3">
                        <div>
                            <h5 class="fw-bold text-primary mb-1">Form Pengisian Nilai Akademik</h5>
                            <p class="text-secondary mb-0" style="font-size: 12.5px;">Mode Kompatibilitas Penuh - Menampilkan Seluruh Mata Kuliah.</p>
                        </div>
                    </div>

                    <div class="p-4 bg-white">

                        <?php if (!empty($notif_pesan)): ?>
                            <div class="alert alert-<?= $notif_status ?> alert-dismissible fade show text-center py-2.5 small fw-medium mb-3">
                                <?= $notif_pesan ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="input_nilai.php" method="POST" id="mainFormNilai">

                            <div class="mb-4 pb-3 border-bottom">
                                <label class="form-label text-primary fw-bold">Langkah 1: Pilih Mata Kuliah</label>
                                <select name="id_kuliah" class="form-select" onchange="this.form.submit()" required>
                                    <option value="">-- Pilih Mata Kuliah Kampus --</option>
                                    <?php
                                    while ($row = mysqli_fetch_assoc($kuliah_result)):
                                        // MODIFIKASI KRUSIAL: Kita pakai KODE_MK atau ID_KULIAH sesuai kebutuhan pencarian nilai kamu
                                        // Berdasarkan database kamu, kolom id_kuliah di tabel nilai diisi oleh value ini:
                                        $val_kuliah = $row['id_kuliah'];
                                        $selected = ($val_kuliah == $id_kuliah_terpilih) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $val_kuliah ?>" <?= $selected ?>>
                                            <?= htmlspecialchars($row['kode_mk'] . ' - ' . $row['nama_mk']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-primary fw-bold">Langkah 2: Pilih Mahasiswa</label>
                                <select name="id_mhs" class="form-select" required>
                                    <option value="">-- Pilih Mahasiswa Terdaftar --</option>
                                    <?php foreach ($mahasiswa_list as $mhs): ?>
                                        <option value="<?= $mhs['id_mhs'] ?>"><?= htmlspecialchars($mhs['nim'] . ' - ' . $mhs['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nilai Angka (0 - 100)</label>
                                    <input type="number" name="nilai_angka" class="form-control" min="0" max="100" placeholder="Contoh: 85.50" step="0.01" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tahun Ajaran</label>
                                    <input type="text" name="tahun_ajaran" class="form-control" placeholder="Contoh: 2025/2026" required>
                                </div>
                            </div>

                            <button type="submit" name="proses_simpan_nilai" class="btn btn-primary-custom w-100 py-2.5 mt-2">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Komponen Nilai
                            </button>
                        </form>

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