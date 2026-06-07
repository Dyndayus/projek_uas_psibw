<?php
require_once '../../config/db.php';
setHeaders();

// KHUSUS MAHASISWA: Ambil KRS Mandiri
requireRole(['mahasiswa', 'admin']); 

$db   = getDB();
$data = json_decode(file_get_contents('php://input'), true);

$id_mhs    = intval($data['id_mhs']    ?? 0);
$id_kuliah = intval($data['id_kuliah'] ?? 0);

// Nilai awal diset kosong (NULL) saat ambil matkul
$nilai_angka  = null;
$nilai_huruf  = null;

// Format tahun ajaran diselaraskan dengan input_nilai.php (Contoh: 2025/2026)
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

if (!$id_mhs || !$id_kuliah) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit;
}

// Validasi: Cek apakah mahasiswa sudah ambil matkul ini sebelumnya
$check = $db->prepare("SELECT id_nilai FROM nilai WHERE id_mhs = ? AND id_kuliah = ?");
$check->bind_param('ii', $id_mhs, $id_kuliah);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Mata kuliah ini sudah Anda ambil sebelumnya']);
    exit;
}
$check->close();

// Masukkan data ke tabel nilai (Nilai angka dan huruf langsung ditulis NULL di query agar aman dari bug bind_param)
$stmt = $db->prepare("INSERT INTO nilai (id_mhs, id_kuliah, nilai_angka, nilai_huruf, tahun_ajaran) VALUES (?, ?, NULL, NULL, ?)");
$stmt->bind_param('iis', $id_mhs, $id_kuliah, $tahun_ajaran);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Mata kuliah berhasil terdaftar di KRS Anda']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil mata kuliah']);
}
$stmt->close();
?>