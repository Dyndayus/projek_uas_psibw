<?php
// 1. Sesuaikan path config karena file berada di folder 'admin'
require_once '../config/db.php';
requireRole(['admin']); 

$db = getDB();

// --- LOGIKA MENANGKAP DAN MENYIMPAN DATA BARU (PROSES INSERT) ---
$pesan_sukses = "";
$pesan_gagal = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_simpan'])) {
    $kode_mk = mysqli_real_escape_string($db, $_POST['kode_mk']);
    $nama_mk = mysqli_real_escape_string($db, $_POST['nama_mk']);
    $sks = intval($_POST['sks']);
    $semester = intval($_POST['semester']);
    $id_dosen = !empty($_POST['id_dosen']) ? intval($_POST['id_dosen']) : "NULL";
    $hari = mysqli_real_escape_string($db, $_POST['hari']);
    $jam = mysqli_real_escape_string($db, $_POST['jam']);

    $query_insert = "INSERT INTO kuliah (kode_mk, nama_mk, sks, semester, id_dosen, hari, jam) 
                     VALUES ('$kode_mk', '$nama_mk', $sks, $semester, $id_dosen, '$hari', '$jam')";
    
    if ($db->query($query_insert)) {
        $pesan_sukses = "Mata kuliah baru berhasil ditambahkan!";
    } else {
        $pesan_gagal = "Gagal menambah mata kuliah: " . $db->error;
    }
}

// --- LOGIKA PROSES UPDATE/EDIT DATA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_update'])) {
    $id_kuliah = intval($_POST['id_kuliah']);
    $kode_mk = mysqli_real_escape_string($db, $_POST['kode_mk']);
    $nama_mk = mysqli_real_escape_string($db, $_POST['nama_mk']);
    $sks = intval($_POST['sks']);
    $semester = intval($_POST['semester']);
    $id_dosen = !empty($_POST['id_dosen']) ? intval($_POST['id_dosen']) : "NULL";
    $hari = mysqli_real_escape_string($db, $_POST['hari']);
    $jam = mysqli_real_escape_string($db, $_POST['jam']);

    $query_update = "UPDATE kuliah SET 
                        kode_mk = '$kode_mk', nama_mk = '$nama_mk', 
                        sks = $sks, semester = $semester, 
                        id_dosen = $id_dosen, hari = '$hari', jam = '$jam' 
                     WHERE id_kuliah = $id_kuliah";

    if ($db->query($query_update)) {
        $pesan_sukses = "Data mata kuliah berhasil diperbarui!";
    } else {
        $pesan_gagal = "Gagal memperbarui data: " . $db->error;
    }
}

// --- LOGIKA FITUR HAPUS DATA ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_hapus = intval($_GET['id']);
    
    if ($db->query("DELETE FROM kuliah WHERE id_kuliah = $id_hapus")) {
        $pesan_sukses = "Mata kuliah berhasil dihapus.";
    } else {
        $pesan_gagal = "Gagal menghapus mata kuliah.";
    }
}

// --- LOGIKA MENENTUKAN QUERY SORTING ---
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$order_by = "k.id_kuliah DESC"; // Default urutan

switch ($sort) {
    case 'az':
        $order_by = "k.nama_mk ASC";
        $label_sort = "Urutkan: Nama (A-Z)";
        break;
    case 'za':
        $order_by = "k.nama_mk DESC";
        $label_sort = "Urutkan: Nama (Z-A)";
        break;
    case 'sks_desc':
        $order_by = "k.sks DESC";
        $label_sort = "Urutkan: SKS Tertinggi";
        break;
    case 'sks_asc':
        $order_by = "k.sks ASC";
        $label_sort = "Urutkan: SKS Terendah";
        break;
    default:
        $label_sort = "Urutkan: Data Terbaru";
        break;
}

// --- FITUR PAGINATION KONSISTEN (DIUBAH JADI 5 BARIS) ---
$limit = 5; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

$total_result = $db->query("SELECT COUNT(*) AS total FROM kuliah");
$total_row = $total_result->fetch_assoc();
$total_data = $total_row['total'];
$total_pages = ceil($total_data / $limit);

