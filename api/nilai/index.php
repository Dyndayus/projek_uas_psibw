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
