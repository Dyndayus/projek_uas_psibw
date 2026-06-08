<?php
ob_start();
require_once '../config/db.php';
requireRole(['dosen']);

$conn = getDB();
$session_email = $_SESSION['username'];

// --- PROSES UPDATE FORM VIA AJAX FETCH ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    header('Content-Type: application/json');

    try {
        $id_dosen = $_POST['id_dosen'];
        $no_hp = isset($_POST['no_hp']) ? trim($_POST['no_hp']) : null;
        $alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : null;

        // 1. Ambil data foto lama
        $q_foto = "SELECT foto FROM dosen WHERE id_dosen = ?";
        $st_foto = $conn->prepare($q_foto);
        $st_foto->bind_param("i", $id_dosen);
        $st_foto->execute();
        $res_foto = $st_foto->get_result()->fetch_assoc();
        $foto_lama = $res_foto['foto'] ?? null;
        $st_foto->close();

        $nama_file_baru = $foto_lama;

        // 2. Handle Upload File Foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['foto']['tmp_name'];
            $file_name = $_FILES['foto']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $ekstensi_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($file_ext, $ekstensi_diizinkan)) {
                throw new Exception("Format foto wajib JPG, JPEG, PNG, atau WEBP.");
            }

            if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Ukuran foto maksimal adalah 2MB.");
            }

            $nama_file_baru = 'dosen_' . $id_dosen . '_' . time() . '.' . $file_ext;
            $folder_tujuan = '../uploads/foto_dosen/';

            if (!is_dir($folder_tujuan)) {
                mkdir($folder_tujuan, 0755, true);
            }

            if (move_uploaded_file($file_tmp, $folder_tujuan . $nama_file_baru)) {
                if (!empty($foto_lama) && file_exists($folder_tujuan . $foto_lama)) {
                    unlink($folder_tujuan . $foto_lama);
                }
            } else {
                throw new Exception("Gagal mengunggah file foto ke server.");
            }
        }

        // 3. Update data ke database
        $query_update = "UPDATE dosen SET no_hp = ?, alamat = ?, foto = ? WHERE id_dosen = ?";
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bind_param("sssi", $no_hp, $alamat, $nama_file_baru, $id_dosen);

        if ($stmt_update->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Profil Anda berhasil diperbarui!']);
        } else {
            throw new Exception("Gagal memperbarui data di database.");
        }
        $stmt_update->close();
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    $conn->close();
    exit;
}


$query = "SELECT * FROM dosen WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $session_email);
$stmt->execute();
$result = $stmt->get_result();
$dosen = $result->fetch_assoc();

$stmt->close();
$conn->close();

