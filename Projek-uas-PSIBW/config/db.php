<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistem_akademik');

function setHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

function getDB() {

    mysqli_report(MYSQLI_REPORT_OFF);

   $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3307);

    if ($conn->connect_error) {

        if (ob_get_length()) {
            ob_clean();
        }

        http_response_code(500);

        echo json_encode([
            'status' => 'error',
            'message' => 'Koneksi database gagal'
        ]);

        exit;
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}

function requireLogin() {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['id_user'])) {

        if (ob_get_length()) {
            ob_clean();
        }

        http_response_code(401);

        echo json_encode([
            'status' => 'error',
            'message' => 'Silakan login terlebih dahulu'
        ]);

        exit;
    }
}

function requireRole(array $roles) {

    requireLogin();

    if (!in_array($_SESSION['role'], $roles)) {

        if (ob_get_length()) {
            ob_clean();
        }

        http_response_code(403);

        echo json_encode([
            'status' => 'error',
            'message' => 'Akses ditolak'
        ]);

        exit;
    }
}