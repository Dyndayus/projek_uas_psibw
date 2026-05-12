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
