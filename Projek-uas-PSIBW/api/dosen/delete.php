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
