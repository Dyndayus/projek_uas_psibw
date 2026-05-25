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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #4e73df; color: white; width: 250px; position: fixed; z-index: 1000; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid white; }
        .main-content { margin-left: 250px; padding: 20px; }
        .card-box { border-radius: 10px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: 0.3s; }
        .card-box:hover { transform: translateY(-5px); }
        .stat-icon { font-size: 2rem; opacity: 0.3; }
        .modal-lg { max-width: 800px; }
        .form-label { margin-bottom: 0.3rem; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="p-4 text-center">
        <h4 class="fw-bold m-0">SIAKAD</h4>
        <small>Universitas Riau</small>
        <hr>
    </div>
    <a href="dashboard_admin.php" class="active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="data_mahasiswa.php"><i class="bi bi-people me-2"></i> Data Mahasiswa</a>
    <a href="data_dosen.php"><i class="bi bi-person-badge me-2"></i> Data Dosen</a>
    <a href="data_kuliah.php"><i class="bi bi-book me-2"></i> Data Matakuliah</a>
    <hr>
    <a href="javascript:void(0)" onclick="handleLogout()" class="text-danger"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Dashboard Admin</h3>
            <small class="text-muted">Selamat datang, <strong><?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Admin' ?></strong></small>
        </div>
        <div>
            <button class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalGenerateUser">
                <i class="bi bi-person-plus-fill me-1"></i> Generate User
            </button>
            <button onclick="handleLogout()" class="btn btn-danger rounded-pill px-4 ms-2">Logout</button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-box p-3 border-start border-primary border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-primary fw-bold text-uppercase small">Total Mahasiswa</div>
                        <div class="h2 fw-bold mb-0"><?= $totalMhs ?></div>
                    </div>
                    <i class="bi bi-mortarboard stat-icon text-primary"></i>
                </div>
                <a href="data_mahasiswa.php" class="small text-muted text-decoration-none mt-2 d-block text-end">Lihat Detail &raquo;</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-box p-3 border-start border-success border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-success fw-bold text-uppercase small">Total Dosen</div>
                        <div class="h2 fw-bold mb-0"><?= $totalDosen ?></div>
                    </div>
                    <i class="bi bi-person-workspace stat-icon text-success"></i>
                </div>
                <a href="data_dosen.php" class="small text-muted text-decoration-none mt-2 d-block text-end">Lihat Detail &raquo;</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-box p-3 border-start border-warning border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-warning fw-bold text-uppercase small">Total Matakuliah</div>
                        <div class="h2 fw-bold mb-0"><?= $totalMK ?></div>
                    </div>
                    <i class="bi bi-book stat-icon text-warning"></i>
                </div>
                <a href="data_kuliah.php" class="small text-muted text-decoration-none mt-2 d-block text-end">Lihat Detail &raquo;</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card card-box">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                    <span class="fw-bold">Mahasiswa Baru</span>
                    <i class="bi bi-people"></i>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 small">
                        <tbody>
                            <?php while($m = $mhsBaru->fetch_assoc()): ?>
                                <tr><td class="ps-3"><?= htmlspecialchars($m['nim']) ?></td><td><?= htmlspecialchars($m['nama']) ?></td></tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-center border-0">
                    <a href="data_mahasiswa.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card card-box">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
                    <span class="fw-bold">Dosen Baru</span>
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 small">
                        <tbody>
                            <?php while($d = $dosenBaru->fetch_assoc()): ?>
                                <tr><td class="ps-3"><?= htmlspecialchars($d['nidn']) ?></td><td><?= htmlspecialchars($d['nama']) ?></td></tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-center border-0">
                    <a href="data_dosen.php" class="btn btn-sm btn-outline-success rounded-pill px-3">Lihat Semua</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card card-box">
                <div class="card-header bg-warning d-flex justify-content-between align-items-center py-3">
                    <span class="fw-bold">MK Baru</span>
                    <i class="bi bi-book"></i>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 small">
                        <tbody>
                            <?php while($mk = $mkBaru->fetch_assoc()): ?>
                                <tr><td class="ps-3"><?= htmlspecialchars($mk['kode_mk']) ?></td><td><?= htmlspecialchars($mk['nama_mk']) ?></td></tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-center border-0">
                    <a href="data_kuliah.php" class="btn btn-sm btn-outline-warning rounded-pill px-3 text-dark">Lihat Semua</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGenerateUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Tambah Data Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formGenerateUser" enctype="multipart/form-data">
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role Pengguna</label>
                        <select name="role" id="selectRole" class="form-select border-2 border-success" required onchange="toggleForm(this.value)">
                            <option value="">-- Pilih Role --</option>
                            <option value="dosen">Dosen</option>
                            <option value="mahasiswa">Mahasiswa</option>
                        </select>
                    </div>

                    <div id="formContainer" style="display:none;" class="row g-3">
                        <div class="col-md-6">
                            <label id="labelID" class="form-label fw-bold small text-primary">ID (NIM/NIDN)</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-primary">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-primary"><i class="bi bi-image me-1"></i>Foto Profil</label>
                            <input type="file" name="foto" id="inputFoto" class="form-control border-2" accept="image/*">
                            <div class="form-text text-muted small">Opsional. Format: JPG/PNG, Maks: 2MB.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">No. HP</label>
                            <input type="text" name="no_hp" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="col-md-6 field-dosen">
                            <label class="form-label fw-bold small">Pendidikan Terakhir</label>
                            <select name="pendidikan_terakhir" class="form-select">
                                <option value="S2">S2</option>
                                <option value="S3">S3</option>
                            </select>
                        </div>
                        <div class="col-md-6 field-dosen">
                            <label class="form-label fw-bold small">Jabatan</label>
                            <input type="text" name="jabatan" class="form-control" placeholder="Lektor / Asisten Ahli">
                        </div>

                        <div class="col-md-6 field-mhs">
                            <label class="form-label fw-bold small">Program Studi</label>
                            <input type="text" name="program_studi" class="form-control">
                        </div>
                        <div class="col-md-3 field-mhs">
                            <label class="form-label fw-bold small">Angkatan</label>
                            <input type="number" name="angkatan" class="form-control" placeholder="2026" value="2026">
                        </div>
                        <div class="col-md-3 field-mhs">
                            <label class="form-label fw-bold small">Semester</label>
                            <input type="number" name="semester" class="form-control" placeholder="1" value="1">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small">Status Keaktifan</label>
                            <select name="status" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
                        </div>

                        <div class="col-12 alert alert-info py-2 small border-0 mb-0 mt-3">
                            <i class="bi bi-info-circle me-1"></i> Akun login akan dibuat otomatis dengan Username dari <strong>Email</strong> dan Password default dari <strong>NIM/NIDN</strong>.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnProses" class="btn btn-success rounded-pill px-5">Simpan Data</button>
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
            labelID.innerText = 'NIDN';
            dosenFields.forEach(f => f.style.display = 'block');
            mhsFields.forEach(f => f.style.display = 'none');
        } else {
            labelID.innerText = 'NIM';
            dosenFields.forEach(f => f.style.display = 'none');
            mhsFields.forEach(f => f.style.display = 'block');
        }
    } else {
        container.style.display = 'none';
    }
}

document.getElementById('formGenerateUser').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    if(!confirm('Buat akun login otomatis?')) return;

    const btn = document.getElementById('btnProses');
    const formElement = e.target;
    const formData = new FormData(formElement);
    const inputFoto = document.getElementById('inputFoto');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';

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
        alert("Terjadi kesalahan: " + err.message);
    } finally {
        btn.disabled = false;
        btn.innerText = 'Simpan Data';
    }
});

function handleLogout() {
    if(confirm('Apakah Anda yakin ingin keluar?')) {
        window.location.href = '../logout.php';
    }
}
</script>
</body>
</html>