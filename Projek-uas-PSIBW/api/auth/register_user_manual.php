<?php
require_once '../../config/db.php';
header('Content-Type: application/json');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data input form
    $role      = $_POST['role'];
    $username  = $_POST['username']; // Berisi NIM / NIDN resmi
    $nama      = $_POST['nama'];
    $tgl_lahir = $_POST['tgl_lahir'];
    $jk        = $_POST['jenis_kelamin'];
    $alamat    = $_POST['alamat'];
    $no_hp     = $_POST['no_hp'];
    $email     = $_POST['email'];   // Ini yang akan jadi USERNAME di tabel user
    
    // Password plaintext menggunakan NIM/NIDN langsung agar sinkron dengan data di phpMyAdmin Kakak
    $password_plain = $username; 

    // --- LOGIKA UPLOAD FOTO ---
    $nama_foto = NULL;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ekstensi = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nama_foto = $role . "_" . $username . "_" . time() . "." . $ekstensi;
        $target_dir = "../../assets/img/profile/"; 
        
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $nama_foto);
    }
    // --------------------------

    try {
        $db->begin_transaction();

        // Validasi: Cek apakah email sudah terdaftar sebagai username di tabel user
        $checkUser = $db->prepare("SELECT username FROM user WHERE username = ?");
        $checkUser->bind_param("s", $email);
        $checkUser->execute();
        $resUser = $checkUser->get_result();
        if ($resUser->num_rows > 0) {
            throw new Exception("Email / Username sudah terdaftar di sistem.");
        }

        $last_id_ref = 0;

        if ($role === 'dosen') {
            $pendidikan = $_POST['pendidikan_terakhir'];
            $jabatan    = $_POST['jabatan'];
            
            // Kolom 'status' dihapus dari query karena tidak ada di tabel dosen
            $stmt = $db->prepare("INSERT INTO dosen (nidn, nama, tgl_lahir, jenis_kelamin, alamat, no_hp, email, pendidikan_terakhir, jabatan, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $username, $nama, $tgl_lahir, $jk, $alamat, $no_hp, $email, $pendidikan, $jabatan, $nama_foto);
            $stmt->execute();
            
            // Mengambil id_dosen (auto increment angka)
            $last_id_ref = $db->insert_id; 
            
        } else if ($role === 'mahasiswa') {
            $prodi    = $_POST['program_studi'];
            $angkatan = $_POST['angkatan'];
            $semester = $_POST['semester'];

            // Kolom 'status' juga dihapus dari query mhs agar aman
            $stmt = $db->prepare("INSERT INTO mhs (nim, nama, tgl_lahir, jenis_kelamin, alamat, no_hp, email, program_studi, angkatan, semester, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssiis", $username, $nama, $tgl_lahir, $jk, $alamat, $no_hp, $email, $prodi, $angkatan, $semester, $nama_foto);
            $stmt->execute();
            
            // Mengambil id_mhs (auto increment angka)
            $last_id_ref = $db->insert_id; 
        }

        // --- PROSES INSERT KE TABEL USER ---
        $stmtUser = $db->prepare("INSERT INTO user (username, password, role, id_ref) VALUES (?, ?, ?, ?)");
        $stmtUser->bind_param("sssi", $email, $password_plain, $role, $last_id_ref);
        
        // TAMBAHKAN VALIDASI EKSEKUSI INI:
        if (!$stmtUser->execute()) {
            // Jika insert ke tabel user gagal, sengaja lempar error agar ketahuan masalahnya!
            throw new Exception("Gagal insert ke tabel USER: " . $stmtUser->error);
        }

        // Kunci perubahan ke database jika semuanya sukses tanpa hambatan
        $db->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data profil dan akun login berhasil digenerate!']);

    } catch (Exception $e) {
        // Jika ada masalah di tengah jalan, batalkan semuanya agar data tidak timpang
        $db->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal simpan: ' . $e->getMessage()]);
    }
}