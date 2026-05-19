<?php
// 1. Path config naik satu tingkat
require_once '../config/db.php'; 
requireRole(['admin']); 

$db = getDB();
// Ambil semua data dosen
$result = $db->query("SELECT * FROM dosen ORDER BY id_dosen DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Dosen - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #4e73df; color: white; width: 250px; position: fixed; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; transition: 0.2s; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar a.active { background: rgba(255,255,255,0.2); color: white; border-left: 4px solid white; }
        .main-content { margin-left: 250px; padding: 20px; }
        .card { border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .img-table { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; background: #eee; }
        .badge-dosen { background: #1cc88a; color: white; font-weight: 500; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="p-4 text-center">
        <h4 class="fw-bold m-0">SIAKAD</h4>
        <hr>
    </div>
    <a href="dashboard_admin.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="data_mahasiswa.php"><i class="bi bi-people me-2"></i> Data Mahasiswa</a>
    <a href="data_dosen.php" class="active"><i class="bi bi-person-badge me-2"></i> Data Dosen</a>
    <a href="data_kuliah.php"><i class="bi bi-book me-2"></i> Data Matakuliah</a>
    <a href="../logout.php" class="text-danger mt-5"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Manajemen Data Dosen</h3>
            <p class="text-muted">Kelola informasi tenaga pengajar Universitas Riau</p>
        </div>
        <button class="btn btn-success rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahDosen">
            <i class="bi bi-plus-lg me-1"></i> Tambah Dosen
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Foto</th>
                            <th>NIDN</th>
                            <th>Nama Lengkap</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <img src="../uploads/foto_dosen/<?= !empty($row['foto']) ? $row['foto'] : 'default_dosen.jpg' ?>" class="img-table border" alt="Foto">
                                </td>
                                <td class="fw-bold"><?= $row['nidn'] ?></td>
                                <td><?= $row['nama'] ?></td>
                                <td><span class="badge badge-dosen rounded-pill px-3">Dosen Tetap</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-warning rounded-circle me-1" onclick='editDosen(<?= json_encode($row) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger rounded-circle" onclick="hapusDosen(<?= $row['id_dosen'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data dosen.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahDosen" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Form Tambah Dosen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTambahDosen" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">NIDN</label>
                        <input type="text" name="nidn" class="form-control" placeholder="10 digit NIDN" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Lengkap & Gelar</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Dr. Budi, M.Kom" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Foto Profil</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSimpan" class="btn btn-success rounded-pill px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// 4. Update path API menggunakan ../
document.getElementById('formTambahDosen').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSimpan');
    const formData = new FormData(e.target);

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

    try {
        const response = await fetch('../api/dosen/store.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if(result.status === 'success') {
            alert(result.message);
            location.reload();
        } else {
            alert("Gagal: " + result.message);
            btn.disabled = false;
            btn.innerText = 'Simpan Dosen';
        }
    } catch (err) {
        alert("Terjadi kesalahan koneksi.");
        btn.disabled = false;
        btn.innerText = 'Simpan Dosen';
    }
});

async function hapusDosen(id) {
    if(confirm('Hapus data dosen ini?')) {
        try {
            const response = await fetch(`../api/dosen/delete.php?id=${id}`);
            const result = await response.json();
            alert(result.message);
            if(result.status === 'success') location.reload();
        } catch (err) {
            alert("Gagal menghapus data.");
        }
    }
}

function editDosen(data) {
    alert("Edit " + data.nama + " sedang disiapkan.");
}
</script>
</body>
</html>