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

// Ambil data Dropdown Mahasiswa & Kuliah
$mhs_result = $conn->query("SELECT id_mhs, nim, nama FROM mhs ORDER BY nim ASC");
$kuliah_result = $conn->query("SELECT id_kuliah, nama_mk, kode_mk FROM kuliah ORDER BY nama_mk ASC");

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai Mahasiswa</title>
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

        .sidebar .nav-link.active-nilai {
            background-color: #f1f5f9;
            color: #0d6efd;
            font-weight: 600;
        }

        /* Form styling agar lebih membumi dan tidak kaku */
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
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: none;
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
                        <a class="nav-link active-nilai" href="input_nilai.php">
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
                <div class="card border-0 rounded-3 mx-auto mt-2" style="max-width: 650px; border: 1px solid #e2e8f0 !important;">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-4"><i class="fa-solid fa-file-invoice border-0 me-2"></i>Form Pengisian Nilai Akademik</h6>

                        <form id="formInputNilai">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mata Kuliah</label>
                                <select name="id_kuliah" class="form-select" required>
                                    <option value="">-- Pilih Mata Kuliah --</option>
                                    <?php while ($row = $kuliah_result->fetch_assoc()): ?>
                                        <option value="<?= $row['id_kuliah'] ?>"><?= htmlspecialchars($row['kode_mk'] . ' - ' . $row['nama_mk']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama / NIM Mahasiswa</label>
                                <select name="id_mhs" class="form-select" required>
                                    <option value="">-- Pilih Mahasiswa --</option>
                                    <?php while ($row = $mhs_result->fetch_assoc()): ?>
                                        <option value="<?= $row['id_mhs'] ?>"><?= htmlspecialchars($row['nim'] . ' - ' . $row['nama']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Nilai Angka (0 - 100)</label>
                                    <input type="number" name="nilai_angka" class="form-control" min="0" max="100" placeholder="Contoh: 85.50" step="0.01" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Tahun Ajaran</label>
                                    <input type="text" name="tahun_ajaran" class="form-control" placeholder="Contoh: 2025/2026" required>
                                </div>
                            </div>

                            <button type="submit" id="btnSimpan" class="btn btn-primary w-100 fw-bold py-2 mt-3 rounded" style="font-size: 13px;">Simpan Komponen Nilai</button>
                        </form>
                        <div id="alertMessage" class="mt-3"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.getElementById('formInputNilai').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSimpan');
            const alertMsg = document.getElementById('alertMessage');
            btn.disabled = true;
            btn.innerHTML = 'Memproses...';
            alertMsg.innerHTML = '';

            const formData = new FormData(e.target);
            const dataObj = Object.fromEntries(formData.entries());

            const angka = parseFloat(dataObj.nilai_angka);
            let huruf = 'E';
            if (angka >= 85) huruf = 'A';
            else if (angka >= 75) huruf = 'B+';
            else if (angka >= 65) huruf = 'B';
            else if (angka >= 55) huruf = 'C+';
            else if (angka >= 45) huruf = 'C';

            dataObj.nilai_huruf = huruf;

            try {
                const response = await fetch('../api/nilai/store.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dataObj)
                });
                const rawText = await response.text();
                const jsonStart = rawText.indexOf('{');
                if (jsonStart === -1) throw new Error("Respon server kotor");
                const result = JSON.parse(rawText.substring(jsonStart));

                if (result.status === 'success') {
                    alertMsg.innerHTML = `<div class="alert alert-success border-0 text-center py-2 small">${result.message}</div>`;
                    document.getElementById('formInputNilai').reset();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alertMsg.innerHTML = `<div class="alert alert-danger border-0 text-center py-2 small">${error.message}</div>`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Simpan Komponen Nilai';
            }
        });
    </script>
</body>

</html>