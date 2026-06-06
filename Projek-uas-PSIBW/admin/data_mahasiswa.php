<?php
// 1. Sesuaikan path config karena file berada di folder 'admin'
require_once '../config/db.php';
requireRole(['admin']); 

$db = getDB();

// --- LOGIKA MENANGKAP DAN MENYIMPAN DATA BARU (PROSES INSERT) ---
$pesan_sukses = "";
$pesan_gagal = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_simpan'])) {
    $nim = mysqli_real_escape_string($db, $_POST['nim']);
    $nama = mysqli_real_escape_string($db, $_POST['nama']);
    $tgl_lahir = mysqli_real_escape_string($db, $_POST['tgl_lahir']);
    $jenis_kelamin = mysqli_real_escape_string($db, $_POST['jenis_kelamin']);
    $alamat = mysqli_real_escape_string($db, $_POST['alamat']);
    $no_hp = mysqli_real_escape_string($db, $_POST['no_hp']);
    $email = mysqli_real_escape_string($db, $_POST['email']);
    $program_studi = mysqli_real_escape_string($db, $_POST['program_studi']);
    $angkatan = intval($_POST['angkatan']);
    $semester = intval($_POST['semester']);
    $status = mysqli_real_escape_string($db, $_POST['status']);
    
    $nama_foto_database = "";

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_name = $_FILES['foto']['name']; 
        
        $nama_foto_database = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $file_name);
        $target_dir = "../uploads/foto_mhs/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        move_uploaded_file($file_tmp, $target_dir . $nama_foto_database);
    }

    // MEMULAI TRANSAKSI DATABASE AGAR KEDUA TABEL SINKRON
    $db->begin_transaction();

    try {
        // Validasi Awal: Pastikan email belum pernah digunakan sebagai username di tabel user
        $checkUser = $db->query("SELECT username FROM user WHERE username = '$email'");
        if ($checkUser && $checkUser->num_rows > 0) {
            throw new Exception("Email '" . htmlspecialchars($email) . "' sudah terdaftar sebagai username di sistem!");
        }

        // Query 1: Insert ke tabel mahasiswa (mhs)
        $query_insert_mhs = "INSERT INTO mhs (nim, nama, tgl_lahir, jenis_kelamin, alamat, no_hp, email, program_studi, angkatan, semester, status, foto) 
                             VALUES ('$nim', '$nama', '$tgl_lahir', '$jenis_kelamin', '$alamat', '$no_hp', '$email', '$program_studi', $angkatan, $semester, '$status', '$nama_foto_database')";
        
        if (!$db->query($query_insert_mhs)) {
            throw new Exception("Gagal menyimpan biodata mahasiswa: " . $db->error);
        }

        // Ambil ID mahasiswa baru yang baru saja digenerate otomatis oleh MySQL
        $last_id_mhs = $db->insert_id;

        // Query 2: Insert ke tabel user (Password langsung menggunakan $nim asli tanpa hash)
        $query_insert_user = "INSERT INTO user (username, password, role, id_ref) 
                              VALUES ('$email', '$nim', 'mahasiswa', $last_id_mhs)";

        if (!$db->query($query_insert_user)) {
            throw new Exception("Gagal membuat akun login mahasiswa di tabel user: " . $db->error);
        }

        // Jika kedua query berhasil tanpa kendala, kunci data ke database
        $db->commit();
        $pesan_sukses = "Data mahasiswa atas nama " . htmlspecialchars($nama) . " beserta akun loginnya berhasil disimpan!";

    } catch (Exception $e) {
        // Jika ada salah satu yang gagal atau email bentrok, batalkan semua perubahan
        $db->rollback();
        $pesan_gagal = $e->getMessage();
    }
}

