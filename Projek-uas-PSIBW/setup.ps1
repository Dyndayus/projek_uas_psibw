# =============================================
# SETUP BACKEND SIAKAD UNRI
# Jalankan di PowerShell: .\setup.ps1
# =============================================

# BUAT FOLDER
New-Item -ItemType Directory -Force -Path "config"
New-Item -ItemType Directory -Force -Path "api/auth"
New-Item -ItemType Directory -Force -Path "api/mahasiswa"
New-Item -ItemType Directory -Force -Path "api/dosen"
New-Item -ItemType Directory -Force -Path "api/kuliah"
New-Item -ItemType Directory -Force -Path "api/nilai"
New-Item -ItemType Directory -Force -Path "uploads/foto_mhs"
New-Item -ItemType Directory -Force -Path "uploads/foto_dosen"
New-Item -ItemType Directory -Force -Path "database"

Write-Host "✅ Folder berhasil dibuat!" -ForegroundColor Green

# =============================================
# config/db.php
# =============================================
@'
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistem_akademik');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Koneksi gagal: ' . $conn->connect_error
        ]);
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function setHeaders() {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['id_user'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu']);
        exit;
    }
}

function requireRole(array $roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
        exit;
    }
}
'@ | Set-Content -Path "config/db.php" -Encoding UTF8

Write-Host "✅ config/db.php dibuat!" -ForegroundColor Green

# =============================================
# api/auth/login.php
# =============================================
@'
<?php
require_once '../../config/db.php';
setHeaders();
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if (!$username || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Username dan password wajib diisi']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("
    SELECT u.*,
        CASE
            WHEN u.role = 'mahasiswa' THEN m.nama
            WHEN u.role = 'dosen'     THEN d.nama
            ELSE 'Administrator'
        END AS nama_lengkap,
        CASE
            WHEN u.role = 'mahasiswa' THEN m.foto
            WHEN u.role = 'dosen'     THEN d.foto
            ELSE NULL
        END AS foto
    FROM user u
    LEFT JOIN mhs   m ON u.role = 'mahasiswa' AND u.id_ref = m.id_mhs
    LEFT JOIN dosen d ON u.role = 'dosen'     AND u.id_ref = d.id_dosen
    WHERE u.username = ?
");
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $password !== $user['password']) {
    echo json_encode(['status' => 'error', 'message' => 'Username atau password salah']);
    exit;
}

$_SESSION['id_user']  = $user['id_user'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];
$_SESSION['id_ref']   = $user['id_ref'];
$_SESSION['nama']     = $user['nama_lengkap'];
$_SESSION['foto']     = $user['foto'];

echo json_encode([
    'status'  => 'success',
    'message' => 'Login berhasil',
    'data'    => [
        'role'   => $user['role'],
        'nama'   => $user['nama_lengkap'],
        'foto'   => $user['foto'],
        'id_ref' => $user['id_ref']
    ]
]);
'@ | Set-Content -Path "api/auth/login.php" -Encoding UTF8

Write-Host "✅ api/auth/login.php dibuat!" -ForegroundColor Green

# =============================================
# api/mahasiswa/index.php
# =============================================
@'
<?php
require_once '../../config/db.php';
setHeaders();
requireLogin();

$db     = getDB();
$page   = max(1, intval($_GET['page']  ?? 1));
$limit  = intval($_GET['limit'] ?? 10);
$offset = ($page - 1) * $limit;
$search = '%' . ($_GET['search'] ?? '') . '%';

$count = $db->prepare("SELECT COUNT(*) as total FROM mhs WHERE nama LIKE ? OR nim LIKE ?");
$count->bind_param('ss', $search, $search);
$count->execute();
$total = $count->get_result()->fetch_assoc()['total'];

$stmt = $db->prepare("SELECT * FROM mhs WHERE nama LIKE ? OR nim LIKE ? ORDER BY id_mhs DESC LIMIT ? OFFSET ?");
$stmt->bind_param('ssii', $search, $search, $limit, $offset);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'status' => 'success',
    'data'   => $data,
    'pagination' => [
        'total'        => $total,
        'per_page'     => $limit,
        'current_page' => $page,
        'last_page'    => ceil($total / $limit)
    ]
]);
'@ | Set-Content -Path "api/mahasiswa/index.php" -Encoding UTF8

