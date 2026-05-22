<?php
// 1. Path config naik satu tingkat
require_once '../config/db.php'; 
// requireRole(['admin']); // Aktifkan jika proteksi role sudah siap

$db = getDB();

// --- PROSES ACTION CRUD SECARA TRADISIONAL (Langsung ke Database) ---
$message = '';
$msg_status = '';

// A. PROSES TAMBAH DOSEN + OTOMATIS BUAT AKUN DI TABEL 'user' SINKRON DENGAN ID_REF
if (isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $nidn = $db->real_escape_string($_POST['nidn']);
    $nama = $db->real_escape_string($_POST['nama']);
    $tgl_lahir = $db->real_escape_string($_POST['tgl_lahir']);
    $jenis_kelamin = $db->real_escape_string($_POST['jenis_kelamin']);
    $alamat = $db->real_escape_string($_POST['alamat']);
    $no_hp = $db->real_escape_string($_POST['no_hp']);
    $email = $db->real_escape_string($_POST['email']);
    $pendidikan_terakhir = $db->real_escape_string($_POST['pendidikan_terakhir']);
    $jabatan = $db->real_escape_string($_POST['jabatan']);
    $status = $db->real_escape_string($_POST['status']);
    
    // Default nama file foto
    $nama_foto = 'default_dosen.jpg';

    // Proses Upload Foto jika ada
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ekstensi_diperbolehkan = array('png', 'jpg', 'jpeg');
        $x = explode('.', $_FILES['foto']['name']);
        $ekstensi = strtolower(end($x));
        $ukuran = $_FILES['foto']['size'];
        $file_tmp = $_FILES['foto']['tmp_name'];

        if (in_array($ekstensi, $ekstensi_diperbolehkan) === true) {
            if ($ukuran < 2000000) { // Max 2MB
                $nama_foto = time() . '_' . preg_replace("/[^a-zA-Z0-9]/", "", $nidn) . '.' . $ekstensi;
                if(!is_dir('../uploads/foto_dosen/')) {
                    mkdir('../uploads/foto_dosen/', 0777, true);
                }
                move_uploaded_file($file_tmp, '../uploads/foto_dosen/' . $nama_foto);
            }
        }
    }

    // Mulai Database Transaction agar sinkronisasi aman
    $db->begin_transaction();

    try {
        // Validasi Awal: Pastikan email belum pernah digunakan sebagai username di tabel user
        $checkUser = $db->query("SELECT username FROM user WHERE username = '$email'");
        if ($checkUser && $checkUser->num_rows > 0) {
            throw new Exception("Email '" . htmlspecialchars($email) . "' sudah terdaftar di sistem!");
        }

        // 1. Insert ke tabel dosen terlebih dahulu
        $query_dosen = "INSERT INTO dosen (nidn, nama, tgl_lahir, jenis_kelamin, alamat, no_hp, email, foto, pendidikan_terakhir, jabatan, status) 
                        VALUES ('$nidn', '$nama', '$tgl_lahir', '$jenis_kelamin', '$alamat', '$no_hp', '$email', '$nama_foto', '$pendidikan_terakhir', '$jabatan', '$status')";
        
        if (!$db->query($query_dosen)) {
            throw new Exception("Gagal menyimpan data dosen: " . $db->error);
        }

        // Ambil ID Dosen yang baru saja dibuat secara otomatis (untuk id_ref)
        $id_dosen_baru = $db->insert_id;

        // 2. Insert ke tabel user (username=email, password=nidn polos tanpa enkripsi, id_ref=id_dosen_baru)
        $query_user = "INSERT INTO user (username, password, role, id_ref) 
                       VALUES ('$email', '$nidn', 'dosen', '$id_dosen_baru')";
        
        if (!$db->query($query_user)) {
            throw new Exception("Gagal membuat akun user login: " . $db->error);
        }

        // Jika kedua query berhasil tanpa masalah, terapkan ke database
        $db->commit();
        $message = "Data Dosen & Akun Login Dosen berhasil ditambahkan!";
        $msg_status = "success";
    } catch (Exception $e) {
        // Jika salah satu gagal, batalkan semua agar data tidak timpang sebelah
        $db->rollback();
        $message = $e->getMessage();
        $msg_status = "danger";
    }
}

