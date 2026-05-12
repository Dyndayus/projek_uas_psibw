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
