<?php
// PASTIKAN TIDAK ADA BARIS KOSONG DI ATAS <?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'D1ND4k3c1l');
define('DB_NAME', 'sistem_akademik');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        // Jika koneksi gagal, pastikan output bersih
        if (ob_get_length()) ob_clean(); 
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'error',
            'message' => 'Koneksi database gagal'
        ]);
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function setHeaders() {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['id_user'])) {
        if (ob_get_length()) ob_clean();
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu']);
        exit;
    }
}

function requireRole(array $roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles)) {
        if (ob_get_length()) ob_clean();
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
        exit;
    }
}