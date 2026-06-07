<?php
session_start();

require_once '../../config/db.php';
setHeaders();

// KHUSUS MAHASISWA: Ambil KRS Mandiri
requireRole(['mahasiswa', 'admin']); 

$db = getDB();
$data = json_decode(file_get_contents('php://input'), true);

// Keamanan: Ambil ID Mahasiswa langsung dari Session (Versi Temanmu)
$id_mhs = isset($_SESSION['id_ref']) ? intval($_SESSION['id_ref']) : 0;
$id_kuliah = isset($data['id_kuliah']) ? intval($data['id_kuliah']) : 0;

if (!$id_mhs || !$id_kuliah) {
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' => 'Data tidak lengkap atau ID mata kuliah tidak ditemukan'
    ]);
    exit;
}

// Logika Akurat Tahun Ajaran Berdasarkan Bulan Sekarang (Versi Kamu)
$tahun_sekarang = intval(date('Y'));
$bulan_sekarang = intval(date('n'));

if ($bulan_sekarang > 6) {
    // Jika bulan Juli - Desember, masuk tahun ajaran baru (contoh: 2026/2027)
    $tahun_depan = $tahun_sekarang + 1;
    $tahun_ajaran = $tahun_sekarang . '/' . $tahun_depan;
} else {
    // Jika bulan Januari - Juni, masuk kelanjutan tahun ajaran lalu (contoh: 2025/2026)
    $tahun_lalu = $tahun_sekarang - 1;
    $tahun_ajaran = $tahun_lalu . '/' . $tahun_sekarang;
}

// Validasi: Cek apakah mahasiswa sudah ambil matkul ini sebelumnya
$cek = $db->prepare("SELECT id_nilai FROM nilai WHERE id_mhs = ? AND id_kuliah = ?");
$cek->bind_param("ii", $id_mhs, $id_kuliah);
$cek->execute();
$hasil = $cek->get_result();

if ($hasil->num_rows > 0) {
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' => 'Mata kuliah ini sudah Anda ambil sebelumnya'
    ]);
    exit;
}
$cek->close();

// Masukkan data ke tabel nilai (Nilai angka & huruf diset NULL secara otomatis)
$stmt = $db->prepare("
    INSERT INTO nilai (id_mhs, id_kuliah, nilai_angka, nilai_huruf, tahun_ajaran) 
    VALUES (?, ?, NULL, NULL, ?)
");
$stmt->bind_param("iis", $id_mhs, $id_kuliah, $tahun_ajaran);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'success' => true,
        'message' => 'Mata kuliah berhasil diambil dan terdaftar di KRS Anda'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' => 'Gagal mengambil mata kuliah'
    ]);
}
$stmt->close();
?>