Write-Host "✅ api/mahasiswa/index.php dibuat!" -ForegroundColor Green

# =============================================
# api/mahasiswa/store.php
# =============================================
@'
<?php
require_once '../../config/db.php';
setHeaders();
requireRole(['admin']);

$db   = getDB();
$foto = null;

if (!empty($_FILES['foto']['name'])) {
    $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $namaFile = 'mhs_' . time() . '.' . $ext;
    $target   = '../../uploads/foto_mhs/' . $namaFile;
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
        $foto = $namaFile;
    }
}

$nim           = trim($_POST['nim']          ?? '');
$nama          = trim($_POST['nama']         ?? '');
$tgl_lahir     = $_POST['tgl_lahir']         ?? '';
$jenis_kelamin = $_POST['jenis_kelamin']     ?? '';
$alamat        = $_POST['alamat']            ?? '';
$no_hp         = $_POST['no_hp']             ?? '';
$email         = $_POST['email']             ?? '';
$angkatan      = $_POST['angkatan']          ?? '';
$semester      = $_POST['semester']          ?? 1;
$program_studi = $_POST['program_studi']     ?? '';

if (!$nim || !$nama || !$tgl_lahir) {
    echo json_encode(['status' => 'error', 'message' => 'NIM, nama, dan tanggal lahir wajib diisi']);
    exit;
}

$stmt = $db->prepare("INSERT INTO mhs (nim, nama, tgl_lahir, jenis_kelamin, alamat, no_hp, email, foto, angkatan, semester, program_studi) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
$stmt->bind_param('sssssssssss', $nim, $nama, $tgl_lahir, $jenis_kelamin, $alamat, $no_hp, $email, $foto, $angkatan, $semester, $program_studi);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Mahasiswa berhasil ditambahkan']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan data']);
}
'@ | Set-Content -Path "api/mahasiswa/store.php" -Encoding UTF8

Write-Host "✅ api/mahasiswa/store.php dibuat!" -ForegroundColor Green

# =============================================
# api/mahasiswa/update.php
# =============================================
@'
<?php
require_once '../../config/db.php';
setHeaders();
requireRole(['admin']);

$db = getDB();
$id = intval($_POST['id_mhs'] ?? 0);

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
    exit;
}

$old  = $db->query("SELECT foto FROM mhs WHERE id_mhs = $id")->fetch_assoc();
$foto = $old['foto'];

if (!empty($_FILES['foto']['name'])) {
    $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $namaFile = 'mhs_' . time() . '.' . $ext;
    $target   = '../../uploads/foto_mhs/' . $namaFile;
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
        if ($foto && file_exists('../../uploads/foto_mhs/' . $foto)) {
            unlink('../../uploads/foto_mhs/' . $foto);
        }
        $foto = $namaFile;
    }
}

$nim           = trim($_POST['nim']          ?? '');
$nama          = trim($_POST['nama']         ?? '');
$tgl_lahir     = $_POST['tgl_lahir']         ?? '';
$jenis_kelamin = $_POST['jenis_kelamin']     ?? '';
$alamat        = $_POST['alamat']            ?? '';
$no_hp         = $_POST['no_hp']             ?? '';
$email         = $_POST['email']             ?? '';
$semester      = $_POST['semester']          ?? 1;
$program_studi = $_POST['program_studi']     ?? '';
$status        = $_POST['status']            ?? 'aktif';

$stmt = $db->prepare("UPDATE mhs SET nim=?, nama=?, tgl_lahir=?, jenis_kelamin=?, alamat=?, no_hp=?, email=?, foto=?, semester=?, program_studi=?, status=? WHERE id_mhs=?");
$stmt->bind_param('sssssssssssi', $nim, $nama, $tgl_lahir, $jenis_kelamin, $alamat, $no_hp, $email, $foto, $semester, $program_studi, $status, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Data mahasiswa berhasil diperbarui']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data']);
}
'@ | Set-Content -Path "api/mahasiswa/update.php" -Encoding UTF8

Write-Host "✅ api/mahasiswa/update.php dibuat!" -ForegroundColor Green

