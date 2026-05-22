<?php
require_once '../config/db.php';
requireRole(['dosen']);

$conn = getDB();
$session_email = $_SESSION['username'];

// Ambil data dosen pendukung sidebar
$dosen_q = "SELECT nama, foto FROM dosen WHERE email = ?";
$stmt_d = $conn->prepare($dosen_q);
$stmt_d->bind_param("s", $session_email);
$stmt_d->execute();
$dosen_res = $stmt_d->get_result()->fetch_assoc();
$stmt_d->close();

$nama_dosen = $dosen_res['nama'] ?? 'Dosen SIAKAD';
$foto_path = !empty($dosen_res['foto']) ? '../uploads/foto_dosen/' . $dosen_res['foto'] : 'https://via.placeholder.com/150';

// QUERY UTAMA: Ambil semua daftar mata kuliah dari database
$kuliah_result = $conn->query("SELECT id_kuliah, kode_mk, nama_mk, sks FROM kuliah ORDER BY nama_mk ASC");

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Mata Kuliah Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Menggunakan sistem font native agar serasi, bersih, dan natural */
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

        /* Penanda aktif untuk menu Daftar Mata Kuliah */
        .sidebar .nav-link.active-mk {
            background-color: #f1f5f9;
            color: #0d6efd;
            font-weight: 600;
        }

        /* Styling tabel yang bersih dan rapi */
        .table-custom {
            font-size: 13.5px;
        }

        .table-custom thead th {
            background-color: #f8fafc;
            color: #4a5568;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px;
        }

        .table-custom tbody td {
            padding: 12px;
            vertical-align: middle;
            color: #333333;
            border-bottom: 1px solid #f1f5f9;
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
                    <a href="edit_profil.php" class="btn btn-sm btn-light border rounded mt-2" style="font-size: 11px; padding: 2px 8px;">
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
                        <a class="nav-link active-mk" href="matakuliah.php">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-3 border-bottom">
                    <h5 class="fw-bold text-dark"><i class="fa-solid fa-book text-primary me-2"></i>Mata Kuliah Yang Diampu</h5>
                </div>

                <div class="card border-0 rounded-3 shadow-sm" style="border: 1px solid #e2e8f0 !important;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;" class="text-center">No</th>
                                        <th style="width: 150px;">Kode MK</th>
                                        <th>Nama Mata Kuliah</th>
                                        <th style="width: 120px;" class="text-center">SKS</th>
                                        <th style="width: 180px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    if ($kuliah_result && $kuliah_result->num_rows > 0):
                                        while ($row = $kuliah_result->fetch_assoc()):
                                    ?>
                                            <tr>
                                                <td class="text-center text-muted"><?= $no++ ?></td>
                                                <td class="fw-bold text-secondary"><?= htmlspecialchars($row['kode_mk']) ?></td>
                                                <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_mk']) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($row['sks'] ?? '3') ?> SKS</td>
                                                <td class="text-center">
                                                    <a href="input_nilai.php?id_kuliah=<?= $row['id_kuliah'] ?>" class="btn btn-sm btn-outline-primary rounded fw-medium" style="font-size: 12px; padding: 4px 12px;">
                                                        <i class="fa-solid fa-pen-to-square me-1"></i> Input Nilai
                                                    </a>
                                                </td>
                                            </tr>
                                    <?php
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="5" class="text-center p-4 text-muted">Belum ada data mata kuliah di database.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

</body>

</html>