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