# =============================================
# api/mahasiswa/delete.php
# =============================================
@'
<?php
require_once '../../config/db.php';
setHeaders();
requireRole(['admin']);

$db   = getDB();
$data = json_decode(file_get_contents('php://input'), true);
$ids  = $data['ids'] ?? [];

if (empty($ids)) {
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada data yang dipilih']);
    exit;
}

$ids = array_map('intval', $ids);
foreach ($ids as $id) {
    $row = $db->query("SELECT foto FROM mhs WHERE id_mhs = $id")->fetch_assoc();
    if ($row && $row['foto'] && file_exists('../../uploads/foto_mhs/' . $row['foto'])) {
        unlink('../../uploads/foto_mhs/' . $row['foto']);
    }
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types        = str_repeat('i', count($ids));
$stmt         = $db->prepare("DELETE FROM mhs WHERE id_mhs IN ($placeholders)");
$stmt->bind_param($types, ...$ids);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data']);
}
'@ | Set-Content -Path "api/mahasiswa/delete.php" -Encoding UTF8

Write-Host "✅ api/mahasiswa/delete.php dibuat!" -ForegroundColor Green

# =============================================
# api/dosen/index.php
# =============================================
@'
<?php
require_once '../../config/db.php';
setHeaders();
requireLogin();

$db     = getDB();
$page   = max(1, intval($_GET['page']  ?? 1));
$limit  = intval($_GET['limit'] ?? 10);
$offset = ($page - 1) * $limit;
$search = '%' . ($_GET['search'] ?? '') . '%';

$count = $db->prepare("SELECT COUNT(*) as total FROM dosen WHERE nama LIKE ? OR nidn LIKE ?");
$count->bind_param('ss', $search, $search);
$count->execute();
$total = $count->get_result()->fetch_assoc()['total'];

$stmt = $db->prepare("SELECT * FROM dosen WHERE nama LIKE ? OR nidn LIKE ? ORDER BY id_dosen DESC LIMIT ? OFFSET ?");
$stmt->bind_param('ssii', $search, $search, $limit, $offset);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'status' => 'success',
    'data'   => $data,
    'pagination' => [
        'total'        => $total,
        'per_page'     => $limit,
        'current_page' => $page,
        'last_page'    => ceil($total / $limit)
    ]
]);
'@ | Set-Content -Path "api/dosen/index.php" -Encoding UTF8

Write-Host "✅ api/dosen/index.php dibuat!" -ForegroundColor Green

# =============================================
# api/dosen/store.php
# =============================================
@'
<?php
require_once '../../config/db.php';
setHeaders();
requireRole(['admin']);

$db   = getDB();
$foto = null;

if (!empty($_FILES['foto']['name'])) {
    $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $namaFile = 'dosen_' . time() . '.' . $ext;
    $target   = '../../uploads/foto_dosen/' . $namaFile;
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
        $foto = $namaFile;
    }
}

$nidn                = trim($_POST['nidn']               ?? '');
$nama                = trim($_POST['nama']               ?? '');
$tgl_lahir           = $_POST['tgl_lahir']               ?? '';
$jenis_kelamin       = $_POST['jenis_kelamin']           ?? '';
$alamat              = $_POST['alamat']                  ?? '';
$no_hp               = $_POST['no_hp']                   ?? '';
$email               = $_POST['email']                   ?? '';
$pendidikan_terakhir = $_POST['pendidikan_terakhir']     ?? '';
$jabatan             = $_POST['jabatan']                 ?? '';

if (!$nidn || !$nama || !$tgl_lahir) {
    echo json_encode(['status' => 'error', 'message' => 'NIDN, nama, dan tanggal lahir wajib diisi']);
    exit;
}

$stmt = $db->prepare("INSERT INTO dosen (nidn, nama, tgl_lahir, jenis_kelamin, alamat, no_hp, email, foto, pendidikan_terakhir, jabatan) VALUES (?,?,?,?,?,?,?,?,?,?)");
$stmt->bind_param('ssssssssss', $nidn, $nama, $tgl_lahir, $jenis_kelamin, $alamat, $no_hp, $email, $foto, $pendidikan_terakhir, $jabatan);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Dosen berhasil ditambahkan']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan data']);
}
'@ | Set-Content -Path "api/dosen/store.php" -Encoding UTF8