// --- LOGIKA PROSES UPDATE/EDIT DATA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_update'])) {
    $id_mhs = intval($_POST['id_mhs']);
    $nim = mysqli_real_escape_string($db, $_POST['nim']);
    $nama = mysqli_real_escape_string($db, $_POST['nama']);
    $tgl_lahir = mysqli_real_escape_string($db, $_POST['tgl_lahir']);
    $jenis_kelamin = mysqli_real_escape_string($db, $_POST['jenis_kelamin']);
    $alamat = mysqli_real_escape_string($db, $_POST['alamat']);
    $no_hp = mysqli_real_escape_string($db, $_POST['no_hp']);
    $email = mysqli_real_escape_string($db, $_POST['email']);
    $program_studi = mysqli_real_escape_string($db, $_POST['program_studi']);
    $angkatan = intval($_POST['angkatan']);
    $semester = intval($_POST['semester']);
    $status = mysqli_real_escape_string($db, $_POST['status']);
    
    $foto_lama_result = $db->query("SELECT foto FROM mhs WHERE id_mhs = $id_mhs");
    $foto_lama_row = $foto_lama_result->fetch_assoc();
    $nama_foto_database = $foto_lama_row['foto'];

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_name = $_FILES['foto']['name']; 
        
        $nama_foto_database = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $file_name);
        $target_dir = "../uploads/foto_mhs/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (move_uploaded_file($file_tmp, $target_dir . $nama_foto_database)) {
            if (!empty($foto_lama_row['foto']) && file_exists($target_dir . $foto_lama_row['foto'])) {
                unlink($target_dir . $foto_lama_row['foto']);
            }
        }
    }

    $db->begin_transaction();
    try {
        // Update data di tabel mhs
        $query_update = "UPDATE mhs SET 
                            nim = '$nim', nama = '$nama', tgl_lahir = '$tgl_lahir', 
                            jenis_kelamin = '$jenis_kelamin', alamat = '$alamat', no_hp = '$no_hp', 
                            email = '$email', program_studi = '$program_studi', angkatan = $angkatan, 
                            semester = $semester, status = '$status', foto = '$nama_foto_database' 
                         WHERE id_mhs = $id_mhs";

        if (!$db->query($query_update)) {
            throw new Exception("Gagal memperbarui data profil: " . $db->error);
        }

        // Sinkronisasi tabel user jika admin mengubah email di form edit (karena email = username)
        $query_update_user = "UPDATE user SET username = '$email' WHERE role = 'mahasiswa' AND id_ref = $id_mhs";
        if (!$db->query($query_update_user)) {
            throw new Exception("Gagal memperbarui username login: " . $db->error);
        }

        $db->commit();
        $pesan_sukses = "Data mahasiswa atas nama " . htmlspecialchars($nama) . " berhasil diperbarui!";
    } catch (Exception $e) {
        $db->rollback();
        $pesan_gagal = $e->getMessage();
    }
}

// --- LOGIKA FITUR HAPUS DATA ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_hapus = intval($_GET['id']);
    
    $foto_result = $db->query("SELECT foto FROM mhs WHERE id_mhs = $id_hapus");
    if($foto_row = $foto_result->fetch_assoc()) {
        if(!empty($foto_row['foto']) && file_exists("../uploads/foto_mhs/" . $foto_row['foto'])) {
            unlink("../uploads/foto_mhs/" . $foto_row['foto']);
        }
    }

    $db->begin_transaction();
    try {
        // Hapus akun loginnya dulu di tabel user (untuk mencegah kendala relasi data)
        $db->query("DELETE FROM user WHERE role = 'mahasiswa' AND id_ref = $id_hapus");

        // Hapus data mahasiswa di tabel mhs
        if (!$db->query("DELETE FROM mhs WHERE id_mhs = $id_hapus")) {
            throw new Exception("Gagal menghapus data mahasiswa.");
        }

        $db->commit();
        $pesan_sukses = "Data mahasiswa dan akun login berhasil dihapus dari sistem.";
    } catch (Exception $e) {
        $db->rollback();
        $pesan_gagal = $e->getMessage();
    }
}

