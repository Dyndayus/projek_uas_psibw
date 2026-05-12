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
