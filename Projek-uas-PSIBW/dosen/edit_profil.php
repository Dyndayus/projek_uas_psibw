<?php
require_once '../config/db.php';
requireRole(['dosen']);

$conn = getDB();
$session_email = $_SESSION['username'];

$query = "SELECT * FROM dosen WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $session_email);
$stmt->execute();
$result = $stmt->get_result();
$dosen = $result->fetch_assoc();

$stmt->close();
$conn->close();

$nama_dosen = $dosen['nama'] ?? 'Dosen SIAKAD';
$foto_path = !empty($dosen['foto']) ? '../uploads/foto_dosen/' . $dosen['foto'] : 'https://via.placeholder.com/150';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Sinkronisasi sistem font native agar natural & layaknya web asli */
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #333333;
            background-color: #f4f6f9;
            overflow-x: hidden;
        }

        .logo-navbar {
            height: 35px;
            /* Mengatur tinggi logo agar pas dengan teks navbar */
            width: auto;
            /* Menjaga proporsi lebar logo agar tidak gepeng */
            object-fit: contain;
        }

        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
        }

        .sidebar .nav-link {
            color: #4a5568;
            font-size: 13.5px;
            font-weight: 500;
            padding: 10px 18px;
            border-radius: 6px;
            margin: 2px 12px;
        }

        .sidebar .nav-link:hover {
            background-color: #f1f5f9;
            color: #0d6efd;
        }

        /* Penanda menu aktif yang konsisten */
        .sidebar .nav-link-profil {
            background-color: #f1f5f9;
            color: #0d6efd;
            font-weight: 600;
        }

        /* Form styling yang natural */
        .form-label {
            font-size: 13px;
            color: #4a5568;
            margin-bottom: 6px;
        }

        .form-control,
        .form-select {
            font-size: 13.5px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            color: #333333;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: none;
        }

        /* Gaya khusus untuk input readonly / disabled dari pusat */
        .form-control:disabled,
        .form-select:disabled {
            background-color: #f8fafc;
            color: #64748b;
            border-color: #e2e8f0;
            cursor: not-allowed;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard_dosen.php">
                <img src="https://unri.ac.id/wp-content/uploads/2016/05/cropped-LogoUR-1-1.png" alt="Logo UNRI" class="logo-navbar me-2">
                Universitas Riau
            </a>
            <div class="ms-auto">
                <a class="btn btn-light btn-sm text-danger fw-bold rounded px-3" href="../logout.php" style="font-size: 12px;">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="text-center p-4 border-bottom mb-3" style="background-color: #fafafa;">
                    <img src="<?= $foto_path ?>" class="rounded-circle img-thumbnail mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                    <div class="fw-bold text-dark text-truncate small px-1"><?= htmlspecialchars($nama_dosen) ?></div>
                    <a href="edit_profil.php" class="btn btn-sm btn-light border rounded mt-2 nav-link-profil" style="font-size: 11px; padding: 2px 8px;">
                        <i class="fa-solid fa-user-gear me-1"></i> Lihat Profil
                    </a>
                </div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_dosen.php">
                            <i class="fa-solid fa-house me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="matakuliah.php">
                            <i class="fa-solid fa-book me-2"></i> Daftar Mata Kuliah
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="jadwal.php">
                            <i class="fa-solid fa-calendar-days me-2"></i> Jadwal Mengajar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="input_nilai.php">
                            <i class="fa-solid fa-pen-to-square me-2"></i> Input Nilai
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="ganti_password.php">
                            <i class="fa-solid fa-key me-2"></i> Ganti Password
                        </a>
                    </li>
                </ul>
            </div>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="card border-0 rounded-3 mx-auto mt-2" style="max-width: 800px; border: 1px solid #e2e8f0 !important;">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-user-gear me-2"></i>Pengaturan Profil Pengguna</h6>
                        <p class="text-muted mb-4" style="font-size: 12.5px;">Kolom dengan tanda (<span class="text-danger">*</span>) merupakan data kepegawaian resmi dan bersifat terkunci.</p>

                        <form id="formEditProfil" enctype="multipart/form-data">
                            <input type="hidden" name="id_dosen" value="<?= $dosen['id_dosen'] ?>">
                            <input type="hidden" name="status" value="<?= htmlspecialchars($dosen['status']) ?>">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($dosen['email']) ?>">

                            <input type="hidden" name="nidn" value="<?= htmlspecialchars($dosen['nidn']) ?>">
                            <input type="hidden" name="nama" value="<?= htmlspecialchars($dosen['nama']) ?>">
                            <input type="hidden" name="tgl_lahir" value="<?= $dosen['tgl_lahir'] ?>">
                            <input type="hidden" name="jenis_kelamin" value="<?= $dosen['jenis_kelamin'] ?>">
                            <input type="hidden" name="pendidikan_terakhir" value="<?= htmlspecialchars($dosen['pendidikan_terakhir'] ?? '') ?>">
                            <input type="hidden" name="jabatan" value="<?= htmlspecialchars($dosen['jabatan'] ?? '') ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">NIDN <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($dosen['nidn']) ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($dosen['nama']) ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Tanggal Lahir <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" value="<?= $dosen['tgl_lahir'] ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <select class="form-select" disabled>
                                        <option value="L" <?= $dosen['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="P" <?= $dosen['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nomor HP / WhatsApp</label>
                                    <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($dosen['no_hp'] ?? '') ?>" placeholder="Contoh: 081234567890">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Pendidikan Terakhir <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($dosen['pendidikan_terakhir'] ?? '') ?>" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Jabatan Struktural <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($dosen['jabatan'] ?? '') ?>" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Alamat Rumah / Tempat Tinggal</label>
                                    <textarea name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat domisili lengkap"><?= htmlspecialchars($dosen['alamat'] ?? '') ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Pembaruan Berkas Foto Profil</label>
                                    <input type="file" name="foto" class="form-control" accept="image/*">
                                </div>
                            </div>

                            <button type="submit" id="btnSimpan" class="btn btn-primary w-100 fw-bold py-2 mt-4 rounded" style="font-size: 13px;">Simpan Perubahan Data</button>
                        </form>
                        <div id="alertMessage" class="mt-3"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.getElementById('formEditProfil').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSimpan');
            const alertMsg = document.getElementById('alertMessage');
            btn.disabled = true;
            btn.innerHTML = 'Menyimpan...';
            alertMsg.innerHTML = '';
            const formData = new FormData(e.target);
            try {
                const response = await fetch('../api/dosen/update.php', {
                    method: 'POST',
                    body: formData
                });
                const rawText = await response.text();
                const jsonStart = rawText.indexOf('{');
                if (jsonStart === -1) throw new Error("Respon dari server kotor");
                const result = JSON.parse(rawText.substring(jsonStart));
                if (result.status === 'success') {
                    alertMsg.innerHTML = `<div class="alert alert-success border-0 text-center py-2 small">${result.message}</div>`;
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alertMsg.innerHTML = `<div class="alert alert-danger border-0 text-center py-2 small">${error.message}</div>`;
                btn.disabled = false;
                btn.innerHTML = 'Simpan Perubahan Data';
            }
        });
    </script>
</body>

</html>