<?php
// Ambil status output buffering untuk mencegah output tidak sengaja sebelum JSON dilempar
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

// --- AMBIL DATA PROFIL UNTUK SIDEBAR DAN FORM ---
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

        /* NAVBAR PREMIUM (SINKRON 100% DASHBOARD) */
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

        /* SIDEBAR MODERN (SINKRON 100% DASHBOARD) */
        .sidebar {
            width: 260px;
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            height: 100%;
            flex-shrink: 0;
        }

        .sidebar .nav-link {
            color: #475569;
            font-size: 13.5px;
            font-weight: 600;
            padding: 12px 20px;
            margin: 3px 0;
            position: relative;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-item-normal:hover {
            color: #2563eb !important;
            background-color: #f8fafc !important;
        }

        .sidebar .nav-link.active {
            background-color: #eff6ff;
            color: #2563eb;
            font-weight: 700;
        }

        .sidebar .nav-link.active::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
            background-color: #2563eb;
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
        }

        .sidebar .nav-link-danger-custom {
            color: #64748b;
            background-color: transparent;
            transition: all 0.2s ease-in-out;
        }

        .sidebar .nav-link-danger-custom:hover {
            background-color: #fff1f2 !important;
            color: #e11d48 !important;
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

        /* CARD EDIT PROFILE STYLING */
        .card-profile-container {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .form-hero-header {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            padding: 24px 28px;
            border-bottom: 1px solid #bfdbfe;
            border-left: 5px solid #2563eb;
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
            color: #2563eb;
            font-size: 18px;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.06);
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control,
        .form-select {
            font-size: 13.5px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            color: #1e293b;
            background-color: #ffffff;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .form-control:disabled,
        .form-select:disabled {
            background-color: #f8fafc;
            color: #64748b;
            border-color: #e2e8f0;
            font-weight: 500;
            cursor: not-allowed;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: #ffffff;
            border: none;
            font-weight: 600;
            font-size: 14px;
            border-radius: 6px;
            padding: 12px 24px;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.25);
        }

        .btn-primary-custom:disabled {
            background: #cbd5e1;
            color: #94a3b8;
            box-shadow: none;
            cursor: not-allowed;
            transform: none;
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
                    <a class="nav-link" href="input_nilai.php">
                        <i class="fa-solid fa-file-pen me-2.5"></i> Input Nilai Mhs
                    </a>
                </li>
                
                <li class="nav-item mt-2 border-top pt-2">
                    <a class="nav-link active" href="edit_profil.php">
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

                <div class="card-profile-container mx-auto" style="max-width: 850px;">
                    
                    <div class="form-hero-header d-flex align-items-center gap-3">
                        <div class="header-icon-box d-none d-sm-flex">
                            <i class="fa-solid fa-user-gear"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-primary mb-1" style="letter-spacing: -0.3px; color: #1e3a8a !important;">Pengaturan Profil Pengguna</h5>
                            <p class="text-secondary mb-0" style="font-size: 12.5px; line-height: 1.4; color: #475569 !important;">
                                Kolom dengan tanda (<span class="text-danger fw-bold">*</span>) merupakan data kepegawaian resmi yang dikunci oleh pusat akademik.
                            </p>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-white">
                        <form id="formEditProfil" enctype="multipart/form-data">
                            <input type="hidden" name="id_dosen" value="<?= htmlspecialchars($dosen['id_dosen'] ?? $id_dosen ?? 0) ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">NIDN <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($dosen['nidn'] ?? '') ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($dosen['nama'] ?? '') ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" value="<?= $dosen['tgl_lahir'] ?? '' ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <select class="form-select" disabled>
                                        <option value="L" <?= isset($dosen['jenis_kelamin']) && $dosen['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="P" <?= isset($dosen['jenis_kelamin']) && $dosen['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nomor HP / WhatsApp</label>
                                    <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($dosen['no_hp'] ?? '') ?>" placeholder="Contoh: 081234567890">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pendidikan Terakhir <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($dosen['pendidikan_terakhir'] ?? '') ?>" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Jabatan Struktural <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($dosen['jabatan'] ?? '') ?>" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Alamat Rumah / Tempat Tinggal</label>
                                    <textarea name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat domisili lengkap"><?= htmlspecialchars($dosen['alamat'] ?? '') ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Pembaruan Berkas Foto Profil</label>
                                    <input type="file" name="foto" class="form-control" accept="image/*">
                                </div>
                            </div>

                            <button type="submit" id="btnSimpan" class="btn btn-primary-custom w-100 py-2.5 mt-4">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Perubahan Data
                            </button>
                        </form>
                        <div id="alertMessage" class="mt-3"></div>
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
                    alertMsg.innerHTML = `<div class="alert alert-success border-0 text-center py-2.5 small fw-medium"><i class="fa-solid fa-circle-check me-2"></i>${result.message}</div>`;
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alertMsg.innerHTML = `<div class="alert alert-danger border-0 text-center py-2.5 small fw-medium"><i class="fa-solid fa-circle-exclamation me-2"></i>${error.message}</div>`;
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i>Simpan Perubahan Data';
            }
        });
    </script>
</body>
</html>