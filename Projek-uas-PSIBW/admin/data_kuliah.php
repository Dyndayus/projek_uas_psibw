<?php
// 1. Sesuaikan path config karena file berada di dalam folder 'admin/'
require_once '../config/db.php';
// requireRole(['admin']); // Aktifkan ini jika fungsi proteksi role-mu sudah siap

$db = getDB();

// --- PROSES ACTION CRUD SECARA TRADISIONAL ---
$message = '';
$msg_status = '';

// A. PROSES TAMBAH MATA KULIAH
if (isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $kode_mk = $db->real_escape_string($_POST['kode_mk']);
    $nama_mk = $db->real_escape_string($_POST['nama_mk']);
    $sks = intval($_POST['sks']);
    $semester = intval($_POST['semester']);

    $query = "INSERT INTO kuliah (kode_mk, nama_mk, sks, semester) VALUES ('$kode_mk', '$nama_mk', $sks, $semester)";
    if ($db->query($query)) {
        $message = "Mata kuliah berhasil ditambahkan!";
        $msg_status = "success";
    } else {
        $message = "Gagal menambah data: " . $db->error;
        $msg_status = "danger";
    }
}

// B. PROSES EDIT MATA KULIAH
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id_kuliah = intval($_POST['id_kuliah']);
    $kode_mk = $db->real_escape_string($_POST['kode_mk']);
    $nama_mk = $db->real_escape_string($_POST['nama_mk']);
    $sks = intval($_POST['sks']);
    $semester = intval($_POST['semester']);

    $query = "UPDATE kuliah SET kode_mk = '$kode_mk', nama_mk = '$nama_mk', sks = $sks, semester = $semester WHERE id_kuliah = $id_kuliah";
    if ($db->query($query)) {
        $message = "Mata kuliah berhasil diperbarui!";
        $msg_status = "success";
    } else {
        $message = "Gagal memperbarui data: " . $db->error;
        $msg_status = "danger";
    }
}

// C. PROSES HAPUS MATA KULIAH
if (isset($_GET['delete'])) {
    $id_hapus = intval($_GET['delete']);
    $query = "DELETE FROM kuliah WHERE id_kuliah = $id_hapus";
    if ($db->query($query)) {
        $message = "Mata kuliah berhasil dihapus!";
        $msg_status = "success";
    } else {
        $message = "Gagal menghapus data: " . $db->error;
        $msg_status = "danger";
    }
}

// --- FITUR PAGINATION (Membatasi 5 Data per Halaman) ---
$limit = 5; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

$total_result = $db->query("SELECT COUNT(*) AS total FROM kuliah");
$total_row = $total_result->fetch_assoc();
$total_data = $total_row['total'];
$total_pages = ceil($total_data / $limit);

$result = $db->query("SELECT * FROM kuliah ORDER BY semester ASC, kode_mk ASC LIMIT $start, $limit");
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
        
        /* Trik Utama: Mengunci tinggi area tabel agar box card tidak mengecil/melar */
        .table-responsive-konsisten {
            min-height: 390px; /* Menjaga tinggi card tetap stabil meski data sedikit */
            display: flex;
            flex-direction: column;
            justify-content:开;
        }
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

    <?php if ($message != ''): ?>
        <div class="alert alert-<?= $msg_status ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body d-flex flex-column" style="min-height: 480px;">
            <div class="table-responsive table-responsive-konsisten flex-grow-1">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 15%;">Kode MK</th>
                            <th style="width: 45%;">Nama Mata Kuliah</th>
                            <th class="text-center" style="width: 15%;">SKS</th>
                            <th class="text-center" style="width: 15%;">Semester</th>
                            <th class="text-center" style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr style="height: 65px;"> <td class="ps-3 fw-bold text-primary"><?= $row['kode_mk'] ?></td>
                                <td><?= $row['nama_mk'] ?></td>
                                <td class="text-center">
                                    <span class="badge badge-sks rounded-pill"><?= $row['sks'] ?> SKS</span>
                                </td>
                                <td class="text-center">Semester <?= $row['semester'] ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-warning rounded-circle me-1" title="Edit" 
                                            onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($row)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="data_kuliah.php?delete=<?= $row['id_kuliah'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus mata kuliah <?= $row['nama_mk'] ?>?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data mata kuliah.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav class="mt-auto pt-3 border-top">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="data_kuliah.php?page=<?= $page - 1 ?>">Previous</a>
                        </li>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="data_kuliah.php?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="data_kuliah.php?page=<?= $page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

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
            <form action="data_kuliah.php" method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Kode Mata Kuliah</label>
                        <input type="text" name="kode_mk" class="form-control" placeholder="Contoh: TI401" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Mata Kuliah</label>
                        <input type="text" name="nama_mk" class="form-control" placeholder="Nama Lengkap MK" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Jumlah SKS</label>
                            <input type="number" name="sks" class="form-control" min="1" max="6" value="3" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Semester</label>
                            <input type="number" name="semester" class="form-control" min="1" max="8" value="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white rounded-pill px-4">Simpan MK</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditMK" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit Mata Kuliah</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="data_kuliah.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_kuliah" id="edit_id_kuliah">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Kode Mata Kuliah</label>
                        <input type="text" name="kode_mk" id="edit_kode_mk" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Mata Kuliah</label>
                        <input type="text" name="nama_mk" id="edit_nama_mk" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Jumlah SKS</label>
                            <input type="number" name="sks" id="edit_sks" class="form-control" min="1" max="6" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Semester</label>
                            <input type="number" name="semester" id="edit_semester" class="form-control" min="1" max="8" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Perbarui MK</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function bukaModalEdit(data) {
    document.getElementById('edit_id_kuliah').value = data.id_kuliah;
    document.getElementById('edit_kode_mk').value = data.kode_mk;
    document.getElementById('edit_nama_mk').value = data.nama_mk;
    document.getElementById('edit_sks').value = data.sks;
    document.getElementById('edit_semester').value = data.semester;
    
    var modal = new bootstrap.Modal(document.getElementById('modalEditMK'));
    modal.show();
}
</script>
</body>
</html>