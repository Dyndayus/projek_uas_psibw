<?php

header('Content-Type: application/json; charset=utf-8');

// Mulai buffer output
ob_start();

require_once '../../config/db.php';
setHeaders();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validasi Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    ob_clean();

    echo json_encode([
        'status' => 'error',
        'message' => 'Method tidak diizinkan'
    ]);

    exit;
}

// Ambil Data Input
$data = json_decode(file_get_contents('php://input'), true);

$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

// Validasi Input
if (!$username || !$password) {

    ob_clean();

    echo json_encode([
        'status' => 'error',
        'message' => 'Username dan password wajib diisi'
    ]);

    exit;
}

// Koneksi Database
$db = getDB();

$stmt = $db->prepare("
    SELECT 
        u.*,
        CASE
            WHEN u.role = 'mahasiswa' THEN m.nama
            WHEN u.role = 'dosen' THEN d.nama
            ELSE 'Administrator'
        END AS nama_lengkap,

        CASE
            WHEN u.role = 'mahasiswa' THEN m.foto
            WHEN u.role = 'dosen' THEN d.foto
            ELSE NULL
        END AS foto

    FROM user u

    LEFT JOIN mhs m 
        ON u.role = 'mahasiswa' 
        AND u.id_ref = m.id_mhs

    LEFT JOIN dosen d 
        ON u.role = 'dosen' 
        AND u.id_ref = d.id_dosen

    WHERE u.username = ?
");

$stmt->bind_param('s', $username);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

// Verifikasi Login
if (!$user || $password !== $user['password']) {

    ob_clean();

    echo json_encode([
        'status' => 'error',
        'message' => 'Username atau password salah'
    ]);

    exit;
}

// Set Session
$_SESSION['id_user']  = $user['id_user'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];
$_SESSION['id_ref']   = $user['id_ref'];
$_SESSION['nama']     = $user['nama_lengkap'];
$_SESSION['foto']     = $user['foto'];

// Bersihkan semua output liar
ob_clean();

// Response JSON Bersih
echo json_encode([
    'status'  => 'success',
    'message' => 'Login berhasil',
    'data'    => [
        'role'   => $user['role'],
        'nama'   => $user['nama_lengkap'],
        'foto'   => $user['foto'],
        'id_ref' => $user['id_ref']
    ]
]);

exit;