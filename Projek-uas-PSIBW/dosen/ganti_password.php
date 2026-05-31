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
$nama_dosen   = !empty($dosen_res['nama']) ? $dosen_res['nama'] : 'Dr. Rina Susanti Pramuda, M.T.';
$password_db  = $dosen_res['password'] ?? '';
$foto_path    = !empty($dosen_res['foto']) ? '../uploads/foto_dosen/' . $dosen_res['foto'] : 'https://via.placeholder.com/150';

$error_msg = "";
$success_msg = "";

// 2. Proses Form Ganti Password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_baru = $_POST['konfirmasi_baru'] ?? '';

    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_baru)) {
        $error_msg = "Semua field wajib diisi!";
    } elseif ($password_lama !== $password_db) {
        $error_msg = "Password lama yang Anda masukkan salah.";
    } elseif ($password_baru !== $konfirmasi_baru) {
        $error_msg = "Konfirmasi password baru tidak cocok.";
    } elseif (strlen($password_baru) < 6) {
        $error_msg = "Password baru minimal harus 6 karakter.";
    } else {
        $update_q = "UPDATE user SET password = ? WHERE id_user = ?";
        $stmt_u = $conn->prepare($update_q);
        $stmt_u->bind_param("si", $password_baru, $id_user);

        if ($stmt_u->execute()) {
            $success_msg = "Password akun berhasil diperbarui!";
            $password_db = $password_baru;
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

        /* NAVBAR PREMIUM */
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

        .sidebar .text-dosen-nama {
            color: #ffffff !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            letter-spacing: -0.1px;
        }

        .sidebar .nav-link {
            color: #bfdbfe !important;
            font-size: 13.5px;
            font-weight: 600;
            padding: 12px 20px;
            margin: 3px 0;
            position: relative;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

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
        /* UPDATE: PASSWORD FORM SIZE AND POSITION ADJUSTMENT       */
        /* ======================================================== */
        .card-custom {
            max-width: 480px;
            /* Diperkecil dari 620px agar lebih compact */
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05);
        }

        /* Colorful Gradient Header Block */
        .colorful-header-block {
            background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);
            padding: 22px 26px;
            /* Padding disesuaikan lebih pas */
            color: #ffffff;
            border-bottom: 4px solid #38bdf8;
        }

        .header-badge-icon {
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #ffffff;
        }

        .custom-form-group label {
            font-size: 11.5px;
            font-weight: 700;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }

        /* Input Control Group */
        .form-control-colored {
            border-radius: 0 8px 8px 0;
            font-size: 13.5px;
            padding: 9.5px 14px;
            border: 1.5px solid #cbd5e1;
            border-left: none;
            color: #0f172a;
            transition: all 0.2s ease-in-out;
        }

        .form-control-colored:focus {
            border-color: #0284c7 !important;
            box-shadow: none !important;
            background-color: #fafafa;
        }

        .input-group-text-colored {
            border-radius: 8px 0 0 8px;
            border: 1.5px solid #cbd5e1;
            border-right: none;
            background-color: #f8fafc;
            color: #64748b;
            transition: all 0.2s;
        }

        /* Efek fokus komponen input */
        .input-group:focus-within .input-group-text-colored {
            border-color: #0284c7;
            color: #0284c7;
            background-color: #f0f9ff;
        }

        .input-group:focus-within .form-control-colored {
            border-color: #0284c7;
        }

        .input-group:focus-within .input-group-text-eye {
            border-color: #0284c7;
        }

        .input-group-text-eye {
            border-radius: 8px;
            margin-left: -10px;
            z-index: 5;
            border: 1.5px solid #cbd5e1;
            border-left: none;
            cursor: pointer;
            background-color: #ffffff;
            color: #64748b;
            transition: all 0.15s;
        }

        .input-group-text-eye:hover {
            color: #0284c7;
            background-color: #f0f9ff;
        }

        /* Pembatas Elemen */
        .colored-divider {
            border-top: 2px dotted #bae6fd;
            margin: 24px 0;
        }

        /* Tombol Aksi */
        .btn-action-save {
            background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);
            color: #ffffff;
            border: none;
            font-weight: 600;
            font-size: 13.5px;
            border-radius: 8px;
            padding: 10.5px 22px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
        }

        .btn-action-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.35);
            color: #ffffff;
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
            }

            .right-layout {
                height: auto;
                overflow: visible;
            }

            .content-scrollable {
                overflow-y: visible;
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
                <div class="text-dosen-nama text-truncate small px-2"><?= htmlspecialchars($nama_dosen) ?></div>
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
            <div class="content-scrollable px-3 px-sm-4 pt-4 pb-5">
                <div class="card card-custom border-0 mx-auto mb-4">

                    <div class="colorful-header-block d-flex align-items-center gap-3">
                        <div class="header-badge-icon flex-shrink-0">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-1" style="letter-spacing: -0.1px; font-size: 15.5px;">Ubah Password Akun</h5>
                            <p class="text-white-50 mb-0" style="font-size: 12px; line-height: 1.4; opacity: 0.9;">
                                Demi keamanan data akademik, gunakan kombinasi password yang kuat.
                            </p>
                        </div>
                    </div>

                    <div class="p-4 bg-white">

                        <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2 small py-2.5 px-3 border-0 mb-3" style="border-radius: 8px; background-color: #fef2f2; color: #991b1b;">
                                <i class="fa-solid fa-triangle-exclamation fs-6"></i>
                                <div><strong>Gagal:</strong> <?= $error_msg ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_msg)): ?>
                            <div class="alert alert-success d-flex align-items-center gap-2 small py-2.5 px-3 border-0 mb-3" style="border-radius: 8px; background-color: #f0fdf4; color: #166534;">
                                <i class="fa-solid fa-circle-check fs-6"></i>
                                <div><strong>Berhasil:</strong> <?= $success_msg ?></div>
                            </div>
                        <?php endif; ?>

                        <form action="ganti_password.php" method="POST" autocomplete="off">

                            <div class="mb-3 custom-form-group">
                                <label>Password Saat Ini</label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-colored border-end-0 px-3"><i class="fa-solid fa-lock" style="font-size: 12px;"></i></span>
                                    <input type="password" id="password_lama" name="password_lama" class="form-control form-control-colored" placeholder="Masukkan password lama Anda" required>
                                    <span class="input-group-text input-group-text-eye" onclick="togglePasswordCustom('password_lama', 'icon_lama')">
                                        <i class="fa-solid fa-eye" id="icon_lama" style="font-size: 12px;"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="colored-divider"></div>

                            <div class="mb-3 custom-form-group">
                                <label>Password Baru</label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-colored border-end-0 px-3"><i class="fa-solid fa-key" style="font-size: 12px;"></i></span>
                                    <input type="password" id="password_baru" name="password_baru" class="form-control form-control-colored" placeholder="Gunakan minimal 6 karakter" required>
                                    <span class="input-group-text input-group-text-eye" onclick="togglePasswordCustom('password_baru', 'icon_baru')">
                                        <i class="fa-solid fa-eye" id="icon_baru" style="font-size: 12px;"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-3 custom-form-group">
                                <label>Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-colored border-end-0 px-3"><i class="fa-solid fa-circle-check" style="font-size: 12px;"></i></span>
                                    <input type="password" id="konfirmasi_baru" name="konfirmasi_baru" class="form-control form-control-colored" placeholder="Ulangi password baru secara presisi" required>
                                    <span class="input-group-text input-group-text-eye" onclick="togglePasswordCustom('konfirmasi_baru', 'icon_konfirmasi')">
                                        <i class="fa-solid fa-eye" id="icon_konfirmasi" style="font-size: 12px;"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-action-save w-100">
                                    <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Perubahan
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
        function togglePasswordCustom(inputId, iconId) {
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