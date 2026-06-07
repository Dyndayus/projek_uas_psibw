<?php
// Pastikan session dimulai di baris paling pertama
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php'; 
requireRole(['admin']); 

$db = getDB();

// --- PENANGANAN BACKEND INTEGRASI FOTO CADANGAN ---
$pesan_sukses = "";
$pesan_gagal = "";

// Jika form mengirim data secara langsung (POST Native) untuk mengamankan unggahan foto profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_FILES['foto'])) {
    $role = mysqli_real_escape_string($db, $_POST['role']);
    $username = mysqli_real_escape_string($db, $_POST['username']);
    
    if ($role === 'mahasiswa' && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_name = $_FILES['foto']['name'];
        
        // Membersihkan nama file dan memberikan timestamp unik agar tidak bentrok
        $nama_foto_database = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $file_name);
        $target_dir = "../uploads/foto_mhs/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Pindahkan file ke folder tujuan fisiknya
        if (move_uploaded_file($file_tmp, $target_dir . $nama_foto_database)) {
            // Update nama file foto di database berdasarkan NIM mahasiswa yang baru saja tersimpan via API
            $db->query("UPDATE mhs SET foto = '$nama_foto_database' WHERE nim = '$username'");
        }
    }
}

// Ambil total untuk dashboard box
$totalMhs = $db->query("SELECT COUNT(*) as total FROM mhs")->fetch_assoc()['total'];
$totalDosen = $db->query("SELECT COUNT(*) as total FROM dosen")->fetch_assoc()['total'];
$totalMK = $db->query("SELECT COUNT(*) as total FROM kuliah")->fetch_assoc()['total'];

