<?php
// 1. Sesuaikan path config karena file berada di dalam folder 'admin/'
require_once '../config/db.php';
requireRole(['admin']); 

$db = getDB();
// Mengambil data mata kuliah dari tabel 'kuliah'
$result = $db->query("SELECT * FROM kuliah ORDER BY semester ASC, kode_mk ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Mata Kuliah - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #4e73df; color: white; width: 250px; position: fixed; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid white; }
        .main-content { margin-left: 250px; padding: 20px; }
        .card { border-radius: 12px; border: none; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); }
        .badge-sks { background-color: #f6c23e; color: #fff; }
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
    <a href="data_dosen.php"><i class="bi bi-person-badge me-2"></i> Data Dosen</a>
    <a href="data_kuliah.php" class="active"><i class="bi bi-book me-2"></i> Data Matakuliah</a>
    <a href="../logout.php" class="text-danger mt-5"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-gray-800">Manajemen Mata Kuliah</h3>
            <p class="text-muted">Daftar kurikulum program studi</p>
        </div>
        <button class="btn btn-warning text-white rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahMK">
            <i class="bi bi-journal-plus me-1"></i> Tambah Matakuliah
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Kode MK</th>
                            <th>Nama Mata Kuliah</th>
                            <th class="text-center">SKS</th>
                            <th class="text-center">Semester</th>
                            <th>Jenis</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-primary"><?= $row['kode_mk'] ?></td>
                                <td><?= $row['nama_mk'] ?></td>
                                <td class="text-center">
                                    <span class="badge badge-sks rounded-pill"><?= $row['sks'] ?> SKS</span>
                                </td>
                                <td class="text-center"><?= $row['semester'] ?></td>
                                <td>
                                    <span class="text-muted small">
                                        <?= isset($row['jenis_mk']) ? $row['jenis_mk'] : 'Wajib' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-warning rounded-circle me-1" title="Edit" onclick="editMK(<?= htmlspecialchars(json_encode($row)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger rounded-circle" onclick="hapusMK(<?= $row['id_kuliah'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data mata kuliah.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahMK" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">Tambah Mata Kuliah</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTambahMK">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Kode Mata Kuliah</label>
                        <input type="text" name="kode_mk" class="form-control" placeholder="Contoh: MSI3105" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Mata Kuliah</label>
                        <input type="text" name="nama_mk" class="form-control" placeholder="Nama Lengkap MK" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Jumlah SKS</label>
                            <input type="number" name="sks" class="form-control" min="1" max="6" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Semester</label>
                            <input type="number" name="semester" class="form-control" min="1" max="8" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Jenis Mata Kuliah</label>
                        <select name="jenis_mk" class="form-select">
                            <option value="Wajib">Wajib</option>
                            <option value="Pilihan">Pilihan</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSimpan" class="btn btn-warning text-white rounded-pill px-4">Simpan MK</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// 2. Gunakan path API relatif ../api/
document.getElementById('formTambahMK').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSimpan');
    const formData = new FormData(e.target);
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

    try {
        const response = await fetch('../api/kuliah/store.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        if(result.status === 'success') location.reload();
    } catch (err) {
        alert("Gagal terhubung ke server.");
    } finally {
        btn.disabled = false;
        btn.innerText = 'Simpan MK';
    }
});

async function hapusMK(id) {
    if(confirm('Hapus mata kuliah ini?')) {
        try {
            const response = await fetch(`../api/kuliah/delete.php?id=${id}`);
            const result = await response.json();
            alert(result.message);
            if(result.status === 'success') location.reload();
        } catch (err) {
            alert("Gagal menghapus data.");
        }
    }
}

function editMK(data) {
    alert("Fitur edit untuk " + data.nama_mk + " sedang disiapkan.");
}
</script>
</body>
</html>