// B. PROSES EDIT DATA DOSEN
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id_dosen = intval($_POST['id_dosen']);
    $nidn = $db->real_escape_string($_POST['nidn']);
    $nama = $db->real_escape_string($_POST['nama']);
    $tgl_lahir = $db->real_escape_string($_POST['tgl_lahir']);
    $jenis_kelamin = $db->real_escape_string($_POST['jenis_kelamin']);
    $alamat = $db->real_escape_string($_POST['alamat']);
    $no_hp = $db->real_escape_string($_POST['no_hp']);
    $email = $db->real_escape_string($_POST['email']);
    $pendidikan_terakhir = $db->real_escape_string($_POST['pendidikan_terakhir']);
    $jabatan = $db->real_escape_string($_POST['jabatan']);
    $status = $db->real_escape_string($_POST['status']);
    
    $res_lama = $db->query("SELECT foto FROM dosen WHERE id_dosen = $id_dosen");
    $row_lama = $res_lama->fetch_assoc();
    $nama_foto = $row_lama['foto'];

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ekstensi_diperbolehkan = array('png', 'jpg', 'jpeg');
        $x = explode('.', $_FILES['foto']['name']);
        $ekstensi = strtolower(end($x));
        $file_tmp = $_FILES['foto']['tmp_name'];

        if (in_array($ekstensi, $ekstensi_diperbolehkan) === true) {
            $nama_foto_baru = time() . '_' . preg_replace("/[^a-zA-Z0-9]/", "", $nidn) . '.' . $ekstensi;
            if(move_uploaded_file($file_tmp, '../uploads/foto_dosen/' . $nama_foto_baru)) {
                if ($nama_foto != 'default_dosen.jpg' && file_exists('../uploads/foto_dosen/' . $nama_foto)) {
                    unlink('../uploads/foto_dosen/' . $nama_foto);
                }
                $nama_foto = $nama_foto_baru;
            }
        }
    }

    $db->begin_transaction();
    try {
        // Update tabel dosen
        $query = "UPDATE dosen SET 
                    nidn = '$nidn', nama = '$nama', tgl_lahir = '$tgl_lahir', jenis_kelamin = '$jenis_kelamin', 
                    alamat = '$alamat', no_hp = '$no_hp', email = '$email', foto = '$nama_foto', 
                    pendidikan_terakhir = '$pendidikan_terakhir', jabatan = '$jabatan', status = '$status' 
                  WHERE id_dosen = $id_dosen";
                  
        if (!$db->query($query)) {
            throw new Exception("Gagal memperbarui data dosen.");
        }

        // Sinkronisasi username di tabel user jika email dosen diubah
        $query_user_update = "UPDATE user SET username = '$email' WHERE role = 'dosen' AND id_ref = $id_dosen";
        if (!$db->query($query_user_update)) {
            throw new Exception("Gagal memperbarui data login user.");
        }

        $db->commit();
        $message = "Data dosen berhasil diperbarui!";
        $msg_status = "success";
    } catch (Exception $e) {
        $db->rollback();
        $message = $e->getMessage();
        $msg_status = "danger";
    }
}

// C. PROSES HAPUS DOSEN
if (isset($_GET['delete'])) {
    $id_hapus = intval($_GET['delete']);
    
    $res_foto = $db->query("SELECT foto FROM dosen WHERE id_dosen = $id_hapus");
    if($res_foto && $res_foto->num_rows > 0) {
        $row_foto = $res_foto->fetch_assoc();
        $foto_file = $row_foto['foto'];
        if ($foto_file != 'default_dosen.jpg' && file_exists('../uploads/foto_dosen/' . $foto_file)) {
            unlink('../uploads/foto_dosen/' . $foto_file);
        }
    }

    $db->begin_transaction();
    try {
        // Hapus akun di tabel user terlebih dahulu
        $db->query("DELETE FROM user WHERE role = 'dosen' AND id_ref = $id_hapus");

        // Hapus data utama di tabel dosen
        if (!$db->query("DELETE FROM dosen WHERE id_dosen = $id_hapus")) {
            throw new Exception("Gagal menghapus data dosen.");
        }

        $db->commit();
        $message = "Data dosen beserta akun login berhasil dihapus!";
        $msg_status = "success";
    } catch (Exception $e) {
        $db->rollback();
        $message = "Gagal menghapus data: " . $db->error;
        $msg_status = "danger";
    }
}

// --- FITUR PAGINATION KONSISTEN MAKSIMAL 5 DATA ---
$limit = 5; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

$total_result = $db->query("SELECT COUNT(*) AS total FROM dosen");
$total_row = $total_result->fetch_assoc();
$total_data = $total_row['total'];
$total_pages = ceil($total_data / $limit);