Write-Host "✅ api/dosen/store.php dibuat!" -ForegroundColor Green

# =============================================
# api/dosen/update.php
# =============================================
@'
<?php
require_once '../../config/db.php';
setHeaders();
requireRole(['admin']);

$db = getDB();
$id = intval($_POST['id_dosen'] ?? 0);

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
    exit;
}

$old  = $db->query("SELECT foto FROM dosen WHERE id_dosen = $id")->fetch_assoc();
$foto = $old['foto'];

if (!empty($_FILES['foto']['name'])) {
    $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $namaFile = 'dosen_' . time() . '.' . $ext;
    $target   = '../../uploads/foto_dosen/' . $namaFile;
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
        if ($foto && file_exists('../../uploads/foto_dosen/' . $foto)) {
            unlink('../../uploads/foto_dosen/' . $foto);
        }
        $foto = $namaFile;
    }
}

$nidn                = trim($_POST['nidn']               ?? '');
$nama                = trim($_POST['nama']               ?? '');
$tgl_lahir           = $_POST['tgl_lahir']               ?? '';
$jenis_kelamin       = $_POST['jenis_kelamin']           ?? '';
$alamat              = $_POST['alamat']                  ?? '';
$no_hp               = $_POST['no_hp']                   ?? '';
$email               = $_POST['email']                   ?? '';
$pendidikan_terakhir = $_POST['pendidikan_terakhir']     ?? '';
$jabatan             = $_POST['jabatan']                 ?? '';
$status              = $_POST['status']                  ?? 'aktif';

$stmt = $db->prepare("UPDATE dosen SET nidn=?, nama=?, tgl_lahir=?, jenis_kelamin=?, alamat=?, no_hp=?, email=?, foto=?, pendidikan_terakhir=?, jabatan=?, status=? WHERE id_dosen=?");
$stmt->bind_param('sssssssssssi', $nidn, $nama, $tgl_lahir, $jenis_kelamin, $alamat, $no_hp, $email, $foto, $pendidikan_terakhir, $jabatan, $status, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Data dosen berhasil diperbarui']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data']);
}
'@ | Set-Content -Path "api/dosen/update.php" -Encoding UTF8

Write-Host "✅ api/dosen/update.php dibuat!" -ForegroundColor Green

# =============================================
# api/dosen/delete.php
# =============================================
@'
<?php
require_once '../../config/db.php';
setHeaders();
requireRole(['admin']);

$db   = getDB();
$data = json_decode(file_get_contents('php://input'), true);
$ids  = $data['ids'] ?? [];

if (empty($ids)) {
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada data yang dipilih']);
    exit;
}

$ids = array_map('intval', $ids);
foreach ($ids as $id) {
    $row = $db->query("SELECT foto FROM dosen WHERE id_dosen = $id")->fetch_assoc();
    if ($row && $row['foto'] && file_exists('../../uploads/foto_dosen/' . $row['foto'])) {
        unlink('../../uploads/foto_dosen/' . $row['foto']);
    }
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types        = str_repeat('i', count($ids));
$stmt         = $db->prepare("DELETE FROM dosen WHERE id_dosen IN ($placeholders)");
$stmt->bind_param($types, ...$ids);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data']);
}
'@ | Set-Content -Path "api/dosen/delete.php" -Encoding UTF8

Write-Host "✅ api/dosen/delete.php dibuat!" -ForegroundColor Green

# =============================================
# api/kuliah/index.php
# =============================================
@'
<?php
require_once '../../config/db.php';
setHeaders();
requireLogin();

$db     = getDB();
$page   = max(1, intval($_GET['page']  ?? 1));
$limit  = intval($_GET['limit'] ?? 10);
$offset = ($page - 1) * $limit;
$search = '%' . ($_GET['search'] ?? '') . '%';

$count = $db->prepare("SELECT COUNT(*) as total FROM kuliah k LEFT JOIN dosen d ON k.id_dosen = d.id_dosen WHERE k.nama_mk LIKE ? OR k.kode_mk LIKE ?");
$count->bind_param('ss', $search, $search);
$count->execute();
$total = $count->get_result()->fetch_assoc()['total'];

