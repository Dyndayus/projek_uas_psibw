<?php
require_once '../config/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (function_exists('requireRole')) {
    requireRole(['dosen']);
}

$conn = getDB();
$session_username = $_SESSION['username']; 

// 1. Ambil data dari tabel user & dosen
$dosen_q = "SELECT u.id_user, u.username, u.password, d.nama, d.foto 
            FROM user u 
            LEFT JOIN dosen d ON u.id_ref = d.id_dosen 
            WHERE u.username = ?";

$stmt_d = $conn->prepare($dosen_q);
$stmt_d->bind_param("s", $session_username);
$stmt_d->execute();
$dosen_res = $stmt_d->get_result()->fetch_assoc();
$stmt_d->close();

$id_user      = $dosen_res['id_user'] ?? 0;
$nama_dosen   = !empty($dosen_res['nama']) ? $dosen_res['nama'] : 'Dosen SIAKAD';
$password_db  = $dosen_res['password'] ?? ''; 
$foto_path    = !empty($dosen_res['foto']) ? '../uploads/foto_dosen/' . $dosen_res['foto'] : 'https://via.placeholder.com/150';

$error_msg = "";
$success_msg = "";

// 2. Proses Form Ganti Password (MENGGUNAKAN TEKS BIASA / TANPA HASH)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_baru = $_POST['konfirmasi_baru'] ?? '';

    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_baru)) {
        $error_msg = "Semua field wajib diisi!";
    } 
    // CEK PASSWORD LAMA LANGSUNG MENGGUNAKAN TEKS BIASA
    elseif ($password_lama !== $password_db) {
        $error_msg = "Password lama yang Anda masukkan salah.";
    } 
    elseif ($password_baru !== $konfirmasi_baru) {
        $error_msg = "Konfirmasi password baru tidak cocok.";
    } 
    elseif (strlen($password_baru) < 6) {
        $error_msg = "Password baru minimal harus 6 karakter.";
    } 
    else {
        // SIMPAN LANGSUNG TANPA PASSWORD_HASH()
        $update_q = "UPDATE user SET password = ? WHERE id_user = ?";
        $stmt_u = $conn->prepare($update_q);
        $stmt_u->bind_param("si", $password_baru, $id_user);
        
        if ($stmt_u->execute()) {
            $success_msg = "Password akun berhasil diperbarui!";
            $password_db = $password_baru; // Sinkronisasi teks baru
        } else {
            $error_msg = "Gagal memperbarui password ke database.";
        }
        $stmt_u->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password Dosen - SIAKAD UNRI</title>
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

        .form-hero-header {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            padding: 24px 28px;
            border-bottom: 1px solid #fca5a5;
            border-left: 5px solid #dc2626;
            border-radius: 12px 12px 0 0;
        }

        .header-icon-box {
            width: 44px;
            height: 44px;
            background-color: #ffffff;
            border: 1px solid #fca5a5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc2626;
            font-size: 18px;
            box-shadow: 0 2px 4px rgba(220, 38, 38, 0.06);
        }

        /* CSS Tombol Intip Mata */
        .input-group-text-custom {
            background-color: #fff;
            border-left: none;
            cursor: pointer;
            color: #64748b;
            transition: color 0.2s;
        }
        .input-group-text-custom:hover {
            color: #dc2626;
        }
        .form-control-custom {
            border-right: none;
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
            .form-hero-header { border-radius: 0; }
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
                        <i class="fa-solid fa-file-pen me-2.5"></i> Input Nilai
                    </a>
                </li>
                <li class="nav-item mt-2 border-top pt-2">
                    <a class="nav-link" href="edit_profil.php">
                        <i class="fa-solid fa-user-gear me-2.5"></i> Pengaturan Profil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active-merah" href="ganti_password.php">
                        <i class="fa-solid fa-lock-open me-2.5"></i> Ganti Password
                    </a>
                </li>
            </ul>
        </div>

        <div class="right-layout">
            <div class="content-scrollable px-4 py-4">
                <div class="card shadow-sm border-0 mx-auto mb-4" style="max-width: 600px; border-radius: 12px; overflow: hidden;">
                    
                    <div class="form-hero-header d-flex align-items-center gap-3">
                        <div class="header-icon-box d-none d-sm-flex">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-danger mb-1" style="letter-spacing: -0.3px;">Keamanan Kredensial Akun</h5>
                            <p class="text-secondary mb-0" style="font-size: 12.5px; line-height: 1.4; color: #475569 !important;">
                                Perbarui kunci sandi akun Anda secara berkala demi menjaga integritas data SIAKAD.
                            </p>
                        </div>
                    </div>

                    <div class="p-4 bg-white">
                        
                        <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger d-flex align-items-center small py-2.5 border-0" style="border-radius: 8px;">
                                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= $error_msg ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_msg)): ?>
                            <div class="alert alert-success d-flex align-items-center small py-2.5 border-0" style="border-radius: 8px;">
                                <i class="fa-solid fa-circle-check me-2"></i> <?= $success_msg ?>
                            </div>
                        <?php endif; ?>

                        <form action="ganti_password.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark small">Password Sekarang / Lama</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" id="password_lama" name="password_lama" class="form-control form-control-custom" placeholder="Masukkan password saat ini" required>
                                    <span class="input-group-text input-group-text-custom" onclick="togglePassword('password_lama', 'icon_lama')">
                                        <i class="fa-solid fa-eye" id="icon_lama"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <hr class="text-muted my-3 opacity-25">

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark small">Password Baru</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" id="password_baru" name="password_baru" class="form-control form-control-custom" placeholder="Minimal 6 karakter" required>
                                    <span class="input-group-text input-group-text-custom" onclick="togglePassword('password_baru', 'icon_baru')">
                                        <i class="fa-solid fa-eye" id="icon_baru"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold text-dark small">Konfirmasi Password Baru</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" id="konfirmasi_baru" name="konfirmasi_baru" class="form-control form-control-custom" placeholder="Ulangi password baru Anda" required>
                                    <span class="input-group-text input-group-text-custom" onclick="togglePassword('konfirmasi_baru', 'icon_konfirmasi')">
                                        <i class="fa-solid fa-eye" id="icon_konfirmasi"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger btn-sm fw-bold shadow-sm py-2" style="background-color: #dc2626; border-color: #dc2626;">
                                    <i class="fa-solid fa-shield-halved me-1.5"></i> Perbarui Password Akun
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
                            <span class="me-3" style="font-size: 12px; font-weight: 500;"><i class="fa-solid fa-circle-shield text-success me-1"></i> Keamanan Sesi Terjamin</span>
                            <span class="text-muted" style="font-size: 11px;">v2.2.0</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>