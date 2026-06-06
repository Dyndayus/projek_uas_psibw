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

// --- FITUR SORT BY DATA DOSEN ---
$valid_columns = ['id_dosen', 'nidn', 'nama', 'jabatan', 'status'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $valid_columns) ? $_GET['sort'] : 'id_dosen';
$order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';

// --- FITUR PAGINATION KONSISTEN MAKSIMAL 5 DATA ---
$limit = 5; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

$total_result = $db->query("SELECT COUNT(*) AS total FROM dosen");
$total_row = $total_result->fetch_assoc();
$total_data = $total_row['total'];
$total_pages = ceil($total_data / $limit);

$result = $db->query("SELECT * FROM dosen ORDER BY $sort $order LIMIT $start, $limit");

// Hitung jumlah data asli yang berhasil diambil pada halaman aktif
$jumlah_data_sekarang = ($result) ? $result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Dosen - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f4f6f9; 
            color: #334155;
            margin: 0;
        }
        
        .wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .main-content { 
            padding: 40px; 
            flex: 1;
        }
        
        .card { 
            border-radius: 16px; 
            border: none; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.02); 
            background: #ffffff;
        }
        .table-responsive-konsisten {
            display: flex;
            flex-direction: column;
            overflow-x: auto;
        }
        .table {
            table-layout: fixed;
            width: 100%;
        }
        .table thead th {
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #475569;
        }
        .table tbody td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .img-table { 
            width: 48px; 
            height: 48px; 
            object-fit: cover; 
            border-radius: 12px; 
            background: #f1f5f9; 
        }
        
        .badge-status { 
            font-size: 0.75rem; 
            padding: 0.4em 1em; 
            font-weight: 600;
            display: inline-block;
        }
        .bg-aktif {
            background-color: #dcfce7;
            color: #15803d;
        }
        .bg-tidak-aktif {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .pagination .page-link {
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            font-size: 0.9rem;
        }
        .pagination .page-item.active .page-link {
            background-color: #1e2640;
            border-color: #1e2640;
            color: #ffffff;
        }
        .pagination .page-link:hover {
            background-color: #f1f5f9;
        }

        .btn-sort-custom {
            background-color: #e6f0ff !important;
            color: #0d6efd !important;
            border: 1px solid #b3d4ff !important;
        }
        .btn-sort-custom:hover, .btn-sort-custom:focus {
            background-color: #cce0ff !important;
            color: #0a58ca !important;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-container">
        
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.5px;">Manajemen Data Dosen</h3>
                    <p class="text-muted mb-0 small">Kelola informasi serta hak akses login seluruh dosen pengajar.</p>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn btn-sort-custom dropdown-toggle rounded-pill px-3 shadow-sm fw-semibold" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-sort-down me-1"></i> Urutkan: 
                            <?php 
                                if($sort == 'id_dosen') echo 'Data Terbaru';
                                if($sort == 'nidn') echo 'NIDN';
                                if($sort == 'nama') echo 'Nama';
                                if($sort == 'jabatan') echo 'Jabatan';
                                if($sort == 'status') echo 'Status';
                                echo ($order == 'ASC') ? ' (A-Z/Terlama)' : ' (Z-A/Terbaru)';
                            ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2" style="border-radius: 10px;">
                            <li><h6 class="dropdown-header small text-uppercase fw-bold text-muted px-2">Berdasarkan</h6></li>
                            <li><a class="dropdown-item rounded" href="data_dosen.php?sort=id_dosen&order=DESC">Data Terbaru</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item rounded" href="data_dosen.php?sort=nama&order=ASC">Nama (A - Z)</a></li>
                            <li><a class="dropdown-item rounded" href="data_dosen.php?sort=nama&order=DESC">Nama (Z - A)</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item rounded" href="data_dosen.php?sort=nidn&order=ASC">NIDN (Terkecil)</a></li>
                            <li><a class="dropdown-item rounded" href="data_dosen.php?sort=jabatan&order=ASC">Jabatan Fungsional</a></li>
                        </ul>
                    </div>

                    <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" style="background-color: #0d6efd; border: none;" data-bs-toggle="modal" data-bs-target="#modalTambahDosen">
                        <i class="bi bi-plus-lg"></i> Tambah Data
                    </button>
                </div>
            </div>

            <?php if ($message != ''): ?>
                <div class="alert alert-<?= $msg_status ?> alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i> <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <div class="table-responsive table-responsive-konsisten flex-grow-1">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 10%">Foto</th>
                                    <th style="width: 15%">NIDN</th>
                                    <th style="width: 25%">Nama Lengkap</th>
                                    <th style="width: 13%" class="text-center">Pendidikan</th>
                                    <th style="width: 22%">Kontak / Email</th>
                                    <th style="width: 13%">Jabatan</th>
                                    <th style="width: 12%">Status</th>
                                    <th style="width: 10%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($jumlah_data_sekarang > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr style="height: 81px;">
                                        <td>
                                            <?php
                                            $avatar_default = "https://ui-avatars.com/api/?name=" . urlencode($row['nama']) . "&background=f1f5f9&color=475569&bold=true";
                                            $path_foto = $avatar_default;
                                            if (!empty($row['foto'])) {
                                                if (file_exists('../uploads/foto_dosen/' . $row['foto'])) {
                                                    $path_foto = '../uploads/foto_dosen/' . $row['foto'];
                                                }
                                            }
                                            ?>
                                            <img src="<?= $path_foto ?>" class="img-table border shadow-sm" alt="Foto" onerror="this.src='<?= $avatar_default ?>'">
                                        </td>
                                        <td class="fw-semibold text-secondary"><?= htmlspecialchars($row['nidn']) ?></td>
                                        <td>
                                            <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($row['nama']) ?>"><?= htmlspecialchars($row['nama']) ?></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border small px-2 py-1"><?= !empty($row['pendidikan_terakhir']) ? htmlspecialchars($row['pendidikan_terakhir']) : '-' ?></span>
                                        </td>
                                        <td>
                                            <div class="small fw-medium text-truncate" title="<?= htmlspecialchars($row['email']) ?>"><i class="bi bi-envelope text-muted me-1"></i><?= htmlspecialchars($row['email']) ?></div>
                                            <div class="small text-muted mt-1"><i class="bi bi-whatsapp text-muted me-1"></i><?= htmlspecialchars($row['no_hp']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border px-2.5 py-1.5 rounded"><?= htmlspecialchars($row['jabatan']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-status <?= strtolower($row['status']) == 'aktif' ? 'bg-aktif' : 'bg-tidak-aktif' ?> rounded-pill">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center align-items-center flex-nowrap">
                                                <button class="btn btn-sm btn-light border text-warning rounded-3 me-2" title="Edit" 
                                                        onclick='bukaModalEdit(<?= htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)'>
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a href="data_dosen.php?delete=<?= $row['id_dosen'] ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="btn btn-sm btn-light border text-danger rounded-3" 
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus data dosen <?= htmlspecialchars($row['nama'], ENT_QUOTES) ?> beserta akun loginnya?')">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>

                                <?php 
                                $sisa_baris = $limit - $jumlah_data_sekarang;
                                if ($sisa_baris > 0): 
                                    for ($i = 0; $i < $sisa_baris; $i++):
                                ?>
                                    <tr style="height: 81px;">
                                        <td colspan="8" class="text-center text-muted small bg-white opacity-50">
                                            <?php if ($jumlah_data_sekarang == 0 && $i == 2): ?>
                                                Belum ada data dosen terdaftar.
                                            <?php else: ?>
                                                &nbsp;
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    endfor;
                                endif; 
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-auto pt-3 border-top">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link rounded-start-3" href="data_dosen.php?page=<?= $page - 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>">Sebelumnya</a>
                                </li>
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link" href="data_dosen.php?page=<?= $i ?>&sort=<?= $sort ?>&order=<?= $order ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link rounded-end-3" href="data_dosen.php?page=<?= $page + 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>">Selanjutnya</a>
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

<div class="modal fade" id="modalTambahDosen" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header text-white p-4" style="background-color: #1e2640; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Form Input Dosen Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="data_dosen.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body p-4 bg-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">NIDN</label>
                            <input type="text" name="nidn" class="form-control rounded-3" placeholder="10 digit NIDN" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Nama Lengkap & Gelar</label>
                            <input type="text" name="nama" class="form-control rounded-3" placeholder="Contoh: Dr. Aris, M.T." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select rounded-3" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">No. HP (WhatsApp)</label>
                            <input type="text" name="no_hp" class="form-control rounded-3" placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Email Resmi</label>
                            <input type="email" name="email" class="form-control rounded-3" placeholder="nama@unri.ac.id" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small text-secondary">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control rounded-3" rows="2" placeholder="Alamat rumah lengkap..." required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Pendidikan Terakhir</label>
                            <input type="text" name="pendidikan_terakhir" class="form-control rounded-3" placeholder="Contoh: S2 / S3" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Jabatan Fungsional</label>
                            <input type="text" name="jabatan" class="form-control rounded-3" placeholder="Contoh: Lektor" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Status Keaktifan</label>
                            <select name="status" class="form-select rounded-3" required>
                                <option value="Aktif">Aktif</option>
                                <option value="Tidak Aktif">Tidak Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small text-secondary">Foto Profil (Opsional)</label>
                            <input type="file" name="foto" class="form-control rounded-3" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 border-top-0">
                    <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark rounded-3 px-4" style="background-color: #1e2640; border: none;">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditDosen" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header text-white p-4" style="background-color: #1e2640; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Form Edit Informasi Dosen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="data_dosen.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_dosen" id="edit_id_dosen">
                <div class="modal-body p-4 bg-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">NIDN</label>
                            <input type="text" name="nidn" id="edit_nidn" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Nama Lengkap & Gelar</label>
                            <input type="text" name="nama" id="edit_nama" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" id="edit_tgl_lahir" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="edit_jenis_kelamin" class="form-select rounded-3" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">No. HP (WhatsApp)</label>
                            <input type="text" name="no_hp" id="edit_no_hp" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Email Resmi</label>
                            <input type="email" name="email" id="edit_email" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small text-secondary">Alamat Lengkap</label>
                            <textarea name="alamat" id="edit_alamat" class="form-control rounded-3" rows="2" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Pendidikan Terakhir</label>
                            <input type="text" name="pendidikan_terakhir" id="edit_pendidikan_terakhir" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Jabatan Fungsional</label>
                            <input type="text" name="jabatan" id="edit_jabatan" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Status Keaktifan</label>
                            <select name="status" id="edit_status" class="form-select rounded-3" required>
                                <option value="Aktif">Aktif</option>
                                <option value="Tidak Aktif">Tidak Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small text-secondary">Foto Profil (Biarkan kosong jika tidak diubah)</label>
                            <input type="file" name="foto" class="form-control rounded-3" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 border-top-0">
                    <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-3 px-4 text-darkfw-semibold">Update Data</button>
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
    document.getElementById('edit_no_hp').value = data.no_hp;
    document.getElementById('edit_email').value = data.email;
    document.getElementById('edit_alamat').value = data.alamat;
    document.getElementById('edit_pendidikan_terakhir').value = data.pendidikan_terakhir;
    document.getElementById('edit_jabatan').value = data.jabatan;
    document.getElementById('edit_status').value = data.status;

    var modal = new Bootstrap.Modal(document.getElementById('modalEditDosen'));
    modal.show();
}
</script>
</body>
</html>