$stmt = $db->prepare("SELECT k.*, d.nama as nama_dosen FROM kuliah k LEFT JOIN dosen d ON k.id_dosen = d.id_dosen WHERE k.nama_mk LIKE ? OR k.kode_mk LIKE ? ORDER BY k.id_kuliah DESC LIMIT ? OFFSET ?");
$stmt->bind_param('ssii', $search, $search, $limit, $offset);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'status' => 'success',
    'data'   => $data,
    'pagination' => [
        'total'        => $total,
        'per_page'     => $limit,
        'current_page' => $page,
        'last_page'    => ceil($total / $limit)
    ]
]);
'@ | Set-Content -Path "api/kuliah/index.php" -Encoding UTF8

Write-Host "✅ api/kuliah/index.php dibuat!" -ForegroundColor Green

# =============================================
# api/nilai/index.php
# =============================================
@'
<?php
require_once '../../config/db.php';
setHeaders();
requireLogin();

$db       = getDB();
$id_mhs   = intval($_GET['id_mhs']   ?? 0);
$id_dosen = intval($_GET['id_dosen'] ?? 0);

if ($id_mhs) {
    $stmt = $db->prepare("
        SELECT n.*, m.nama as nama_mhs, m.nim, k.nama_mk, k.kode_mk, k.sks
        FROM nilai n
        JOIN mhs m    ON n.id_mhs    = m.id_mhs
        JOIN kuliah k ON n.id_kuliah = k.id_kuliah
        WHERE n.id_mhs = ?
        ORDER BY n.tahun_ajaran DESC
    ");
    $stmt->bind_param('i', $id_mhs);
} elseif ($id_dosen) {
    $stmt = $db->prepare("
        SELECT n.*, m.nama as nama_mhs, m.nim, k.nama_mk, k.kode_mk, k.sks
        FROM nilai n
        JOIN mhs m    ON n.id_mhs    = m.id_mhs
        JOIN kuliah k ON n.id_kuliah = k.id_kuliah
        WHERE k.id_dosen = ?
        ORDER BY k.nama_mk, m.nama
    ");
    $stmt->bind_param('i', $id_dosen);
} else {
    $stmt = $db->prepare("
        SELECT n.*, m.nama as nama_mhs, m.nim, k.nama_mk, k.kode_mk, k.sks
        FROM nilai n
        JOIN mhs m    ON n.id_mhs    = m.id_mhs
        JOIN kuliah k ON n.id_kuliah = k.id_kuliah
        ORDER BY n.id_nilai DESC
    ");
}

$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo json_encode(['status' => 'success', 'data' => $data]);
'@ | Set-Content -Path "api/nilai/index.php" -Encoding UTF8

Write-Host "✅ api/nilai/index.php dibuat!" -ForegroundColor Green

# =============================================
# api/nilai/store.php
# =============================================
@'
<?php
require_once '../../config/db.php';
setHeaders();
requireRole(['admin', 'dosen']);

$db   = getDB();
$data = json_decode(file_get_contents('php://input'), true);

$id_mhs       = intval($data['id_mhs']      ?? 0);
$id_kuliah    = intval($data['id_kuliah']   ?? 0);
$nilai_angka  = $data['nilai_angka']        ?? null;
$nilai_huruf  = $data['nilai_huruf']        ?? null;
$tahun_ajaran = $data['tahun_ajaran']       ?? null;

if (!$id_mhs || !$id_kuliah) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit;
}

$stmt = $db->prepare("INSERT INTO nilai (id_mhs, id_kuliah, nilai_angka, nilai_huruf, tahun_ajaran) VALUES (?,?,?,?,?)");
$stmt->bind_param('iidss', $id_mhs, $id_kuliah, $nilai_angka, $nilai_huruf, $tahun_ajaran);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Nilai berhasil disimpan']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan nilai']);
}
'@ | Set-Content -Path "api/nilai/store.php" -Encoding UTF8

Write-Host "✅ api/nilai/store.php dibuat!" -ForegroundColor Green

# =============================================
# SELESAI
# =============================================
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host " SEMUA FILE BERHASIL DIBUAT!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "File yang dibuat:" -ForegroundColor Yellow
Get-ChildItem -Path "config","api","uploads","database" -Recurse | Select-Object FullName