// Query SQL menggunakan variabel $order_by yang dinamis
$result = $db->query("SELECT k.*, d.nama AS nama_dosen 
                      FROM kuliah k 
                      LEFT JOIN dosen d ON k.id_dosen = d.id_dosen 
                      ORDER BY $order_by 
                      LIMIT $start, $limit");

$dosen_opt = $db->query("SELECT id_dosen, nama FROM dosen ORDER BY nama ASC");
$list_dosen = [];
while($d = $dosen_opt->fetch_assoc()) { $list_dosen[] = $d; }

// Menghitung jumlah data yang tampil di halaman ini untuk membuat baris kosong opsional
$current_rows_count = $result ? $result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Mata Kuliah - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; margin: 0; padding: 0; min-height: 100vh; overflow-x: hidden; }
        
        .wrapper { display: flex; width: 100%; min-height: 100vh; }
        
        .sidebar { background: #1a233a; color: white; width: 250px; position: fixed; top: 0; left: 0; bottom: 0; z-index: 1000; }
        
        .main-wrapper { 
            margin-left: 250px; 
            display: flex; 
            flex-direction: column; 
            flex-grow: 1; 
            min-height: 100vh;
            width: calc(100% - 250px);
        }
        
        .content-body { padding: 40px; flex-grow: 1; }
        
        .card { border-radius: 12px; border: none; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05); width: 100%; }

        .btn-sort {
            background-color: #ecf3ff;
            color: #0d6efd;
            border: 1px solid #d2e3ff;
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            transition: all 0.2s ease;
        }
        .btn-sort:hover, .btn-sort:focus {
            background-color: #dbe7ff;
            color: #0b5ed7;
            border-color: #bcccff;
        }
        .dropdown-menu-sort {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-wrapper">
        
        <div class="content-body">
            <?php if(!empty($pesan_sukses)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= $pesan_sukses ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(!empty($pesan_gagal)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $pesan_gagal ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark m-0" style="font-family: 'Segoe UI', sans-serif; letter-spacing: -0.5px;">Manajemen Mata Kuliah</h2>
                    <small class="text-muted">Daftar kurikulum program studi aktif</small>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn btn-sort d-flex align-items-center gap-2 dropdown-toggle shadow-sm" type="button" id="dropdownSort" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-sort-down"></i> <?= $label_sort ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-sort dropdown-menu-end" aria-labelledby="dropdownSort">
                            <li><a class="dropdown-menu-item dropdown-item <?= $sort === 'default' ? 'active' : '' ?>" href="data_kuliah.php?sort=default">Data Terbaru</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-menu-item dropdown-item <?= $sort === 'az' ? 'active' : '' ?>" href="data_kuliah.php?sort=az">Nama MK (A-Z)</a></li>
                            <li><a class="dropdown-menu-item dropdown-item <?= $sort === 'za' ? 'active' : '' ?>" href="data_kuliah.php?sort=za">Nama MK (Z-A)</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-menu-item dropdown-item <?= $sort === 'sks_desc' ? 'active' : '' ?>" href="data_kuliah.php?sort=sks_desc">SKS Tertinggi</a></li>
                            <li><a class="dropdown-menu-item dropdown-item <?= $sort === 'sks_asc' ? 'active' : '' ?>" href="data_kuliah.php?sort=sks_asc">SKS Terendah</a></li>
                        </ul>
                    </div>

                    <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="bi bi-plus-lg"></i> Tambah Matakuliah
                    </button>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th style="width: 15%">Kode MK</th>
                                    <th style="width: 45%">Nama Mata Kuliah</th>
                                    <th style="width: 15%">SKS</th>
                                    <th style="width: 15%">Semester</th>
                                    <th style="width: 10%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($current_rows_count > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr style="height: 65px;">
                                        <td class="fw-bold text-secondary"><?= htmlspecialchars($row['kode_mk']) ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_mk']) ?></div>
                                            <small class="text-muted"><i class="bi bi-person-badge me-1"></i>Dosen: <?= $row['nama_dosen'] ? htmlspecialchars($row['nama_dosen']) : 'Belum diplot' ?></small>
                                        </td>
                                        <td class="fw-bold"><?= htmlspecialchars($row['sks']) ?> SKS</td>
                                        <td><span class="badge bg-light text-dark border px-2 py-1">Semester <?= htmlspecialchars($row['semester']) ?></span></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center align-items-center">
                                                <button class="btn btn-sm btn-outline-warning rounded-circle me-2" 
                                                        title="Edit" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalEdit"
                                                        data-id="<?= $row['id_kuliah'] ?>"
                                                        data-kode="<?= htmlspecialchars($row['kode_mk']) ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_mk']) ?>"
                                                        data-sks="<?= $row['sks'] ?>"
                                                        data-semester="<?= $row['semester'] ?>"
                                                        data-dosen="<?= $row['id_dosen'] ?>"
                                                        data-hari="<?= htmlspecialchars($row['hari']) ?>"
                                                        data-jam="<?= htmlspecialchars($row['jam']) ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus" onclick="hapusData(<?= $row['id_kuliah'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php 
                                    $empty_rows = $limit - $current_rows_count;
                                    for ($i = 0; $i < $empty_rows; $i++): 
                                    ?>
                                    <tr style="height: 65px;">
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <?php endfor; ?>

                                <?php else: ?>
                                    <tr style="height: 65px;">
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada data mata kuliah.</td>
                                    </tr>
                                    <?php for ($i = 1; $i < $limit; $i++): ?>
                                    <tr style="height: 65px;">
                                        <td colspan="5">&nbsp;</td>
                                    </tr>
                                    <?php endfor; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4 pt-3 border-top">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="data_kuliah.php?page=<?= $page - 1 ?>&sort=<?= $sort ?>">Previous</a>
                                </li>
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link" href="data_kuliah.php?page=<?= $i ?>&sort=<?= $sort ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="data_kuliah.php?page=<?= $page + 1 ?>&sort=<?= $sort ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php include 'footer.php'; ?>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                <h5 class="modal-title fw-bold">Tambah Mata Kuliah</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="data_kuliah.php?sort=<?= $sort ?>" method="POST">
                <input type="hidden" name="proses_simpan" value="1">
                <div class="modal-body p-4 bg-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Kode MK</label>
                            <input type="text" name="kode_mk" class="form-control" placeholder="Contoh: INF101" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Jumlah SKS</label>
                            <input type="number" name="sks" class="form-control" value="3" min="1" max="6" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-secondary">Nama Mata Kuliah</label>
                            <input type="text" name="nama_mk" class="form-control" placeholder="Nama Lengkap Mata Kuliah" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Semester</label>
                            <input type="number" name="semester" class="form-control" value="1" min="1" max="8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Dosen Pengampu</label>
                            <select name="id_dosen" class="form-select">
                                <option value="">-- Pilih Dosen (Opsional) --</option>
                                <?php foreach($list_dosen as $dsn): ?>
                                    <option value="<?= $dsn['id_dosen'] ?>"><?= htmlspecialchars($dsn['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Hari</label>
                            <select name="hari" class="form-select">
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Jam Kuliah</label>
                            <input type="text" name="jam" class="form-control" placeholder="Contoh: 08:00 - 10:30">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Matakuliah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-warning text-dark" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Form Edit Mata Kuliah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="data_kuliah.php?page=<?= $page ?>&sort=<?= $sort ?>" method="POST">
                <input type="hidden" name="proses_update" value="1">
                <input type="hidden" name="id_kuliah" id="edit_id_kuliah">
                
                <div class="modal-body p-4 bg-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Kode MK</label>
                            <input type="text" name="kode_mk" id="edit_kode_mk" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Jumlah SKS</label>
                            <input type="number" name="sks" id="edit_sks" class="form-control" min="1" max="6" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-secondary">Nama Mata Kuliah</label>
                            <input type="text" name="nama_mk" id="edit_nama_mk" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Semester</label>
                            <input type="number" name="semester" id="edit_semester" class="form-control" min="1" max="8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Dosen Pengampu</label>
                            <select name="id_dosen" id="edit_id_dosen" class="form-select">
                                <option value="">-- Pilih Dosen --</option>
                                <?php foreach($list_dosen as $dsn): ?>
                                    <option value="<?= $dsn['id_dosen'] ?>"><?= htmlspecialchars($dsn['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Hari</label>
                            <select name="hari" id="edit_hari" class="form-select">
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Jam Kuliah</label>
                            <input type="text" name="jam" id="edit_jam" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark shadow-sm">Perbarui Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function hapusData(id) {
    if(confirm('Yakin ingin menghapus data mata kuliah ini?')) {
        window.location.href = `data_kuliah.php?action=delete&id=${id}&sort=<?= $sort ?>`;
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const modalEdit = document.getElementById('modalEdit');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; 
            
            const id = button.getAttribute('data-id');
            const kode = button.getAttribute('data-kode');
            const nama = button.getAttribute('data-nama');
            const sks = button.getAttribute('data-sks');
            const semester = button.getAttribute('data-semester');
            const dosen = button.getAttribute('data-dosen');
            const hari = button.getAttribute('data-hari');
            const jam = button.getAttribute('data-jam');

            document.getElementById('edit_id_kuliah').value = id;
            document.getElementById('edit_kode_mk').value = kode;
            document.getElementById('edit_nama_mk').value = nama;
            document.getElementById('edit_sks').value = sks;
            document.getElementById('edit_semester').value = semester;
            document.getElementById('edit_id_dosen').value = dosen ? dosen : "";
            document.getElementById('edit_hari').value = hari;
            document.getElementById('edit_jam').value = jam;
        });
    }
});
</script>
</body>
</html>