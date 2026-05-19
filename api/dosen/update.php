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
