<?php
require_once '../../config/db.php';
header('Content-Type: application/json');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role      = $_POST['role'];
    $username  = $_POST['username']; 
    $nama      = $_POST['nama'];
    $tgl_lahir = $_POST['tgl_lahir'];
    $jk        = $_POST['jenis_kelamin'];
    $alamat    = $_POST['alamat'];
    $no_hp     = $_POST['no_hp'];
    $email     = $_POST['email'];
    $status    = $_POST['status'];
    $password  = password_hash($username, PASSWORD_DEFAULT); 

    // --- LOGIKA UPLOAD FOTO ---
    $nama_foto = NULL;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ekstensi = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nama_foto = $role . "_" . $username . "_" . time() . "." . $ekstensi;
        $target_dir = "../../assets/img/profile/"; // Pastikan folder ini ada
        
        // Buat folder jika belum ada
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $nama_foto);
    }
    // --------------------------

    try {
        $db->begin_transaction();
        $last_id_ref = 0;

        if ($role === 'dosen') {
            $pendidikan = $_POST['pendidikan_terakhir'];
            $jabatan    = $_POST['jabatan'];
            
            // Tambahkan kolom foto di query INSERT
            $stmt = $db->prepare("INSERT INTO dosen (nidn, nama, tgl_lahir, jenis_kelamin, alamat, no_hp, email, pendidikan_terakhir, jabatan, status, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", $username, $nama, $tgl_lahir, $jk, $alamat, $no_hp, $email, $pendidikan, $jabatan, $status, $nama_foto);
            $stmt->execute();
            $last_id_ref = $db->insert_id; 
            
        } else if ($role === 'mahasiswa') {
            $prodi    = $_POST['program_studi'];
            $angkatan = $_POST['angkatan'];
            $semester = $_POST['semester'];

            $stmt = $db->prepare("INSERT INTO mhs (nim, nama, tgl_lahir, jenis_kelamin, alamat, no_hp, email, program_studi, angkatan, semester, status, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssiss", $username, $nama, $tgl_lahir, $jk, $alamat, $no_hp, $email, $prodi, $angkatan, $semester, $status, $nama_foto);
            $stmt->execute();
            $last_id_ref = $db->insert_id; 
        }

        $stmtUser = $db->prepare("INSERT INTO user (username, password, role, id_ref) VALUES (?, ?, ?, ?)");
        $stmtUser->bind_param("sssi", $username, $password, $role, $last_id_ref);
        $stmtUser->execute();

        $db->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data Berhasil Disimpan beserta Foto!']);

    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal simpan: ' . $e->getMessage()]);
    }
}