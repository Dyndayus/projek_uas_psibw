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