// Ambil data terbaru untuk tabel
$mhsBaru = $db->query("SELECT nim, nama FROM mhs ORDER BY id_mhs DESC LIMIT 4");
$dosenBaru = $db->query("SELECT nidn, nama FROM dosen ORDER BY id_dosen DESC LIMIT 2");
$mkBaru = $db->query("SELECT kode_mk, nama_mk FROM kuliah ORDER BY id_kuliah DESC LIMIT 2");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SIAKAD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --bg-main: #f4f6f9;
            --card-shadow: 0 10px 30px rgba(149, 157, 165, 0.08);
            --accent-teal: #0ea5e9; 
            --accent-emerald: #10b981; 
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main); 
            color: #334155;
        }

        /* --- CONTENT RE-DESIGN --- */
        .main-content { 
            padding: 40px; 
        }
        
        .welcome-text h3 {
            color: #0f172a;
            font-weight: 700;
        }

        /* --- CARD STATISTIK MINIMALIS --- */
        .card-stat { 
            border-radius: 16px; 
            border: none; 
            background: white;
            box-shadow: var(--card-shadow); 
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-stat:hover { 
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
        }
        .card-stat::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 6px; height: 100%;
        }
        .card-stat.stat-mhs::before { background: var(--accent-teal); }
        .card-stat.stat-dosen::before { background: #6366f1; } 
        .card-stat.stat-mk::before { background: #f59e0b; } 

        .stat-icon-wrapper {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stat-mhs .stat-icon-wrapper { background: rgba(14, 165, 233, 0.1); color: var(--accent-teal); }
        .stat-dosen .stat-icon-wrapper { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
        .stat-mk .stat-icon-wrapper { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

        /* --- UTILITY CARDS & TABLES --- */
        .card-data {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            background: white;
            height: 100%;
            overflow: hidden;
        }
        .card-data .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 20px 24px;
            font-weight: 600;
            color: #0f172a;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 12px 24px;
            border-bottom: 1px solid #e2e8f0;
        }
        .table td {
            padding: 16px 24px;
            color: #334155;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .badge-id {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
        }

        /* --- BUTTONS & MODAL --- */
        .btn-premium-success {
            background: var(--accent-emerald);
            border: none;
            color: white;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-premium-success:hover {
            background: #059669;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            color: white;
        }
        .btn-premium-danger {
            background: #ef4444;
            border: none;
            color: white;
            font-weight: 500;
        }
        .btn-premium-danger:hover {
            background: #dc2626;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            color: white;
        }
        .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }
        .form-control, .form-select {
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.95rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
        }
        .drop-shadow {
            filter: drop-shadow(0px 4px 8px rgba(255, 255, 255, 0.15));
        }
    </style>
</head>
<body>

<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-container">
        
        <div class="main-content">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-5">
                <div class="welcome-text">
                    <h3 class="mb-1">Dashboard Admin</h3>
                    <p class="text-muted m-0">Selamat datang kembali, <span class="text-dark fw-semibold"><?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Admin' ?></span> ✨</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-premium-success rounded-xl px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalGenerateUser">
                        <i class="bi bi-plus-circle-fill me-2"></i> Tambah Data Baru
                    </button>
                    <button onclick="handleLogout()" class="btn btn-outline-secondary rounded-xl px-3 py-2 border-secondary-subtle">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card-stat stat-mhs">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing: 0.5px;">Mahasiswa</div>
                                <div class="h1 fw-bold text-dark mb-0"><?= $totalMhs ?></div>
                            </div>
                            <div class="stat-icon-wrapper">
                                <i class="bi bi-mortarboard-fill"></i>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top border-light d-flex justify-content-between align-items-center">
                            <a href="data_mahasiswa.php" class="small text-decoration-none text-primary fw-medium">Kelola Data &rarr;</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-stat stat-dosen">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing: 0.5px;">Dosen Pengajar</div>
                                <div class="h1 fw-bold text-dark mb-0"><?= $totalDosen ?></div>
                            </div>
                            <div class="stat-icon-wrapper">
                                <i class="bi bi-person-workspace"></i>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top border-light d-flex justify-content-between align-items-center">
                            <a href="data_dosen.php" class="small text-decoration-none text-indigo fw-medium" style="color: #6366f1;">Kelola Data &rarr;</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-stat stat-mk">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing: 0.5px;">Total Matakuliah</div>
                                <div class="h1 fw-bold text-dark mb-0"><?= $totalMK ?></div>
                            </div>
                            <div class="stat-icon-wrapper">
                                <i class="bi bi-book-half"></i>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top border-light d-flex justify-content-between align-items-center">
                            <a href="data_kuliah.php" class="small text-decoration-none text-warning fw-medium">Kelola Data &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card-data">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-clock-history me-2 text-primary"></i>Mahasiswa Baru</span>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 small" style="font-size:0.75rem">Terbaru</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>NIM</th>
                                        <th>Nama</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($mhsBaru->num_rows > 0): ?>
                                        <?php while($m = $mhsBaru->fetch_assoc()): ?>
                                            <tr>
                                                <td><span class="badge-id"><?= htmlspecialchars($m['nim']) ?></span></td>
                                                <td class="fw-medium text-truncate" style="max-width: 140px;"><?= htmlspecialchars($m['nama']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="2" class="text-center text-muted py-4">Belum ada data</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-data">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-person-badge text-success me-2"></i>Dosen Bergabung</span>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 small" style="font-size:0.75rem">Terbaru</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>NIDN</th>
                                        <th>Nama Dosen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($dosenBaru->num_rows > 0): ?>
                                        <?php while($d = $dosenBaru->fetch_assoc()): ?>
                                            <tr>
                                                <td><span class="badge-id"><?= htmlspecialchars($d['nidn']) ?></span></td>
                                                <td class="fw-medium text-truncate" style="max-width: 140px;"><?= htmlspecialchars($d['nama']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="2" class="text-center text-muted py-4">Belum ada data</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-data">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-journal-plus text-warning me-2"></i>Kurikulum Baru</span>
                            <span class="badge bg-warning-subtle text-warning rounded-pill px-2 py-1 small" style="font-size:0.75rem">Terbaru</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Matakuliah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($mkBaru->num_rows > 0): ?>
                                        <?php while($mk = $mkBaru->fetch_assoc()): ?>
                                            <tr>
                                                <td><span class="badge-id"><?= htmlspecialchars($mk['kode_mk']) ?></span></td>
                                                <td class="fw-medium text-truncate" style="max-width: 140px;"><?= htmlspecialchars($mk['nama_mk']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="2" class="text-center text-muted py-4">Belum ada data</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div> <?php include 'footer.php'; ?>

    </div> </div> <div class="modal fade" id="modalGenerateUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-person-plus-fill me-2 text-info"></i>Form Registrasi Akademik</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formGenerateUser" enctype="multipart/form-data">
                <div class="modal-body p-4 bg-light-subtle">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">Pilih Peran Civitas Akademika</label>
                        <select name="role" id="selectRole" class="form-select border-2" style="border-color: #e2e8f0" required onchange="toggleForm(this.value)">
                            <option value="">-- Pilih Peran --</option>
                            <option value="dosen">Dosen </option>
                            <option value="mahasiswa">Mahasiswa </option>
                        </select>
                    </div>

                    <div id="formContainer" style="display:none;" class="row g-3">
                        <div class="col-md-6">
                            <label id="labelID" class="form-label fw-bold text-dark">ID (NIM/NIDN)</label>
                            <input type="text" name="username" class="form-control" required placeholder="Masukkan nomor identitas">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required placeholder="Nama tanpa gelar">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark"><i class="bi bi-image me-2 text-muted"></i>Unggah Foto Profil resmi</label>
                            <input type="file" name="foto" id="inputFoto" class="form-control" accept="image/*">
                            <div class="form-text text-muted">Format yang didukung: JPG, JPEG, PNG. Maksimal ukuran file 2 MB.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-secondary small">Alamat Rumah</label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder=""></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">No. Handphone</label>
                            <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">Alamat Email Institusi</label>
                            <input type="email" name="email" class="form-control" required placeholder="name@univ.ac.id">
                        </div>

                        <div class="col-md-6 field-dosen">
                            <label class="form-label fw-bold text-secondary small">Strata Pendidikan Terakhir</label>
                            <select name="pendidikan_terakhir" class="form-select">
                                <option value="S2">Magister (S2)</option>
                                <option value="S3">Doktor (S3)</option>
                            </select>
                        </div>
                        <div class="col-md-6 field-dosen">
                            <label class="form-label fw-bold text-secondary small">Jabatan Fungsional</label>
                            <input type="text" name="jabatan" class="form-control" placeholder="Tenaga Pengajar / Lektor / Asisten Ahli">
                        </div>

                        <div class="col-md-6 field-mhs">
                            <label class="form-label fw-bold text-secondary small">Program Studi</label>
                            <input type="text" name="program_studi" class="form-control" placeholder="Teknik Informatika">
                        </div>
                        <div class="col-md-3 field-mhs">
                            <label class="form-label fw-bold text-secondary small">Tahun Angkatan</label>
                            <input type="number" name="angkatan" class="form-control" value="2026">
                        </div>
                        <div class="col-md-3 field-mhs">
                            <label class="form-label fw-bold text-secondary small">Semester Berjalan</label>
                            <input type="number" name="semester" class="form-control" value="1">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold text-secondary small">Status Registrasi</label>
                            <select name="status" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
                        </div>

                        <div class="col-12 mt-4">
                            <div class="p-3 bg-primary-subtle border-0 rounded-3 d-flex align-items-center text-primary-emphasis small">
                                <i class="bi bi-shield-check-fill fs-5 me-3"></i>
                                <span><strong>Pemberitahuan Sistem:</strong> Kredensial akun sistem login mahasiswa/dosen baru akan digenerate otomatis. Username = <strong>Email</strong>, sandi awal = <strong>NIM/NIDN</strong>.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batalkan</button>
                    <button type="submit" id="btnProses" class="btn btn-premium-success rounded-pill px-5">Simpan ke Basis Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleForm(role) {
    const container = document.getElementById('formContainer');
    const labelID = document.getElementById('labelID');
    const dosenFields = document.querySelectorAll('.field-dosen');
    const mhsFields = document.querySelectorAll('.field-mhs');
    
    if(role !== "") {
        container.style.display = 'flex';
        if(role === 'dosen') {
            labelID.innerText = 'NIDN (Nomor Induk Dosen Nasional)';
            dosenFields.forEach(f => f.style.display = 'block');
            mhsFields.forEach(f => f.style.display = 'none');
        } else {
            labelID.innerText = 'NIM (Nomor Induk Mahasiswa)';
            dosenFields.forEach(f => f.style.display = 'none');
            mhsFields.forEach(f => f.style.display = 'block');
        }
    } else {
        container.style.display = 'none';
    }
}

document.getElementById('formGenerateUser').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    if(!confirm('Konfirmasi pembuatan akun sistem otomatis untuk pengguna baru ini?')) return;

    const btn = document.getElementById('btnProses');
    const formElement = e.target;
    const formData = new FormData(formElement);
    const inputFoto = document.getElementById('inputFoto');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan Data...';

    try {
        const response = await fetch('../api/auth/register_user_manual.php', {
            method: 'POST',
            body: formData
        });

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("Server tidak memberikan respon valid JSON.");
        }

        const result = await response.json();
        
        if(result.status === 'success') {
            if (inputFoto && inputFoto.files.length > 0) {
                await fetch('dashboard_admin.php', {
                    method: 'POST',
                    body: formData
                });
            }
            alert(result.message);
            location.reload();
        } else {
            alert(result.message);
        }
    } catch (err) {
        console.error(err);
        alert("Terjadi kegagalan komunikasi sistem: " + err.message);
    } finally {
        btn.disabled = false;
        btn.innerText = 'Simpan ke Basis Data';
    }
});

function handleLogout() {
    if(confirm('Apakah Anda yakin ingin keluar dari sesi administrasi SIAKAD?')) {
        window.location.href = '../logout.php';
    }
}
</script>
</body>
</html>