// --- FITUR PAGINATION KONSISTEN MAKSIMAL 7 DATA ---
$limit = 7; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

$total_result = $db->query("SELECT COUNT(*) AS total FROM mhs");
$total_row = $total_result->fetch_assoc();
$total_data = $total_row['total'];
$total_pages = ceil($total_data / $limit);

$result = $db->query("SELECT * FROM mhs ORDER BY id_mhs DESC LIMIT $start, $limit");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Mahasiswa - SIAKAD</title>
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
        .img-table { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; }
        .badge-status { font-size: 0.75rem; padding: 0.4em 0.8em; }
        
        .table-responsive-konsisten {
            min-height: 540px; 
            display: flex;
            flex-direction: column;
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
                    <h2 class="fw-bold text-dark m-0" style="font-family: 'Segoe UI', sans-serif; letter-spacing: -0.5px;">Master Data Mahasiswa</h2>
                </div>
                <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" style="background-color: #0d6efd;" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-lg"></i> Tambah Data
                </button>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body d-flex flex-column" style="min-height: 650px;">
                    <div class="table-responsive table-responsive-konsisten flex-grow-1">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th style="width: 7%">Foto</th>
                                    <th style="width: 28%">NIM / Nama</th>
                                    <th style="width: 25%">Prodi & Semester</th>
                                    <th style="width: 22%">Kontak</th>
                                    <th style="width: 10%">Status</th>
                                    <th style="width: 12%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr style="height: 75px;"> 
                                        <td class="align-middle">
                                            <?php 
                                            $avatar_default = "https://ui-avatars.com/api/?name=" . urlencode($row['nama']) . "&background=random&color=fff"; 
                                            
                                            $path_foto = $avatar_default;
                                            if (!empty($row['foto'])) {
                                                if (file_exists("../uploads/foto_mhs/" . $row['foto'])) {
                                                    $path_foto = "../uploads/foto_mhs/" . $row['foto'];
                                                } elseif (file_exists("../uploads/" . $row['foto'])) {
                                                    $path_foto = "../uploads/" . $row['foto'];
                                                }
                                            }
                                            ?>
                                            <img src="<?= $path_foto ?>" class="img-table shadow-sm border" alt="Foto" onerror="this.src='<?= $avatar_default ?>'">
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($row['nim']) ?> | Angkatan <?= htmlspecialchars($row['angkatan']) ?></small>
                                        </td>
                                        <td class="align-middle">
                                            <div><?= htmlspecialchars($row['program_studi']) ?></div>
                                            <span class="badge bg-light text-dark border small">Smstr <?= htmlspecialchars($row['semester']) ?></span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="small"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($row['email']) ?></div>
                                            <div class="small text-muted"><i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($row['no_hp']) ?></div>
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge badge-status <?= strtolower($row['status']) == 'aktif' ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                                <?= !empty($row['status']) ? htmlspecialchars($row['status']) : 'Aktif' ?>
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <div class="d-flex justify-content-center align-items-center flex-nowrap">
                                                <button class="btn btn-sm btn-outline-warning rounded-circle me-1" 
                                                        title="Edit Data" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalEdit"
                                                        data-id="<?= $row['id_mhs'] ?>"
                                                        data-nim="<?= htmlspecialchars($row['nim']) ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama']) ?>"
                                                        data-tgl_lahir="<?= htmlspecialchars($row['tgl_lahir']) ?>"
                                                        data-jk="<?= htmlspecialchars($row['jenis_kelamin']) ?>"
                                                        data-alamat="<?= htmlspecialchars($row['alamat']) ?>"
                                                        data-no_hp="<?= htmlspecialchars($row['no_hp']) ?>"
                                                        data-email="<?= htmlspecialchars($row['email']) ?>"
                                                        data-prodi="<?= htmlspecialchars($row['program_studi']) ?>"
                                                        data-angkatan="<?= $row['angkatan'] ?>"
                                                        data-semester="<?= $row['semester'] ?>"
                                                        data-status="<?= htmlspecialchars($row['status']) ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <button class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus" onclick="hapusData(<?= $row['id_mhs'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data mahasiswa.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-auto pt-3 border-top">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="data_mahasiswa.php?page=<?= $page - 1 ?>">Previous</a>
                                </li>
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link" href="data_mahasiswa.php?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="data_mahasiswa.php?page=<?= $page + 1 ?>">Next</a>
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                <h5 class="modal-title fw-bold">Form Input Mahasiswa Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="data_mahasiswa.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="proses_simpan" value="1">
                <div class="modal-body p-4 bg-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">NIM</label>
                            <input type="text" name="nim" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-secondary">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">No. HP (WhatsApp)</label>
                            <input type="text" name="no_hp" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Program Studi</label>
                            <input type="text" name="program_studi" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Angkatan</label>
                            <input type="number" name="angkatan" class="form-control" value="2026">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Semester</label>
                            <input type="number" name="semester" class="form-control" value="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Status Keaktifan</label>
                            <select name="status" class="form-select">
                                <option value="aktif">aktif</option>
                                <option value="non-aktif">non-aktif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Foto Profil</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Data Mahasiswa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-warning text-dark" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Form Edit Data Mahasiswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="data_mahasiswa.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="proses_update" value="1">
                <input type="hidden" name="id_mhs" id="edit_id_mhs">
                
                <div class="modal-body p-4 bg-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">NIM</label>
                            <input type="text" name="nim" id="edit_nim" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Nama Lengkap</label>
                            <input type="text" name="nama" id="edit_nama" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" id="edit_tgl_lahir" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="edit_jenis_kelamin" class="form-select">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-secondary">Alamat</label>
                            <textarea name="alamat" id="edit_alamat" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">No. HP (WhatsApp)</label>
                            <input type="text" name="no_hp" id="edit_no_hp" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Program Studi</label>
                            <input type="text" name="program_studi" id="edit_program_studi" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Angkatan</label>
                            <input type="number" name="angkatan" id="edit_angkatan" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Semester</label>
                            <input type="number" name="semester" id="edit_semester" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Status Keaktifan</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="aktif">aktif</option>
                                <option value="non-aktif">non-aktif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Ganti Foto Profil</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                            <div class="form-text text-muted small">Biarkan kosong jika tidak ingin mengubah foto.</div>
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
    if(confirm('Yakin ingin menghapus data mahasiswa ini? Menghapus data ini juga akan menghapus akun login terkait.')) {
        window.location.href = `data_mahasiswa.php?action=delete&id=${id}`;
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const modalEdit = document.getElementById('modalEdit');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; 
            
            const id = button.getAttribute('data-id');
            const nim = button.getAttribute('data-nim');
            const nama = button.getAttribute('data-nama');
            const tgl_lahir = button.getAttribute('data-tgl_lahir');
            const jk = button.getAttribute('data-jk');
            const alamat = button.getAttribute('data-alamat');
            const no_hp = button.getAttribute('data-no_hp');
            const email = button.getAttribute('data-email');
            const prodi = button.getAttribute('data-prodi');
            const angkatan = button.getAttribute('data-angkatan');
            const semester = button.getAttribute('data-semester');
            const status = button.getAttribute('data-status');

            document.getElementById('edit_id_mhs').value = id;
            document.getElementById('edit_nim').value = nim;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_tgl_lahir').value = tgl_lahir;
            document.getElementById('edit_jenis_kelamin').value = jk;
            document.getElementById('edit_alamat').value = alamat;
            document.getElementById('edit_no_hp').value = no_hp;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_program_studi').value = prodi;
            document.getElementById('edit_angkatan').value = angkatan;
            document.getElementById('edit_semester').value = semester;
            document.getElementById('edit_status').value = status;
        });
    }
});
</script>
</body>
</html>