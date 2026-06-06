<?php

session_start();

require_once '../../config/db.php';

setHeaders();

requireRole(['mahasiswa', 'admin']);

$db = getDB();

$data = json_decode(file_get_contents('php://input'), true);

$id_mhs = $_SESSION['id_ref'];
$id_kuliah = $data['id_kuliah'] ?? null;

if (!$id_kuliah) {

    echo json_encode([
        'success' => false,
        'message' => 'ID mata kuliah tidak ditemukan'
    ]);

    exit;
}

$cek = $db->prepare("
    SELECT id_nilai
    FROM nilai
    WHERE id_mhs = ?
    AND id_kuliah = ?
");

$cek->bind_param("ii", $id_mhs, $id_kuliah);
$cek->execute();

$hasil = $cek->get_result();

if ($hasil->num_rows > 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Mata kuliah sudah pernah diambil'
    ]);

    exit;
}

$tahun_ajaran = date('Y') . '/' . (date('Y') + 1);

$stmt = $db->prepare("
    INSERT INTO nilai
    (
        id_mhs,
        id_kuliah,
        nilai_angka,
        nilai_huruf,
        tahun_ajaran
    )
    VALUES
    (
        ?,
        ?,
        NULL,
        NULL,
        ?
    )
");

$stmt->bind_param(
    "iis",
    $id_mhs,
    $id_kuliah,
    $tahun_ajaran
);

if ($stmt->execute()) {

    echo json_encode([
        'success' => true,
        'message' => 'Mata kuliah berhasil diambil'
    ]);

} else {

    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil mata kuliah'
    ]);
}