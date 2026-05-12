<?php
require_once '../../config/db.php';
setHeaders();
requireRole(['admin']);

$db = getDB();

// 1. Generate untuk Mahasiswa (Username = NIM, Password = NIM)
$db->query("INSERT IGNORE INTO user (username, password, role, id_ref)
            SELECT nim, nim, 'mahasiswa', id_mhs FROM mhs");

// 2. Generate untuk Dosen (Username = NIDN, Password = NIDN)
$db->query("INSERT IGNORE INTO user (username, password, role, id_ref)
            SELECT nidn, nidn, 'dosen', id_dosen FROM dosen");

echo json_encode([
    'status' => 'success', 
    'message' => 'Akun login untuk semua Mahasiswa & Dosen berhasil dibuat/diperbarui!'
]);