$result = $db->query("SELECT * FROM dosen ORDER BY id_dosen DESC LIMIT $start, $limit");
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
        .card { border-radius: 12px; border: none; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); }
        .img-table { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; background: #eee; }
        .badge-status { font-size: 0.75rem; padding: 0.4em 0.8em; }
        
        /* Mengunci tinggi minimal tabel pembungkus agar pas setara 5 baris data (meski data kurang dari 5) */
        .table-responsive-konsisten {
            min-height: 400px; 
            display: flex;
            flex-direction: column;
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
    <a href="data_dosen.php" class="active"><i class="bi bi-person-badge me-2"></i> Data Dosen</a>
    <a href="data_kuliah.php"><i class="bi bi-book me-2"></i> Data Matakuliah</a>
    <a href="../logout.php" class="text-danger mt-5"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-gray-800">Manajemen Data Dosen</h3>
            <p class="text-muted mb-0">Kelola informasi tenaga pengajar Universitas Riau</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahDosen">
            <i class="bi bi-plus-lg me-1"></i> Tambah Dosen
        </button>
    </div>

    <?php if ($message != ''): ?>
        <div class="alert alert-<?= $msg_status ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body d-flex flex-column" style="min-height: 520px;">
            <div class="table-responsive table-responsive-konsisten flex-grow-1">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th style="width: 7%">Foto</th>
                            <th style="width: 15%">NIDN</th>
                            <th style="width: 25%">Nama Lengkap</th>
                            <th style="width: 10%" class="text-center">Pendidikan</th>
                            <th style="width: 20%">Kontak / Email</th>
                            <th style="width: 13%">Jabatan</th>
                            <th style="width: 10%">Status</th>
                            <th style="width: 10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr style="height: 75px;">
                                <td>
                                    <?php
                                    $avatar_default = "https://ui-avatars.com/api/?name=" . urlencode($row['nama']) . "&background=random&color=fff";
                                    $path_foto = $avatar_default;
                                    if (!empty($row['foto'])) {
                                        if (file_exists('../uploads/foto_dosen/' . $row['foto'])) {
                                            $path_foto = '../uploads/foto_dosen/' . $row['foto'];
                                        }
                                    }
                                    ?>
                                    <img src="<?= $path_foto ?>" class="img-table shadow-sm border" alt="Foto" onerror="this.src='<?= $avatar_default ?>'">
                                </td>
                                <td class="fw-bold text-secondary"><?= htmlspecialchars($row['nidn']) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama']) ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border small px-2 py-1"><?= !empty($row['pendidikan_terakhir']) ? htmlspecialchars($row['pendidikan_terakhir']) : '-' ?></span>
                                </td>
                                <td>
                                    <div class="small"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($row['email']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($row['no_hp']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-primary rounded-pill px-3"><?= htmlspecialchars($row['jabatan']) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-status <?= strtolower($row['status']) == 'aktif' ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center flex-nowrap">
                                        <button class="btn btn-sm btn-outline-warning rounded-circle me-1" title="Edit" 
                                                onclick='bukaModalEdit(<?= htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="data_dosen.php?delete=<?= $row['id_dosen'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data dosen <?= htmlspecialchars($row['nama'], ENT_QUOTES) ?> beserta akun loginnya?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data dosen.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav class="mt-auto pt-3 border-top">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="data_dosen.php?page=<?= $page - 1 ?>">Previous</a>
                        </li>
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="data_dosen.php?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="data_dosen.php?page=<?= $page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahDosen" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Form Input Dosen Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="data_dosen.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body bg-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">NIDN</label>
                            <input type="text" name="nidn" class="form-control" placeholder="10 digit NIDN" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nama Lengkap & Gelar</label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: Dr. Aris, M.T." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">No. HP (WhatsApp)</label>
                            <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="nama@unri.ac.id" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat rumah lengkap..." required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Pendidikan Terakhir</label>
                            <input type="text" name="pendidikan_terakhir" class="form-control" placeholder="Contoh: S2 / S3" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Jabatan Fungsional</label>
                            <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Lektor" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Status Keaktifan</label>
                            <select name="status" class="form-select" required>
                                <option value="Aktif">Aktif</option>
                                <option value="Tidak Aktif">Tidak Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">Foto Profil (Opsional)</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Data Dosen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditDosen" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Form Edit Informasi Dosen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="data_dosen.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_dosen" id="edit_id_dosen">
                <div class="modal-body bg-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">NIDN</label>
                            <input type="text" name="nidn" id="edit_nidn" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nama Lengkap & Gelar</label>
                            <input type="text" name="nama" id="edit_nama" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" id="edit_tgl_lahir" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="edit_jenis_kelamin" class="form-select" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">No. HP (WhatsApp)</label>
                            <input type="text" name="no_hp" id="edit_no_hp" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">Alamat</label>
                            <textarea name="alamat" id="edit_alamat" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Pendidikan Terakhir</label>
                            <input type="text" name="pendidikan_terakhir" id="edit_pendidikan_terakhir" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Jabatan Fungsional</label>
                            <input type="text" name="jabatan" id="edit_jabatan" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Status Keaktifan</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="Aktif">Aktif</option>
                                <option value="Tidak Aktif">Tidak Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">Ganti Foto Profil</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                            <div class="form-text text-muted small">Biarkan kosong jika tidak ingin mengubah foto.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">Perbarui Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function bukaModalEdit(data) {
    document.getElementById('edit_id_dosen').value = data.id_dosen;
    document.getElementById('edit_nidn').value = data.nidn;
    document.getElementById('edit_nama').value = data.nama;
    document.getElementById('edit_tgl_lahir').value = data.tgl_lahir;
    document.getElementById('edit_jenis_kelamin').value = data.jenis_kelamin;
    document.getElementById('edit_alamat').value = data.alamat;
    document.getElementById('edit_no_hp').value = data.no_hp;
    document.getElementById('edit_email').value = data.email;
    document.getElementById('edit_pendidikan_terakhir').value = data.pendidikan_terakhir;
    document.getElementById('edit_jabatan').value = data.jabatan;
    document.getElementById('edit_status').value = data.status;
    
    var modal = new bootstrap.Modal(document.getElementById('modalEditDosen'));
    modal.show();
}
</script>
</body>
</html>