$nama_dosen = !empty($dosen['nama']) ? $dosen['nama'] : 'Dr. Rina Susanti Pramuda, M.T.';
$foto_path = !empty($dosen['foto']) ? '../uploads/foto_dosen/' . $dosen['foto'] : 'https://via.placeholder.com/150';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Dosen - SIAKAD UNRI</title>
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

        .custom-form-group label {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .custom-form-group .form-control,
        .custom-form-group .form-select {
            font-size: 14px;
            padding: 11px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            color: #0f172a;
            background-color: #fff;
            transition: all 0.2s ease-in-out;
        }

        .custom-form-group .form-control:focus,
        .custom-form-group .form-select:focus {
            border-color: #0284c7 !important;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12) !important;
            outline: 0;
            background-color: #fafafa;
        }

        .locked-fields-bg {
            background: linear-gradient(180deg, #f0f7ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 14px;
            padding: 24px;
            height: 100%;
        }

        .locked-fields-bg label {
            color: #0369a1;
        }

        .locked-fields-bg .form-control:disabled,
        .locked-fields-bg .form-select:disabled {
            background-color: #ffffff;
            color: #334155;
            border-color: #bae6fd;
            font-weight: 500;
            cursor: not-allowed;
            opacity: 0.9;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
        }

        .avatar-uploader-box {
            background: #f8fafc;
            border: 2px dashed #0284c7;
            border-radius: 14px;
            padding: 24px;
        }

        .avatar-preview-wrapper img {
            width: 88px;
            height: 88px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #ffffff;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.2);
        }

        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .btn-custom-file {
            background: #4f46e5;
            color: #ffffff;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        .btn-custom-file:hover {
            background: #4338ca;
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.3);
        }

        .file-upload-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }

        .btn-action-save {
            background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);
            color: #ffffff;
            border: none;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            padding: 12px 28px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 16px rgba(2, 132, 199, 0.3);
        }

        .btn-action-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(2, 132, 199, 0.45);
            color: #ffffff;
        }

        .btn-action-save:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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

            .sidebar .sidebar-profile-img {
                width: 40px !important;
                height: 40px !important;
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
            .avatar-uploader-box {
                flex-direction: column;
                text-align: center;
                padding: 16px;
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
                    <a class="nav-link active" href="edit_profil.php">
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
                            <i class="fa-solid fa-user-gear"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1" style="letter-spacing: -0.5px;">Edit Profil Pengguna</h4>
                            <p class="mb-0 text-white-50" style="font-size: 12.5px;">Kelola berkas pasfoto dan nomor operasional aktif Anda secara mandiri.</p>
                        </div>
                    </div>

                    <div class="p-3 p-sm-4 p-md-5 bg-white">
                        <form id="formEditProfil" enctype="multipart/form-data" autocomplete="off">
                            <input type="hidden" name="id_dosen" value="<?= htmlspecialchars($dosen['id_dosen'] ?? $id_dosen ?? 0) ?>">
                            
                            <div class="row g-4">
                                
                                <div class="col-12 col-md-6 d-flex flex-column gap-4">
                                    
                                    <div class="avatar-uploader-box d-flex flex-column flex-sm-row align-items-center gap-4">
                                        <div class="avatar-preview-wrapper flex-shrink-0">
                                            <img src="<?= $foto_path ?>" id="imgPreview" alt="Pasfoto">
                                        </div>
                                        <div class="text-center text-sm-start custom-form-group flex-grow-1">
                                            <label class="d-block mb-2">Berkas Pasfoto Baru</label>
                                            <div class="file-upload-wrapper">
                                                <button type="button" class="btn-custom-file">
                                                    <i class="fa-solid fa-cloud-arrow-up"></i> Pilih Foto
                                                </button>
                                                <input type="file" name="foto" id="inputFoto" accept="image/*">
                                            </div>
                                            <span id="fileNameDisplay" class="d-block text-primary mt-1 small fw-medium ms-1"></span>
                                            <div class="text-muted mt-2" style="font-size: 11px; line-height: 1.4;">Batas ukuran maksimal 2MB (JPG, PNG, WEBP).</div>
                                        </div>
                                    </div>

                                    <div class="custom-form-group">
                                        <label>Nomor HP / WhatsApp Aktif</label>
                                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($dosen['no_hp'] ?? '') ?>" placeholder="Masukkan nomor handphone aktif">
                                    </div>
                                    
                                    <div class="custom-form-group">
                                        <label>Alamat Rumah / Domisili Tinggal</label>
                                        <textarea name="alamat" class="form-control" rows="4" placeholder="Tuliskan alamat lengkap tempat tinggal Anda saat ini"><?= htmlspecialchars($dosen['alamat'] ?? '') ?></textarea>
                                    </div>

                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="locked-fields-bg">
                                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-info-subtle">
                                            <i class="fa-solid fa-circle-info text-info fs-5"></i>
                                            <span class="fw-bold text-dark small" style="letter-spacing: 0.3px;">DATA UTAMA AKADEMIK</span>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12 col-sm-6 custom-form-group">
                                                <label>NIDN <span class="text-danger fw-bold">*</span></label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($dosen['nidn'] ?? '') ?>" disabled>
                                            </div>
                                            <div class="col-12 col-sm-6 custom-form-group">
                                                <label>Nama Lengkap & Gelar <span class="text-danger fw-bold">*</span></label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($dosen['nama'] ?? '') ?>" disabled>
                                            </div>
                                            <div class="col-12 col-sm-6 custom-form-group">
                                                <label>Jenis Kelamin <span class="text-danger fw-bold">*</span></label>
                                                <select class="form-select" disabled>
                                                    <option value="L" <?= isset($dosen['jenis_kelamin']) && $dosen['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                                    <option value="P" <?= isset($dosen['jenis_kelamin']) && $dosen['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-sm-6 custom-form-group">
                                                <label>Tanggal Lahir <span class="text-danger fw-bold">*</span></label>
                                                <input type="date" class="form-control" value="<?= $dosen['tgl_lahir'] ?? '' ?>" disabled>
                                            </div>
                                            <div class="col-12 col-sm-6 custom-form-group">
                                                <label>Pendidikan Terakhir <span class="text-danger fw-bold">*</span></label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($dosen['pendidikan_terakhir'] ?? '') ?>" disabled>
                                            </div>
                                            <div class="col-12 col-sm-6 custom-form-group">
                                                <label>Jabatan Fungsional <span class="text-danger fw-bold">*</span></label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($dosen['jabatan'] ?? '') ?>" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div id="alertMessage" class="mt-4"></div>

                            <div class="d-flex justify-content-end mt-4 pt-2 border-top border-light">
                                <button type="submit" id="btnSimpan" class="btn btn-action-save w-100 w-sm-auto px-4 py-2.5">
                                    <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Perubahan Profil
                                </button>
                            </div>
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
    <script>
        // Update Nama File & Live Preview Gambar Saat Memilih Foto Baru
        document.getElementById('inputFoto').addEventListener('change', function(e) {
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            const imgPreview = document.getElementById('imgPreview');
            
            if (this.files && this.files[0]) {
                fileNameDisplay.textContent = 'Terpilih: ' + this.files[0].name;
                
                // Live preview instan
                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            } else {
                fileNameDisplay.textContent = '';
            }
        });

        // AJAX Fetch Form Submission
        document.getElementById('formEditProfil').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSimpan');
            const alertMsg = document.getElementById('alertMessage');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Menyimpan...';
            alertMsg.innerHTML = '';

            const formData = new FormData(e.target);
            try {
                const response = await fetch('edit_profil.php', {
                    method: 'POST',
                    body: formData
                });

                const rawText = await response.text();
                const jsonStart = rawText.indexOf('{');
                if (jsonStart === -1) throw new Error("Respon dari server kotor");

                const result = JSON.parse(rawText.substring(jsonStart));
                if (result.status === 'success') {
                    alertMsg.innerHTML = `<div class="alert alert-success border-0 text-center py-2.5 small fw-medium" style="border-radius:8px;"><i class="fa-solid fa-circle-check me-2"></i>${result.message}</div>`;
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alertMsg.innerHTML = `<div class="alert alert-danger border-0 text-center py-2.5 small fw-medium" style="border-radius:8px;"><i class="fa-solid fa-circle-exclamation me-2"></i>${error.message}</div>`;
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i>Simpan Perubahan Profil';
            }
        });
    </script>
</body>

</html>