<?php
// 1. Sesuaikan path config karena file berada di folder 'admin'
require_once '../config/db.php';
requireRole(['admin']); 

$db = getDB();
// Ambil semua data mahasiswa
$result = $db->query("SELECT * FROM mhs ORDER BY id_mhs DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Mahasiswa - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #4e73df; color: white; width: 250px; position: fixed; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid white; }
        .main-content { margin-left: 250px; padding: 20px; }
        .card { border-radius: 12px; border: none; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); }
        .img-table { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; }
        .badge-status { font-size: 0.75rem; padding: 0.4em 0.8em; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="p-4 text-center">
        <h4 class="fw-bold m-0">SIAKAD</h4>
        <hr>
    </div>
    <a href="dashboard_admin.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="data_mahasiswa.php" class="active"><i class="bi bi-people me-2"></i> Data Mahasiswa</a>
    <a href="data_dosen.php"><i class="bi bi-person-badge me-2"></i> Data Dosen</a>
    <a href="data_kuliah.php"><i class="bi bi-book me-2"></i> Data Matakuliah</a>
    <a href="../logout.php" class="text-danger mt-5"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-gray-800">Master Data Mahasiswa</h3>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Tambah Data
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th>Foto</th>
                            <th>NIM / Nama</th>
                            <th>Prodi & Semester</th>
                            <th>Kontak</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="../uploads/foto_mhs/<?= $row['foto'] ?: 'default.jpg' ?>" class="img-table shadow-sm border">
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= $row['nama'] ?></div>
                                <small class="text-muted"><?= $row['nim'] ?> | Angkatan <?= $row['angkatan'] ?></small>
                            </td>
                            <td>
                                <div><?= $row['program_studi'] ?></div>
                                <span class="badge bg-light text-dark border small">Smstr <?= $row['semester'] ?></span>
                            </td>
                            <td>
                                <div class="small"><i class="bi bi-envelope me-1"></i><?= $row['email'] ?></div>
                                <div class="small"><i class="bi bi-whatsapp me-1"></i><?= $row['no_hp'] ?></div>
                            </td>
                            <td>
                                <span class="badge badge-status <?= $row['status'] == 'Aktif' ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                    <?= $row['status'] ?: 'Aktif' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-info rounded-circle me-1" title="Detail" onclick='showDetail(<?= json_encode($row) ?>)'>
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning rounded-circle me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger rounded-circle" onclick="hapusData(<?= $row['id_mhs'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Form Input Mahasiswa Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTambah" enctype="multipart/form-data">
                <div class="modal-body bg-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">NIM</label>
                            <input type="text" name="nim" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
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
                            <textarea name="alamat" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">No. HP (WhatsApp)</label>
                            <input type="text" name="no_hp" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Program Studi</label>
                            <input type="text" name="program_studi" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Angkatan</label>
                            <input type="number" name="angkatan" class="form-control" value="2024">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Semester</label>
                            <input type="number" name="semester" class="form-control" value="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Status Keaktifan</label>
                            <select name="status" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Cuti">Cuti</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Foto Profil</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light text-dark">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Data Mahasiswa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showDetail(data) {
    alert(`DETAIL MAHASISWA:\n\nNIM: ${data.nim}\nNama: ${data.nama}\nProdi: ${data.program_studi}\nAngkatan: ${data.angkatan}\nStatus: ${data.status}`);
}

// Path API disesuaikan menggunakan ../
document.getElementById('formTambah').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const response = await fetch('../api/mahasiswa/store.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if(result.status === 'success') location.reload();
    } catch (err) { alert("Gagal menyimpan data"); }
});

async function hapusData(id) {
    if(confirm('Yakin ingin menghapus data ini?')) {
        try {
            const response = await fetch(`../api/mahasiswa/delete.php?id=${id}`);
            const result = await response.json();
            alert(result.message);
            if(result.status === 'success') location.reload();
        } catch (err) { alert("Gagal menghapus data"); }
    }
}
</script>
</body>
</html>